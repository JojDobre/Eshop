<?php

namespace App\Filament\Forms;

use Closure;
use Filament\Forms\Components\Field;

/**
 * Pole s mriežkou obrázkov z knižnice (stránkovanie, hľadanie, vybrané hore). Stav je zoznam ID súborov.
 */
class MediaLibraryField extends Field
{
    protected string $view = 'filament.forms.media-picker';

    /** @var array<int, array{id: int, url: string, name: string}>|Closure */
    protected array|Closure $items = [];

    protected bool|Closure $multiple = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);
    }

    /**
     * @param  array<int, array{id: int, url: string, name: string}>|Closure  $items
     */
    public function items(array|Closure $items): static
    {
        $this->items = $items;

        return $this;
    }

    public function multiple(bool|Closure $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    /**
     * @return array<int, array{id: int, url: string, name: string}>
     */
    public function getItems(): array
    {
        return array_values((array) $this->evaluate($this->items));
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->multiple);
    }
}
