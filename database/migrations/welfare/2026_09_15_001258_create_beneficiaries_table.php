<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();

            /*
             * System-generated beneficiary identifier.
             * Example: BEN-00001
             */
            $table->string('beneficiary_number', 30)->unique();

            /*
             * Basic identity
             */
            $table->string('full_name', 200);

            $table->string('father_husband_name', 200)->nullable();

            $table->string('cnic', 30)->nullable();

            $table->string('phone', 50)->nullable();

            $table->string('alternate_phone', 50)->nullable();

            $table->text('address')->nullable();

            $table->string('location', 150)->nullable();

            /*
             * Beneficiary classification
             *
             * elderly_poor
             * disabled_vulnerable
             * poor_employable
             * student
             * emergency
             */
            $table->string('category', 50);

            /*
             * Household / financial condition
             */
            $table->string('marital_status', 30)->nullable();

            $table->unsignedInteger('dependents_count')
                ->default(0);

            $table->string('occupation', 150)->nullable();

            $table->decimal('monthly_income', 15, 2)
                ->nullable();

            $table->text('assets_description')->nullable();

            $table->text('debts_description')->nullable();

            $table->text('household_condition')->nullable();

            /*
             * Verification workflow
             *
             * pending
             * verified
             * rejected
             */
            $table->string('verification_status', 30)
                ->default('pending');

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')
                ->nullable();

            /*
             * Approval workflow
             *
             * pending
             * approved
             * rejected
             */
            $table->string('approval_status', 30)
                ->default('pending');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')
                ->nullable();

            /*
             * Periodic review
             */
            $table->date('next_review_date')
                ->nullable();

            /*
             * Internal administrative notes
             */
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->softDeletes();

            /*
             * Indexes
             */
            $table->index('category');
            $table->index('verification_status');
            $table->index('approval_status');
            $table->index('next_review_date');
            $table->index('phone');
            $table->index('cnic');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};