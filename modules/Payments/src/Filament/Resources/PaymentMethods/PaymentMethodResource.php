<?php

namespace Modules\Payments\Filament\Resources\PaymentMethods;

use App\Core\Support\Price;
use App\Filament\AdminGroup;
use App\Filament\Forms\MoneyInput;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Modules\Payments\Filament\Resources\PaymentMethods\Pages\CreatePaymentMethod;
use Modules\Payments\Filament\Resources\PaymentMethods\Pages\EditPaymentMethod;
use Modules\Payments\Filament\Resources\PaymentMethods\Pages\ListPaymentMethods;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\PaymentType;
use Modules\Shipping\Models\ShippingMethod;
use UnitEnum;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'settings/payments';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Settings;
    }

    public static function getNavigationLabel(): string
    {
        return __('payments::admin.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('payments::admin.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payments::admin.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('payments.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('payments::admin.'.$key);

        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label($t('name'))->required()->maxLength(120),
                Select::make('type')->label($t('type'))->options(PaymentType::class)->required()->native(false),
                Textarea::make('description')->label($t('description'))->helperText($t('description_help'))
                    ->rows(2)->maxLength(300)->columnSpanFull(),
                Textarea::make('instructions')->label($t('instructions'))->helperText($t('instructions_help'))
                    ->rows(3)->maxLength(1000)->columnSpanFull(),
                MoneyInput::make('fee')->label($t('fee'))->default(0),
                TextInput::make('position')->label($t('position'))->integer()->default(0)->minValue(0),
                CheckboxList::make('shippingMethods')->label($t('shipping_methods'))->helperText($t('shipping_methods_help'))
                    ->relationship('shippingMethods', 'id')
                    ->getOptionLabelFromRecordUsing(fn (ShippingMethod $record) => $record->name)
                    ->columns(2)->columnSpanFull(),
                Toggle::make('is_active')->label($t('is_active'))->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('payments::admin.'.$key);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('shippingMethods'))
            ->columns([
                TextColumn::make('name')->label($t('name'))->description(fn (PaymentMethod $record) => $record->description),
                TextColumn::make('type')->label($t('type'))->badge()->color('gray'),
                TextColumn::make('fee')->label($t('fee'))
                    ->formatStateUsing(fn (PaymentMethod $record) => Price::format($record->fee, $record->currency)),
                TextColumn::make('shipping')->label($t('shipping_methods'))
                    ->state(fn (PaymentMethod $record) => $record->shippingMethods->isEmpty()
                        ? $t('all_shipping')
                        : $record->shippingMethods->map->name->implode(', ')),
                ToggleColumn::make('is_active')->label($t('is_active')),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentMethods::route('/'),
            'create' => CreatePaymentMethod::route('/create'),
            'edit' => EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
