<?php

namespace Modules\Orders\Filament\Resources\Coupons;

use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;
use App\Filament\AdminGroup;
use App\Filament\Forms\MoneyInput;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Modules\Orders\CouponType;
use Modules\Orders\Filament\Resources\Coupons\Pages\ManageCoupons;
use Modules\Orders\Models\Coupon;
use UnitEnum;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Sales;
    }

    public static function getModelLabel(): string
    {
        return __('orders::admin.coupons.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('orders::admin.coupons.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('orders.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('orders::admin.coupons.'.$key);
        $type = fn (Get $get) => $get('type') instanceof CouponType ? $get('type') : CouponType::tryFrom((string) $get('type'));
        $currency = app(CurrencyContext::class)->current();

        return $schema->columns(2)->components([
            TextInput::make('code')->label($t('code'))->required()->maxLength(64)
                ->default(fn () => Str::upper(Str::random(8)))
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn (string $state) => Coupon::normalize($state)),
            TextInput::make('description')->label($t('description'))->helperText($t('description_help'))->maxLength(255),
            Select::make('type')->label($t('type'))->options(CouponType::class)->required()->default(CouponType::Percent)->live()->native(false),
            TextInput::make('value')->label($t('percent'))->integer()->minValue(1)->maxValue(100)->suffix('%')->required()
                ->visible(fn (Get $get) => $type($get) === CouponType::Percent),
            MoneyInput::make('value')->label($t('amount'))->required()
                ->visible(fn (Get $get) => $type($get) === CouponType::Fixed),
            MoneyInput::make('min_order_amount')->label($t('min_order_amount'))->helperText($t('min_order_amount_help')),
            TextInput::make('usage_limit')->label($t('usage_limit'))->helperText($t('usage_limit_help'))->integer()->minValue(1),
            DateTimePicker::make('starts_at')->label($t('starts_at'))->seconds(false),
            DateTimePicker::make('ends_at')->label($t('ends_at'))->seconds(false)->after('starts_at'),
            Toggle::make('is_active')->label($t('is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('orders::admin.coupons.'.$key);
        $currency = app(CurrencyContext::class)->current();

        return $table
            ->columns([
                TextColumn::make('code')->label($t('code'))->searchable()->copyable()->weight('medium')
                    ->description(fn (Coupon $record) => $record->description),
                TextColumn::make('value')->label($t('discount'))
                    ->formatStateUsing(fn (Coupon $record) => match ($record->type) {
                        CouponType::Percent => $record->value.' %',
                        CouponType::Fixed => Price::format($record->value, $record->currency),
                        CouponType::FreeShipping => CouponType::FreeShipping->getLabel(),
                    }),
                TextColumn::make('used_count')->label($t('used'))
                    ->formatStateUsing(fn (Coupon $record) => $record->used_count.($record->usage_limit ? ' / '.$record->usage_limit : '')),
                TextColumn::make('ends_at')->label($t('ends_at'))->dateTime()->placeholder('—'),
                ToggleColumn::make('is_active')->label($t('is_active')),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCoupons::route('/')];
    }
}
