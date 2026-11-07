<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Odstúpenia od zmluvy a reklamácie.
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('number', 32)->unique();
            $table->string('type', 16);
            $table->string('status', 16)->default('new');
            // Vrátené položky: [{order_item_id, name, quantity}]
            $table->json('items');
            $table->text('reason')->nullable();
            // Pri reklamácii: požadovaný spôsob vybavenia (oprava, výmena, vrátenie peňazí, zľava).
            $table->string('preferred_resolution', 16)->nullable();
            // Účet na vrátenie peňazí.
            $table->string('refund_account', 64)->nullable();
            // Zákonná lehota (vrátenie peňazí pri odstúpení, vybavenie reklamácie).
            $table->timestamp('deadline_at');
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'deadline_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
