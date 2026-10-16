<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committees', function (Blueprint $table) {
            $table->id();

            $table->string('committee_number', 30)->unique();

            $table->string('name', 150);

            $table->text('description')->nullable();

            $table->string('type', 50)
                ->default('committee');

            $table->string('status', 30)
                ->default('active');

            $table->date('established_at')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committees');
    }
};