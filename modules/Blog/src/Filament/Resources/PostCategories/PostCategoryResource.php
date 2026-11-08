<?php

namespace Modules\Blog\Filament\Resources\PostCategories;

use App\Filament\AdminGroup;
use App\Filament\Concerns\FillsContentTranslations;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Blog\Filament\Resources\PostCategories\Pages\ManagePostCategories;
use Modules\Blog\Models\PostCategory;
use UnitEnum;

class PostCategoryResource extends Resource
{
    protected static ?string $model = PostCategory::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?int $navigationSort = 31;

    protected static ?string $slug = 'blog/categories';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Content;
    }

    public static function getModelLabel(): string
    {
        return __('blog::admin.category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('blog::admin.categories');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('blog.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('blog::admin.'.$key);

        return $schema->columns(1)->components([
            TextInput::make('name')->label($t('name'))->required()->maxLength(120),
            TextInput::make('slug')->label($t('slug'))->maxLength(140)->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
            Textarea::make('description')->label($t('description'))->rows(3)->maxLength(1000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('posts'))
            ->columns([
                TextColumn::make('name')->label(__('blog::admin.name')),
                TextColumn::make('posts_count')->label(__('blog::admin.posts'))->badge()->color('gray'),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, PostCategory $record) => FillsContentTranslations::contentTranslationsForForm($record, $data)),
                DeleteAction::make(),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePostCategories::route('/')];
    }
}
