<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('name_key')->unique();
            $t->string('contact')->nullable();
            $t->text('address')->nullable();
            $t->string('registration')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('procurement_policies', function (Blueprint $t) {
            $t->id();
            $t->unsignedTinyInteger('quote_count');
            $t->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        DB::table('procurement_policies')->insert(['quote_count' => 2, 'created_at' => now(), 'updated_at' => now()]);
        Schema::create('procurements', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->string('parent_type');
            $t->unsignedBigInteger('parent_id')->nullable();
            $t->foreignId('fund_id')->constrained('funds')->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->string('status')->default('draft');
            $t->unsignedInteger('version')->default(1);
            $t->unsignedInteger('revision')->default(1);
            $t->unsignedInteger('approved_revision')->nullable();
            $t->boolean('frozen')->default(false);
            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['parent_type', 'parent_id']);
        });
        Schema::create('procurement_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('procurement_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('stage')->default('draft');
            $t->string('title');
            $t->text('description');
            $t->string('delivery_location');
            $t->date('required_on');
            $t->foreignId('supplier_id')->constrained('procurement_suppliers')->restrictOnDelete();
            $t->json('supplier_snapshot');
            $t->json('items');
            $t->unsignedBigInteger('total_minor');
            $t->unsignedTinyInteger('quote_count')->nullable();
            $t->text('exception_reason')->nullable();
            $t->text('reason')->nullable();
            $t->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('issued_at')->nullable();
            $t->timestamps();
            $t->unique(['procurement_id', 'revision']);
        });
        Schema::create('procurement_quotes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('procurement_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->foreignId('supplier_id')->constrained('procurement_suppliers')->restrictOnDelete();
            $t->json('supplier_snapshot');
            $t->string('reference');
            $t->date('quoted_on');
            $t->date('valid_until');
            $t->text('terms');
            $t->json('items');
            $t->unsignedBigInteger('total_minor');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['procurement_id', 'revision', 'supplier_id']);
        });
        Schema::create('procurement_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('procurement_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('stage');
            $t->string('decision');
            $t->text('notes');
            $t->json('resolution')->nullable();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['procurement_id', 'revision', 'stage']);
        });
        Schema::create('procurement_receipts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('procurement_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('item_key');
            $t->date('received_on');
            $t->string('reference');
            $t->unsignedBigInteger('accepted_milli');
            $t->unsignedBigInteger('rejected_milli');
            $t->text('notes');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('status')->default('posted');
            $t->text('void_reason')->nullable();
            $t->timestamps();
        });
        Schema::create('procurement_invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('procurement_id')->constrained()->restrictOnDelete();
            $t->foreignId('supplier_id')->constrained('procurement_suppliers')->restrictOnDelete();
            $t->string('reference');
            $t->string('reference_key');
            $t->date('invoiced_on');
            $t->date('due_on');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('status')->default('posted');
            $t->text('void_reason')->nullable();
            $t->timestamps();
            $t->unique(['supplier_id', 'reference_key']);
        });
        Schema::create('procurement_invoice_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('procurement_id')->constrained()->restrictOnDelete();
            $t->foreignId('invoice_id')->constrained('procurement_invoices')->restrictOnDelete();
            $t->foreignId('receipt_id')->constrained('procurement_receipts')->restrictOnDelete();
            $t->string('item_key');
            $t->unsignedBigInteger('quantity_milli');
            $t->unsignedBigInteger('amount_minor');
            $t->timestamps();
        });
        Schema::create('procurement_returns', function (Blueprint $t) {
            $t->id();
            $t->foreignId('procurement_id')->constrained()->restrictOnDelete();
            $t->foreignId('receipt_id')->constrained('procurement_receipts')->restrictOnDelete();
            $t->foreignId('invoice_line_id')->nullable()->constrained('procurement_invoice_lines')->restrictOnDelete();
            $t->string('item_key');
            $t->unsignedBigInteger('quantity_milli');
            $t->unsignedBigInteger('credit_minor');
            $t->unsignedBigInteger('refund_due_minor');
            $t->string('credit_reference');
            $t->date('returned_on');
            $t->date('refund_due_on')->nullable();
            $t->text('reason');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('status')->default('posted');
            $t->text('void_reason')->nullable();
            $t->timestamps();
        });
        Schema::create('procurement_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('procurement_id')->constrained()->restrictOnDelete();
            $t->foreignId('fund_id')->constrained('funds')->restrictOnDelete();
            $t->foreignId('invoice_line_id')->constrained('procurement_invoice_lines')->restrictOnDelete();
            $t->foreignId('return_id')->nullable()->constrained('procurement_returns')->restrictOnDelete();
            $t->foreignId('original_payment_id')->nullable()->constrained('procurement_movements')->restrictOnDelete();
            $t->string('kind');
            $t->decimal('amount', 15, 2);
            $t->date('payment_date');
            $t->string('payment_method');
            $t->string('receipt');
            $t->string('reference')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->string('status')->default('posted');
            $t->string('source_type')->nullable();
            $t->unsignedBigInteger('source_id')->nullable();
            $t->uuid('idempotency_key')->unique();
            $t->string('payload_hash', 64);
            $t->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('voided_at')->nullable();
            $t->text('void_reason')->nullable();
            $t->timestamps();
            $t->unique(['source_type', 'source_id']);
        });
        Schema::create('procurement_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('procurement_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('kind');
            $t->unsignedBigInteger('record_id')->nullable();
            $t->string('path');
            $t->string('original_name');
            $t->string('sha256', 64);
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('procurement_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('procurement_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('action');
            $t->json('before_values')->nullable();
            $t->json('after_values')->nullable();
            $t->timestamps();
        });
        Schema::create('procurement_reminders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('procurement_id')->unique()->constrained()->restrictOnDelete();
            $t->boolean('delivery_due')->default(false);
            $t->boolean('invoice_due')->default(false);
            $t->boolean('refund_due')->default(false);
            $t->timestamps();
        });
        Schema::table('project_budget_versions', fn (Blueprint $t) => $t->boolean('legacy_direct_purchase')->default(false));
        foreach (DB::table('projects')->whereNotNull('approved_revision')->get() as $p) {
            DB::table('project_budget_versions')->where('project_id', $p->id)->where('revision', $p->approved_revision)->update(['legacy_direct_purchase' => true]);
        }
        Schema::table('business_budget_items', fn (Blueprint $t) => $t->boolean('legacy_direct_purchase')->default(false));
        foreach (DB::table('business_applications')->whereIn('status', ['approved', 'signed', 'closed'])->get() as $p) {
            DB::table('business_budget_items')->where('business_application_id', $p->id)->where('revision', $p->revision)->update(['legacy_direct_purchase' => true]);
        }
        Schema::table('expenses', function (Blueprint $t) {
            $t->string('purchase_kind')->default('unclassified');
            $t->boolean('legacy_posted')->default(false);
        });
        DB::table('expenses')->whereIn('status', ['approved', 'paid'])->update(['legacy_posted' => true]);
    }

    public function down(): void
    {
        Schema::table('expenses', fn (Blueprint $t) => $t->dropColumn(['purchase_kind', 'legacy_posted']));
        Schema::table('business_budget_items', fn (Blueprint $t) => $t->dropColumn('legacy_direct_purchase'));
        Schema::table('project_budget_versions', fn (Blueprint $t) => $t->dropColumn('legacy_direct_purchase'));
        foreach (['reminders', 'events', 'documents', 'movements', 'returns', 'invoice_lines', 'invoices', 'receipts', 'reviews', 'quotes', 'revisions'] as $name) {
            Schema::dropIfExists('procurement_'.$name);
        }
        Schema::dropIfExists('procurements');
        Schema::dropIfExists('procurement_policies');
        Schema::dropIfExists('procurement_suppliers');
    }
};
