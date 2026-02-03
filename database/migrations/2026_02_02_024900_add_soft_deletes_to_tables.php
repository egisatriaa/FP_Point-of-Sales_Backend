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
        // 1. Add deleted_at columns
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes(); // Adds deleted_at
        });

        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 2. Migrate existing data (Legacy is_deleted -> Soft Delete)
        // Use Query Builder for cross-database compatibility (SQLite vs MySQL)
        DB::table('users')->where('is_deleted', 1)->update(['deleted_at' => now()]);

        // 3. Drop legacy column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Restore legacy column
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(false)->after('is_active');
        });

        // 2. Restore data (Soft Delete -> Legacy is_deleted)
        DB::table('users')->whereNotNull('deleted_at')->update(['is_deleted' => 1]);

        // 3. Drop deleted_at columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
