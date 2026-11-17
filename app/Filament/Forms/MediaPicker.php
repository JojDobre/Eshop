<?php

namespace App\Filament\Forms;

use App\Core\Content\MediaLibrary;
use App\Core\Media\ResponsiveImage;
use App\Models\MediaFile;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Prepojenie polí na nahrávanie obrázkov s knižnicou médií: každý nahratý obrázok sa zaradí do knižnice
 * a pri poli pribudne tlačidlo „Vybrať z knižnice“ (bez opätovného nahrávania).
 */
final class MediaPicker
{
    public static function register(): void
    {
        FileUpload::configureUsing(function (FileUpload $upload): void {
            $upload
                ->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file): ?string {
                    $path = $component->saveUploadedFile($file);

                    if ($path && self::isImageField($component)) {
                        app(MediaLibrary::class)->register($path, $file->getClientOriginalName());
                        // Zmenšené verzie hneď po nahratí, aby boli obrázky v obchode rýchle od prvej návštevy.
                        rescue(fn () => app(ResponsiveImage::class)->generateAll($path), report: true);
                    }

                    return $path;
                })
                ->hintAction(self::action());
        });

        RichEditor::configureUsing(fn (RichEditor $editor) => $editor
            ->saveUploadedFileAttachmentUsing(function (RichEditor $component, TemporaryUploadedFile $file): mixed {
                $path = $file->store($component->getFileAttachmentsDirectory(), [
                    'disk' => $component->getFileAttachmentsDiskName(),
                    'mimetype' => $file->getMimeType(),
                ]);

                if ($path && $component->getFileAttachmentsDiskName() === 'public') {
                    app(MediaLibrary::class)->register($path, $file->getClientOriginalName());
                }

                return $path;
            }));
    }

    /** Pole na verejné obrázky (nie súbory na stiahnutie, zálohy či balíky). */
    public static function isImageField(FileUpload $component): bool
    {
        $types = $component->getAcceptedFileTypes() ?? [];

        return $component->getDiskName() === 'public'
            && $types !== []
            && collect($types)->every(fn (string $type) => str_starts_with($type, 'image/'));
    }

    public static function action(): Action
    {
        return Action::make('pickFromMediaLibrary')
            ->label(__('admin.media.pick'))
            ->icon(Heroicon::OutlinedPhoto)
            ->link()
            ->visible(fn (FileUpload $component) => self::isImageField($component) && ! $component->isDisabled())
            ->modalHeading(__('admin.media.pick_heading'))
            ->modalWidth('5xl')
            ->modalSubmitActionLabel(__('admin.media.use_selected'))
            // Hlavička a tlačidlo „Použiť vybrané“ ostanú viditeľné aj pri dlhom zozname obrázkov.
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->schema(fn (FileUpload $component) => [
                MediaLibraryField::make('files')->hiddenLabel()
                    ->items(fn () => self::items())
                    ->multiple($component->isMultiple())
                    ->helperText(__('admin.media.pick_help'))
                    ->required(),
            ])
            ->action(function (array $data, FileUpload $component): void {
                $paths = MediaFile::query()->whereIn('id', (array) $data['files'])->pluck('path')->all();

                if ($paths === []) {
                    return;
                }

                $state = $component->isMultiple() ? (array) $component->getRawState() : [];

                foreach ($component->isMultiple() ? $paths : [reset($paths)] as $path) {
                    if (! in_array($path, $state, true)) {
                        $state[(string) Str::uuid()] = $path;
                    }
                }

                $component->rawState($state);
                $component->callAfterStateUpdated();
            });
    }

    /**
     * Obrázky knižnice od najnovších (mriežka ich stránkuje po 24).
     *
     * @return list<array{id: int, url: string, name: string}>
     */
    private static function items(): array
    {
        app(MediaLibrary::class)->syncOccasionally();

        return MediaFile::query()->where('mime_type', 'like', 'image/%')->latest('id')->limit(2000)->get()
            ->map(fn (MediaFile $file) => ['id' => $file->id, 'url' => $file->url(), 'name' => (string) $file->name])
            ->values()
            ->all();
    }
}
