<?php

namespace Modules\Reviews;

use App\Core\Features\FeatureToggle;
use App\Core\Features\FeatureToggles;
use App\Core\Installer\Installation;
use App\Core\Mail\EmailTemplateDefinition;
use App\Core\Mail\EmailTemplates;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Modules\Catalog\Filament\ProductRelations;
use Modules\Catalog\Models\Product;
use Modules\Customers\Models\Customer;
use Modules\Orders\Events\OrderStatusChanged;
use Modules\Reviews\Filament\ProductReviewsRelationManager;
use Modules\Reviews\Listeners\ScheduleReviewRequest;
use Modules\Reviews\Models\Review;

class ReviewsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'reviews';
    }

    public function boot(): void
    {
        parent::boot();

        Product::resolveRelationUsing('reviews', fn (Product $product) => $product->hasMany(Review::class));
        Event::listen(OrderStatusChanged::class, ScheduleReviewRequest::class);
        ProductRelations::add(ProductReviewsRelationManager::class);

        $this->app->make(FeatureToggles::class)->register(new FeatureToggle(
            key: 'reviews',
            group: 'catalog',
            label: fn () => __('reviews::admin.feature'),
            description: fn () => __('reviews::admin.feature_help'),
            isEnabled: fn () => ReviewSettings::isEnabled(),
            set: function (bool $enabled): void {
                $settings = app(ReviewSettings::class);
                $settings->enabled = $enabled;
                $settings->save();
            },
            settingsUrl: fn () => url('/admin/settings/reviews'),
            sizes: ['medium' => true, 'large' => true],
        ));

        // Hviezdičky vo výpise produktov a hodnotenia na detaile produktu.
        View::composer('theme::*', fn (\Illuminate\View\View $view) => $view->with('reviewsEnabled', ReviewSettings::isEnabled()));
        View::composer('theme::templates.product', function (\Illuminate\View\View $view): void {
            $product = $view->getData()['product'] ?? null;

            if (! $product instanceof Product || ! ReviewSettings::isEnabled()) {
                return;
            }

            $customer = auth('customer')->user();
            $reviews = app(Reviews::class);

            $view->with('productReviews', [
                'items' => Review::query()->approved()->where('product_id', $product->id)->latest()->limit(20)->get(),
                'count' => (int) $product->reviews_count,
                'average' => $product->rating_average !== null ? (float) $product->rating_average : null,
                'distribution' => ReviewStats::distribution($product->id),
                'canReview' => $reviews->canReview($product, $customer instanceof Customer ? $customer : null),
                'verifiedOnly' => app(ReviewSettings::class)->verified_only,
                'verificationInfo' => $reviews->verificationInfo(),
                'action' => route('reviews.store', $product),
            ]);
        });

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->call(fn () => app(SendReviewRequests::class)->handle())
                ->name('reviews:send-requests')
                ->hourly()
                ->withoutOverlapping(30)
                ->when(fn () => app(Installation::class)->isInstalled());
        });

        $this->app->make(EmailTemplates::class)->register(new EmailTemplateDefinition('reviews.request', 'reviews::mail_templates.request',
            ['customer_name', 'order_number', 'review_url', 'shop_name'], canBeDisabled: true,
            sample: ['customer_name' => 'Jana Nováková', 'order_number' => now()->format('Y').'00042', 'review_url' => url('/hodnotenie/1')]));
    }
}
