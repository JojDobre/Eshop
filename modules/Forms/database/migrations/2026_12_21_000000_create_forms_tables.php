<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Forms\DefaultForm;

return new class extends Migration
{
    public function up(): void
    {
        // Formuláre: názov, polia a texty sú preložiteľné (JSON podľa jazyka).
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('fields')->nullable();
            $table->json('submit_label')->nullable();
            $table->json('success_message')->nullable();
            $table->string('recipient')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->json('data');
            $table->string('email')->nullable();
            $table->string('locale', 8)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        // Pri aktualizácii existujúceho obchodu: formulár „Kontakt“ a jeho blok na stránke Kontakt.
        if (DB::table('users')->exists()) {
            DefaultForm::create();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('forms');
    }
};
