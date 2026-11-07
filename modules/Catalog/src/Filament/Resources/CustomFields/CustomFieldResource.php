<?php

namespace Modules\Catalog\Filament\Resources\CustomFields;

use App\Filament\AdminGroup;
use App\Filament\Concerns\FillsContentTranslations;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\CustomFieldType;
use Modules\Catalog\Filament\Resources\CustomFields\Pages\ManageCustomFields;
use UnitEnum;

class CustomFieldResource extends Resource
{
    protected static ?string $model = CustomField::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?int $navigationSort = 25;

    protected static ?string $slug = 'custom-fields';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Catalog;
    }

    public static function getModelLabel(): string
    {
        return __('catalog::admin.custom_fields.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog::admin.custom_fields.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('catalog.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('catalog::admin.custom_fields.'.$key);
        $type = fn (Get $get) => $get('type') instanceof CustomFieldType ? $get('type') : CustomFieldType::tryFrom((string) $get('type'));

        return $schema->columns(2)->components([
            TextInput::make('name')->label($t('name'))->helperText($t('name_help'))->required()->maxLength(80),
            Select::make('type')->label($t('type'))->options(CustomFieldType::class)->required()->live()->native(false)
                ->disabledOn('edit')->helperText(fn (string $operation) => $operation === 'edit' ? $t('type_locked') : null),
            TextInput::make('unit')->label($t('unit'))->helperText($t('unit_help'))->maxLength(32)
                ->visible(fn (Get $get) => in_array($type($get), [CustomFieldType::Number, CustomFieldType::Text, CustomFieldType::Select], true)),
            TextInput::make('help')->label($t('help'))->maxLength(200),
            Repeater::make('options')->label($t('options'))
                ->schema([
                    TextInput::make('label')->label($t('option_label'))->required()->maxLength(80),
                    TextInput::make('key')->hidden()->dehydrated(),
                ])
                ->addActionLabel($t('add_option'))
                ->reorderable()
                ->minItems(1)
                ->columnSpanFull()
                ->visible(fn (Get $get) => (bool) $type($get)?->hasOptions()),
            Section::make()->columns(3)->columnSpanFull()->schema([
                Toggle::make('is_required')->label($t('is_required')),
                Toggle::make('is_visible')->label($t('is_visible'))->helperText($t('is_visible_help'))->default(true),
                Toggle::make('is_filterable')->label($t('is_filterable'))->helperText($t('is_filterable_help'))
                    ->visible(fn (Get $get) => (bool) $type($get)?->isFilterable()),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('catalog::admin.custom_fields.'.$key);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('entity', CustomField::PRODUCT))
            ->columns([
                TextColumn::make('name')->label($t('name'))->description(fn (CustomField $record) => $record->unit),
                TextColumn::make('type')->label($t('type'))->badge()->color('gray'),
                IconColumn::make('is_required')->label($t('is_required'))->boolean(),
                IconColumn::make('is_visible')->label($t('is_visible'))->boolean(),
                IconColumn::make('is_filterable')->label($t('is_filterable'))->boolean(),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, CustomField $record) => self::fillOptions(
                    FillsContentTranslations::contentTranslationsForForm($record, $data), $record,
                )),
                DeleteAction::make(),
            ])
            ->emptyStateDescription($t('empty_help'))
            ->paginated(false);
    }

    /**
     * Názvy možností vo formulári v jazyku obsahu.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fillOptions(array $data, CustomField $record): array
    {
        $labels = $record->optionLabels();
        $data['options'] = collect($record->options ?? [])->map(fn ($option) => ['key' => $option['key'], 'label' => $labels[$option['key']] ?? ''])->all();

        return $data;
    }

    public static function getPages(): array
    {
        return ['index' => ManageCustomFields::route('/')];
    }
}
