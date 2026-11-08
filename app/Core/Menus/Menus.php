<?php

namespace App\Core\Menus;

use App\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Položky ponúk pripravené pre šablónu: názov, adresa a podpoložky.
 */
class Menus
{
    /** @var array<string, list<array{label: string, url: string, new_window: bool, active: bool, children: list<array<string, mixed>>}>> */
    private array $cache = [];

    public function __construct(private readonly MenuLinkTypes $types) {}

    /**
     * Položky ponuky v danom mieste (prázdne pole = ponuka nie je nastavená, šablóna môže použiť náhradný obsah).
     *
     * @return list<array{label: string, url: string, new_window: bool, active: bool, children: list<array<string, mixed>>}>
     */
    public function items(string $location): array
    {
        if (isset($this->cache[$location])) {
            return $this->cache[$location];
        }

        try {
            if (! Schema::hasTable('menu_items')) {
                return [];
            }

            $items = MenuItem::query()->where('location', $location)->ordered()->get();
        } catch (Throwable) {
            return [];
        }

        return $this->cache[$location] = $this->tree($items->groupBy(fn (MenuItem $item) => $item->parent_id ?? 0), 0);
    }

    public function forget(): void
    {
        $this->cache = [];
    }

    /**
     * @param  Collection<int|string, Collection<int, MenuItem>>  $grouped
     * @return list<array<string, mixed>>
     */
    private function tree($grouped, int $parent, int $depth = 0): array
    {
        $result = [];
        $current = url()->current();

        foreach ($grouped->get($parent, collect()) as $item) {
            $resolved = $this->resolve($item);

            if ($resolved === null) {
                continue;
            }

            $children = $depth < 2 ? $this->tree($grouped, $item->id, $depth + 1) : [];

            $result[] = [
                'label' => $resolved['label'],
                'url' => $resolved['url'],
                'new_window' => $item->new_window,
                'active' => rtrim($resolved['url'], '/') === rtrim($current, '/'),
                'children' => $children,
            ];
        }

        return $result;
    }

    /**
     * @return array{url: string, label: string}|null
     */
    private function resolve(MenuItem $item): ?array
    {
        $label = (string) $item->label;

        if ($item->type === 'url') {
            return filled($item->target) ? ['url' => (string) $item->target, 'label' => $label ?: (string) $item->target] : null;
        }

        $type = $this->types->find($item->type);
        $target = $type && filled($item->target) ? ($type->resolve)((string) $item->target) : null;

        return $target === null ? null : ['url' => $target['url'], 'label' => $label ?: $target['label']];
    }
}
