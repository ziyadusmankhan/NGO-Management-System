
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('welfare_application_agreements', function (Blueprint $table) {
            $table->foreignId('beneficiary_signature_verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('organization_signature_verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
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
