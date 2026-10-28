<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('notification_key', 190);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'notification_key'], 'notification_state_user_key');
            $table->index(['user_id', 'dismissed_at', 'read_at'], 'notification_state_inbox');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_states');
    }
};
