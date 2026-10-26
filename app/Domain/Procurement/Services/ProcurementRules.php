<?php

namespace App\Domain\Procurement\Services;

use Illuminate\Support\Facades\Validator;

class ProcurementRules
{
    public static function validate(string $action, array $input): array
    {
        if ($action === 'invoice' && is_array($input['lines'] ?? null)) {
            $input['lines'] = array_values(array_filter($input['lines'], fn ($line) => ! is_array($line) || isset($line['quantity']) && trim((string) $line['quantity']) !== ''));
        }
        $text = ['required', 'string', 'max:5000'];
        $short = ['required', 'string', 'max:255'];
        $money = ['required', 'string', 'regex:/^\d{1,10}(\.\d{1,2})?$/'];
        $quantity = ['required', 'string', 'regex:/^\d{1,9}(\.\d{1,3})?$/'];
        $file = ['file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:10240'];
        $date = ['required', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Karachi')->toDateString()];
        $rules = match ($action) {
            'store','update' => ['title' => $short, 'description' => $text, 'parent_type' => ['required', 'in:project,business,office'], 'parent_id' => ['nullable', 'integer'], 'fund_id' => ['required', 'integer'], 'supplier_id' => ['required', 'integer'], 'delivery_location' => $short, 'required_on' => ['required', 'date_format:Y-m-d'], 'exception_reason' => ['nullable', 'string', 'max:5000'],
                'items' => ['required', 'array', 'min:1', 'max:50'], 'items.*.key' => ['nullable', 'uuid', 'distinct'], 'items.*.description' => $short, 'items.*.unit' => $short, 'items.*.quantity' => $quantity, 'items.*.unit_price' => $money, 'items.*.budget_item_id' => ['nullable', 'integer', 'distinct'], 'evidence' => ['nullable', ...$file]],
            'quote' => ['supplier_id' => ['required', 'integer'], 'reference' => $short, 'quoted_on' => $date, 'valid_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:quoted_on'], 'terms' => $text, 'items' => ['required', 'array', 'min:1', 'max:50'], 'items.*.key' => ['required', 'uuid', 'distinct'], 'items.*.unit_price' => $money, 'evidence' => ['required', ...$file]],
            'decide' => ['decision' => ['required', 'in:accept,reject'], 'notes' => $text, 'committee_id' => ['nullable', 'integer'], 'resolution_reference' => ['nullable', 'string', 'max:255'], 'resolution_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Karachi')->toDateString()], 'consenting_member_ids' => ['nullable', 'array', 'min:2'], 'consenting_member_ids.*' => ['integer', 'distinct'], 'accept_exception' => ['sometimes', 'accepted'], 'evidence' => ['nullable', ...$file]],
            'receive' => ['item_key' => ['required', 'uuid'], 'received_on' => $date, 'reference' => $short, 'accepted' => $quantity, 'rejected' => $quantity, 'notes' => $text, 'evidence' => ['required', ...$file]],
            'invoice' => ['reference' => $short, 'invoiced_on' => $date, 'due_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:invoiced_on'], 'lines' => ['required', 'array', 'min:1', 'max:50'], 'lines.*.receipt_id' => ['required', 'integer', 'distinct'], 'lines.*.quantity' => $quantity, 'evidence' => ['required', ...$file]],
            'pay','refund' => ['invoice_line_id' => [$action === 'pay' ? 'required' : 'prohibited', 'integer'], 'return_id' => [$action === 'refund' ? 'required' : 'prohibited', 'integer'], 'original_payment_id' => [$action === 'refund' ? 'required' : 'prohibited', 'integer'], 'amount' => $money, 'payment_date' => $date, 'payment_method' => ['required', 'in:cash,bank_transfer,mobile_wallet,cheque'], 'receipt' => $short, 'reference' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:5000'], 'evidence' => ['required', ...$file], 'idempotency_key' => ['required', 'uuid']],
            'return' => ['receipt_id' => ['required', 'integer'], 'invoice_line_id' => ['nullable', 'integer'], 'quantity' => $quantity, 'credit_reference' => $short, 'returned_on' => $date, 'refund_due_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:returned_on'], 'reason' => $text, 'evidence' => ['required', ...$file]],
            'void' => ['record_type' => ['required', 'in:movement,receipt,invoice,return'], 'record_id' => ['required', 'integer'], 'reason' => $text],
            'amend','withdraw','resume','cancel','close','archive' => ['reason' => $text],
            default => [],
        };
        if ($action !== 'store') {
            $rules['version'] = ['required', 'integer', 'min:1'];
        }
        if (in_array($action, ['store', 'update'])) {
            $rules['parent_id'] = [($input['parent_type'] ?? 'office') === 'office' ? 'prohibited' : 'required', 'nullable', 'integer'];
            $rules['items.*.budget_item_id'] = [($input['parent_type'] ?? 'office') === 'office' ? 'prohibited' : 'required', 'nullable', 'integer', 'distinct'];
        }

        return Validator::make($input, $rules)->validate();
    }
}
