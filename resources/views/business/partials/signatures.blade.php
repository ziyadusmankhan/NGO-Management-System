<div class="qgrid">
<x-business-field label="Borrower signatory name" name="borrower_name" :value="$application->beneficiary_snapshot['full_name'] ?? $application->beneficiary->full_name" />
<x-business-field label="Foundation signatory name" name="foundation_name" />
<x-business-field label="Witness 1 name" name="witnesses[0]" />
<x-business-field label="Witness 2 name" name="witnesses[1]" />
<x-business-field label="Signing date" name="signed_on" type="date" :value="now(config('business.timezone'))->toDateString()" />
<x-business-field label="Borrower signed copy" name="borrower_file" type="file" />
<x-business-field label="Foundation signed copy" name="foundation_file" type="file" />
</div>
