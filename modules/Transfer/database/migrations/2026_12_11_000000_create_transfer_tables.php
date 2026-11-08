<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Import po častiach: stav sa ukladá, aby sa zmestil do časového limitu webhostingu.
        Schema::create('data_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entity', 32);
            $table->string('file_path');
            $table->string('file_name');
            $table->json('mapping')->nullable();
            $table->string('status', 16)->default('mapping');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();
        });

        // Uložené šablóny exportu (entita, formát, stĺpce, filtre) a voliteľné posielanie e-mailom.
        Schema::create('export_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->json('config');
            // none, daily, weekly, monthly
            $table->string('schedule', 16)->default('none');
            $table->string('email')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_templates');
        Schema::dropIfExists('data_imports');
    }
};
