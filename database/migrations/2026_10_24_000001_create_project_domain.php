<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('purpose');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('projects', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->string('name');
            $t->foreignId('fund_id')->nullable()->constrained('funds')->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->string('status')->default('draft')->index();
            $t->unsignedInteger('version')->default(1);
            $t->unsignedInteger('revision')->default(1);
            $t->unsignedInteger('approved_revision')->nullable();
            $t->boolean('payments_frozen')->default(false);
            $t->timestamp('fund_locked_at')->nullable();
            $t->date('started_on')->nullable();
            $t->date('completed_on')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->date('next_review_date')->nullable()->index();
            $t->unsignedTinyInteger('progress_percent')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::table('funds', function (Blueprint $t) {
            $t->foreignId('project_id')->nullable()->unique()->constrained('projects')->restrictOnDelete();
        });
        Schema::create('project_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('kind')->default('proposal');
            $t->string('stage')->default('draft');
            $t->text('reason')->nullable();
            $t->string('name');
            $t->text('description');
            $t->string('location');
            $t->text('outcomes');
            $t->text('approach');
            $t->unsignedInteger('beneficiaries');
            $t->foreignId('category_id')->constrained('project_categories')->restrictOnDelete();
            $t->string('category_name');
            $t->string('purpose');
            $t->foreignId('committee_id')->constrained('committees')->restrictOnDelete();
            $t->foreignId('responsible_id')->constrained('users')->restrictOnDelete();
            $t->date('planned_start');
            $t->date('planned_completion');
            $t->decimal('budget', 15, 2);
            $t->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->unique(['project_id', 'revision']);
        });
        Schema::create('project_budget_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->restrictOnDelete();
            $t->string('reference')->unique();
            $t->timestamps();
        });
        Schema::create('project_budget_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->foreignId('budget_item_id')->constrained('project_budget_items')->restrictOnDelete();
            $t->string('description');
            $t->string('kind');
            $t->string('payee');
            $t->decimal('amount', 15, 2);
            $t->json('price_checks')->nullable();
            $t->text('estimate')->nullable();
            $t->text('exception_reason')->nullable();
            $t->timestamps();
            $t->unique(['budget_item_id', 'revision']);
        });
        Schema::create('project_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('stage');
            $t->string('decision');
            $t->text('notes');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->json('resolution')->nullable();
            $t->timestamps();
            $t->unique(['project_id', 'revision', 'stage']);
        });
        Schema::create('project_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->foreignId('budget_item_id')->constrained('project_budget_items')->restrictOnDelete();
            $t->foreignId('original_payment_id')->nullable()->constrained('project_movements')->restrictOnDelete();
            $t->foreignId('fund_id')->constrained('funds')->restrictOnDelete();
            $t->string('kind');
            $t->string('status')->default('posted');
            $t->decimal('amount', 15, 2);
            $t->date('payment_date');
            $t->string('payment_method');
            $t->string('payee');
            $t->string('reference')->nullable();
            $t->string('receipt');
            $t->text('notes')->nullable();
            $t->uuid('idempotency_key')->unique();
            $t->string('payload_hash', 64);
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('voided_at')->nullable();
            $t->text('void_reason')->nullable();
            $t->timestamps();
            $t->index(['project_id', 'kind', 'status']);
        });
        Schema::create('project_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('kind');
            $t->date('report_date');
            $t->unsignedTinyInteger('percent');
            $t->text('findings');
            $t->text('risks')->nullable();
            $t->date('next_review_date')->nullable();
            $t->timestamps();
        });
        Schema::create('project_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('kind');
            $t->foreignId('movement_id')->nullable()->constrained('project_movements')->restrictOnDelete();
            $t->foreignId('progress_id')->nullable()->constrained('project_progress')->restrictOnDelete();
            $t->string('path');
            $t->string('original_name');
            $t->string('sha256', 64);
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('project_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('action');
            $t->json('before_values')->nullable();
            $t->json('after_values')->nullable();
            $t->timestamps();
        });
        Schema::create('project_reminders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->unique()->constrained()->restrictOnDelete();
            $t->boolean('review_due');
            $t->boolean('completion_overdue');
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['project_reminders', 'project_events', 'project_documents', 'project_progress', 'project_movements', 'project_reviews', 'project_budget_versions', 'project_budget_items', 'project_revisions'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('funds', fn (Blueprint $t) => $t->dropConstrainedForeignId('project_id'));
        Schema::dropIfExists('projects');
        Schema::dropIfExists('project_categories');
    }
};
