<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_verifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('beneficiary_id')
                ->constrained('beneficiaries')
                ->cascadeOnDelete();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('verification_date')->nullable();

            $table->string('status', 30)->default('pending');

            $table->string('method', 50)->nullable();

            $table->text('findings')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['beneficiary_id', 'status']);
            $table->index('verification_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_verifications');
    }
};