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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->string('ifood_order_id')->unique();
            $table->string('short_reference')->nullable();
            $table->string('display_id')->nullable();
            
            // Order status
            $table->string('status'); // PLACED, CONFIRMED, DISPATCHED, DELIVERED, CANCELLED, etc.
            $table->string('sub_status')->nullable();
            
            // Customer info
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_delivery_address')->nullable();
            
            // Financial info
            $table->decimal('total_amount', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('currency', 3)->default('BRL');
            
            // Order details
            $table->integer('items_count')->default(0);
            $table->text('notes')->nullable();
            $table->json('payment_methods')->nullable();
            $table->json('delivery_method')->nullable();
            
            // Timestamps
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expected_delivery_at')->nullable();
            
            // Additional data from iFood
            $table->json('ifood_data')->nullable();
            
            $table->timestamps();
            
            $table->index('restaurant_id');
            $table->index('status');
            $table->index('placed_at');
            $table->index(['restaurant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
