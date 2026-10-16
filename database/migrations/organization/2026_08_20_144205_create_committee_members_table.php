<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('committee_id')
                ->constrained('committees')
                ->cascadeOnDelete();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            $table->string('position', 100)
                ->default('Member');

            $table->date('joined_at')->nullable();

            $table->date('ended_at')->nullable();

            $table->string('status', 30)
                ->default('active');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('committee_id');
            $table->index('member_id');
            $table->index('status');

            $table->unique(
                ['committee_id', 'member_id', 'joined_at'],
                'committee_member_unique_period'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_members');
    }
};