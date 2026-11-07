<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // S ktorými verziami obchodných podmienok a zásad ochrany údajov zákazník súhlasil.
        Schema::table('orders', function (Blueprint $table) {
            $table->json('legal_consents')->nullable()->after('terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('legal_consents');
        });
    }
};
