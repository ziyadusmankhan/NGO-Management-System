<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_verification_evidence', function (Blueprint $table) {
            $table->id();

            $table->foreignId('verification_id')
                ->constrained('beneficiary_verifications')
                ->cascadeOnDelete();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);

            $table->timestamps();

            $table->index('verification_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_verification_evidence');
    }
};
