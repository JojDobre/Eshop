<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('customer_group_id')->nullable()->after('id')->constrained()->nullOnDelete();
            // Blokovaný zákazník sa nemôže prihlásiť ani objednať (ani bez prihlásenia s rovnakým e-mailom).
            $table->boolean('is_blocked')->default(false);
            $table->string('blocked_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_group_id');
            $table->dropColumn(['is_blocked', 'blocked_reason']);
        });
    }
};
