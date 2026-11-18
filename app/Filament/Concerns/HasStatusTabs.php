<?php

namespace App\Filament\Concerns;

use BackedEnum;
use Closure;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Záložky stavov nad tabuľkou (Všetky / Nové / Vybavené…) s počtami – rovnaký vzhľad vo všetkých zoznamoch.
 * Stránka zoznamu definuje statusTabs(): kľúč => [popis, úprava dotazu].
 */
trait HasStatusTabs
{
    /**
     * @return array<string, array{0: string, 1: Closure(Builder): Builder}>
     */
    abstract protected function statusTabs(): array;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $base = fn (): Builder => static::getResource()::getEloquentQuery();
        $tabs = ['all' => Tab::make(__('admin.tabs.all'))->badge($base()->count())];

        foreach ($this->statusTabs() as $key => [$label, $query]) {
            $tabs[$key] = Tab::make($label)->badge($query($base())->count())->modifyQueryUsing($query);
        }

        return $tabs;
    }

    /**
     * Záložky podľa hodnôt stĺpca (napr. stav objednávky z enumu alebo konštanty modelu).
     *
     * @param  iterable<BackedEnum|string>  $values
     * @param  (Closure(string): string)|null  $label
     * @return array<string, array{0: string, 1: Closure(Builder): Builder}>
     */
    protected static function columnTabs(string $column, iterable $values, ?Closure $label = null): array
    {
        $tabs = [];

        foreach ($values as $value) {
            $key = $value instanceof BackedEnum ? (string) $value->value : $value;
            $text = $label !== null ? $label($key) : ($value instanceof HasLabel ? (string) $value->getLabel() : $key);
            $tabs[$key] = [$text, fn (Builder $query) => $query->where($column, $key)];
        }

        return $tabs;
    }
}
