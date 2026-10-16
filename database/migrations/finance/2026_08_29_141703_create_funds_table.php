<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funds', function (Blueprint $table) {
            $table->id();

            $table->string('fund_reference', 30)
                ->unique();

            $table->string('name', 150);

            $table->string('code', 50)
                ->nullable()
                ->unique();

            $table->string('type', 30)
                ->default('general');

            $table->string('purpose', 100);

            $table->text('description')->nullable();

            $table->text('usage_rules')->nullable();

            $table->string('responsible_authority', 150)
                ->nullable();

            $table->decimal('opening_balance', 15, 2)
                ->default(0);

            $table->date('opening_balance_date')
                ->nullable();

            $table->string('status', 30)
                ->default('active');

            /*
             * Financial control flags
             */
            $table->boolean('restrict_spending')
                ->default(false);

            $table->boolean('require_approval')
                ->default(false);

            $table->boolean('require_documents')
                ->default(false);

            $table->boolean('monitor_balance')
                ->default(false);

            $table->boolean('record_transactions')
                ->default(true);

            $table->boolean('prevent_negative_balance')
                ->default(true);

            $table->boolean('maintain_audit_history')
                ->default(true);

            $table->boolean('protect_restricted_designation')
                ->default(true);

            $table->text('internal_notes')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index('type');
            $table->index('purpose');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funds');
    }
};