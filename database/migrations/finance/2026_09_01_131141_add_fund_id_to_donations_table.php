<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->foreignId('fund_id')
                ->nullable()
                ->after('fund_reference')
                ->constrained('funds')
                ->nullOnDelete();

            $table->index('fund_id');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign([
                'fund_id',
            ]);

            $table->dropIndex([
                'donations_fund_id_index',
            ]);

            $table->dropColumn('fund_id');
        });
    }
};