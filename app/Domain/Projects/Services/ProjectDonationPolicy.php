<?php

namespace App\Domain\Projects\Services;

use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Funds\Models\Fund;
use Illuminate\Validation\ValidationException;

class ProjectDonationPolicy
{
    public function validate(array $data, ?Donation $existing = null): void
    {
        $fund = Fund::withTrashed()->whereKey($data['fund_id'] ?? 0)->lockForUpdate()->first();
        $old = $existing?->fund_id ? Fund::withTrashed()->find($existing->fund_id) : null;
        if ($old?->project_id && (int) $fund?->id !== (int) $old->id) {
            throw ValidationException::withMessages(['fund_id' => 'This donation must stay in its original named-project fund.']);
        }
        if ($fund?->project_id && (($data['designation'] ?? '') !== 'restricted' || ($data['purpose'] ?? '') !== 'project')) {
            throw ValidationException::withMessages(['designation' => 'Select Restricted designation and Project purpose for a dedicated project fund.']);
        }
        if (($data['purpose'] ?? '') === 'project' && ! $fund?->project_id && (! $existing || $existing->purpose !== 'project' || (int) $existing->fund_id !== (int) $fund?->id)) {
            throw ValidationException::withMessages(['fund_id' => 'Create a dedicated fund from the named project, then select it for this donation.']);
        }
    }
}
