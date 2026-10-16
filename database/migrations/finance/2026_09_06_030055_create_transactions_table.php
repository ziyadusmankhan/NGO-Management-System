<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_number', 30)->unique();

            $table->foreignId('fund_id')
                ->constrained('funds')
                ->restrictOnDelete();

            $table->string('type', 30);

            $table->string('reference', 100)->nullable();

            $table->string('description', 255);

            $table->decimal('amount', 15, 2);

            $table->string('direction', 10);

            $table->date('transaction_date');

            $table->string('status', 30)
                ->default('posted');

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index('fund_id');
            $table->index('transaction_date');
            $table->index('type');
            $table->index('direction');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};