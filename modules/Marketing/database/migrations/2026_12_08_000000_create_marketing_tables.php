<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name', 120)->nullable();
            $table->string('locale', 5)->nullable();
            // pending (čaká na potvrdenie), subscribed, unsubscribed
            $table->string('status', 16)->default('pending');
            // Tajný kľúč na potvrdenie a odhlásenie z e-mailu.
            $table->string('token', 64)->unique();
            // Odkiaľ sa prihlásil (päta, pokladňa, administrácia) a čo odsúhlasil – dôkaz súhlasu.
            $table->string('source', 32)->nullable();
            $table->string('consent_text', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        // Opustené košíky: e-mail z pokladne a čas pripomienky.
        Schema::table('carts', function (Blueprint $table) {
            $table->string('email')->nullable();
            $table->string('locale', 5)->nullable();
            $table->timestamp('reminded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('carts', fn (Blueprint $table) => $table->dropColumn(['email', 'locale', 'reminded_at']));
        Schema::dropIfExists('newsletter_subscribers');
    }
};
