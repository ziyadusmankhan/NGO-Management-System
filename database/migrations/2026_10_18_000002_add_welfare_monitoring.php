<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('welfare_assistances', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('closure_reason')->nullable();
            $table->index(['status', 'end_date'], 'assistance_monitoring_end');
        });
        Schema::table('welfare_assistance_reviews', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable()->unique();
            $table->text('follow_up_action')->nullable();
        });
        Schema::create('welfare_assistance_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistance_id')->constrained('welfare_assistances')->cascadeOnDelete();
            $table->string('kind', 30);
            $table->date('due_date');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['assistance_id', 'kind', 'due_date'], 'assistance_reminder_unique');
            $table->index(['resolved_at', 'due_date'], 'assistance_reminder_due');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('welfare_assistance_reminders');
        Schema::table('welfare_assistance_reviews', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['idempotency_key', 'follow_up_action']);
        });
        Schema::table('welfare_assistances', function (Blueprint $table) {
            $table->dropForeign(['closed_by']);
            $table->dropIndex('assistance_monitoring_end');
            $table->dropColumn(['closed_at', 'closed_by', 'closure_reason']);
        });
    }
};
