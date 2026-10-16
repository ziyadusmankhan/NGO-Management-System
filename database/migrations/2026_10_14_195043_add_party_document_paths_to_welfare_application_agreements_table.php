
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('welfare_application_agreements', function (Blueprint $table) {
            $table->string('beneficiary_document_path')
                ->nullable();

            $table->string('organization_document_path')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('welfare_application_agreements', function (Blueprint $table) {
            $table->dropColumn([
                'beneficiary_document_path',
                'organization_document_path',
            ]);
        });
    }
};
