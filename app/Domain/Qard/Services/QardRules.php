<?php

namespace App\Domain\Qard\Services;

use App\Domain\Finance\Support\Money;
use Illuminate\Support\Facades\Validator;

class QardRules
{
    public static function rules(string $action): array
    {
        $today = now('Asia/Karachi')->toDateString();
        $money = ['required', 'string', 'regex:/^\d{1,13}(\.\d{1,2})?$/', function ($attribute, $value, $fail) {
            if (is_string($value) && preg_match('/^\d{1,13}(\.\d{1,2})?$/', $value) && Money::minor($value) < 1) {
                $fail('The amount must be greater than zero.');
            }
        }];
        $file = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:10240'];

        return match ($action) {
            'store', 'update' => [
                'beneficiary_id' => ['required', 'integer', 'exists:beneficiaries,id'],
                'fund_id' => ['required', 'integer', 'exists:funds,id'],
                'purpose' => ['required', 'string', 'max:5000'],
                'principal' => $money,
                'frequency' => ['required', 'in:weekly,monthly'],
                'installment_count' => ['required', 'integer', 'min:1', 'max:600'],
                'first_due_date' => ['required', 'date_format:Y-m-d', 'after:'.$today],
            ],
            'verify' => ['notes' => ['required', 'string', 'max:5000'], 'evidence' => $file],
            'decide' => [
                'decision' => ['required', 'in:accept,reject'],
                'notes' => ['required', 'string', 'max:5000'],
            ],
            'sign' => [
                'borrower_name' => ['required', 'string', 'max:255'],
                'foundation_name' => ['required', 'string', 'max:255'],
                'borrower_file' => $file, 'foundation_file' => $file,
                'witnesses' => ['required', 'array', 'size:2'], 'witnesses.*' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            ],
            'disburse', 'repay' => [
                'amount' => $money, 'idempotency_key' => ['required', 'uuid'],
                'payment_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today],
                'payment_method' => ['required', 'in:cash,bank_transfer,mobile_wallet,cheque'],
                'reference' => ['nullable', 'string', 'max:255'],
                'receipt' => ['nullable', 'string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:5000'],
            ],
            'void', 'revise' => ['reason' => ['required', 'string', 'max:2000']],
            'followup' => ['notes' => ['required', 'string', 'max:5000'], 'next_followup_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$today]],
            default => [],
        };
    }

    public static function validate(string $action, array $data): array
    {
        if ($action === 'sign' && isset($data['witnesses']) && is_array($data['witnesses'])) {
            $data['witnesses'] = array_map(fn ($v) => is_string($v) ? preg_replace('/\s+/u', ' ', trim($v)) : $v, $data['witnesses']);
        }

        return Validator::make($data, self::rules($action))->validate();
    }
}
