<?php

namespace App\Core\Blocks;

use App\Filament\Forms\BlockBuilder;
use App\Filament\Forms\RichText;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Základné bloky jadra: banner, text, obrázok, video, výhody obchodu, vlastný HTML kód.
 * Bloky s produktmi a kategóriami pridáva modul Katalóg.
 */
final class CoreBlocks
{
    /** Rozloženia sekcie: podiely šírky stĺpcov (mriežka 12). */
    public const SECTION_LAYOUTS = [
        'one' => [12],
        'halves' => [6, 6],
        'wide_left' => [8, 4],
        'wide_right' => [4, 8],
        'thirds' => [4, 4, 4],
        'quarters' => [3, 3, 3, 3],
    ];

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
            RichText::make('content', 'blocks')->label($t('content'))->required(),
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

        $types->register(new BlockType('quote', fn () => $t('types.quote'), fn () => [
            Textarea::make('text')->label($t('quote_text'))->rows(3)->required()->maxLength(1000),
            TextInput::make('author')->label($t('quote_author'))->maxLength(120),
            self::hidden(),
        ], icon: 'heroicon-o-chat-bubble-bottom-center-text'));

        $types->register(new BlockType('gallery', fn () => $t('types.gallery'), fn () => [
            TextInput::make('title')->label($t('title'))->maxLength(160),
            self::image('images')->multiple()->reorderable()->maxFiles(24)->required(),
            Select::make('columns')->label($t('columns'))->options(['2' => '2', '3' => '3', '4' => '4'])->default('3')->selectablePlaceholder(false),
            self::hidden(),
        ], icon: 'heroicon-o-squares-plus'));

        $types->register(new BlockType('cta', fn () => $t('types.cta'), fn () => [
            TextInput::make('title')->label($t('title'))->required()->maxLength(160),
            Textarea::make('text')->label($t('text'))->rows(2)->maxLength(500),
            TextInput::make('button_label')->label($t('button_label'))->required()->maxLength(60),
            TextInput::make('button_url')->label($t('button_url'))->required()->maxLength(255)->rule('regex:/^(https?:\/\/|\/|#)/'),
            self::hidden(),
        ], icon: 'heroicon-o-cursor-arrow-rays'));

        // Sekcia so stĺpcami: do každého stĺpca sa vkladajú ďalšie bloky (skladanie vlastných rozložení).
        $types->register(new BlockType('section', fn () => $t('types.section'), fn () => [
            // Nastavenia sekcie vedľa seba, pod nimi stĺpce s blokmi.
            Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])->schema([
                Select::make('layout')->label($t('grid.layout'))->live()->selectablePlaceholder(false)->default('halves')
                    ->options(collect(self::SECTION_LAYOUTS)->mapWithKeys(fn ($columns, $key) => [$key => $t('grid.layouts.'.$key)])->all()),
                Select::make('background')->label($t('grid.background'))->live()->selectablePlaceholder(false)->default('none')
                    ->options(collect(['none', 'light', 'brand', 'dark', 'image'])->mapWithKeys(fn ($key) => [$key => $t('grid.backgrounds.'.$key)])->all()),
                self::image('background_image')->label($t('grid.background_image'))->visible(fn (Get $get) => $get('background') === 'image'),
                Select::make('width')->label($t('grid.width'))->selectablePlaceholder(false)->default('contained')
                    ->options(collect(['narrow', 'contained', 'full'])->mapWithKeys(fn ($key) => [$key => $t('grid.widths.'.$key)])->all()),
                Select::make('padding')->label($t('grid.padding'))->selectablePlaceholder(false)->default('medium')
                    ->options(collect(['none', 'small', 'medium', 'large'])->mapWithKeys(fn ($key) => [$key => $t('grid.paddings.'.$key)])->all()),
                Select::make('align')->label($t('grid.align'))->selectablePlaceholder(false)->default('start')
                    ->options(['start' => $t('grid.align_start'), 'center' => $t('grid.align_center')]),
            ]),
            ...collect(range(1, 4))->map(fn (int $column) => BlockBuilder::make('column_'.$column, ['section'])
                ->label($t('grid.column').' '.$column)->hiddenLabel(false)->columnSpanFull()
                ->visible(fn (Get $get) => count(self::SECTION_LAYOUTS[$get('layout') ?? 'halves'] ?? [1]) >= $column))->all(),
            self::hidden(),
        ], icon: 'heroicon-o-view-columns'));

        $types->register(new BlockType('heading', fn () => $t('types.heading'), fn () => [
            TextInput::make('title')->label($t('title'))->required()->maxLength(200),
            Textarea::make('subtitle')->label($t('subtitle'))->rows(2)->maxLength(500),
            Select::make('size')->label($t('size'))->selectablePlaceholder(false)->default('medium')
                ->options(['small' => $t('size_small'), 'medium' => $t('size_medium'), 'large' => $t('size_large')]),
            Select::make('align')->label($t('align'))->selectablePlaceholder(false)->default('left')
                ->options(['left' => $t('align_left'), 'center' => $t('align_center'), 'right' => $t('align_right')]),
            self::hidden(),
        ], icon: 'heroicon-o-h1'));

        $types->register(new BlockType('image_text', fn () => $t('types.image_text'), fn () => [
            self::image('image')->required(),
            Select::make('image_side')->label($t('image_side'))->selectablePlaceholder(false)->default('left')
                ->options(['left' => $t('align_left'), 'right' => $t('align_right')]),
            TextInput::make('title')->label($t('title'))->maxLength(200),
            RichText::make('content', 'blocks')->label($t('content')),
            TextInput::make('button_label')->label($t('button_label'))->maxLength(60),
            TextInput::make('button_url')->label($t('button_url'))->maxLength(255)->rule('regex:/^(https?:\/\/|\/|#)/'),
            self::hidden(),
        ], icon: 'heroicon-o-photo'));

        $types->register(new BlockType('buttons', fn () => $t('types.buttons'), fn () => [
            Repeater::make('items')->label($t('items'))->schema([
                TextInput::make('label')->label($t('button_label'))->required()->maxLength(60),
                TextInput::make('url')->label($t('button_url'))->required()->maxLength(255)->rule('regex:/^(https?:\/\/|\/|#)/'),
                Select::make('style')->label($t('button_style'))->selectablePlaceholder(false)->default('primary')
                    ->options(['primary' => $t('button_primary'), 'secondary' => $t('button_secondary')]),
            ])->columns(3)->defaultItems(1)->maxItems(4),
            Select::make('align')->label($t('align'))->selectablePlaceholder(false)->default('left')
                ->options(['left' => $t('align_left'), 'center' => $t('align_center'), 'right' => $t('align_right')]),
            self::hidden(),
        ], icon: 'heroicon-o-cursor-arrow-ripple'));

        $types->register(new BlockType('feature', fn () => $t('types.feature'), fn () => [
            Select::make('icon')->label($t('icon'))->default('star')
                ->options(collect(['truck', 'shield', 'gift', 'refresh', 'chat', 'star', 'leaf', 'clock'])->mapWithKeys(fn ($icon) => [$icon => $t('icons.'.$icon)])->all()),
            TextInput::make('title')->label($t('title'))->required()->maxLength(120),
            Textarea::make('text')->label($t('text'))->rows(3)->maxLength(500),
            TextInput::make('url')->label($t('link'))->maxLength(255)->rule('regex:/^(https?:\/\/|\/|#)/'),
            self::hidden(),
        ], icon: 'heroicon-o-sparkles'));

        $types->register(new BlockType('spacer', fn () => $t('types.spacer'), fn () => [
            Select::make('height')->label($t('height'))->selectablePlaceholder(false)->default('medium')
                ->options(['small' => $t('height_small'), 'medium' => $t('height_medium'), 'large' => $t('height_large')]),
            Toggle::make('divider')->label($t('divider')),
            self::hidden(),
        ], icon: 'heroicon-o-minus'));

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
