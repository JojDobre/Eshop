<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            // Výsledok vybavenia: oprava, výmena, zľava, vrátenie peňazí, zamietnutie.
            $table->string('outcome', 16)->nullable()->after('resolution');
            $table->unsignedBigInteger('refund_amount')->nullable()->after('outcome');
            // Dobropis z modulu dokladov (bez cudzieho kľúča – modul dokladov je voliteľný).
            $table->unsignedBigInteger('credit_note_id')->nullable()->after('refund_amount');
            $table->timestamp('deadline_warned_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('return_requests', fn (Blueprint $table) => $table->dropColumn(['outcome', 'refund_amount', 'credit_note_id', 'deadline_warned_at']));
    }
};
