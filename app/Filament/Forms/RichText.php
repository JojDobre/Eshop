<?php

namespace App\Filament\Forms;

use App\Settings\BrandingSettings;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\TextColor;

/**
 * Editor formátovaného textu pre obsah obchodu (popisy, stránky, bloky): nadpisy, farba a zvýraznenie textu,
 * zarovnanie, obrázky (uložia sa aj do knižnice súborov), oddeľovač, stĺpce, tabuľky a rozbaľovacie časti.
 * Odkaz na YouTube alebo Vimeo v samostatnom riadku sa v obchode zobrazí ako video.
 */
final class RichText
{
    public static function make(string $name, string $directory = 'content'): RichEditor
    {
        return RichEditor::make($name)
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'textColor', 'highlight', 'link'],
                ['h2', 'h3', 'lead', 'small'],
                ['alignStart', 'alignCenter', 'alignEnd'],
                ['bulletList', 'orderedList', 'blockquote', 'horizontalRule'],
                ['attachFiles', 'table', 'grid', 'details'],
                ['undo', 'redo'],
            ])
            ->textColors(fn () => [
                // Farba značky obchodu (Nastavenia → Značka).
                'brand' => TextColor::make(__('admin.rich_text.brand'), $brand = rescue(fn () => app(BrandingSettings::class)->primaryColor(), '#4f46e5', false), darkColor: $brand),
                ...TextColor::getDefaults(),
            ])
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory('media/'.trim($directory, '/'))
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif'])
            ->fileAttachmentsMaxSize(8192)
            ->helperText(__('admin.rich_text.help'));
    }
}
