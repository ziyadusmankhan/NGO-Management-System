<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('welfare_assistance_payments', function (Blueprint $table) {
            $table->id();

            $table->string('payment_number')->unique();

            $table->foreignId('assistance_id')
                ->constrained('welfare_assistances')
                ->cascadeOnDelete();

            /*
             * Keep the actual fund used for this payment on the payment
             * record. This preserves the historical financial source even
             * if the parent assistance is later changed.
             */
            $table->foreignId('fund_id')
                ->constrained('funds')
                ->restrictOnDelete();

            $table->decimal('amount', 15, 2);

            $table->date('payment_date');

            $table->string('payment_method', 30);

            $table->string('transaction_reference')->nullable();

            $table->string('receipt_number')->nullable();

            $table->string('paid_to')->nullable();

            $table->string('status', 30)->default('posted');

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('assistance_id');
            $table->index('fund_id');
            $table->index('payment_date');
            $table->index('status');
            $table->index('transaction_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('welfare_assistance_payments');
    }
};