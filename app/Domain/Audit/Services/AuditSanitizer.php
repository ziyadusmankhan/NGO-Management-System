<?php

namespace App\Domain\Audit\Services;

class AuditSanitizer
{
    private const FIELDS = ['changed_fields', 'id', 'status', 'from_status', 'to_status', 'type', 'kind', 'stage', 'decision', 'outcome', 'revision', 'approved_revision', 'fund_id', 'project_id', 'business_application_id', 'loan_id', 'procurement_id', 'application_id', 'assistance_id', 'beneficiary_id', 'committee_id', 'member_id', 'user_id', 'role_id', 'permission_id', 'movement_id', 'payment_id', 'transaction_id', 'transaction_number', 'original_payment_id', 'budget_item_id', 'invoice_line_id', 'return_id', 'record_type', 'receipt_id', 'record_id', 'amount', 'approved_amount', 'requested_amount', 'recommended_amount', 'opening_balance', 'budget', 'principal', 'paid', 'refunded', 'balance', 'funding_status', 'payments_frozen', 'frozen', 'category', 'purchase_kind', 'payment_date', 'payment_method', 'direction', 'transaction_date', 'expense_date', 'donation_date', 'effective_date', 'next_review_date', 'review_date', 'joined_at', 'ended_at', 'position', 'quote_count', 'active', 'archived', 'deleted_at', 'password_changed', 'roles', 'permissions', 'added', 'removed', 'items', 'quantity_milli', 'unit_minor', 'amount_minor', 'release_method', 'organization_amount', 'applicant_amount', 'cost', 'key', 'number', 'reference', 'event_count', 'historical_count', 'source_count', 'imported_count', 'existing_count', 'excluded_count', 'export_count', 'format', 'snapshot_id', 'from', 'to', 'module', 'action', 'historical', 'filters', 'source_type', 'source_id', 'ledger_entries', 'permission_slug', 'role_slug', 'name'];

    public function text(?string $text, int $limit = 1000): ?string
    {
        if ($text === null) {
            return null;
        }
        $text = strip_tags($text);
        $text = preg_replace(['/\b\d{5}-?\d{7}-?\d\b/', '/\b[A-Z]{2}\d{2}[A-Z0-9]{10,30}\b/i', '/[\w.+-]+@[\w.-]+\.[a-z]{2,}/i', '/\b\d{9,}\b/'], ['[identification redacted]', '[bank reference redacted]', '[email redacted]', '[number redacted]'], $text);

        return mb_substr($text, 0, $limit);
    }

    public function values(array $values, int $depth = 0): array
    {
        if ($depth > 5) {
            return [];
        }
        $out = [];
        foreach ($values as $key => $value) {
            if (! is_int($key) && ! in_array($key, self::FIELDS, true)) {
                continue;
            }
            if (is_array($value)) {
                $out[$key] = $this->values($value, $depth + 1);
            } elseif ($value instanceof \DateTimeInterface) {
                $out[$key] = $value->format(DATE_ATOM);
            } elseif (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $out[$key] = $value;
            } elseif (is_string($value)) {
                $out[$key] = $this->text($value, 500);
            }
        }

        return $out;
    }
}
