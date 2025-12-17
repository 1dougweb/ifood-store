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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('restaurant_name')->nullable();
            $table->text('message')->nullable();
            $table->string('source')->default('landing_page'); // landing_page, referral, etc.
            $table->boolean('contacted')->default(false);
            $table->timestamp('contacted_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // IP, user agent, etc.
            $table->timestamps();
            
            $table->index('email');
            $table->index('contacted');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
