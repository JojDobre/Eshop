<?php

namespace Modules\Blog\Filament\Resources\Posts;

use App\Filament\AdminGroup;
use App\Filament\Forms\BlockBuilder;
use App\Filament\Tables\MissingTranslationsColumn;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Blog\Filament\Resources\Posts\Pages\CreatePost;
use Modules\Blog\Filament\Resources\Posts\Pages\EditPost;
use Modules\Blog\Filament\Resources\Posts\Pages\ListPosts;
use Modules\Blog\Models\Post;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'blog/posts';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Content;
    }

    public static function getNavigationLabel(): string
    {
        return __('blog::admin.nav');
    }

    public static function getModelLabel(): string
    {
        return __('blog::admin.post');
    }

    public static function getPluralModelLabel(): string
    {
        return __('blog::admin.posts');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('blog.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('blog::admin.'.$key);

        return $schema->columns(3)->components([
            Group::make()->columnSpan(2)->schema([
                Section::make()->schema([
                    TextInput::make('name')->label($t('name'))->required()->maxLength(200),
                    TextInput::make('slug')->label($t('slug'))->helperText(__('catalog::admin.slug_help'))->maxLength(200)
                        ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
                    Textarea::make('excerpt')->label($t('excerpt'))->helperText($t('excerpt_help'))->rows(3)->maxLength(500),
                ]),
                Section::make($t('content'))->description($t('content_help'))->schema([BlockBuilder::make()]),
                Section::make($t('links'))->description($t('links_help'))->collapsed()->schema([
                    Select::make('products')->label($t('products'))->relationship('products', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)->multiple()->searchable()->preload(),
                    Select::make('catalogCategories')->label($t('catalog_categories'))->relationship('catalogCategories', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->pathName())->multiple()->searchable()->preload(),
                ]),
                Section::make(__('catalog::admin.seo'))->collapsed()->schema([
                    TextInput::make('meta_title')->label(__('catalog::admin.meta_title'))->maxLength(70),
                    Textarea::make('meta_description')->label(__('catalog::admin.meta_description'))->rows(2)->maxLength(170),
                ]),
            ]),
            Group::make()->schema([
                Section::make()->schema([
                    Select::make('status')->label($t('status'))
                        ->options([Post::DRAFT => $t('statuses.draft'), Post::PUBLISHED => $t('statuses.published')])
                        ->default(Post::DRAFT)->selectablePlaceholder(false)->native(false),
                    DateTimePicker::make('published_at')->label($t('published_at'))->helperText($t('published_at_help'))->seconds(false),
                    Select::make('blog_category_id')->label($t('category'))->relationship('category', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)->preload(),
                    Select::make('tags')->label($t('tags'))->relationship('tags', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)->multiple()->preload()
                        ->createOptionForm([TextInput::make('name')->label($t('name'))->required()->maxLength(80)]),
                    Select::make('author_id')->label($t('author'))
                        ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->default(fn () => auth()->id()),
                    Toggle::make('comments_enabled')->label($t('comments_enabled'))->default(true),
                    FileUpload::make('cover_image')->label($t('cover_image'))
                        ->disk('public')->directory('blog')->visibility('public')
                        ->image()->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(8192)->imageResizeTargetWidth('2000')->imageResizeMode('contain')->imageResizeUpscale(false),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('blog::admin.'.$key);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('category')->withCount(['comments' => fn ($query) => $query->where('is_approved', false)]))
            ->columns([
                ImageColumn::make('cover_image')->label('')->disk('public')->square()->size(40),
                TextColumn::make('name')->label($t('name'))->searchable(query: fn ($query, string $search) => $query->where('name', 'like', '%'.$search.'%'))
                    ->description(fn (Post $record) => $record->category?->name),
                MissingTranslationsColumn::make(),
                TextColumn::make('status')->label($t('status'))->badge()
                    ->state(fn (Post $record) => $record->isScheduled() ? 'scheduled' : $record->status)
                    ->formatStateUsing(fn (string $state) => $t('statuses.'.$state))
                    ->color(fn (string $state) => ['published' => 'success', 'scheduled' => 'warning', 'draft' => 'gray'][$state] ?? 'gray'),
                TextColumn::make('published_at')->label($t('published_at'))->dateTime()->sortable()->placeholder('—'),
                TextColumn::make('comments_count')->label($t('pending_comments'))->badge()->color('warning'),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label($t('status'))->options([Post::DRAFT => $t('statuses.draft'), Post::PUBLISHED => $t('statuses.published')]),
                SelectFilter::make('blog_category_id')->label($t('category'))->relationship('category', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
