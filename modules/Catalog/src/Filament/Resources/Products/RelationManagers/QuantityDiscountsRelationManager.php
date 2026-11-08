<?php

namespace Modules\Catalog\Filament\Resources\Products\RelationManagers;

use App\Core\Pricing\PriceContext;
use App\Models\CustomerGroup;
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

/**
 * Množstevné zľavy produktu: od počtu kusov zľava v %, pre všetkých alebo pre zákaznícku skupinu.
 */
class QuantityDiscountsRelationManager extends RelationManager
{
    protected static string $relationship = 'quantityDiscounts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('catalog::admin.quantity_discounts.title');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('catalog::admin.quantity_discounts.'.$key);

        return $schema->columns(3)->components([
            TextInput::make('min_quantity')->label($t('min_quantity'))->required()->integer()->minValue(2),
            TextInput::make('discount_percent')->label($t('discount_percent'))->required()->numeric()->minValue(0.01)->maxValue(99.99)->step(0.01)->suffix('%'),
            Select::make('customer_group_id')->label($t('group'))->placeholder($t('everyone'))
                ->options(fn () => CustomerGroup::query()->ordered()->get()->mapWithKeys(fn (CustomerGroup $group) => [$group->id => $group->name])->all())
                ->visible(fn () => PriceContext::groupsEnabled()),
        ]);
    }

    public function table(Table $table): Table
    {
        $t = fn (string $key) => __('catalog::admin.quantity_discounts.'.$key);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('customerGroup'))
            ->columns([
                TextColumn::make('min_quantity')->label($t('min_quantity'))->formatStateUsing(fn (int $state) => $t('from').' '.$state),
                TextColumn::make('discount_percent')->label($t('discount_percent'))->suffix(' %'),
                TextColumn::make('customerGroup.name')->label($t('group'))->placeholder($t('everyone'))
                    ->visible(fn () => PriceContext::groupsEnabled()),
            ])
            ->description($t('help'))
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->paginated(false);
    }
}
