<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('welfare_assistances', function (Blueprint $table) {
            $table->string('assistance_type', 60)->change();
        });
    }

    public function down(): void
    {
        $length = DB::connection()->getDriverName() === 'mysql' ? 'CHAR_LENGTH' : 'LENGTH';
        if (DB::table('welfare_assistances')->whereRaw($length.'(assistance_type) > 50')->exists()) {
            throw new RuntimeException('Shorten assistance types exceeding 50 characters before rolling back; no data was truncated.');
        }

        Schema::table('welfare_assistances', function (Blueprint $table) {
            $table->string('assistance_type', 50)->change();
        });
    }
};
