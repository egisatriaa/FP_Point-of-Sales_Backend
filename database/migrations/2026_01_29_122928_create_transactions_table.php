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
            $table->id();

            $table->string('transaction_code')->unique();
            $table->dateTime('transaction_date');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('payment_amount', 12, 2);
            $table->decimal('change_amount', 12, 2);
            $table->enum('status', ['completed', 'cancelled', 'failed']);
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('created_at')->useCurrent();
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
