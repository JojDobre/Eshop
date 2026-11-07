<?php

namespace Modules\Catalog\Filament\Resources\Products\Pages;

use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;
use App\Filament\Concerns\FillsContentTranslations;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Catalog\Filament\Resources\Products\ProductResource;
use Modules\Catalog\Models\Product;

class EditProduct extends EditRecord
{
    use FillsContentTranslations, HandlesProductPrice;

    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Product $product */
        $product = $this->getRecord();
        $currency = app(CurrencyContext::class)->current();
        $price = $product->priceIn($currency);

        $data = $this->fillContentTranslations($data);
        $data['price'] = $price === null ? null : Price::toDecimal($price, $currency);

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
            Action::make('view')->label(__('catalog::admin.product.view_in_shop'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn () => $product->url(), shouldOpenInNewTab: true)
                ->visible(fn () => $product->is_visible),
            DeleteAction::make(),
        ];
    }
}
