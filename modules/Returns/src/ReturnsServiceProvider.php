<?php

namespace Modules\Returns;

use App\Core\Installer\Installation;
use App\Core\Mail\EmailTemplateDefinition;
use App\Core\Mail\EmailTemplates;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\View;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Modules\Orders\Models\Order;
use Modules\Returns\Filament\RelationManagers\ReturnRequestsRelationManager;
use Modules\Returns\Models\ReturnRequest;

class ReturnsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'returns';
    }

    public function register(): void
    {
        Order::resolveRelationUsing('returnRequests', fn (Order $order) => $order->hasMany(ReturnRequest::class)->latest('id'));
    }

    public function boot(): void
    {
        parent::boot();

        OrderResource::registerRelationManager(ReturnRequestsRelationManager::class);

        // Upozornenie obchodníka pred uplynutím lehoty na vybavenie (webový cron).
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->call(fn () => app(ReturnDeadlines::class)->warn())
                ->name('returns:deadline-warnings')
                ->dailyAt('07:15')
                ->when(fn () => app(Installation::class)->isInstalled());
        });

        // Odkazy na odstúpenie a reklamáciu a stav žiadostí na stránke objednávky.
        View::composer('theme::templates.order', function (\Illuminate\View\View $view): void {
            $order = $view->getData()['order'] ?? null;

            $view->with('returnRequests', $order instanceof Order ? $order->returnRequests()->get() : collect());
            $view->with('canRequestReturn', $order instanceof Order && ! $order->isCancelled());
        });

        $templates = $this->app->make(EmailTemplates::class);
        $sample = [
            'customer_name' => 'Jana Nováková', 'request_number' => 'R'.now()->format('Y').'00001', 'request_type' => ReturnType::Complaint->getLabel(),
            'request_status' => ReturnStatus::Accepted->getLabel(), 'request_items' => '1 × Ukážkový produkt', 'reason' => 'Nefunguje vypínač.',
            'resolution' => 'Tovar vymeníme za nový.', 'deadline' => now()->addDays(30)->format('j. n. Y'), 'order_number' => now()->format('Y').'00042',
            'order_url' => url('/'), 'admin_url' => url('/admin'),
        ];

        $templates->register(new EmailTemplateDefinition('returns.received', 'returns::mail_templates.received',
            ['customer_name', 'request_type', 'request_number', 'order_number', 'request_items', 'deadline', 'order_url', 'shop_name'], sample: $sample));
        $templates->register(new EmailTemplateDefinition('returns.notification', 'returns::mail_templates.notification',
            ['request_type', 'request_number', 'order_number', 'customer_name', 'request_items', 'reason', 'deadline', 'admin_url'], sample: $sample));
        $templates->register(new EmailTemplateDefinition('returns.deadline_warning', 'returns::mail_templates.deadline_warning',
            ['requests', 'admin_url', 'shop_name'], canBeDisabled: true, sample: [...$sample, 'requests' => '- R'.now()->format('Y').'00001 – do '.now()->addDays(2)->format('j. n. Y')]));
        $templates->register(new EmailTemplateDefinition('returns.status', 'returns::mail_templates.status',
            ['customer_name', 'request_type', 'request_number', 'request_status', 'resolution', 'order_url', 'shop_name'], canBeDisabled: true, sample: $sample));
    }
}
