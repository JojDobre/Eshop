<?php

namespace Modules\Catalog\Filament\Resources\Products\RelationManagers;

use App\Core\Pricing\PriceContext;
use App\Core\Support\Currencies;
use App\Core\Support\Price;
use App\Filament\Forms\MoneyInput;
use App\Models\CustomerGroup;
use App\Settings\TaxSettings;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductGroupPrice;
use Modules\Catalog\Models\ProductVariant;

/**
 * Ceny produktu a variantov pre zákaznícke skupiny (B2B ceny).
 */
class GroupPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'allGroupPrices';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('catalog::admin.group_prices.title');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return PriceContext::groupsEnabled();
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('catalog::admin.group_prices.'.$key);
        /** @var Product $product */
        $product = $this->getOwnerRecord();
        $currencies = app(Currencies::class);

        return $schema->columns(2)->components([
            Select::make('customer_group_id')->label($t('group'))->required()
                ->options(fn () => CustomerGroup::query()->ordered()->get()->mapWithKeys(fn (CustomerGroup $group) => [$group->id => $group->name])->all()),
            Select::make('product_variant_id')->label(__('catalog::admin.variants.variant'))->placeholder($t('whole_product'))
                ->options(fn () => $product->variants()->with('values')->get()->mapWithKeys(fn (ProductVariant $variant) => [$variant->id => $variant->name()])->all())
                ->visible(fn () => $product->hasVariants()),
            Select::make('currency')->label($t('currency'))->required()->selectablePlaceholder(false)->live()
                ->options(collect($currencies->enabled())->mapWithKeys(fn (string $code) => [$code => $code])->all())
                ->default($currencies->default())
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, $get) => $rule
                    ->where('product_id', $product->id)
                    ->where('customer_group_id', $get('customer_group_id'))
                    ->where(fn ($query) => filled($get('product_variant_id')) ? $query->where('product_variant_id', $get('product_variant_id')) : $query->whereNull('product_variant_id')))
                ->validationMessages(['unique' => $t('duplicate')]),
            TextInput::make('amount')->label($t('price'))->helperText(fn () => app(TaxSettings::class)->vat_payer && ! app(TaxSettings::class)->prices_include_vat ? __('catalog::admin.product.price_help_net') : __('catalog::admin.product.price_help'))->required()
                ->rule(MoneyInput::RULE)->inputMode('decimal')
                ->suffix(fn ($get) => $get('currency'))
                ->formatStateUsing(fn ($state, $get) => is_numeric($state) ? Price::toDecimal((int) $state, (string) ($get('currency') ?: $currencies->default())) : $state)
                ->dehydrateStateUsing(fn ($state, $get) => Price::toMinor((string) $state, (string) $get('currency'))),
        ]);
    }

    public function table(Table $table): Table
    {
        $t = fn (string $key) => __('catalog::admin.group_prices.'.$key);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['customerGroup', 'variant.values']))
            ->columns([
                TextColumn::make('customerGroup.name')->label($t('group'))->weight('medium'),
                TextColumn::make('variant')->label(__('catalog::admin.variants.variant'))->placeholder($t('whole_product'))
                    ->state(fn (ProductGroupPrice $record) => $record->variant?->name()),
                TextColumn::make('amount')->label($t('price'))
                    ->formatStateUsing(fn (int $state, ProductGroupPrice $record) => Price::format($state, $record->currency)),
            ])
            ->description($t('help'))
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->paginated(false);
    }
}
