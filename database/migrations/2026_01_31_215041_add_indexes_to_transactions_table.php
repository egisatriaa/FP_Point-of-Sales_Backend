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
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['user_id', 'transaction_date']); // Optimasi sort & filter Kasir
            $table->index('transaction_date');              // Optimasi report range date
            $table->index('status');                        // Optimasi filter status completed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'transaction_date']);
            $table->dropIndex(['transaction_date']);
            $table->dropIndex(['status']);
        });
    }
};
