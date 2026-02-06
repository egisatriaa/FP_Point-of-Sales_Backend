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
        // Drop existing flat carts table
        Schema::dropIfExists('carts');

        // Create Header Table: carts
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            
            // Status: active, completed, abandoned
            // Default: active
            $table->string('status')->default('active')->index();

            // Index for fast lookup of active cart per user
            $table->index(['user_id', 'status']);
            
            $table->timestamps();
        });

        // Create Detail Table: cart_items
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cart_id')
                ->constrained('carts')
                ->cascadeOnDelete(); // Delete cart -> delete items
            
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete(); // Prevent deleting product if in cart? Or null? detailed logic might vary but restrict is safe.
            
            $table->integer('quantity');
            
            // Snapshot price to avoid price changes affecting cart history if needed later
            $table->decimal('price', 12, 2);

            $table->timestamps();

            // Unique constraint: One product per cart
            $table->unique(['cart_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');

        // Recreate old flat table (approximate structure based on backup)
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantity');
            $table->timestamps();
        });
    }
};
