<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('welfare_assistances', function (Blueprint $table) {
            $table->id();

            $table->string('assistance_number')->unique();

            $table->foreignId('beneficiary_id')
                ->constrained('beneficiaries')
                ->cascadeOnDelete();

            $table->foreignId('fund_id')
                ->nullable()
                ->constrained('funds')
                ->nullOnDelete();

            $table->string('assistance_type', 50);

            $table->decimal('amount', 15, 2)->nullable();

            $table->string('frequency', 30)->nullable();

            $table->date('start_date')->nullable();

            $table->date('end_date')->nullable();

            $table->string('status', 30)->default('pending');

            $table->dateTime('requested_at')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('approved_at')->nullable();

            $table->date('next_review_date')->nullable();

            $table->text('purpose_notes')->nullable();

            $table->text('internal_notes')->nullable();

            $table->boolean('has_supporting_document')->default(false);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index('beneficiary_id');
            $table->index('fund_id');
            $table->index('assistance_type');
            $table->index('status');
            $table->index('next_review_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('welfare_assistances');
    }
};