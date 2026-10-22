<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('welfare_assistance_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistance_id')->constrained('welfare_assistances')->restrictOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('changed_at');
            $table->text('reason')->nullable();
            $table->index(['assistance_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('welfare_assistance_status_changes');
    }
};
