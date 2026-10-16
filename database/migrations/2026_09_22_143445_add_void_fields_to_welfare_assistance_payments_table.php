<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('welfare_assistance_payments', function (Blueprint $table) {
            $table->foreignId('voided_by')
                ->nullable()
                ->after('recorded_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('voided_at')
                ->nullable()
                ->after('voided_by');

            $table->text('void_reason')
                ->nullable()
                ->after('voided_at');
        });
    }

    public function down(): void
    {
        Schema::table('welfare_assistance_payments', function (Blueprint $table) {
            $table->dropForeign([
                'voided_by',
            ]);

            $table->dropColumn([
                'voided_by',
                'voided_at',
                'void_reason',
            ]);
        });
    }
};