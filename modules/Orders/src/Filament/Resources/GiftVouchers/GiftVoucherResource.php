<?php

namespace Modules\Orders\Filament\Resources\GiftVouchers;

use App\Core\Support\Currencies;
use App\Core\Support\Price;
use App\Filament\AdminGroup;
use App\Filament\Forms\MoneyInput;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Orders\Filament\Resources\Coupons\CouponResource;
use Modules\Orders\Filament\Resources\GiftVouchers\Pages\ManageGiftVouchers;
use Modules\Orders\GiftVouchers;
use Modules\Orders\GiftVoucherSettings;
use Modules\Orders\Models\GiftVoucher;
use Modules\Orders\Models\GiftVoucherUsage;
use UnitEnum;

class GiftVoucherResource extends Resource
{
    protected static ?string $model = GiftVoucher::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static ?int $navigationSort = 22;

    protected static ?string $slug = 'gift-vouchers';

    protected static ?string $recordTitleAttribute = 'code';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Sales;
    }

    public static function getModelLabel(): string
    {
        return __('orders::admin.vouchers.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('orders::admin.vouchers.plural');
    }

    public static function canAccess(): bool
    {
        return GiftVoucherSettings::isEnabled() && (auth()->user()?->can('orders.manage') ?? false);
    }

    /**
     * Úprava: platnosť, aktívnosť a poznámka (hodnotu a zostatok mení iba čerpanie).
     */
    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('orders::admin.vouchers.'.$key);

        return $schema->columns(2)->components([
            DatePicker::make('expires_at')->label($t('expires_at'))->native(false),
            Toggle::make('is_active')->label($t('is_active'))->inline(false),
            TextInput::make('note')->label($t('note'))->maxLength(255)->columnSpanFull(),
        ]);
    }

    /**
     * Formulár ručného vydania poukážky (napr. darček, náhrada za reklamáciu).
     *
     * @return list<Component|Field>
     */
    public static function issueFields(): array
    {
        $t = fn (string $key) => __('orders::admin.vouchers.'.$key);
        $currencies = app(Currencies::class);

        return [
            Select::make('currency')->label($t('currency'))->required()->selectablePlaceholder(false)->live()
                ->options(collect($currencies->enabled())->mapWithKeys(fn (string $code) => [$code => $code])->all())
                ->default($currencies->default()),
            MoneyInput::make('amount')->label($t('amount'))->required(),
            TextInput::make('recipient_email')->label($t('recipient_email'))->email()->maxLength(255),
            Toggle::make('send')->label($t('send'))->default(true),
            TextInput::make('note')->label($t('note'))->maxLength(255)->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('orders::admin.vouchers.'.$key);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('order'))
            ->columns([
                TextColumn::make('code')->label($t('code'))->searchable()->copyable()->fontFamily('mono')
                    ->formatStateUsing(fn (GiftVoucher $record) => $record->formattedCode()),
                TextColumn::make('balance')->label($t('balance'))->sortable()
                    ->formatStateUsing(fn (GiftVoucher $record) => Price::format($record->balance, $record->currency))
                    ->description(fn (GiftVoucher $record) => $t('of').' '.Price::format($record->initial_amount, $record->currency)),
                TextColumn::make('expires_at')->label($t('expires_at'))->date()->placeholder($t('no_expiry'))->sortable()
                    ->color(fn (GiftVoucher $record) => $record->expires_at?->isPast() ? 'danger' : null),
                TextColumn::make('order.number')->label($t('order'))->placeholder($t('manual'))
                    ->url(fn (GiftVoucher $record) => $record->order_id ? url('/admin/orders/'.$record->order_id.'/edit') : null),
                TextColumn::make('recipient_email')->label($t('recipient_email'))->placeholder('—')->toggleable(),
                IconColumn::make('is_active')->label($t('is_active'))->boolean(),
                TextColumn::make('created_at')->label($t('created_at'))->date()->sortable()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('with_balance')->label($t('with_balance'))
                    ->queries(true: fn (Builder $query) => $query->where('balance', '>', 0), false: fn (Builder $query) => $query->where('balance', 0)),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('history')->label($t('history'))->icon(Heroicon::OutlinedClock)->color('gray')
                    ->modalSubmitAction(false)
                    ->modalContent(fn (GiftVoucher $record) => view('orders::filament.voucher-history', [
                        'voucher' => $record,
                        'usages' => GiftVoucherUsage::query()->where('gift_voucher_id', $record->id)->with('order')->latest('id')->get(),
                    ])),
                Action::make('resend')->label($t('resend'))->icon(Heroicon::OutlinedEnvelope)->color('gray')
                    ->visible(fn (GiftVoucher $record) => filled($record->recipient_email))
                    ->requiresConfirmation()
                    ->action(function (GiftVoucher $record) use ($t): void {
                        app(GiftVouchers::class)->send([$record], (string) $record->recipient_email, $record->order?->billing_name, $record->order?->locale);
                        Notification::make()->success()->title($t('sent'))->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageGiftVouchers::route('/')];
    }

    /** V menu pod položkou „CouponResource“ (zobrazí sa po jej otvorení), aby bolo hlavné menu krátke. */
    public static function getNavigationParentItem(): ?string
    {
        return CouponResource::getNavigationLabel();
    }
}
