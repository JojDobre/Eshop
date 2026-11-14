<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Vzhľad stránky alebo kategórie v obchode: klasický nadpis, banner s obrázkom (nadpis na obrázku) alebo bez
 * nadpisu (napr. keď stránku tvoria iba bloky), šírka obsahu a omrvinková navigácia.
 */
final class DisplayOptions
{
    public static function section(bool $category = false): Section
    {
        $t = fn (string $key) => __('admin.display.'.$key);

        return Section::make($t('section'))
            ->description($t('section_help'))
            ->collapsed()
            ->statePath('display')
            ->columns(['default' => 1, 'sm' => 2])
            ->schema(array_values(array_filter([
                Select::make('header')->label($t('header'))->live()->selectablePlaceholder(false)->default('title')
                    ->options(['title' => $t('header_title'), 'hero' => $t('header_hero'), 'none' => $t('header_none')]),
                Select::make('hero_height')->label($t('hero_height'))->selectablePlaceholder(false)->default('medium')
                    ->options(['small' => $t('height_small'), 'medium' => $t('height_medium'), 'large' => $t('height_large')])
                    ->visible(fn (Get $get) => $get('header') === 'hero'),
                FileUpload::make('hero_image')->label($t('hero_image'))
                    ->helperText($category ? $t('hero_image_category_help') : null)
                    ->disk('public')->directory('pages')->visibility('public')
                    ->image()->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->maxSize(8192)->imageResizeTargetWidth('2400')->imageResizeMode('contain')->imageResizeUpscale(false)
                    ->visible(fn (Get $get) => $get('header') === 'hero')
                    ->columnSpanFull(),
                Textarea::make('hero_text')->label($t('hero_text'))->rows(2)->maxLength(300)
                    ->visible(fn (Get $get) => $get('header') === 'hero')
                    ->columnSpanFull(),
                $category ? null : Select::make('width')->label($t('width'))->selectablePlaceholder(false)->default('narrow')
                    ->options(['narrow' => $t('width_narrow'), 'wide' => $t('width_wide')]),
                Toggle::make('breadcrumbs')->label($t('breadcrumbs'))->default(true)->inline(false),
                $category ? Toggle::make('show_description')->label($t('show_description'))->default(true)->inline(false) : null,
            ])));
    }
}
