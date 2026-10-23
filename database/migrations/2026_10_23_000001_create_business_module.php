<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('business_applications', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->foreignId('beneficiary_id')->constrained()->restrictOnDelete();
            $t->foreignId('fund_id')->constrained()->restrictOnDelete();
            $t->foreignId('category_id')->constrained('business_categories')->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->string('name');
            $t->string('location');
            $t->text('description');
            $t->text('skills');
            $t->text('market_assessment');
            $t->text('risks');
            $t->date('expected_start_date');
            $t->decimal('expected_sales', 15, 2);
            $t->decimal('expected_expenses', 15, 2);
            $t->decimal('total_investment', 15, 2);
            $t->decimal('applicant_contribution', 15, 2);
            $t->decimal('approved_amount', 15, 2);
            $t->string('support_type', 20);
            $t->string('frequency', 10)->nullable();
            $t->unsignedInteger('installment_count')->nullable();
            $t->json('beneficiary_snapshot')->nullable();
            $t->string('category_snapshot')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->unsignedInteger('version')->default(1);
            $t->string('status', 25)->default('draft')->index();
            $t->string('funding_status', 20)->default('open');
            $t->unsignedInteger('funding_version')->default(0);
            $t->timestamp('funding_closed_at')->nullable();
            $t->string('operating_status', 20)->default('not_started');
            $t->date('started_on')->nullable();
            $t->date('next_review_date')->nullable()->index();
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('business_budget_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_application_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('description');
            $t->decimal('cost', 15, 2);
            $t->decimal('applicant_amount', 15, 2);
            $t->decimal('organization_amount', 15, 2);
            $t->string('release_method', 20);
            $t->string('supplier')->nullable();
            $t->json('price_checks')->nullable();
            $t->text('exception_reason')->nullable();
            $t->timestamps();
            $t->index(['business_application_id', 'revision']);
        });
        Schema::create('business_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_application_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('stage');
            $t->string('decision');
            $t->text('notes');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('committee_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('resolution_reference')->nullable();
            $t->date('resolution_date')->nullable();
            $t->json('consenting_members')->nullable();
            $t->json('purchase_exceptions')->nullable();
            $t->timestamps();
            $t->unique(['business_application_id', 'revision', 'stage']);
        });
        Schema::create('business_agreements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_application_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->unsignedInteger('funding_version')->default(0);
            $t->string('kind', 20);
            $t->json('terms');
            $t->string('borrower_name');
            $t->string('foundation_name');
            $t->json('witnesses');
            $t->date('signed_on');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['business_application_id', 'revision', 'kind', 'funding_version'], 'business_agreement_version_unique');
        });
        Schema::create('business_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_application_id')->constrained()->restrictOnDelete();
            $t->foreignId('fund_id')->constrained()->restrictOnDelete();
            $t->foreignId('budget_item_id')->nullable()->constrained('business_budget_items')->restrictOnDelete();
            $t->string('kind', 20);
            $t->string('status', 15)->default('posted');
            $t->uuid('idempotency_key')->unique();
            $t->string('request_hash', 64);
            $t->decimal('amount', 15, 2);
            $t->date('payment_date');
            $t->string('payment_method', 30);
            $t->string('payee');
            $t->string('reference')->nullable();
            $t->string('receipt')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('voided_at')->nullable();
            $t->text('void_reason')->nullable();
            $t->timestamps();
            $t->index(['business_application_id', 'kind', 'status']);
        });
        Schema::create('business_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_application_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('kind');
            $t->foreignId('movement_id')->nullable()->constrained('business_movements')->restrictOnDelete();
            $t->foreignId('agreement_id')->nullable()->constrained('business_agreements')->restrictOnDelete();
            $t->string('path');
            $t->string('original_name');
            $t->string('sha256', 64);
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('business_handovers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_application_id')->constrained()->restrictOnDelete();
            $t->foreignId('movement_id')->unique()->constrained('business_movements')->restrictOnDelete();
            $t->string('recipient');
            $t->date('handed_over_on');
            $t->text('items');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('business_installments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_application_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('funding_version');
            $t->unsignedInteger('sequence');
            $t->date('due_date')->index();
            $t->decimal('amount', 15, 2);
            $t->timestamps();
            $t->unique(['business_application_id', 'funding_version', 'sequence'], 'business_installment_version_unique');
        });
        Schema::create('business_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_movement_id')->constrained()->restrictOnDelete();
            $t->foreignId('business_installment_id')->constrained()->restrictOnDelete();
            $t->decimal('amount', 15, 2);
            $t->timestamps();
            $t->unique(['business_movement_id', 'business_installment_id'], 'business_installment_allocation_unique');
        });
        Schema::create('business_monitorings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_application_id')->constrained()->restrictOnDelete();
            $t->date('review_date');
            $t->string('condition');
            $t->text('findings');
            $t->text('actions');
            $t->decimal('sales', 15, 2)->nullable();
            $t->decimal('expenses', 15, 2)->nullable();
            $t->date('next_review_date')->nullable();
            $t->boolean('final_report')->default(false);
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('business_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_application_id')->constrained()->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('action');
            $t->json('before_values');
            $t->json('after_values');
            $t->timestamps();
        });
        Schema::create('business_reminders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_application_id')->unique()->constrained()->restrictOnDelete();
            $t->text('message');
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });
        Schema::create('business_category_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained('business_categories')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->json('before_values');
            $t->json('after_values');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['category_events', 'reminders', 'events', 'monitorings', 'allocations', 'installments', 'handovers', 'documents', 'movements', 'agreements', 'reviews', 'budget_items', 'applications', 'categories'] as $table) {
            Schema::dropIfExists('business_'.$table);
        }
    }
};
