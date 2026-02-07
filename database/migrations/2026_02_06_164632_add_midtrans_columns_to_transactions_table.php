<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('snap_token')->nullable()->after('payment_method');
            $table->string('gateway_status')->nullable()->after('status');
            $table->string('payment_channel')->nullable()->after('gateway_status');
            $table->timestamp('paid_at')->nullable()->after('transaction_date');
        });

        // Update status enum
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('pending', 'completed', 'failed', 'cancelled', 'expired') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['snap_token', 'gateway_status', 'payment_channel', 'paid_at']);
        });

        // Revert status enum (Warning: data loss or error if pending exists)
        // DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('completed', 'cancelled', 'failed') NOT NULL");
    }
};
