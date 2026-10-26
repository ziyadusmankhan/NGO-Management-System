<?php

namespace App\Domain\Projects\Services;

use Illuminate\Support\Facades\Validator;

class ProjectRules
{
    public static function validate(string $action, array $data): array
    {
        $today = now(config('projects.timezone'))->toDateString();
        $money = ['required', 'string', 'regex:/^\d{1,13}(\.\d{1,2})?$/'];
        $file = ['file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:10240'];
        $reason = ['required', 'string', 'max:5000'];
        if (in_array($action, ['store', 'update']) && is_array($data['items'] ?? null)) {
            $data['items'] = array_values(array_filter($data['items'], fn ($i) => ! is_array($i) || ! is_string($i['description'] ?? '') || trim($i['description'] ?? '') !== ''));
            foreach ($data['items'] as &$item) {
                if (is_array($item) && is_array($item['price_checks'] ?? null)) {
                    $item['price_checks'] = array_values(array_filter($item['price_checks'], fn ($q) => ! is_array($q) || ! is_string($q['supplier'] ?? '') || trim($q['supplier'] ?? '') !== ''));
                }
            } unset($item);
        }
        $rules = match ($action) {
            'store','update' => [
                'name' => ['required', 'string', 'max:255'], 'description' => $reason, 'location' => ['required', 'string', 'max:255'], 'outcomes' => $reason, 'approach' => $reason,
                'beneficiaries' => ['required', 'integer', 'min:0', 'max:100000000'], 'category_id' => ['required', 'integer', 'exists:project_categories,id'],
                'fund_id' => ['nullable', 'integer', 'exists:funds,id'], 'committee_id' => ['required', 'integer', 'exists:committees,id'], 'responsible_id' => ['required', 'integer', 'exists:users,id'],
                'planned_start' => ['required', 'date_format:Y-m-d'], 'planned_completion' => ['required', 'date_format:Y-m-d', 'after_or_equal:planned_start'],
                'items' => ['required', 'array', 'min:1', 'max:50'], 'items.*' => ['array'], 'items.*.budget_item_id' => ['nullable', 'integer', 'distinct'],
                'items.*.description' => ['required', 'string', 'max:255'], 'items.*.kind' => ['required', 'in:purchase,labor,service'],
                'items.*.payee' => ['required', 'string', 'max:255'], 'items.*.amount' => $money,
                'items.*.price_checks' => ['nullable', 'array', 'max:10'], 'items.*.price_checks.*' => ['array'],
                'items.*.price_checks.*.supplier' => ['required', 'string', 'max:255'], 'items.*.price_checks.*.amount' => $money, 'items.*.price_checks.*.reference' => ['required', 'string', 'max:255'],
                'items.*.estimate' => ['nullable', 'string', 'max:3000'], 'items.*.exception_reason' => ['nullable', 'string', 'max:3000'],
                'evidence' => ['nullable', ...$file], 'attachments' => ['nullable', 'array', 'max:10'], 'attachments.*' => $file,
            ],
            'verify' => ['notes' => $reason, 'evidence' => ['required', ...$file]],
            'decide' => ['decision' => ['required', 'in:accept,reject'], 'notes' => $reason,
                'committee_id' => ['nullable', 'integer'], 'resolution_reference' => ['nullable', 'string', 'max:255'],
                'resolution_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today], 'evidence' => ['nullable', ...$file],
                'consenting_member_ids' => ['nullable', 'array', 'max:100'], 'consenting_member_ids.*' => ['integer', 'distinct'],
                'approve_purchase_exceptions' => ['sometimes', 'accepted']],
            'pay','refund' => ['amount' => $money, 'idempotency_key' => ['required', 'uuid'], 'payment_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today],
                'payment_method' => ['required', 'in:cash,bank_transfer,mobile_wallet,cheque'], 'payee' => ['required', 'string', 'max:255'], 'receipt' => ['required', 'string', 'max:255'],
                'reference' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:5000'], 'evidence' => ['required', ...$file],
                'budget_item_id' => [$action === 'pay' ? 'required' : 'prohibited', 'integer'], 'original_payment_id' => [$action === 'refund' ? 'required' : 'prohibited', 'integer'], 'fund_id' => ['prohibited']],
            'start' => ['date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today], 'evidence' => ['required', ...$file]],
            'monitor','complete' => ['date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today], 'percent' => ['required', 'integer', 'min:0', 'max:100'],
                'findings' => $reason, 'risks' => ['nullable', 'string', 'max:5000'], 'next_review_date' => ['nullable', 'date_format:Y-m-d', 'after:date'], 'evidence' => [$action === 'complete' ? 'required' : 'nullable', ...$file]],
            'close' => ['reason' => $reason, 'final_bills_settled' => ['required', 'accepted']],
            'cancel' => ['reason' => $reason, 'evidence' => ['nullable', ...$file]],
            'amend','reopen','resume','withdraw','archive','void' => ['reason' => $reason],
            'dedicated-fund' => ['name' => ['required', 'string', 'max:255']],
            default => [],
        };
        if ($action !== 'store') {
            $rules['version'] = ['required', 'integer', 'min:1'];
        }

        return Validator::make($data, $rules)->validate();
    }
}
