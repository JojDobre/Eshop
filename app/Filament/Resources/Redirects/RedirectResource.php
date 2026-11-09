<?php

namespace App\Filament\Resources\Redirects;

use App\Filament\AdminGroup;
use App\Filament\Pages\Settings\ManageGeneralSettings;
use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Models\Redirect;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    protected static ?int $navigationSort = 65;

    protected static ?string $slug = 'settings/redirects';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Settings;
    }

    public static function getModelLabel(): string
    {
        return __('admin.redirects.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.redirects.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('admin.redirects.'.$key);

        return $schema->columns(2)->components([
            TextInput::make('from_path')->label($t('from'))->helperText($t('from_help'))->required()->maxLength(500)
                ->dehydrateStateUsing(fn (string $state) => Redirect::normalizePath($state))
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule),
            TextInput::make('to_url')->label($t('to'))->helperText($t('to_help'))->required()->maxLength(1000),
            Select::make('status_code')->label($t('status'))->selectablePlaceholder(false)->default(301)
                ->options([301 => '301 – '.$t('permanent'), 302 => '302 – '.$t('temporary')]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('admin.redirects.'.$key);

        return $table
            ->columns([
                TextColumn::make('from_path')->label($t('from'))->searchable()->limit(60),
                TextColumn::make('to_url')->label($t('to'))->searchable()->limit(60),
                TextColumn::make('status_code')->label($t('status'))->badge()->color('gray'),
                IconColumn::make('is_automatic')->label($t('automatic'))->boolean(),
                TextColumn::make('hits')->label($t('hits'))->sortable()->alignEnd(),
                TextColumn::make('last_hit_at')->label($t('last_hit'))->since()->placeholder('—')->sortable(),
            ])
            ->filters([TernaryFilter::make('is_automatic')->label($t('automatic'))])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRedirects::route('/')];
    }

    /** V menu pod položkou „ManageGeneralSettings“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return ManageGeneralSettings::getNavigationLabel();
    }
}
