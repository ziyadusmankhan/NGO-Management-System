<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $t) {
            $t->id();
            $t->uuid('event_uuid')->unique();
            $t->string('module', 40);
            $t->string('action', 100);
            $t->string('outcome', 20);
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->string('actor_name')->nullable();
            $t->string('actor_kind', 20);
            $t->string('subject_type', 120)->nullable();
            $t->string('subject_id', 100)->nullable();
            $t->string('subject_reference', 150)->nullable();
            $t->timestamp('occurred_at')->nullable();
            $t->timestamp('captured_at');
            $t->boolean('historical')->default(false);
            $t->string('source_key', 190)->nullable()->unique();
            $t->uuid('correlation_id')->nullable()->index();
            $t->string('summary', 500);
            $t->text('reason')->nullable();
            $t->json('before_values')->nullable();
            $t->json('after_values')->nullable();
            $t->json('context')->nullable();
            $t->index(['occurred_at', 'id']);
            $t->index(['module', 'occurred_at']);
            $t->index(['actor_id', 'occurred_at']);
            $t->index(['outcome', 'occurred_at']);
            $t->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
