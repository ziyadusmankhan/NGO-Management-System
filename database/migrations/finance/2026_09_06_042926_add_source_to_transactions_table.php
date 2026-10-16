<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('source_type', 100)
                ->nullable()
                ->after('transaction_number');

            $table->unsignedBigInteger('source_id')
                ->nullable()
                ->after('source_type');

            $table->index([
                'source_type',
                'source_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex([
                'transactions_source_type_source_id_index',
            ]);

            $table->dropColumn([
                'source_type',
                'source_id',
            ]);
        });
    }
};