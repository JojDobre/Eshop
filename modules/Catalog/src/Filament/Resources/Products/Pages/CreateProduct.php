<?php

namespace Modules\Catalog\Filament\Resources\Products\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Catalog\Filament\Resources\Products\ProductResource;
use Modules\Catalog\Models\Product;

class CreateProduct extends CreateRecord
{
    use HandlesProductPrice;

    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extractPrice($data);
    }

    protected function afterCreate(): void
    {
        /** @var Product $product */
        $product = $this->getRecord();
        $this->savePrice($product);
    }
}
