<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vystavený doklad sa už nikdy nemení: všetky údaje sú kópiou z času vystavenia.
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            // proforma, invoice, credit_note
            $table->string('type', 32);
            $table->string('number', 32)->unique();
            $table->foreignId('related_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->date('issued_at');
            $table->date('due_at')->nullable();
            // Dátum dodania (zdaniteľného plnenia).
            $table->date('supplied_at')->nullable();
            $table->char('currency', 3);
            $table->boolean('vat_payer');
            $table->string('variable_symbol', 10)->nullable();
            $table->string('payment_method')->nullable();
            $table->json('supplier');
            $table->json('customer');
            $table->json('lines');
            $table->json('vat_breakdown');
            $table->bigInteger('total');
            $table->string('locale', 8);
            $table->text('note')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
