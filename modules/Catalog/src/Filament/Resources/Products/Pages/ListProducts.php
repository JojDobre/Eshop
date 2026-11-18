<?php

namespace Modules\Catalog\Filament\Resources\Products\Pages;

use App\Settings\CatalogSettings;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Modules\Catalog\Filament\Resources\Products\ProductResource;
use Modules\Catalog\Models\Product;
use Throwable;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    /** @var array<string, int>|null Počty produktov v záložkách (počítajú sa raz za požiadavku). */
    private ?array $counts = null;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('catalog::admin.product.new'))->icon('heroicon-m-plus')];
    }

    /** Prehľad pod nadpisom: počet produktov, koľko je v obchode a koľko je vypredaných. */
    public function getSubheading(): ?string
    {
        $counts = $this->counts();

        return collect([
            trans_choice('catalog::admin.product.summary_total', $counts['all'], ['count' => $counts['all']]),
            __('catalog::admin.product.summary_visible', ['count' => $counts['visible']]),
            $counts['sold_out'] > 0 ? __('catalog::admin.product.summary_sold_out', ['count' => $counts['sold_out']]) : null,
        ])->filter()->implode(' · ');
    }

    /**
     * Záložky nad tabuľkou: všetky, v obchode, dochádza, vypredané, skryté – s počtami.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $t = fn (string $key) => __('catalog::admin.product.tabs.'.$key);
        $counts = $this->counts();

        return [
            'all' => Tab::make($t('all'))->badge($counts['all']),
            'visible' => Tab::make($t('visible'))->badge($counts['visible'])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_visible', true)),
            'low' => Tab::make($t('low'))->badge($counts['low'])
                ->modifyQueryUsing(fn (Builder $query) => self::lowStock($query)),
            'sold_out' => Tab::make($t('sold_out'))->badge($counts['sold_out'])
                ->modifyQueryUsing(fn (Builder $query) => self::soldOut($query)),
            'hidden' => Tab::make($t('hidden'))->badge($counts['hidden'])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_visible', false)),
        ];
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return $this->counts ??= [
            'all' => Product::query()->count(),
            'visible' => Product::query()->where('is_visible', true)->count(),
            'low' => self::lowStock(Product::query())->count(),
            'sold_out' => self::soldOut(Product::query())->count(),
            'hidden' => Product::query()->where('is_visible', false)->count(),
        ];
    }

    /** Sledovaný sklad, ešte nie vypredaný, ale na hranici upozornenia (vlastnej alebo z nastavení katalógu). */
    private static function lowStock(Builder $query): Builder
    {
        try {
            $default = app(CatalogSettings::class)->low_stock_threshold;
        } catch (Throwable) {
            $default = 3;
        }

        return $query->where('track_stock', true)->where('stock_quantity', '>', 0)
            ->whereRaw('stock_quantity <= COALESCE(low_stock_threshold, ?)', [$default]);
    }

    private static function soldOut(Builder $query): Builder
    {
        return $query->where('track_stock', true)->where('stock_quantity', '<=', 0);
    }
}
