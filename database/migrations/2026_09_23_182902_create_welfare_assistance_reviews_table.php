<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('welfare_assistance_reviews', function (Blueprint $table) {

            $table->id();

            $table->foreignId('assistance_id')
                ->constrained('welfare_assistances')
                ->cascadeOnDelete();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('review_date');

            $table->string('outcome', 40);

            $table->date('next_review_date')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->index([
                'assistance_id',
                'review_date',
            ]);

            $table->index([
                'outcome',
                'next_review_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('welfare_assistance_reviews');
    }
};