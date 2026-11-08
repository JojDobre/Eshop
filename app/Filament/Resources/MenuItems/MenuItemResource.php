<?php

namespace App\Filament\Resources\MenuItems;

use App\Core\Menus\MenuLinkTypes;
use App\Core\Themes\Theme;
use App\Core\Themes\ThemeManager;
use App\Filament\AdminGroup;
use App\Filament\Concerns\FillsContentTranslations;
use App\Filament\Resources\MenuItems\Pages\ManageMenuItems;
use App\Models\MenuItem;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Ponuky (menu) v miestach, ktoré určuje šablóna vzhľadu (napr. hlavička, päta).
 */
class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'menus';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Content;
    }

    public static function getModelLabel(): string
    {
        return __('admin.menus.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.menus.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    /**
     * Miesta na ponuky v aktívnej šablóne.
     *
     * @return array<string, string>
     */
    public static function locations(): array
    {
        return collect(app(ThemeManager::class)->menus())->map(fn (array $labels, string $key) => Theme::label($labels, $key))->all();
    }

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return ['url' => __('admin.menus.types.url'), ...collect(app(MenuLinkTypes::class)->all())->map(fn ($type) => ($type->label)())->all()];
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('admin.menus.'.$key);

        return $schema->columns(2)->components([
            Select::make('location')->label($t('location'))->options(fn () => self::locations())->required()->native(false)->live()
                ->default(fn () => array_key_first(self::locations())),
            Select::make('parent_id')->label($t('parent'))->placeholder($t('no_parent'))
                ->options(fn (Get $get, ?MenuItem $record) => MenuItem::query()->where('location', $get('location'))->whereNull('parent_id')
                    ->when($record, fn ($query) => $query->whereKeyNot($record->id))
                    ->ordered()->get()->mapWithKeys(fn (MenuItem $item) => [$item->id => self::title($item)])->all()),
            Select::make('type')->label($t('type'))->options(fn () => self::types())->default('url')->required()->native(false)->live()
                ->afterStateUpdated(fn (Set $set) => $set('target', null)),
            Select::make('target')->label($t('target'))
                ->options(fn (Get $get) => ($type = app(MenuLinkTypes::class)->find((string) $get('type'))) ? ($type->options)() : [])
                ->searchable()->required()
                ->visible(fn (Get $get) => $get('type') !== 'url')
                ->key('target-select'),
            TextInput::make('target')->label($t('url'))->helperText($t('url_help'))->required()->maxLength(255)
                ->rule('regex:/^(https?:\/\/|\/|#|mailto:|tel:)/')
                ->visible(fn (Get $get) => $get('type') === 'url')
                ->key('target-url'),
            TextInput::make('label')->label($t('label_field'))->helperText($t('label_help'))->maxLength(120)
                ->required(fn (Get $get) => $get('type') === 'url'),
            Toggle::make('new_window')->label($t('new_window')),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('admin.menus.'.$key);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('parent'))
            ->columns([
                TextColumn::make('label')->label($t('label_field'))
                    ->state(fn (MenuItem $record) => ($record->parent_id ? '— ' : '').self::title($record)),
                TextColumn::make('location')->label($t('location'))->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => self::locations()[$state] ?? $state),
                TextColumn::make('type')->label($t('type'))->formatStateUsing(fn (string $state) => self::types()[$state] ?? $state),
                IconColumn::make('new_window')->label($t('new_window'))->boolean(),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->groups(['location'])
            ->filters([SelectFilter::make('location')->label($t('location'))->options(fn () => self::locations())])
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, MenuItem $record) => FillsContentTranslations::contentTranslationsForForm($record, $data)),
                DeleteAction::make(),
            ])
            ->emptyStateDescription($t('empty_help'))
            ->paginated(false);
    }

    /** Názov položky pre administráciu (vlastný alebo názov cieľa). */
    public static function title(MenuItem $item): string
    {
        if (filled($item->label)) {
            return (string) $item->label;
        }

        $type = app(MenuLinkTypes::class)->find($item->type);

        return ($type ? (($type->options)()[(string) $item->target] ?? null) : null) ?? (string) $item->target;
    }

    public static function getPages(): array
    {
        return ['index' => ManageMenuItems::route('/')];
    }
}
