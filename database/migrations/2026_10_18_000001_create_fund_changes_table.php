<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_id')->constrained('funds')->restrictOnDelete();
            $table->string('action', 30);
            $table->json('before_values');
            $table->json('after_values');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->index(['fund_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_changes');
    }
};
