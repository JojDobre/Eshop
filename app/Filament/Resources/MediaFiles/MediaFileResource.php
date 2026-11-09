<?php

namespace App\Filament\Resources\MediaFiles;

use App\Core\Content\MediaLibrary;
use App\Filament\AdminGroup;
use App\Filament\Resources\MediaFiles\Pages\ManageMediaFiles;
use App\Models\MediaFile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;
use UnitEnum;

class MediaFileResource extends Resource
{
    protected static ?string $model = MediaFile::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'media';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Content;
    }

    public static function getModelLabel(): string
    {
        return __('admin.media.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.media.title');
    }

    public static function canAccess(): bool
    {
        return (bool) (auth()->user()?->can('media.manage') || auth()->user()?->can('settings.manage'));
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('admin.media.'.$key);

        return $schema->components([
            TextInput::make('name')->label($t('name'))->required()->maxLength(255),
            TextInput::make('alt')->label($t('alt'))->helperText($t('alt_help'))->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('admin.media.'.$key);

        return $table
            ->columns([
                ImageColumn::make('path')->label('')->disk('public')->imageSize(56)->square()
                    ->visible(true)->defaultImageUrl(null)
                    ->getStateUsing(fn (MediaFile $record) => $record->isImage() ? $record->path : null),
                TextColumn::make('name')->label($t('name'))->searchable()->weight('medium')
                    ->description(fn (MediaFile $record) => $record->width ? $record->width.' × '.$record->height.' px' : $record->mime_type),
                TextColumn::make('size')->label($t('size'))->formatStateUsing(fn (int $state) => Number::fileSize($state, 1))->sortable(),
                TextColumn::make('created_at')->label($t('uploaded_at'))->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('kind')->label($t('kind'))
                    ->options(['image' => $t('images'), 'document' => $t('documents')])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'image' => $query->where('mime_type', 'like', 'image/%'),
                        'document' => $query->where('mime_type', 'not like', 'image/%'),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('copyUrl')->label($t('copy_url'))->icon(Heroicon::OutlinedLink)->color('gray')
                    ->alpineClickHandler(fn (MediaFile $record) => 'window.navigator.clipboard.writeText('.json_encode($record->url()).'); $tooltip('.json_encode($t('copied')).', { timeout: 1500 })'),
                Action::make('open')->label($t('open'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                    ->url(fn (MediaFile $record) => $record->url(), shouldOpenInNewTab: true),
                EditAction::make(),
                // Používaný súbor (produkt, stránka, šablóna…) sa nedá zmazať – obchod by prišiel o obrázok.
                DeleteAction::make()
                    ->before(function (MediaFile $record, DeleteAction $action) use ($t): void {
                        if (app(MediaLibrary::class)->isUsed($record)) {
                            Notification::make()->danger()->title($t('in_use'))->body($t('in_use_help'))->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('deleteUnused')->label($t('delete_unused'))->icon(Heroicon::OutlinedTrash)->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) use ($t): void {
                        $library = app(MediaLibrary::class);
                        [$used, $unused] = $records->partition(fn (MediaFile $file) => $library->isUsed($file));
                        $unused->each->delete();

                        Notification::make()->success()
                            ->title($t('deleted', ['count' => $unused->count()]))
                            ->body($used->isNotEmpty() ? $t('skipped_in_use', ['count' => $used->count()]) : null)
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageMediaFiles::route('/')];
    }
}
