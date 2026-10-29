<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('other');
            $table->string('priority')->default('medium');
            $table->string('status')->default('pending');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('committee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->date('starts_on')->nullable();
            $table->date('due_on')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('expected_outcome')->nullable();
            $table->text('completion_notes')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'due_on']);
            $table->index(['assigned_user_id', 'status']);
        });

        Schema::create('operation_task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('operation_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('operation_meetings', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->string('title');
            $table->string('type')->default('general');
            $table->string('status')->default('scheduled');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('chair_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('committee_id')->nullable()->constrained()->nullOnDelete();
            $table->date('meeting_date');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('location')->nullable();
            $table->text('agenda');
            $table->text('minutes')->nullable();
            $table->text('decisions')->nullable();
            $table->unsignedInteger('quorum_required')->default(1);
            $table->timestamp('minutes_recorded_at')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'meeting_date']);
        });

        Schema::create('operation_meeting_attendees', function (Blueprint $table) {
            $table->foreignId('meeting_id')->constrained('operation_meetings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('response')->default('invited');
            $table->boolean('attended')->default(false);
            $table->timestamps();
            $table->primary(['meeting_id', 'user_id']);
        });

        Schema::table('operation_tasks', function (Blueprint $table) {
            $table->foreignId('source_meeting_id')->nullable()->after('project_id')->constrained('operation_meetings')->nullOnDelete();
        });

        Schema::create('report_runs', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->string('type');
            $table->string('title');
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->json('filters');
            $table->json('snapshot');
            $table->foreignId('generated_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('generated_at');
            $table->timestamps();
            $table->index(['type', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_runs');
        Schema::table('operation_tasks', fn (Blueprint $table) => $table->dropConstrainedForeignId('source_meeting_id'));
        Schema::dropIfExists('operation_meeting_attendees');
        Schema::dropIfExists('operation_meetings');
        Schema::dropIfExists('operation_task_comments');
        Schema::dropIfExists('operation_tasks');
    }
};
