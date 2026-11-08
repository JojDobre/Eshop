<?php

namespace Modules\Customers\Filament\Resources\Customers;

use App\Filament\AdminGroup;
use App\Models\CustomerGroup;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\CustomFieldsSection;
use Modules\Customers\CustomerSettings;
use Modules\Customers\Filament\Resources\Customers\Pages\EditCustomer;
use Modules\Customers\Filament\Resources\Customers\Pages\ListCustomers;
use Modules\Customers\Filament\Resources\Customers\RelationManagers\OrdersRelationManager;
use Modules\Customers\Models\Customer;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Sales;
    }

    public static function getModelLabel(): string
    {
        return __('customers::admin.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('customers::admin.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('customers.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        // Účet si zakladá zákazník sám (súhlas s podmienkami, vlastné heslo).
        return false;
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('customers::admin.'.$key);
        $address = fn (?array $address) => collect([
            $address['name'] ?? null, $address['company'] ?? null, $address['street'] ?? null,
            trim(($address['zip'] ?? '').' '.($address['city'] ?? '')),
            filled($address['country'] ?? null) ? __('countries.'.$address['country']) : null,
        ])->filter()->implode("\n") ?: '—';

        return $schema->columns(3)->components([
            Section::make($t('section_account'))->columnSpan(2)->columns(2)->schema([
                TextInput::make('name')->label($t('name'))->required()->maxLength(120),
                TextInput::make('email')->label($t('email'))->email()->required()->maxLength(255)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule)
                    ->dehydrateStateUsing(fn (string $state) => Str::lower(trim($state))),
                TextInput::make('phone')->label($t('phone'))->tel()->maxLength(40),
                Select::make('customer_group_id')->label($t('group'))->helperText($t('group_help'))
                    ->relationship('customerGroup', 'name', fn (Builder $query) => $query->ordered())
                    ->getOptionLabelFromRecordUsing(fn (CustomerGroup $record) => $record->name)
                    ->placeholder($t('group_default'))
                    ->visible(fn () => CustomerSettings::groupsEnabled()),
            ]),
            Section::make($t('section_blocking'))->columnSpan(2)->columns(2)->collapsible()->schema([
                Toggle::make('is_blocked')->label($t('is_blocked'))->helperText($t('is_blocked_help'))->live(),
                TextInput::make('blocked_reason')->label($t('blocked_reason'))->maxLength(255)
                    ->visible(fn ($get) => (bool) $get('is_blocked')),
            ]),
            CustomFieldsSection::make(CustomField::CUSTOMER)->columnSpan(2),
            Section::make()->columnSpan(1)->schema([
                TextEntry::make('created_at')->label($t('created_at'))->dateTime(),
                TextEntry::make('last_login_at')->label($t('last_login_at'))->dateTime()->placeholder('—'),
                TextEntry::make('billing_address')->label($t('billing_address'))->state(fn (?Customer $record) => $address($record?->billing_address))->extraAttributes(['class' => 'whitespace-pre-line']),
                TextEntry::make('shipping_address')->label($t('shipping_address'))->state(fn (?Customer $record) => $address($record?->shipping_address))->extraAttributes(['class' => 'whitespace-pre-line']),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('customers::admin.'.$key);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('orders'))
            ->columns([
                TextColumn::make('name')->label($t('name'))->searchable()->sortable()->weight('medium'),
                TextColumn::make('email')->label($t('email'))->searchable()->copyable(),
                TextColumn::make('phone')->label($t('phone'))->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('customerGroup.name')->label($t('group'))->badge()->color('gray')->placeholder('—')
                    ->visible(fn () => CustomerSettings::groupsEnabled()),
                IconColumn::make('is_blocked')->label($t('is_blocked'))->boolean()->trueColor('danger')->falseIcon(null)->toggleable(),
                TextColumn::make('orders_count')->label($t('orders_count'))->sortable()->alignEnd(),
                TextColumn::make('created_at')->label($t('created_at'))->date()->sortable(),
                TextColumn::make('last_login_at')->label($t('last_login_at'))->since()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('customer_group_id')->label($t('group'))
                    ->relationship('customerGroup', 'name')
                    ->getOptionLabelFromRecordUsing(fn (CustomerGroup $record) => $record->name)
                    ->visible(fn () => CustomerSettings::groupsEnabled()),
                TernaryFilter::make('is_blocked')->label($t('is_blocked')),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [OrdersRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
