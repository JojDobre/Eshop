<?php

namespace Modules\Reviews;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Product;
use Modules\Reviews\Models\Review;

/**
 * Súhrn hodnotení produktu: počet schválených hodnotení, priemer a rozdelenie podľa hviezdičiek.
 */
final class ReviewStats
{
    public static function refresh(int $productId): void
    {
        $row = Review::query()->approved()->where('product_id', $productId)
            ->selectRaw('count(*) as total, avg(rating) as average')->first();

        Product::query()->whereKey($productId)->update([
            'reviews_count' => (int) $row?->total,
            'rating_average' => $row?->total ? round((float) $row->average, 2) : null,
        ]);
    }

    /**
     * Počet hodnotení podľa hviezdičiek (5 → 1).
     *
     * @return array<int, int>
     */
    public static function distribution(int $productId): array
    {
        $counts = Review::query()->approved()->where('product_id', $productId)
            ->select('rating', DB::raw('count(*) as total'))->groupBy('rating')->pluck('total', 'rating');

        return collect([5, 4, 3, 2, 1])->mapWithKeys(fn (int $stars) => [$stars => (int) ($counts[$stars] ?? 0)])->all();
    }
}
