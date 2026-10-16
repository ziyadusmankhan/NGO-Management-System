<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('welfare_application_reviews', function (Blueprint $table) {
            $table->id();

            /*
             * Human-readable audit identifier.
             * Example: REV-00001
             */
            $table->string('review_number', 30)->unique();

            /*
             * Welfare application being reviewed.
             */
            $table->foreignId('application_id')
                ->constrained('welfare_applications')
                ->cascadeOnDelete();

            /*
             * Which organizational stage made the review.
             *
             * implementation_committee
             * general_secretary
             * decision_making_committee
             */
            $table->string('stage', 50);

            /*
             * Decision/action taken at that stage.
             *
             * approve
             * revise
             * reject
             * return
             */
            $table->string('decision', 30);

            /*
             * Amount recommended/confirmed at this stage.
             */
            $table->decimal('recommended_amount', 15, 2)
                ->nullable();

            /*
             * Explanation, conditions or review notes.
             */
            $table->text('notes')->nullable();

            /*
             * User who made the decision.
             */
            $table->foreignId('decided_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Actual decision timestamp.
             */
            $table->timestamp('decided_at')->nullable();

            /*
             * Optional structured information for future
             * committee-specific details without changing
             * the schema every time.
             */
            $table->json('metadata')->nullable();

            $table->timestamps();

            /*
             * Common workflow lookups.
             */
            $table->index([
                'application_id',
                'stage',
            ]);

            $table->index([
                'application_id',
                'decided_at',
            ]);

            $table->index('decision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('welfare_application_reviews');
    }
};