<?php

namespace Modules\Customers\Filament\Resources\CustomerGroups;

use App\Filament\AdminGroup;
use App\Filament\Concerns\FillsContentTranslations;
use App\Models\CustomerGroup;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Modules\Customers\CustomerSettings;
use Modules\Customers\Filament\Resources\CustomerGroups\Pages\ManageCustomerGroups;
use UnitEnum;

class CustomerGroupResource extends Resource
{
    protected static ?string $model = CustomerGroup::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 31;

    protected static ?string $slug = 'customer-groups';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Sales;
    }

    public static function getModelLabel(): string
    {
        return __('customers::admin.groups.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('customers::admin.groups.plural');
    }

    public static function canAccess(): bool
    {
        return CustomerSettings::groupsEnabled() && (auth()->user()?->can('customers.manage') ?? false);
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('customers::admin.groups.'.$key);

        return $schema->columns(2)->components([
            TextInput::make('name')->label($t('name'))->required()->maxLength(120)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set, $get) => blank($get('code')) ? $set('code', Str::slug((string) $state, '_')) : null),
            TextInput::make('code')->label($t('code'))->helperText($t('code_help'))->required()->maxLength(32)
                ->alphaDash()->unique(ignoreRecord: true),
            TextInput::make('discount_percent')->label($t('discount_percent'))->helperText($t('discount_percent_help'))
                ->numeric()->minValue(0)->maxValue(100)->step(0.01)->suffix('%')->default(0),
            Toggle::make('prices_without_vat')->label($t('prices_without_vat'))->helperText($t('prices_without_vat_help')),
            Toggle::make('is_default')->label($t('is_default'))->helperText($t('is_default_help')),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('customers::admin.groups.'.$key);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('customers'))
            ->columns([
                TextColumn::make('name')->label($t('name'))->description(fn (CustomerGroup $record) => $record->code)->weight('medium'),
                TextColumn::make('discount_percent')->label($t('discount_percent'))->suffix(' %')->alignEnd(),
                IconColumn::make('prices_without_vat')->label($t('prices_without_vat'))->boolean(),
                IconColumn::make('is_default')->label($t('is_default'))->boolean(),
                TextColumn::make('customers_count')->label($t('customers'))->badge()->color('gray'),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->description($t('help'))
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, CustomerGroup $record) => FillsContentTranslations::contentTranslationsForForm($record, $data)),
                // Zákazníci zmazanej skupiny dostanú predvolenú skupinu, ceny pre skupinu sa zmažú.
                DeleteAction::make(),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCustomerGroups::route('/')];
    }
}
