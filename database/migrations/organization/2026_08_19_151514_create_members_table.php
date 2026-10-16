<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            // Optional link to a system login account.
            // Not every member needs a login.
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('member_number', 30)->unique();

            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();

            $table->string('father_name', 150)->nullable();

            $table->string('cnic', 30)
                ->nullable()
                ->unique();

            $table->date('date_of_birth')->nullable();

            $table->string('gender', 30)->nullable();

            $table->string('phone', 30)->nullable();
            $table->string('alternate_phone', 30)->nullable();

            $table->string('email', 150)->nullable();

            $table->text('address')->nullable();

            $table->date('joining_date')->nullable();

            $table->string('status', 30)
                ->default('active');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('phone');
            $table->index('joining_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};