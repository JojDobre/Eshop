<?php

namespace Modules\Blog\Filament\Resources\Comments;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Modules\Blog\Filament\Resources\Comments\Pages\ManageComments;
use Modules\Blog\Models\PostComment;
use UnitEnum;

/**
 * Komentáre k článkom: schválenie, skrytie, zmazanie.
 */
class CommentResource extends Resource
{
    protected static ?string $model = PostComment::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 32;

    protected static ?string $slug = 'blog/comments';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Content;
    }

    public static function getModelLabel(): string
    {
        return __('blog::admin.comment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('blog::admin.comments');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('blog.manage') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = PostComment::query()->where('is_approved', false)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('blog::admin.'.$key);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('post'))
            ->columns([
                TextColumn::make('name')->label($t('comment_author'))->description(fn (PostComment $record) => $record->email),
                TextColumn::make('content')->label($t('comment'))->limit(120)->wrap(),
                TextColumn::make('post.name')->label($t('post')),
                IconColumn::make('is_approved')->label($t('approved'))->boolean(),
                TextColumn::make('created_at')->label($t('date'))->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([TernaryFilter::make('is_approved')->label($t('approved'))])
            ->recordActions([
                Action::make('approve')->label($t('approve'))->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (PostComment $record) => ! $record->is_approved)
                    ->action(fn (PostComment $record) => $record->update(['is_approved' => true])),
                Action::make('hide')->label($t('hide'))->icon(Heroicon::OutlinedEyeSlash)->color('gray')
                    ->visible(fn (PostComment $record) => $record->is_approved)
                    ->action(fn (PostComment $record) => $record->update(['is_approved' => false])),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([
                BulkAction::make('approve')->label($t('approve'))->icon(Heroicon::OutlinedCheck)
                    ->action(fn (Collection $records) => $records->each->update(['is_approved' => true]))->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageComments::route('/')];
    }
}
