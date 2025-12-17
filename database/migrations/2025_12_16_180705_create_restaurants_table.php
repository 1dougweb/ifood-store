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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('cnpj', 18)->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp_number')->nullable();
            
            // iFood OAuth credentials
            $table->string('ifood_client_id')->nullable();
            $table->text('ifood_client_secret')->nullable();
            $table->text('ifood_access_token')->nullable();
            $table->text('ifood_refresh_token')->nullable();
            $table->timestamp('ifood_token_expires_at')->nullable();
            $table->string('ifood_merchant_id')->nullable();
            
            // Notification settings (JSON)
            $table->json('notification_settings')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('ifood_merchant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
