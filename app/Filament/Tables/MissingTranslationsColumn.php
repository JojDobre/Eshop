<?php

namespace App\Filament\Tables;

use App\Core\Content\ShopLocales;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/**
 * Stĺpec so zoznamom jazykov, v ktorých záznamu chýba preklad (iba pri viacjazyčnom obchode).
 */
final class MissingTranslationsColumn
{
    public static function make(string $attribute = 'name'): TextColumn
    {
        return TextColumn::make('missing_translations')
            ->label(__('admin.translations.column'))
            ->state(function (Model $record) use ($attribute): array {
                $locales = app(ShopLocales::class);

                return collect($locales->enabled())
                    ->reject(fn (string $locale) => filled($record->getTranslation($attribute, $locale, false)))
                    ->map(fn (string $locale) => strtoupper($locales->urlCode($locale)))
                    ->values()
                    ->all();
            })
            ->badge()
            ->color('warning')
            ->placeholder('✓')
            ->tooltip(__('admin.translations.column_help'))
            ->visible(fn () => app(ShopLocales::class)->isMultilingual());
    }
}
