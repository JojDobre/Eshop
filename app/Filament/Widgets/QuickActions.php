<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ManageBlocks;
use App\Filament\Pages\Themes;
use Filament\Widgets\Widget;
use Modules\Catalog\Filament\Resources\Products\ProductResource;
use Modules\Forms\Filament\Resources\Submissions\FormSubmissionResource;
use Modules\Forms\Models\FormSubmission;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Reviews\Filament\Resources\Reviews\ReviewResource;
use Modules\Reviews\Models\Review;

/**
 * Rýchle akcie na nástenke: najčastejšie úlohy na jedno kliknutie a čo čaká na vybavenie.
 * Zobrazia sa iba dlaždice modulov, ktoré sú zapnuté a na ktoré má používateľ oprávnenie.
 */
class QuickActions extends Widget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.quick-actions';

    /**
     * @return list<array{label: string, url: string, icon: string, count?: int|null, accent?: bool}>
     */
    public function tiles(): array
    {
        $tiles = [];
        $add = function (string $class, string $page, string $label, string $icon, ?\Closure $count = null, bool $accent = false, array $parameters = []) use (&$tiles): void {
            if (! class_exists($class) || ! $class::canAccess()) {
                return;
            }

            $tiles[] = [
                'label' => $label,
                // Zdroj (Resource) má stránky podľa názvu, samostatná stránka (Page) iba parametre.
                'url' => $page === '' ? $class::getUrl($parameters) : $class::getUrl($page, $parameters),
                'icon' => $icon,
                'count' => $count ? rescue($count, null, false) : null,
                'accent' => $accent,
            ];
        };

        $add(ProductResource::class, 'create', __('admin.quick.new_product'), 'heroicon-o-plus-circle', accent: true);
        $add(OrderResource::class, 'index', __('admin.quick.orders'), 'heroicon-o-shopping-bag',
            fn () => Order::query()->where('status', OrderStatus::New)->count(),
            parameters: ['filters' => ['status' => ['values' => [OrderStatus::New->value]]]]);
        $add(FormSubmissionResource::class, 'index', __('admin.quick.messages'), 'heroicon-o-inbox',
            fn () => FormSubmission::query()->unread()->count());
        $add(ReviewResource::class, 'index', __('admin.quick.reviews'), 'heroicon-o-star',
            fn () => Review::query()->where('status', Review::PENDING)->count());
        $add(ManageBlocks::class, '', __('admin.quick.home'), 'heroicon-o-squares-2x2');
        $add(Themes::class, '', __('admin.quick.design'), 'heroicon-o-paint-brush');

        return $tiles;
    }
}
