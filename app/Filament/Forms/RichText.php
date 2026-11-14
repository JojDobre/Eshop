<?php

namespace App\Filament\Forms;

use App\Settings\BrandingSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\RichEditor\TextColor;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

/**
 * Editor formátovaného textu pre obsah obchodu (popisy, stránky, bloky): nadpisy, farba a zvýraznenie textu,
 * zarovnanie, obrázky (uložia sa aj do knižnice súborov), video z YouTube/Vimeo tlačidlom, oddeľovač, stĺpce,
 * tabuľky a rozbaľovacie časti. Video sa uloží ako odkaz v samostatnom odseku a v obchode sa zobrazí prehrávač.
 */
final class RichText
{
    /** Overenie odkazu na video (YouTube vrátane Shorts, Vimeo). */
    public const VIDEO_URL_PATTERN = '#^https?://(www\.|m\.)?(youtube\.com/(watch\?|shorts/|embed/)|youtu\.be/|vimeo\.com/|player\.vimeo\.com/)#i';

    public static function make(string $name, string $directory = 'content'): RichEditor
    {
        return self::base($name, $directory)
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'textColor', 'highlight', 'link'],
                ['h2', 'h3', 'lead', 'small'],
                ['alignStart', 'alignCenter', 'alignEnd'],
                ['bulletList', 'orderedList', 'blockquote', 'horizontalRule'],
                ['attachFiles', 'video', 'table', 'grid', 'details'],
                ['undo', 'redo'],
            ])
            ->helperText(__('admin.rich_text.help'));
    }

    /** Krátky text (napr. krátky popis produktu): základné formátovanie, zoznamy a odkazy. */
    public static function compact(string $name): RichEditor
    {
        return self::base($name, 'content')
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'textColor', 'link'],
                ['bulletList', 'orderedList'],
                ['undo', 'redo'],
            ])
            ->extraInputAttributes(['style' => 'min-height: 6rem']);
    }

    private static function base(string $name, string $directory): RichEditor
    {
        return RichEditor::make($name)
            ->tools([
                RichEditorTool::make('video')
                    ->label(__('admin.rich_text.video'))
                    ->icon(Heroicon::OutlinedPlayCircle)
                    ->action(),
            ])
            ->registerActions([self::videoAction()])
            ->textColors(fn () => [
                // Farba značky obchodu (Nastavenia → Značka).
                'brand' => TextColor::make(__('admin.rich_text.brand'), $brand = rescue(fn () => app(BrandingSettings::class)->primaryColor(), '#4f46e5', false), darkColor: $brand),
                ...TextColor::getDefaults(),
            ])
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory('media/'.trim($directory, '/'))
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif'])
            ->fileAttachmentsMaxSize(8192);
    }

    /**
     * Tlačidlo „Video“: vloží odkaz na video do samostatného odseku (v obchode sa z neho stane prehrávač).
     */
    private static function videoAction(): Action
    {
        return Action::make('video')
            ->modalHeading(__('admin.rich_text.video_heading'))
            ->modalWidth('lg')
            ->modalSubmitActionLabel(__('admin.rich_text.video_insert'))
            ->schema([
                TextInput::make('url')
                    ->label(__('admin.rich_text.video_url'))
                    ->helperText(__('admin.rich_text.video_url_help'))
                    ->placeholder('https://www.youtube.com/watch?v=…')
                    ->url()
                    ->regex(self::VIDEO_URL_PATTERN)
                    ->required()
                    ->autofocus(),
            ])
            ->action(function (array $arguments, array $data, RichEditor $component): void {
                $url = e(trim($data['url']));

                $component->runCommands(
                    [EditorCommand::make('insertContent', arguments: ['<p><a href="'.$url.'">▶ '.$url.'</a></p><p></p>'])],
                    editorSelection: $arguments['editorSelection'] ?? null,
                );
            });
    }
}
