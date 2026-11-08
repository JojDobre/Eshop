<?php

namespace App\Filament\Resources\MediaFiles\Pages;

use App\Filament\Resources\MediaFiles\MediaFileResource;
use App\Models\MediaFile;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageMediaFiles extends ManageRecords
{
    protected static string $resource = MediaFileResource::class;

    protected function getHeaderActions(): array
    {
        $t = fn (string $key, array $replace = []) => __('admin.media.'.$key, $replace);

        return [
            Action::make('upload')
                ->label($t('upload'))
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->schema([
                    FileUpload::make('files')
                        ->label($t('files'))
                        ->helperText($t('files_help'))
                        ->disk('public')
                        ->directory(MediaFile::DIRECTORY.'/'.now()->format('Y/m'))
                        ->multiple()
                        ->maxFiles(20)
                        ->maxSize(10240)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'])
                        ->preserveFilenames(false)
                        ->storeFileNamesIn('original_names')
                        ->required(),
                ])
                ->action(function (array $data) use ($t): void {
                    foreach ((array) $data['files'] as $path) {
                        MediaFile::query()->create([
                            'path' => $path,
                            'name' => $data['original_names'][$path] ?? basename($path),
                            'uploaded_by' => auth()->id(),
                        ]);
                    }

                    Notification::make()->success()->title($t('uploaded', ['count' => count((array) $data['files'])]))->send();
                }),
        ];
    }
}
