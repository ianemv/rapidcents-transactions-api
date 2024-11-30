<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('masked_card_number');
            $table->string('card_hash');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('customer_email');
            $table->enum('status', ['approved', 'declined', 'nsf', 'pending']);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('card_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
