<?php

namespace App\Rules;

use App\Domain\Security\Services\FieldEncryption;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueMemberCnic implements ValidationRule
{
    public function __construct(public ?int $except = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cipher = app(FieldEncryption::class);
        $digest = $cipher->lookup('cnic', $value);
        $exists = DB::table('members')->when($this->except, fn ($q) => $q->where('id', '!=', $this->except))->where('cnic_lookup', $digest)->exists();
        if ($exists) {
            $fail('This CNIC already belongs to a member, including archived members.');
        }
    }
}
