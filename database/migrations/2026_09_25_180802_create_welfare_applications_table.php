<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('welfare_applications', function (Blueprint $table) {

            $table->id();

            /*
             * Public application reference.
             * Example: APP-00001
             */
            $table->string('application_number', 30)
                ->nullable()
                ->unique();

            /*
             * Applicant
             */
            $table->foreignId('beneficiary_id')
                ->constrained('beneficiaries')
                ->restrictOnDelete();

            /*
             * Proposed funding source.
             */
            $table->foreignId('fund_id')
                ->nullable()
                ->constrained('funds')
                ->restrictOnDelete();

            /*
             * Welfare program/category.
             *
             * Examples:
             * welfare
             * livelihood
             * education
             * emergency
             */
            $table->string('category', 40);

            /*
             * Specific nature of the request.
             */
            $table->string('request_type', 60);

            /*
             * Financial request.
             */
            $table->decimal('requested_amount', 15, 2);

            $table->decimal('applicant_contribution', 15, 2)
                ->default(0);

            $table->decimal('organization_contribution', 15, 2)
                ->default(0);

            /*
             * Applicant's explanation of the request.
             */
            $table->text('purpose_details');

            /*
             * Urgency.
             */
            $table->string('priority', 20)
                ->default('normal');

            /*
             * Requested assistance frequency.
             *
             * one_time
             * weekly
             * monthly
             * quarterly
             */
            $table->string('frequency', 20)
                ->default('one_time');

            $table->date('start_date')
                ->nullable();

            $table->date('end_date')
                ->nullable();

            /*
             * Main workflow status.
             *
             * submitted
             * verification
             * recommendation
             * approved
             * rejected
             */
            $table->string('status', 30)
                ->default('submitted');

            /*
             * Submission information.
             */
            $table->timestamp('submitted_at')
                ->nullable();

            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Recommendation stage.
             */
            $table->decimal('recommended_amount', 15, 2)
                ->nullable();

            $table->text('recommendation_notes')
                ->nullable();

            $table->foreignId('recommended_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('recommended_at')
                ->nullable();

            /*
             * Approval stage.
             */
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')
                ->nullable();

            /*
             * Rejection stage.
             */
            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('rejected_at')
                ->nullable();

            $table->text('rejection_reason')
                ->nullable();

            /*
             * Documents / internal administrative information.
             */
            $table->boolean('has_supporting_document')
                ->default(false);

            $table->text('internal_notes')
                ->nullable();

            $table->timestamps();

            $table->softDeletes();

            /*
             * Useful indexes for the application queue.
             */
            $table->index([
                'status',
                'priority',
            ]);

            $table->index([
                'category',
                'status',
            ]);

            $table->index('submitted_at');

            $table->index('beneficiary_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('welfare_applications');
    }
};