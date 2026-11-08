<?php

namespace App\Core\Blocks;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

/**
 * Základné bloky jadra: banner, text, obrázok, video, výhody obchodu, vlastný HTML kód.
 * Bloky s produktmi a kategóriami pridáva modul Katalóg.
 */
final class CoreBlocks
{
    public static function register(BlockTypes $types): void
    {
        $t = fn (string $key) => __('admin.blocks.'.$key);

        $types->register(new BlockType('banner', fn () => $t('types.banner'), fn () => [
            TextInput::make('title')->label($t('title'))->required()->maxLength(160),
            Textarea::make('text')->label($t('text'))->rows(2)->maxLength(500),
            TextInput::make('button_label')->label($t('button_label'))->maxLength(60),
            TextInput::make('button_url')->label($t('button_url'))->maxLength(255)->rule('regex:/^(https?:\/\/|\/|#)/'),
            self::image('image'),
            Select::make('align')->label($t('align'))->options(['left' => $t('align_left'), 'center' => $t('align_center')])->default('left')->selectablePlaceholder(false),
            Select::make('height')->label($t('height'))->options(['small' => $t('height_small'), 'medium' => $t('height_medium'), 'large' => $t('height_large')])->default('medium')->selectablePlaceholder(false),
            self::hidden(),
        ], icon: 'heroicon-o-photo'));

        $types->register(new BlockType('text', fn () => $t('types.text'), fn () => [
            TextInput::make('title')->label($t('title'))->maxLength(160),
            RichEditor::make('content')->label($t('content'))->required()
                ->toolbarButtons([['bold', 'italic', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList'], ['undo', 'redo']]),
            self::hidden(),
        ], icon: 'heroicon-o-document-text'));

        $types->register(new BlockType('image', fn () => $t('types.image'), fn () => [
            self::image('image')->required(),
            TextInput::make('alt')->label($t('alt'))->helperText($t('alt_help'))->maxLength(200),
            TextInput::make('url')->label($t('link'))->maxLength(255)->rule('regex:/^(https?:\/\/|\/|#)/'),
            Toggle::make('full_width')->label($t('full_width')),
            self::hidden(),
        ], icon: 'heroicon-o-photo'));

        $types->register(new BlockType('video', fn () => $t('types.video'), fn () => [
            TextInput::make('title')->label($t('title'))->maxLength(160),
            TextInput::make('url')->label($t('video_url'))->helperText($t('video_url_help'))->required()->url()->maxLength(255),
            self::hidden(),
        ], prepare: fn (array $data) => [...$data, 'embed' => self::videoEmbed((string) ($data['url'] ?? ''))], icon: 'heroicon-o-play'));

        $types->register(new BlockType('benefits', fn () => $t('types.benefits'), fn () => [
            TextInput::make('title')->label($t('title'))->maxLength(160),
            Repeater::make('items')->label($t('items'))->schema([
                Select::make('icon')->label($t('icon'))->options(collect(['truck', 'shield', 'gift', 'refresh', 'chat', 'star', 'leaf', 'clock'])->mapWithKeys(fn ($icon) => [$icon => $t('icons.'.$icon)])->all())->default('truck'),
                TextInput::make('title')->label($t('title'))->required()->maxLength(80),
                TextInput::make('text')->label($t('text'))->maxLength(200),
            ])->columns(3)->maxItems(6)->defaultItems(3),
            self::hidden(),
        ], icon: 'heroicon-o-check-badge'));

        $types->register(new BlockType('html', fn () => $t('types.html'), fn () => [
            Textarea::make('html')->label($t('html'))->helperText($t('html_help'))->rows(8)->required(),
            self::hidden(),
        ], icon: 'heroicon-o-code-bracket'));
    }

    public static function image(string $name): FileUpload
    {
        return FileUpload::make($name)->label(__('admin.blocks.image'))
            ->disk('public')->directory('blocks')->visibility('public')
            ->image()->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
            ->maxSize(8192)->imageResizeTargetWidth('2400')->imageResizeMode('contain')->imageResizeUpscale(false);
    }

    /** Prepínač „skryť blok“ (blok ostane uložený, ale nezobrazí sa). */
    public static function hidden(): Toggle
    {
        return Toggle::make('hidden')->label(__('admin.blocks.hidden'));
    }

    /**
     * Adresa na vloženie videa z YouTube (bez sledovacích cookies) alebo Vimeo.
     */
    public static function videoEmbed(string $url): ?string
    {
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $match)) {
            return 'https://www.youtube-nocookie.com/embed/'.$match[1];
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $match)) {
            return 'https://player.vimeo.com/video/'.$match[1].'?dnt=1';
        }

        return null;
    }
}
