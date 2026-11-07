<?php

namespace Modules\Catalog\Filament\Resources\Products\RelationManagers;

use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;
use App\Filament\Forms\MoneyInput;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\VariantGenerator;

/**
 * Varianty produktu: kombinácie hodnôt možností s vlastnou cenou, skladom, SKU a obrázkami.
 */
class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('catalog::admin.variants.title');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('catalog::admin.variants.'.$key);
        $currency = app(CurrencyContext::class)->current();

        return $schema->columns(2)->components([
            TextInput::make('sku')->label(__('catalog::admin.product.sku'))->maxLength(64)->unique(ignoreRecord: true),
            TextInput::make('price')->label($t('price'))->helperText($t('price_help'))
                ->rule(MoneyInput::RULE)->inputMode('decimal')->suffix($currency),
            TextInput::make('stock_quantity')->label(__('catalog::admin.product.stock_quantity'))->integer()->default(0),
            TextInput::make('weight')->label(__('catalog::admin.product.weight'))->integer()->minValue(0)->suffix('g'),
            FileUpload::make('images')->label($t('images'))->helperText($t('images_help'))
                ->disk('public')->directory('products')->visibility('public')
                ->image()->multiple()->reorderable()->panelLayout('grid')
                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                ->maxFiles(10)->maxSize(8192)
                ->imageResizeTargetWidth('2000')->imageResizeTargetHeight('2000')->imageResizeMode('contain')->imageResizeUpscale(false)
                ->columnSpanFull(),
            Toggle::make('is_active')->label($t('is_active'))->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        $t = fn (string $key) => __('catalog::admin.variants.'.$key);
        $currency = app(CurrencyContext::class)->current();

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['values', 'prices', 'product.prices']))
            ->columns([
                ImageColumn::make('images')->label('')->disk('public')->limit(1)->square()->imageSize(36),
                TextColumn::make('name')->label($t('variant'))->state(fn (ProductVariant $record) => $record->name())->weight('medium'),
                TextColumn::make('sku')->label(__('catalog::admin.product.sku'))->placeholder('—'),
                TextColumn::make('price')->label($t('price'))
                    ->state(fn (ProductVariant $record) => $record->priceIn($currency))
                    ->formatStateUsing(fn (?int $state) => $state === null ? '—' : Price::format($state, $currency))
                    ->description(fn (ProductVariant $record) => $record->enteredPriceIn($currency) === null ? $t('inherits_price') : null),
                TextColumn::make('stock_quantity')->label(__('catalog::admin.product.stock_quantity'))->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'danger'),
                ToggleColumn::make('is_active')->label($t('is_active')),
            ])
            ->reorderable('position')
            ->defaultSort('position')
            ->headerActions([
                Action::make('generate')->label($t('generate'))->icon(Heroicon::OutlinedSparkles)
                    ->requiresConfirmation()
                    ->modalDescription($t('generate_help'))
                    ->action(function (VariantGenerator $generator): void {
                        /** @var Product $product */
                        $product = $this->getOwnerRecord();
                        $created = $generator->generate($product);

                        Notification::make()->success()->title(trans_choice('catalog::admin.variants.generated', $created, ['count' => $created]))->send();
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, ProductVariant $record) use ($currency): array {
                        $price = $record->enteredPriceIn($currency);
                        $data['price'] = $price === null ? null : Price::toDecimal($price, $currency);

                        return $data;
                    })
                    ->using(function (ProductVariant $record, array $data) use ($currency): ProductVariant {
                        $price = filled($data['price'] ?? null) ? Price::toMinor((string) $data['price'], $currency) : null;
                        unset($data['price']);

                        $record->update($data);
                        $record->setPrice($currency, $price);

                        return $record;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->emptyStateHeading($t('empty'))
            ->emptyStateDescription($t('empty_help'))
            ->paginated(false);
    }
}
