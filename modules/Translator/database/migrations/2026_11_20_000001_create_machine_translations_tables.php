<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Strojové preklady čakajúce na kontrolu (záznam + jazyk).
        Schema::create('machine_translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('locale', 5);
            $table->unsignedInteger('characters')->default(0);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'locale'], 'machine_translations_unique');
        });

        // Front hromadného prekladu (spracúva sa po dávkach plánovačom alebo na stránke v administrácii).
        Schema::create('translation_queue', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('source_locale', 5);
            $table->string('target_locale', 5);
            $table->string('status', 16)->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_queue');
        Schema::dropIfExists('machine_translations');
    }
};
