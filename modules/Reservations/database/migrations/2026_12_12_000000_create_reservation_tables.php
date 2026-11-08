<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_services', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            // Prestávka po termíne (upratanie, príprava).
            $table->unsignedSmallInteger('buffer_minutes')->default(0);
            // Koľko zákazníkov naraz (napr. skupinová lekcia).
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->unsignedInteger('price')->default(0);
            $table->char('currency', 3);
            // on_site, deposit, prepay
            $table->string('payment_mode', 16)->default('on_site');
            $table->unsignedInteger('deposit')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Zamestnanci alebo miestnosti s týždenným rozvrhom; prestávky sú medzery v rozvrhu.
        Schema::create('booking_resources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('type', 16)->default('staff');
            $table->json('schedule')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('booking_resource_service', function (Blueprint $table) {
            $table->foreignId('booking_resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_service_id')->constrained()->cascadeOnDelete();
            $table->primary(['booking_resource_id', 'booking_service_id']);
        });

        // Sviatky a dovolenky (bez zdroja = zatvorené pre všetkých).
        Schema::create('booking_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_resource_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();
            $table->string('token', 48)->unique();
            $table->foreignId('booking_service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_resource_id')->nullable()->constrained()->nullOnDelete();
            // Rezervácia jedinečného kusu (napr. zviera) namiesto služby.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            // pending, confirmed, cancelled, completed
            $table->string('status', 16)->default('confirmed');
            $table->string('payment_mode', 16)->default('on_site');
            $table->boolean('is_paid')->default(false);
            $table->unsignedInteger('amount_due')->default(0);
            $table->char('currency', 3);
            $table->string('customer_name', 120);
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->text('note')->nullable();
            $table->string('locale', 5)->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['starts_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('booking_closures');
        Schema::dropIfExists('booking_resource_service');
        Schema::dropIfExists('booking_resources');
        Schema::dropIfExists('booking_services');
    }
};
