<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('welfare_applications', function (Blueprint $table) {
            $table->boolean('is_test')->default(false)->index();
        });

        // One-time classification; workflow guards use the flag, never record IDs.
        DB::table('welfare_applications')
            ->whereIn('application_number', ['APP-00005', 'APP-00010'])
            ->update(['is_test' => true]);
    }

    public function down(): void
    {
        Schema::table('welfare_applications', function (Blueprint $table) {
            $table->dropIndex(['is_test']);
            $table->dropColumn('is_test');
        });
    }
};
