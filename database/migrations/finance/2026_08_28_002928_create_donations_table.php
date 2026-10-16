<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();

            $table->string('donation_number', 30)->unique();

            $table->string('donor_type', 30)
                ->default('individual');

            $table->string('donor_name', 200);

            $table->string('phone', 30)->nullable();

            $table->string('email', 150)->nullable();

            $table->decimal('amount', 15, 2);

            $table->date('donation_date');

            $table->string('payment_method', 30)
                ->default('cash');

            $table->string('transaction_reference', 150)
                ->nullable();

            $table->string('receipt_number', 50)
                ->nullable()
                ->unique();

            $table->string('designation', 30)
                ->default('unrestricted');

            $table->string('purpose', 50);

            /*
             * Temporary fund classification.
             *
             * This will later become a foreign key when
             * the Funds module is implemented.
             */
            $table->string('fund_reference', 100)
                ->nullable();

            $table->text('purpose_notes')->nullable();

            $table->text('internal_notes')->nullable();

            $table->string('status', 30)
                ->default('received');

            /*
             * The user who recorded the donation.
             */
            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->softDeletes();

            $table->index('donation_date');
            $table->index('status');
            $table->index('payment_method');
            $table->index('designation');
            $table->index('purpose');
            $table->index('donor_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};