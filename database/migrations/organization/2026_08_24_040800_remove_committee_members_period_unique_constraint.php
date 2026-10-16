<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('committee_members', function (Blueprint $table) {
            $table->dropUnique(
                'committee_member_unique_period'
            );
        });

        Schema::table('committee_members', function (Blueprint $table) {
            $table->index([
                'committee_id',
                'member_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('committee_members', function (Blueprint $table) {
            $table->dropIndex([
                'committee_members_committee_id_member_id_status_index'
            ]);
        });

        Schema::table('committee_members', function (Blueprint $table) {
            $table->unique(
                [
                    'committee_id',
                    'member_id',
                    'joined_at'
                ],
                'committee_member_unique_period'
            );
        });
    }
};