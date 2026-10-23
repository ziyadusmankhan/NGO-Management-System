<?php

namespace App\Domain\Business\Services;

use Illuminate\Support\Facades\Validator;

class BusinessRules
{
    public static function validate(string $action, array $data): array
    {
        if (in_array($action, ['store', 'update']) && is_array($data['items'] ?? null)) {
            $data['items'] = array_values(array_filter($data['items'], fn ($item) => ! is_array($item) || ! is_string($item['description'] ?? '') || trim($item['description'] ?? '') !== ''));
            foreach ($data['items'] as &$item) {
                if (is_array($item) && is_array($item['price_checks'] ?? [])) {
                    $item['price_checks'] = array_values(array_filter($item['price_checks'] ?? [], fn ($q) => ! is_array($q) || ! is_string($q['supplier'] ?? '') || trim($q['supplier'] ?? '') !== ''));
                }
            }
            unset($item);
            if (($data['support_type'] ?? null) === 'grant') {
                $data['frequency'] = null;
                $data['installment_count'] = null;
            }
        }
        $today = now(config('business.timezone'))->toDateString();
        $money = ['required', 'string', 'regex:/^\d{1,13}(\.\d{1,2})?$/'];
        $file = ['file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:10240'];
        $signatures = [
            'borrower_name' => ['required', 'string', 'max:255'], 'foundation_name' => ['required', 'string', 'max:255', 'different:borrower_name'],
            'witnesses' => ['required', 'array', 'size:2'], 'witnesses.*' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            'signed_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'borrower_file' => ['required', ...$file], 'foundation_file' => ['required', ...$file],
        ];
        $rules = match ($action) {
            'store', 'update' => [
                'beneficiary_id' => ['required', 'integer', 'exists:beneficiaries,id'], 'fund_id' => ['required', 'integer', 'exists:funds,id'],
                'category_id' => ['required', 'integer', 'exists:business_categories,id'],
                'name' => ['required', 'string', 'max:255'], 'location' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string', 'max:5000'], 'skills' => ['required', 'string', 'max:3000'],
                'market_assessment' => ['required', 'string', 'max:5000'], 'risks' => ['required', 'string', 'max:3000'],
                'expected_start_date' => ['required', 'date_format:Y-m-d'], 'expected_sales' => $money, 'expected_expenses' => $money,
                'support_type' => ['required', 'in:grant,repayable'], 'frequency' => ['nullable', 'required_if:support_type,repayable', 'in:weekly,monthly'],
                'installment_count' => ['nullable', 'required_if:support_type,repayable', 'integer', 'min:1', 'max:'.config('business.maximum_installments')],
                'items' => ['required', 'array', 'min:1', 'max:50'], 'items.*' => ['array'], 'items.*.description' => ['required', 'string', 'max:255'],
                'items.*.cost' => $money, 'items.*.applicant_amount' => $money,
                'items.*.release_method' => ['required', 'in:supplier,asset,cash'], 'items.*.supplier' => ['nullable', 'string', 'max:255'],
                'items.*.price_checks' => ['nullable', 'array', 'max:10'],
                'items.*.price_checks.*' => ['array'],
                'items.*.price_checks.*.supplier' => ['required', 'string', 'max:255'], 'items.*.price_checks.*.amount' => $money,
                'items.*.price_checks.*.reference' => ['required', 'string', 'max:255'],
                'items.*.exception_reason' => ['nullable', 'string', 'max:2000'],
                'attachments' => ['nullable', 'array', 'max:10'], 'attachments.*' => $file,
                'price_check_file' => ['nullable', ...$file],
            ],
            'verify' => ['notes' => ['required', 'string', 'max:5000'], 'evidence' => ['required', ...$file]],
            'decide' => [
                'decision' => ['required', 'in:accept,reject'], 'notes' => ['required', 'string', 'max:5000'],
                'committee_id' => ['nullable', 'integer', 'exists:committees,id'],
                'resolution_reference' => ['nullable', 'string', 'max:255'], 'resolution_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today],
                'consenting_member_ids' => ['nullable', 'array', 'max:100'], 'consenting_member_ids.*' => ['integer', 'distinct'],
                'resolution_file' => ['nullable', ...$file],
                'approve_purchase_exceptions' => ['sometimes', 'accepted'],
            ],
            'sign' => $signatures + ['contribution_confirmed' => ['required', 'accepted'], 'terms_hash' => ['required', 'string', 'size:64']],
            'finalize' => ['terms_hash' => ['required', 'string', 'size:64'], 'reason' => ['required', 'string', 'max:2000']] +
                (($data['support_type'] ?? null) === 'grant' ? [] : $signatures),
            'disburse', 'repay' => [
                'amount' => $money, 'idempotency_key' => ['required', 'uuid'], 'payment_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today],
                'payment_method' => ['required', 'in:cash,bank_transfer,mobile_wallet,cheque'], 'payee' => ['required', 'string', 'max:255'],
                'reference' => ['nullable', 'string', 'max:255'], 'receipt' => ['required', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:5000'],
                'budget_item_id' => [$action === 'disburse' ? 'required' : 'prohibited', 'integer'],
                'evidence' => [$action === 'disburse' ? 'required' : 'nullable', ...$file],
            ],
            'handover' => ['movement_id' => ['required', 'integer'], 'recipient' => ['required', 'string', 'max:255'],
                'items' => ['required', 'string', 'max:5000'], 'handed_over_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today], 'evidence' => ['required', ...$file]],
            'start' => ['started_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today], 'evidence' => ['required', ...$file]],
            'monitor' => ['review_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today],
                'condition' => ['required', 'in:running,paused,stopped'], 'findings' => ['required', 'string', 'max:5000'], 'actions' => ['required', 'string', 'max:3000'],
                'sales' => ['nullable', ...array_slice($money, 1)], 'expenses' => ['nullable', ...array_slice($money, 1)],
                'final_report' => ['required', 'boolean'], 'next_review_date' => ['nullable', 'required_unless:final_report,1', 'date_format:Y-m-d', 'after_or_equal:review_date'],
                'evidence' => ['nullable', ...$file]],
            'revise', 'cancel', 'close', 'void', 'archive' => ['reason' => ['required', 'string', 'max:2000']],
            default => [],
        };
        if ($action !== 'store') {
            $rules['version'] = ['required', 'integer', 'min:1'];
        }

        return Validator::make($data, $rules)->validate();
    }
}
