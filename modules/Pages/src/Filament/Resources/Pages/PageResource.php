<?php

namespace Modules\Pages\Filament\Resources\Pages;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Modules\Pages\Filament\Resources\Pages\Pages\CreatePage;
use Modules\Pages\Filament\Resources\Pages\Pages\EditPage;
use Modules\Pages\Filament\Resources\Pages\Pages\ListPages;
use Modules\Pages\Models\Page;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Content;
    }

    public static function getModelLabel(): string
    {
        return __('pages::admin.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pages::admin.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('pages.manage') ?? false;
    }

    /**
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        return collect([Page::ROLE_TERMS, Page::ROLE_PRIVACY, Page::ROLE_SHIPPING, Page::ROLE_CONTACT])
            ->mapWithKeys(fn (string $role) => [$role => __('pages::admin.roles.'.$role)])
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('pages::admin.'.$key);

        return $schema->columns(3)->components([
            Group::make()->columnSpan(2)->schema([
                Section::make()->schema([
                    TextInput::make('name')->label($t('name'))->required()->maxLength(160),
                    TextInput::make('slug')->label($t('slug'))->helperText(__('catalog::admin.slug_help'))->maxLength(180)
                        ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
                    RichEditor::make('content')->label($t('content'))
                        ->toolbarButtons([['bold', 'italic', 'underline', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote', 'table'], ['undo', 'redo']]),
                ]),
                Section::make($t('seo'))->collapsed()->schema([
                    TextInput::make('meta_title')->label($t('meta_title'))->maxLength(70),
                    Textarea::make('meta_description')->label($t('meta_description'))->rows(2)->maxLength(170),
                ]),
            ]),
            Group::make()->schema([
                Section::make()->schema([
                    Toggle::make('is_visible')->label($t('is_visible'))->default(true),
                    Toggle::make('show_in_footer')->label($t('show_in_footer'))->default(true),
                    Select::make('role')->label($t('role'))->helperText($t('role_help'))
                        ->options(self::roleOptions())
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule)
                        ->native(false),
                    TextInput::make('position')->label($t('position'))->integer()->default(0)->minValue(0),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('pages::admin.'.$key);

        return $table
            ->columns([
                TextColumn::make('name')->label($t('name'))
                    ->description(fn (Page $record) => $record->role ? __('pages::admin.roles.'.$record->role) : null),
                IconColumn::make('show_in_footer')->label($t('show_in_footer'))->boolean(),
                ToggleColumn::make('is_visible')->label($t('is_visible')),
                TextColumn::make('updated_at')->label($t('updated_at'))->dateTime()->sortable(),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
