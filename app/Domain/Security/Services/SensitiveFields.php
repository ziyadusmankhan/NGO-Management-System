<?php

namespace App\Domain\Security\Services;

class SensitiveFields
{
    public const TABLES = [
        'members' => ['cnic', 'phone', 'alternate_phone', 'email', 'address', 'notes'],
        'beneficiaries' => ['cnic', 'phone', 'alternate_phone', 'address', 'assets_description', 'debts_description', 'household_condition', 'notes'],
        'donations' => ['phone', 'email', 'purpose_notes', 'internal_notes'],
        'expenses' => ['purpose_notes', 'internal_notes'],
        'transactions' => ['notes'],
        'welfare_applications' => ['purpose_details', 'recommendation_notes', 'rejection_reason', 'internal_notes'],
        'beneficiary_verifications' => ['findings', 'notes', 'recommendation_notes'],
        'welfare_application_reviews' => ['notes'],
        'welfare_application_agreements' => ['terms', 'notes'],
        'welfare_assistances' => ['purpose_notes', 'internal_notes', 'notes'],
        'welfare_assistance_payments' => ['notes', 'void_reason'],
        'welfare_assistance_reviews' => ['notes'],
        'welfare_assistance_status_changes' => ['reason'],
        'qard_loans' => ['purpose'],
        'qard_reviews' => ['notes'],
        'qard_agreements' => ['terms'],
        'qard_movements' => ['notes', 'void_reason'],
        'qard_followups' => ['notes'],
        'qard_events' => ['before', 'after'],
        'business_applications' => ['beneficiary_snapshot', 'description', 'skills', 'market_assessment', 'risks'],
        'business_agreements' => ['terms'],
        'business_reviews' => ['notes'],
        'business_movements' => ['notes', 'void_reason'],
        'business_monitorings' => ['findings', 'actions'],
        'business_events' => ['before_values', 'after_values'],
        'business_category_events' => ['before_values', 'after_values'],
        'fund_changes' => ['before_values', 'after_values'],
        'project_reviews' => ['notes'],
        'project_movements' => ['notes', 'void_reason'],
        'project_progress' => ['findings'],
        'project_events' => ['before_values', 'after_values'],
        'procurement_suppliers' => ['contact', 'address'],
        'procurement_revisions' => ['supplier_snapshot'],
        'procurement_quotes' => ['supplier_snapshot', 'terms'],
        'procurement_reviews' => ['notes'],
        'procurement_receipts' => ['notes'],
        'procurement_movements' => ['notes', 'void_reason'],
        'procurement_events' => ['before_values', 'after_values'],
        'operation_tasks' => ['description', 'expected_outcome', 'completion_notes'],
        'operation_task_comments' => ['body'],
        'operation_meetings' => ['agenda', 'minutes', 'decisions'],
    ];

    public static function fields(string $table): array
    {
        return self::TABLES[$table] ?? [];
    }

    public static function indexed(string $field): bool
    {
        return in_array($field, ['cnic', 'phone', 'alternate_phone'], true);
    }
}
