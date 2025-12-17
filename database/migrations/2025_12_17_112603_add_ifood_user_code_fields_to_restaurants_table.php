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
        Schema::table('restaurants', function (Blueprint $table) {
            $table->text('ifood_authorization_code_verifier')->nullable()->after('ifood_merchant_id');
            $table->timestamp('ifood_user_code_expires_at')->nullable()->after('ifood_authorization_code_verifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['ifood_authorization_code_verifier', 'ifood_user_code_expires_at']);
        });
    }
};
