<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->string('expense_number', 30)->unique();

            $table->foreignId('fund_id')
                ->constrained('funds')
                ->restrictOnDelete();

            $table->string('category', 50);

            $table->string('description', 255);

            $table->decimal('amount', 15, 2);

            $table->date('expense_date');

            $table->string('payee_name', 200);

            $table->string('payment_method', 30)
                ->default('cash');

            $table->string('transaction_reference', 150)
                ->nullable();

            $table->string('receipt_number', 100)
                ->nullable();

            $table->string('status', 30)
                ->default('pending');

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')
                ->nullable();

            $table->text('purpose_notes')
                ->nullable();

            $table->text('internal_notes')
                ->nullable();

            $table->boolean('has_supporting_document')
                ->default(false);

            $table->timestamps();

            $table->softDeletes();

            $table->index('fund_id');
            $table->index('expense_date');
            $table->index('category');
            $table->index('status');
            $table->index('payee_name');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};