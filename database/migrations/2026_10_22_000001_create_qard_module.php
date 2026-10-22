<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qard_loans', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->foreignId('beneficiary_id')->constrained('beneficiaries')->restrictOnDelete();
            $t->foreignId('fund_id')->constrained('funds')->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->text('purpose');
            $t->decimal('principal', 15, 2);
            $t->string('frequency', 10);
            $t->unsignedInteger('installment_count');
            $t->date('first_due_date');
            $t->unsignedInteger('revision')->default(1);
            $t->string('status', 30)->default('draft')->index();
            $t->timestamps();
        });
        Schema::create('qard_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('qard_loan_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('stage');
            $t->string('decision');
            $t->text('notes');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['qard_loan_id', 'revision', 'stage']);
        });
        Schema::create('qard_agreements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('qard_loan_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->json('terms');
            $t->string('borrower_name');
            $t->string('foundation_name');
            $t->timestamp('signed_at');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['qard_loan_id', 'revision']);
        });
        Schema::create('qard_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('qard_loan_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('kind');
            $t->string('path');
            $t->string('original_name');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('qard_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('qard_loan_id')->constrained()->restrictOnDelete();
            $t->foreignId('fund_id')->constrained()->restrictOnDelete();
            $t->string('kind', 20);
            // Nullable on void: at most one posted disbursement per loan.
            $t->foreignId('active_disbursement_for')->nullable()->unique()->constrained('qard_loans')->restrictOnDelete();
            $t->uuid('idempotency_key')->unique();
            $t->decimal('amount', 15, 2);
            $t->date('payment_date');
            $t->string('payment_method', 30);
            $t->string('reference')->nullable();
            $t->string('receipt')->nullable();
            $t->text('notes')->nullable();
            $t->string('status', 15)->default('posted');
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('voided_at')->nullable();
            $t->text('void_reason')->nullable();
            $t->timestamps();
            $t->index(['qard_loan_id', 'kind', 'status']);
        });
        Schema::create('qard_installments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('qard_loan_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->unsignedInteger('sequence');
            $t->date('due_date')->index();
            $t->decimal('amount', 15, 2);
            $t->timestamps();
            $t->unique(['qard_loan_id', 'revision', 'sequence']);
        });
        Schema::create('qard_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('qard_movement_id')->constrained()->restrictOnDelete();
            $t->foreignId('qard_installment_id')->constrained()->restrictOnDelete();
            $t->decimal('amount', 15, 2);
            $t->timestamps();
            $t->unique(['qard_movement_id', 'qard_installment_id']);
        });
        Schema::create('qard_followups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('qard_loan_id')->constrained()->restrictOnDelete();
            $t->text('notes');
            $t->date('next_followup_date')->nullable();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('qard_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('qard_loan_id')->constrained()->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('action');
            $t->json('before')->nullable();
            $t->json('after')->nullable();
            $t->timestamps();
        });
        Schema::create('qard_reminders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('qard_loan_id')->unique()->constrained()->restrictOnDelete();
            $t->text('message');
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['reminders', 'events', 'followups', 'allocations', 'installments', 'movements', 'documents', 'agreements', 'reviews', 'loans'] as $table) {
            Schema::dropIfExists('qard_'.$table);
        }
    }
};
