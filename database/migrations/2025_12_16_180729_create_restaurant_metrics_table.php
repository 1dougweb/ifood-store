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
        Schema::create('restaurant_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            
            // Period
            $table->date('period_date');
            $table->string('period_type')->default('daily'); // daily, weekly, monthly
            
            // Order metrics
            $table->integer('total_orders')->default(0);
            $table->integer('placed_orders')->default(0);
            $table->integer('confirmed_orders')->default(0);
            $table->integer('delivered_orders')->default(0);
            $table->integer('cancelled_orders')->default(0);
            $table->integer('delayed_orders')->default(0);
            
            // Financial metrics
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('average_order_value', 10, 2)->default(0);
            $table->decimal('total_delivery_fees', 10, 2)->default(0);
            $table->decimal('total_discounts', 10, 2)->default(0);
            
            // Time metrics
            $table->decimal('average_preparation_time', 8, 2)->nullable(); // in minutes
            $table->decimal('average_delivery_time', 8, 2)->nullable(); // in minutes
            $table->decimal('average_total_time', 8, 2)->nullable(); // in minutes
            
            // Additional aggregated data
            $table->json('additional_data')->nullable();
            
            $table->timestamps();
            
            $table->unique(['restaurant_id', 'period_date', 'period_type']);
            $table->index('restaurant_id');
            $table->index('period_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_metrics');
    }
};
