<?php

namespace Modules\Blog\Filament\Resources\Posts\Pages;

use App\Filament\Concerns\FillsContentTranslations;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Blog\Filament\Resources\Posts\PostResource;
use Modules\Blog\Models\Post;
use Modules\Blog\Models\PostVersion;

class EditPost extends EditRecord
{
    use FillsContentTranslations;

    protected static string $resource = PostResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === Post::PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        /** @var Post $post */
        $post = $this->getRecord();
        $t = fn (string $key) => __('blog::admin.'.$key);

        return [
            ...$this->localeSwitcherActions(),
            // Náhľad funguje aj pre koncept a naplánovaný článok (tajný odkaz, neindexuje sa).
            Action::make('preview')->label($t('preview'))->icon(Heroicon::OutlinedEye)->color('gray')
                ->url(fn () => $post->isPublished() ? $post->url() : $post->previewUrl(), shouldOpenInNewTab: true),
            Action::make('versions')->label($t('versions'))->icon(Heroicon::OutlinedClock)->color('gray')
                ->modalDescription($t('versions_help'))
                ->schema([
                    Select::make('version')->label($t('version'))->required()
                        ->options(fn () => $post->versions()->with('user')->get()->mapWithKeys(fn (PostVersion $version) => [
                            $version->id => '#'.$version->version.' · '.$version->created_at?->format('j. n. Y H:i').($version->user ? ' · '.$version->user->name : ''),
                        ])->all()),
                ])
                ->action(function (array $data) use ($post, $t): void {
                    $version = $post->versions()->findOrFail($data['version']);
                    $post->restoreVersion($version);
                    Notification::make()->success()->title($t('restored'))->send();
                    $this->redirect(PostResource::getUrl('edit', ['record' => $post]));
                }),
            DeleteAction::make(),
        ];
    }
}
