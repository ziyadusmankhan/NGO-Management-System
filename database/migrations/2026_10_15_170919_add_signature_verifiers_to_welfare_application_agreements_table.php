
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['beneficiary' => 'waa_beneficiary_verifier_fk', 'organization' => 'waa_organization_verifier_fk'] as $party => $name) {
            $column = $party.'_signature_verified_by';
            if (! Schema::hasColumn('welfare_application_agreements', $column)) {
                Schema::table('welfare_application_agreements', fn (Blueprint $table) => $table->foreignId($column)->nullable());
            }
            $exists = collect(Schema::getForeignKeys('welfare_application_agreements'))->contains(fn ($key) => in_array($column, $key['columns']));
            if (! $exists) {
                Schema::table('welfare_application_agreements', fn (Blueprint $table) => $table->foreign($column, $name)->references('id')->on('users')->nullOnDelete());
            }
        }
    }

    public function down(): void
    {
        Schema::table('welfare_application_agreements', function (Blueprint $table) {
            $table->dropColumn([
                'beneficiary_signature_verified_by',
                'organization_signature_verified_by',
            ]);
        });
    }
};
