<?php

namespace App\Core\Content;

use App\Models\BlockLayout;
use App\Models\CustomerGroup;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Blog\Models\Post;
use Modules\Blog\Models\PostCategory;
use Modules\Blog\Models\PostTag;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\Models\Addon;
use Modules\Catalog\Models\AvailabilityStatus;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Manufacturer;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductLabel;
use Modules\Catalog\Models\ProductOption;
use Modules\Catalog\Models\ProductOptionValue;
use Modules\Catalog\Models\ProductType;
use Modules\Catalog\Models\Warehouse;
use Modules\Forms\Models\Form;
use Modules\Pages\Models\Page;
use Modules\Payments\Models\PaymentMethod;
use Modules\Reservations\Models\BookingService;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Tax\Models\TaxRate;
use Throwable;

/**
 * Doplní chýbajúce texty obsahu (názvy, popisy, adresy, bloky…) v jazyku z iného jazyka.
 *
 * Používa sa pri zmene predvoleného jazyka obchodu: obsah napísaný v pôvodnom jazyku by inak v novom
 * predvolenom jazyku chýbal (prázdne názvy, nenájdené stránky). Existujúce preklady sa neprepisujú.
 */
class ContentTranslationFiller
{
    /**
     * Modely s preložiteľným obsahom. E-mailové šablóny chýbajú zámerne (prázdna šablóna = predvolený text
     * v danom jazyku), verzie stránok sú archív.
     *
     * @var list<class-string<Model>>
     */
    public const MODELS = [
        Product::class,
        Category::class,
        Manufacturer::class,
        ProductOption::class,
        ProductOptionValue::class,
        ProductType::class,
        ProductLabel::class,
        AvailabilityStatus::class,
        Addon::class,
        Warehouse::class,
        CustomField::class,
        Page::class,
        Post::class,
        PostCategory::class,
        PostTag::class,
        ShippingMethod::class,
        PaymentMethod::class,
        TaxRate::class,
        Form::class,
        BookingService::class,
        BlockLayout::class,
        MenuItem::class,
        CustomerGroup::class,
    ];

    /**
     * @param  string|null  $source  Preferovaný zdrojový jazyk; ak v ňom text nie je, použije sa prvý vyplnený.
     * @return int Počet upravených záznamov.
     */
    public function fill(string $target, ?string $source = null): int
    {
        $changed = 0;

        foreach (self::MODELS as $class) {
            if (! class_exists($class)) {
                continue;
            }

            try {
                /** @var Model $model */
                $model = new $class;
                $table = $model->getTable();
                $columns = array_values(array_filter(
                    method_exists($model, 'getTranslatableAttributes') ? $model->getTranslatableAttributes() : [],
                    fn (string $column) => Schema::hasColumn($table, $column),
                ));

                if ($columns === [] || ! Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)->select([$model->getKeyName(), ...$columns])->orderBy($model->getKeyName())
                    ->chunk(500, function ($rows) use ($table, $columns, $target, $source, $model, &$changed): void {
                        foreach ($rows as $row) {
                            $update = [];

                            foreach ($columns as $column) {
                                $values = json_decode((string) $row->{$column}, true);

                                if (! is_array($values) || self::filled($values[$target] ?? null)) {
                                    continue;
                                }

                                $from = $source !== null && self::filled($values[$source] ?? null)
                                    ? $source
                                    : collect($values)->filter(fn ($value) => self::filled($value))->keys()->first();

                                if ($from !== null) {
                                    $values[$target] = $values[$from];
                                    $update[$column] = json_encode($values, JSON_UNESCAPED_UNICODE);
                                }
                            }

                            if ($update !== []) {
                                DB::table($table)->where($model->getKeyName(), $row->{$model->getKeyName()})->update($update);
                                $changed++;
                            }
                        }
                    });
            } catch (Throwable $e) {
                // Chyba jednej tabuľky (napr. modul bez tabuliek) nezastaví doplnenie ostatných.
                report($e);
            }
        }

        return $changed;
    }

    private static function filled(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== [];
    }
}
