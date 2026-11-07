<?php

namespace App\Core\Content;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Modely s preložiteľným obsahom (pre hromadný preklad a prehľad chýbajúcich prekladov).
 * Moduly ich registrujú vo svojom Service Provideri.
 */
class TranslatableModels
{
    /** @var array<class-string<Model>, Closure(): string> */
    private array $models = [];

    /**
     * @param  class-string<Model>  $model
     * @param  Closure(): string  $label  Názov skupiny v administrácii (napr. „Produkty“).
     */
    public function register(string $model, Closure $label): void
    {
        $this->models[$model] = $label;
    }

    /**
     * @return array<class-string<Model>, string>
     */
    public function all(): array
    {
        return array_map(fn (Closure $label) => $label(), $this->models);
    }
}
