
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('welfare_application_agreements', function (Blueprint $table) {
            $table->id();

            // Unique agreement identifier, e.g. AGR-00001.
            $table->string('agreement_number', 30)->unique();

            // Enforce at most one agreement per application.
            $table->foreignId('application_id')
                ->unique()
                ->constrained('welfare_applications')
                ->restrictOnDelete();

            $table->decimal('approved_amount', 15, 2);

            $table->date('agreement_date')->nullable();
            $table->date('effective_date')->nullable();

            // draft, prepared, signed, cancelled
            $table->string('status', 30)->default('draft');

            $table->text('terms');

            $table->string('beneficiary_signatory_name')->nullable();
            $table->timestamp('beneficiary_signed_at')->nullable();

            $table->string('organization_signatory_name')->nullable();
            $table->timestamp('organization_signed_at')->nullable();

            $table->string('witness_one_name')->nullable();
            $table->string('witness_two_name')->nullable();

            // Store a private-disk path, never a public URL.
            $table->string('document_path')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('prepared_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('fully_signed_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'agreement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('welfare_application_agreements');
    }
};
