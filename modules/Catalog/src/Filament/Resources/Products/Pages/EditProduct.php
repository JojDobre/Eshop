<?php

namespace Modules\Catalog\Filament\Resources\Products\Pages;

use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;
use App\Filament\Concerns\FillsContentTranslations;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Filament\Resources\Products\ProductResource;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\StockMovement;
use Modules\Catalog\StockService;

class EditProduct extends EditRecord
{
    use FillsContentTranslations, HandlesProductPrice;

    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Product $product */
        $product = $this->getRecord();
        $currency = app(CurrencyContext::class)->current();
        $price = $product->enteredPriceIn($currency);
        $compareAt = $product->enteredCompareAtPriceIn($currency);

        $data = $this->fillContentTranslations($data);
        $data['price'] = $price === null ? null : Price::toDecimal($price, $currency);
        $data['compare_at_price'] = $compareAt === null ? null : Price::toDecimal($compareAt, $currency);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractPrice($data);
    }

    protected function afterSave(): void
    {
        /** @var Product $product */
        $product = $this->getRecord();
        $this->savePrice($product);
    }

    protected function getHeaderActions(): array
    {
        /** @var Product $product */
        $product = $this->getRecord();

        return [
            Action::make('adjustStock')->label(__('catalog::admin.stock.adjust'))
                ->icon(Heroicon::OutlinedArrowsUpDown)->color('gray')
                ->visible(fn () => $product->track_stock)
                ->schema([
                    TextInput::make('quantity')->label(__('catalog::admin.stock.adjust_quantity'))
                        ->helperText(__('catalog::admin.stock.adjust_quantity_help'))->integer()->required()->notIn([0]),
                    TextInput::make('note')->label(__('catalog::admin.stock.note'))->maxLength(255),
                ])
                ->action(function (array $data, StockService $stock) use ($product): void {
                    DB::transaction(function () use ($data, $stock, $product): void {
                        $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
                        $stock->adjust($locked, (int) $data['quantity'], StockMovement::MANUAL, null, $data['note'] ?? null);
                    });

                    Notification::make()->success()->title(__('catalog::admin.stock.adjusted'))->send();
                    $this->redirect(static::getUrl(['record' => $product]));
                }),
            Action::make('view')->label(__('catalog::admin.product.view_in_shop'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn () => $product->url(), shouldOpenInNewTab: true)
                ->visible(fn () => $product->is_visible),
            DeleteAction::make(),
        ];
    }
}
