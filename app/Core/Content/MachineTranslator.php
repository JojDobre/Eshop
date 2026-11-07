<?php

namespace App\Core\Content;

use Illuminate\Database\Eloquent\Model;

/**
 * Strojový preklad obsahu (implementuje modul Prekladač, napr. cez DeepL alebo Google).
 * Ak modul nie je zapnutý, rozhranie nie je v kontajneri a tlačidlá prekladu sa nezobrazia.
 */
interface MachineTranslator
{
    /** Je zadaný poskytovateľ a kľúč? */
    public function isConfigured(): bool;

    /**
     * Preloží preložiteľné stĺpce záznamu z jedného jazyka do druhého, uloží ich a označí „na kontrolu“.
     * Vráti počet preložených znakov.
     */
    public function translateModel(Model $record, string $from, string $to): int;

    /** Čaká strojový preklad v jazyku na kontrolu? */
    public function needsReview(Model $record, string $locale): bool;

    /** Preklad v jazyku je skontrolovaný. */
    public function approve(Model $record, string $locale): void;
}
