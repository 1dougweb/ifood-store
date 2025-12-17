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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            
            // Notification type: new_order, delayed_order, delivered_order, cancelled_order, etc.
            $table->string('type');
            $table->string('channel')->default('whatsapp'); // whatsapp, email, sms
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->text('message')->nullable();
            $table->text('recipient')->nullable(); // phone number, email, etc.
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->index('restaurant_id');
            $table->index('order_id');
            $table->index('status');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
