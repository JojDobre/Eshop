<?php

namespace App\Core\Blocks;

/**
 * Hotové šablóny sekcií: skupiny blokov s ukážkovými textmi (v jazyku administrácie), ktoré sa vložia
 * do editora blokov a správca ich len prepíše. Obrázky sa nevkladajú – doplní si ich sám.
 */
final class SectionPresets
{
    /** Šablóny, ktoré obsahujú sekciu so stĺpcami (nedajú sa vložiť do stĺpca inej sekcie). */
    private const WITH_SECTIONS = ['features', 'testimonials', 'about', 'contact_split'];

    /**
     * @return array<string, string>
     */
    public static function options(bool $withoutSections = false): array
    {
        return collect(array_keys(self::definitions()))
            ->reject(fn (string $key) => $withoutSections && in_array($key, self::WITH_SECTIONS, true))
            ->mapWithKeys(fn (string $key) => [$key => __('admin.section_presets.items.'.$key)])
            ->all();
    }

    /**
     * Bloky zvolenej šablóny vo formáte editora blokov.
     *
     * @return list<array{type: string, data: array<string, mixed>}>
     */
    public static function blocks(string $key, bool $withoutSections = false): array
    {
        if ($withoutSections && in_array($key, self::WITH_SECTIONS, true)) {
            return [];
        }

        return self::definitions()[$key] ?? [];
    }

    /**
     * @return array<string, list<array{type: string, data: array<string, mixed>}>>
     */
    private static function definitions(): array
    {
        $c = fn (string $key) => __('admin.section_presets.content.'.$key);
        $block = fn (string $type, array $data) => ['type' => $type, 'data' => [...$data, 'visibility' => 'all']];
        $section = fn (string $layout, array $columns, string $background = 'none') => $block('section', [
            'layout' => $layout, 'background' => $background, 'width' => 'contained', 'padding' => 'medium', 'align' => 'start',
            ...collect($columns)->mapWithKeys(fn (array $blocks, int $index) => ['column_'.($index + 1) => $blocks])->all(),
        ]);

        return [
            'hero' => [
                $block('banner', ['title' => $c('hero_title'), 'text' => $c('hero_text'), 'button_label' => $c('hero_button'), 'button_url' => '/', 'align' => 'center', 'height' => 'large']),
            ],
            'benefits' => [
                $block('benefits', ['title' => null, 'items' => [
                    ['icon' => 'truck', 'title' => $c('benefit_shipping'), 'text' => $c('benefit_shipping_text')],
                    ['icon' => 'refresh', 'title' => $c('benefit_returns'), 'text' => $c('benefit_returns_text')],
                    ['icon' => 'shield', 'title' => $c('benefit_secure'), 'text' => $c('benefit_secure_text')],
                ]]),
            ],
            'features' => [
                $block('heading', ['title' => $c('features_title'), 'subtitle' => $c('features_subtitle'), 'size' => 'medium', 'align' => 'center']),
                $section('thirds', [
                    [$block('feature', ['icon' => 'leaf', 'title' => $c('feature_1'), 'text' => $c('feature_1_text')])],
                    [$block('feature', ['icon' => 'star', 'title' => $c('feature_2'), 'text' => $c('feature_2_text')])],
                    [$block('feature', ['icon' => 'chat', 'title' => $c('feature_3'), 'text' => $c('feature_3_text')])],
                ]),
            ],
            'products' => [
                $block('products', ['title' => $c('products_title'), 'source' => 'latest', 'limit' => 8]),
            ],
            'sale' => [
                $block('products', ['title' => $c('sale_title'), 'source' => 'sale', 'limit' => 4]),
                $block('buttons', ['items' => [['label' => $c('sale_button'), 'url' => '/', 'style' => 'secondary']], 'align' => 'center']),
            ],
            'testimonials' => [
                $block('heading', ['title' => $c('testimonials_title'), 'subtitle' => null, 'size' => 'medium', 'align' => 'center']),
                $section('thirds', [
                    [$block('quote', ['text' => $c('testimonial_1'), 'author' => $c('testimonial_1_author')])],
                    [$block('quote', ['text' => $c('testimonial_2'), 'author' => $c('testimonial_2_author')])],
                    [$block('quote', ['text' => $c('testimonial_3'), 'author' => $c('testimonial_3_author')])],
                ], 'light'),
            ],
            'about' => [
                $section('halves', [
                    [
                        $block('heading', ['title' => $c('about_title'), 'subtitle' => $c('about_subtitle'), 'size' => 'large', 'align' => 'left']),
                        $block('buttons', ['items' => [['label' => $c('about_button'), 'url' => '/', 'style' => 'primary']], 'align' => 'left']),
                    ],
                    [$block('text', ['title' => null, 'content' => '<p>'.$c('about_text').'</p>'])],
                ]),
            ],
            'cta' => [
                $block('cta', ['title' => $c('cta_title'), 'text' => $c('cta_text'), 'button_label' => $c('cta_button'), 'button_url' => '/']),
            ],
            'faq' => [
                $block('heading', ['title' => $c('faq_title'), 'subtitle' => null, 'size' => 'medium', 'align' => 'left']),
                $block('text', ['title' => null, 'content' => collect(range(1, 3))
                    ->map(fn (int $i) => '<h3>'.e($c('faq_q'.$i)).'</h3><p>'.e($c('faq_a'.$i)).'</p>')->implode('')]),
            ],
            'contact_split' => [
                $section('halves', [
                    [
                        $block('heading', ['title' => $c('contact_title'), 'subtitle' => $c('contact_subtitle'), 'size' => 'medium', 'align' => 'left']),
                        $block('text', ['title' => null, 'content' => '<p>'.$c('contact_text').'</p>']),
                    ],
                    [$block('feature', ['icon' => 'clock', 'title' => $c('contact_hours'), 'text' => $c('contact_hours_text')])],
                ], 'light'),
            ],
        ];
    }
}
