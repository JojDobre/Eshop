<?php

namespace Modules\Catalog\Listeners;

use App\Core\Events\EshopInstalled;
use App\Core\Support\Price;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

/**
 * Ukážkový katalóg (pražiareň kávy), ak si ho obchodník vybral v inštalátore.
 */
class SeedDemoCatalog
{
    public function handle(EshopInstalled $event): void
    {
        if (! $event->withDemoData || Product::query()->exists()) {
            return;
        }

        $locale = in_array($event->locale, ['sk', 'cs', 'en'], true) ? $event->locale : 'en';
        $t = fn (array $texts) => [$event->locale => $texts[$locale]];

        $coffee = $this->category($t(['sk' => 'Káva', 'cs' => 'Káva', 'en' => 'Coffee']), 1);
        $beans = $this->category($t(['sk' => 'Zrnková káva', 'cs' => 'Zrnková káva', 'en' => 'Coffee beans']), 1, $coffee);
        $ground = $this->category($t(['sk' => 'Mletá káva', 'cs' => 'Mletá káva', 'en' => 'Ground coffee']), 2, $coffee);
        $tea = $this->category($t(['sk' => 'Čaj', 'cs' => 'Čaj', 'en' => 'Tea']), 2);
        $gear = $this->category($t(['sk' => 'Príslušenstvo', 'cs' => 'Příslušenství', 'en' => 'Accessories']), 3);

        $products = [
            [$beans, ['sk' => 'Etiópia Yirgacheffe 250 g', 'cs' => 'Etiopie Yirgacheffe 250 g', 'en' => 'Ethiopia Yirgacheffe 250 g'], '12.90', 40,
                ['sk' => 'Kvetinová káva s tónmi citrusov a jazmínu.', 'cs' => 'Květinová káva s tóny citrusů a jasmínu.', 'en' => 'Floral coffee with notes of citrus and jasmine.']],
            [$beans, ['sk' => 'Kolumbia Huila 250 g', 'cs' => 'Kolumbie Huila 250 g', 'en' => 'Colombia Huila 250 g'], '11.50', 35,
                ['sk' => 'Vyvážená káva s chuťou karamelu a červených jabĺk.', 'cs' => 'Vyvážená káva s chutí karamelu a červených jablek.', 'en' => 'Balanced coffee tasting of caramel and red apples.']],
            [$beans, ['sk' => 'Brazília Santos 1 kg', 'cs' => 'Brazílie Santos 1 kg', 'en' => 'Brazil Santos 1 kg'], '34.00', 12,
                ['sk' => 'Čokoládová a oriešková káva, ideálna na espresso.', 'cs' => 'Čokoládová a oříšková káva, ideální na espresso.', 'en' => 'Chocolatey, nutty coffee, ideal for espresso.']],
            [$ground, ['sk' => 'Ranná zmes, mletá 250 g', 'cs' => 'Ranní směs, mletá 250 g', 'en' => 'Morning blend, ground 250 g'], '9.90', 50,
                ['sk' => 'Jemne mletá zmes na filter a french press.', 'cs' => 'Jemně mletá směs na filtr a french press.', 'en' => 'Finely ground blend for filter and French press.']],
            [$tea, ['sk' => 'Zelený čaj Sencha 100 g', 'cs' => 'Zelený čaj Sencha 100 g', 'en' => 'Sencha green tea 100 g'], '7.50', 25,
                ['sk' => 'Japonský zelený čaj so sviežou trávovou chuťou.', 'cs' => 'Japonský zelený čaj se svěží travnatou chutí.', 'en' => 'Japanese green tea with a fresh, grassy taste.']],
            [$tea, ['sk' => 'Rooibos s vanilkou 100 g', 'cs' => 'Rooibos s vanilkou 100 g', 'en' => 'Vanilla rooibos 100 g'], '6.90', 0,
                ['sk' => 'Bezkofeínový čaj s jemnou vanilkovou vôňou.', 'cs' => 'Bezkofeinový čaj s jemnou vanilkovou vůní.', 'en' => 'Caffeine-free tea with a gentle vanilla aroma.']],
            [$gear, ['sk' => 'Ručný mlynček na kávu', 'cs' => 'Ruční mlýnek na kávu', 'en' => 'Manual coffee grinder'], '49.00', 8,
                ['sk' => 'Keramické mlecie kamene, nastaviteľná hrubosť mletia.', 'cs' => 'Keramické mlecí kameny, nastavitelná hrubost mletí.', 'en' => 'Ceramic burrs, adjustable grind size.']],
            [$gear, ['sk' => 'Sklenená French press 0,6 l', 'cs' => 'Skleněný French press 0,6 l', 'en' => 'Glass French press 0.6 l'], '24.90', 15,
                ['sk' => 'Klasická príprava kávy aj čaju pre 2 – 3 osoby.', 'cs' => 'Klasická příprava kávy i čaje pro 2–3 osoby.', 'en' => 'Classic brewing for coffee or tea, 2–3 cups.']],
        ];

        foreach ($products as [$category, $name, $price, $stock, $description]) {
            $product = Product::query()->create([
                'name' => $t($name),
                'short_description' => $t($description),
                'description' => [$event->locale => '<p>'.e($description[$locale]).'</p>'],
                'stock_quantity' => $stock,
                'track_stock' => true,
                'weight' => 300,
            ]);

            $product->setPrice($event->currency, Price::toMinor($price, $event->currency));
            $product->categories()->attach($category->id);
        }
    }

    /**
     * @param  array<string, string>  $name
     */
    private function category(array $name, int $position, ?Category $parent = null): Category
    {
        return Category::query()->create([
            'name' => $name,
            'position' => $position,
            'parent_id' => $parent?->id,
        ]);
    }
}
