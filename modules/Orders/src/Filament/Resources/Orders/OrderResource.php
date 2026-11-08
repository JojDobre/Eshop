<?php

namespace Modules\Orders\Filament\Resources\Orders;

use App\Core\Support\Price;
use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Orders\CheckoutFields;
use Modules\Orders\Filament\Resources\Orders\Pages\ListOrders;
use Modules\Orders\Filament\Resources\Orders\Pages\ViewOrder;
use Modules\Orders\Filament\ShipmentActions;
use Modules\Orders\Models\DownloadLink;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderHistoryEntry;
use Modules\Orders\OrderStatus;
use Modules\Orders\PaymentStatus;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Sales;
    }

    public static function getModelLabel(): string
    {
        return __('orders::admin.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('orders::admin.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('orders.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Order::query()->where('status', OrderStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'email', 'billing_name'];
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('orders::admin.'.$key);

        return $table
            ->columns([
                TextColumn::make('number')->label($t('number'))->searchable()->weight('medium'),
                TextColumn::make('created_at')->label($t('created_at'))->dateTime()->sortable(),
                TextColumn::make('billing_name')->label($t('customer'))
                    ->description(fn (Order $record) => $record->billing_company ?: $record->email)
                    ->searchable(['billing_name', 'billing_company', 'email', 'phone']),
                TextColumn::make('total')->label($t('total'))
                    ->formatStateUsing(fn (Order $record) => Price::format($record->total, $record->currency))
                    ->alignEnd()->sortable(),
                TextColumn::make('status')->label($t('status'))->badge(),
                TextColumn::make('payment_status')->label($t('payment_status'))->badge()
                    ->description(fn (Order $record) => $record->payment_method_name),
                TextColumn::make('shipping_method_name')->label($t('shipping'))->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label($t('status'))->options(OrderStatus::class)->multiple(),
                SelectFilter::make('payment_status')->label($t('payment_status'))->options(PaymentStatus::class),
            ])
            ->recordUrl(fn (Order $record) => static::getUrl('view', ['record' => $record]))
            ->recordActions([ViewAction::make()])
            ->toolbarActions([BulkActionGroup::make([ShipmentActions::bulk()])]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $t = fn (string $key) => __('orders::admin.'.$key);
        $money = fn (Order $record, int $amount) => Price::format($amount, $record->currency);

        return $schema->columns(3)->components([
            Group::make()->columnSpan(2)->schema([
                Section::make(__('orders::admin.shipments.title'))
                    ->visible(fn (Order $record) => $record->shipments()->exists())
                    ->schema([
                        RepeatableEntry::make('shipments')->hiddenLabel()
                            ->table([
                                TableColumn::make(__('orders::admin.shipments.carrier')),
                                TableColumn::make(__('orders::admin.shipments.tracking_number')),
                                TableColumn::make(__('orders::admin.shipments.status')),
                            ])
                            ->schema([
                                TextEntry::make('carrier')->formatStateUsing(fn ($state, $record) => $record->carrierService()?->label() ?? $state),
                                TextEntry::make('tracking_number')->placeholder('—')
                                    ->url(fn ($record) => $record->trackingUrl(), shouldOpenInNewTab: true),
                                TextEntry::make('status')->badge()
                                    ->helperText(fn ($record) => $record->status_text),
                            ]),
                    ]),
                Section::make(__('orders::admin.downloads.title'))
                    ->visible(fn (Order $record) => DownloadLink::query()->where('order_id', $record->id)->exists())
                    ->schema([
                        RepeatableEntry::make('downloadLinks')->hiddenLabel()
                            ->table([
                                TableColumn::make(__('orders::admin.downloads.file')),
                                TableColumn::make(__('orders::admin.downloads.count'))->alignment('center'),
                                TableColumn::make(__('orders::admin.downloads.expires_at')),
                            ])
                            ->schema([
                                TextEntry::make('file_name'),
                                TextEntry::make('downloads')->formatStateUsing(fn ($state, $record) => $record->max_downloads ? $state.' / '.$record->max_downloads : (string) $state),
                                TextEntry::make('expires_at')->dateTime()->placeholder('—'),
                            ]),
                    ]),
                Section::make($t('section_items'))->schema([
                    RepeatableEntry::make('items')->hiddenLabel()
                        ->table([
                            TableColumn::make($t('item_name')),
                            TableColumn::make($t('item_sku')),
                            TableColumn::make($t('item_quantity'))->alignment('center'),
                            TableColumn::make($t('item_unit_price'))->alignment('end'),
                            TableColumn::make($t('item_total'))->alignment('end'),
                        ])
                        ->schema([
                            TextEntry::make('name')->label($t('item_name'))->formatStateUsing(fn ($state, $record) => $record->fullName())
                                ->helperText(fn ($record) => ($batches = $record->batchesLabel()) ? __('orders::admin.batches', ['batches' => $batches]) : null),
                            TextEntry::make('sku')->label($t('item_sku'))->placeholder('—'),
                            TextEntry::make('quantity')->label($t('item_quantity')),
                            TextEntry::make('unit_price')->label($t('item_unit_price'))->formatStateUsing(fn ($state, $record) => Price::format((int) $state, $record->order->currency)),
                            TextEntry::make('total')->label($t('item_total'))->formatStateUsing(fn ($state, $record) => Price::format((int) $state, $record->order->currency)),
                        ]),
                    Grid::make(5)->schema([
                        TextEntry::make('subtotal')->label($t('subtotal'))->formatStateUsing(fn (Order $record) => $money($record, $record->subtotal)),
                        TextEntry::make('discount')->label($t('discount'))
                            ->formatStateUsing(fn (Order $record) => $record->discount > 0 ? '−'.$money($record, $record->discount).' ('.$record->coupon_code.')' : '—'),
                        TextEntry::make('shipping_price')->label($t('shipping'))->formatStateUsing(fn (Order $record) => $money($record, $record->shipping_price)),
                        TextEntry::make('payment_fee')->label($t('payment_fee'))->formatStateUsing(fn (Order $record) => $money($record, $record->payment_fee)),
                        TextEntry::make('total')->label($t('total'))->weight('bold')->formatStateUsing(fn (Order $record) => $money($record, $record->total)),
                    ]),
                ]),
                Section::make($t('section_customer'))->columns(2)->schema([
                    TextEntry::make('billing_name')->label($t('customer')),
                    TextEntry::make('email')->label($t('email'))->copyable()->url(fn (Order $record) => 'mailto:'.$record->email),
                    TextEntry::make('phone')->label($t('phone'))->placeholder('—')->url(fn (Order $record) => $record->phone ? 'tel:'.preg_replace('/\s+/', '', $record->phone) : null),
                    TextEntry::make('billing_company')->label($t('company'))->placeholder('—')
                        ->formatStateUsing(fn (Order $record) => collect([
                            $record->billing_company,
                            $record->billing_company_id ? $t('company_id').': '.$record->billing_company_id : null,
                            $record->billing_tax_id ? $t('tax_id').': '.$record->billing_tax_id : null,
                            $record->billing_vat_id ? $t('vat_id').': '.$record->billing_vat_id : null,
                        ])->filter()->implode(', ')),
                    TextEntry::make('billing_street')->label($t('billing_address'))
                        ->formatStateUsing(fn (Order $record) => implode(', ', array_filter([$record->billing_street, trim($record->billing_zip.' '.$record->billing_city), $record->billing_country]))),
                    TextEntry::make('shipping_street')->label($t('shipping_address'))->placeholder('—')
                        ->formatStateUsing(fn (Order $record) => implode(', ', array_filter([$record->shipping_name, $record->shipping_street, trim($record->shipping_zip.' '.$record->shipping_city), $record->shipping_country]))),
                    TextEntry::make('customer_note')->label($t('customer_note'))->placeholder('—')->columnSpanFull(),
                    TextEntry::make('order_fields')->label(__('orders::admin.additional_details'))
                        ->state(fn (Order $record) => collect(CheckoutFields::display($record->id))->map(fn ($value, $label) => $label.': '.$value)->values()->all() ?: null)
                        ->listWithLineBreaks()->columnSpanFull()
                        ->visible(fn (Order $record) => CheckoutFields::display($record->id) !== []),
                ]),
            ]),
            Group::make()->schema([
                Section::make()->schema([
                    TextEntry::make('status')->label($t('status'))->badge(),
                    TextEntry::make('created_at')->label($t('created_at'))->dateTime(),
                    TextEntry::make('shipping_method_name')->label($t('shipping')),
                    TextEntry::make('payment_method_name')->label($t('payment')),
                    TextEntry::make('payment_status')->label($t('payment_status'))->badge(),
                    TextEntry::make('paid_at')->label($t('paid_at'))->dateTime()->visible(fn (Order $record) => $record->paid_at !== null),
                    TextEntry::make('variable_symbol')->label($t('variable_symbol'))->state(fn (Order $record) => $record->variableSymbol())->copyable(),
                    TextEntry::make('legal_consents')->label($t('legal_consents'))
                        ->state(fn (Order $record) => collect($record->legal_consents ?? [])
                            ->map(fn (array $consent) => $consent['title'].' ('.__('orders::admin.version', ['version' => $consent['version']]).')')->all() ?: null)
                        ->listWithLineBreaks()->placeholder('—'),
                ]),
                Section::make($t('section_history'))->schema([
                    RepeatableEntry::make('history')->hiddenLabel()->contained(false)->schema([
                        TextEntry::make('description')->hiddenLabel()
                            ->state(fn (OrderHistoryEntry $record) => $record->description())
                            ->helperText(fn (OrderHistoryEntry $record) => $record->created_at->format(app()->getLocale() === 'en' ? 'j M Y H:i' : 'j. n. Y H:i')
                                .' · '.($record->user?->name ?? __('orders::admin.history.system'))),
                    ]),
                ]),
            ]),
        ]);
    }

    /** @var list<class-string> Záložky v detaile objednávky dodané inými modulmi (napr. doklady). */
    private static array $extraRelations = [];

    /**
     * @param  class-string  $relationManager
     */
    public static function registerRelationManager(string $relationManager): void
    {
        if (! in_array($relationManager, self::$extraRelations, true)) {
            self::$extraRelations[] = $relationManager;
        }
    }

    public static function getRelations(): array
    {
        return self::$extraRelations;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['items.order', 'history.user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
