<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiary_verifications', function (Blueprint $table) {
            /*
             * Application-specific field verification.
             *
             * Existing beneficiary verification records may not have
             * an application attached, so application_id is nullable.
             */
            $table->foreignId('application_id')
                ->nullable()
                ->after('beneficiary_id')
                ->constrained('welfare_applications')
                ->nullOnDelete();

            /*
             * Human-readable verification reference.
             * Example: VER-00001
             */
            $table->string('verification_number', 30)
                ->nullable()
                ->unique()
                ->after('id');

            /*
             * Officer responsible for carrying out the field verification.
             */
            $table->foreignId('assigned_to')
                ->nullable()
                ->after('verified_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('assigned_at')
                ->nullable()
                ->after('verification_date');

            /*
             * Planned field visit.
             */
            $table->date('visit_date')
                ->nullable()
                ->after('assigned_at');

            /*
             * Actual workflow timestamps.
             */
            $table->timestamp('started_at')
                ->nullable()
                ->after('visit_date');

            $table->timestamp('completed_at')
                ->nullable()
                ->after('started_at');

            /*
             * Field verification workflow state.
             *
             * assigned
             * in_progress
             * completed
             */
            $table->string('workflow_status', 30)
                ->nullable()
                ->after('status');

            /*
             * Priority of the verification assignment.
             */
            $table->string('priority', 20)
                ->default('normal')
                ->after('workflow_status');

            /*
             * Structured checklist information.
             *
             * Example:
             * {
             *   "identity_verified": true,
             *   "residence_verified": true,
             *   "family_condition_verified": true,
             *   "financial_condition_verified": true,
             *   "documents_reviewed": true,
             *   "photos_attached": true
             * }
             */
            $table->json('checklist')
                ->nullable()
                ->after('findings');

            /*
             * Final recommendation resulting from the field visit.
             *
             * approve
             * reject
             * revise
             */
            $table->string('recommendation', 30)
                ->nullable()
                ->after('checklist');

            $table->text('recommendation_notes')
                ->nullable()
                ->after('recommendation');

            /*
             * Useful indexes for the verification queue.
             */
            $table->index(
                ['application_id', 'workflow_status'],
                'bv_application_workflow_idx'
            );

            $table->index(
                ['assigned_to', 'workflow_status'],
                'bv_assigned_workflow_idx'
            );

            $table->index(
                ['visit_date', 'workflow_status'],
                'bv_visit_workflow_idx'
            );

            $table->index(
                ['priority', 'workflow_status'],
                'bv_priority_workflow_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('beneficiary_verifications', function (Blueprint $table) {
            $table->dropIndex('bv_application_workflow_idx');
            $table->dropIndex('bv_assigned_workflow_idx');
            $table->dropIndex('bv_visit_workflow_idx');
            $table->dropIndex('bv_priority_workflow_idx');

            $table->dropForeign(['application_id']);
            $table->dropForeign(['assigned_to']);

            $table->dropUnique([
                'verification_number',
            ]);

            $table->dropColumn([
                'application_id',
                'verification_number',
                'assigned_to',
                'assigned_at',
                'visit_date',
                'started_at',
                'completed_at',
                'workflow_status',
                'priority',
                'checklist',
                'recommendation',
                'recommendation_notes',
            ]);
        });
    }
};