<?php

namespace Tests\Feature\Shop;

use App\Core\Privacy\DataRetention;
use App\Filament\Pages\Settings\ManagePrivacySettings;
use App\Settings\PrivacySettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Cart\Models\Cart;
use Modules\Customers\Models\Customer;
use Modules\Epr\EprSettings;
use Modules\Epr\Filament\Pages\EprReport;
use Modules\Epr\Packaging;
use Modules\Marketing\Models\Subscriber;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Pages\Filament\Resources\Pages\PageResource;
use Modules\Pages\Models\Page;

class LegalComplianceTest extends ShopTestCase
{
    private function order(): Order
    {
        $this->post(route('cart.add'), ['product_id' => $this->product('Vajcia', '0.40', attributes: ['packaging' => ['paper' => 5, 'plastic' => 2]])->id, 'quantity' => 10]);
        $this->post(route('checkout.store'), $this->checkoutData());

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_retention_anonymizes_old_orders_and_deletes_old_data(): void
    {
        $order = $this->order();
        $order->forceFill(['ip_address' => '1.2.3.4'])->save();
        Subscriber::query()->create(['email' => 'stary@example.com', 'status' => Subscriber::PENDING, 'source' => 'footer']);
        $customer = Customer::query()->create(['name' => 'Neaktívny', 'email' => 'neaktivny@example.com', 'password' => 'tajne123']);

        // Nič nie je dosť staré.
        app(DataRetention::class)->run();
        $this->assertSame('jana@example.com', $order->fresh()->email);

        $this->travel(11 * 365)->days();
        $settings = app(PrivacySettings::class);
        $settings->retention = ['inactive_customers' => ['enabled' => true, 'days' => 1095]];
        $settings->save();

        $results = app(DataRetention::class)->run();

        $order->refresh();
        $this->assertSame('order-'.$order->id.'@anonymized.invalid', $order->email);
        $this->assertSame('—', $order->billing_name);
        $this->assertNull($order->ip_address);
        $this->assertGreaterThan(0, $order->total);
        $this->assertSame(1, $results['orders']);
        $this->assertSame(0, Subscriber::query()->count());
        $this->assertSame(0, Cart::query()->count());
        $this->assertNull(Customer::query()->find($customer->id));
    }

    public function test_disabled_rule_is_skipped_and_admin_page_saves_settings(): void
    {
        $order = $this->order();
        $this->travel(11 * 365)->days();

        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/privacy')->assertOk()->assertSee(__('admin.privacy.policies.orders'));
        Livewire::test(ManagePrivacySettings::class)
            ->set('data.retention.orders.enabled', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertArrayNotHasKey('orders', app(DataRetention::class)->run());
        $this->assertSame('jana@example.com', $order->fresh()->email);
    }

    public function test_epr_report_sums_product_and_shipment_packaging(): void
    {
        $settings = app(EprSettings::class);
        $settings->shipment_packaging = ['paper' => 150];
        $settings->save();

        $this->order();
        $cancelled = $this->order();
        $cancelled->update(['status' => OrderStatus::Cancelled]);

        $report = app(Packaging::class)->report(Carbon::today(), Carbon::today());

        $this->assertSame(1, $report['orders']);
        $this->assertSame(['products' => 50, 'shipments' => 150, 'total' => 200], $report['materials']['paper']);
        $this->assertSame(20, $report['materials']['plastic']['total']);

        $this->actingAs($this->superAdmin());
        $this->get('/admin/epr')->assertOk();
        Livewire::test(EprReport::class)
            ->set('data.from', today()->toDateString())->set('data.to', today()->toDateString())
            ->assertSee('0,200')
            ->callAction('download')
            ->assertFileDownloaded('epr-'.today()->format('Ymd').'-'.today()->format('Ymd').'.csv');
    }

    public function test_accessibility_statement_is_a_legal_page(): void
    {
        // Kostra sa vytvorí pri inštalácii (a migráciou v existujúcich obchodoch).
        $page = Page::query()->where('role', Page::ROLE_ACCESSIBILITY)->sole();

        $this->get($page->url())->assertOk()->assertSee('WCAG 2.1')->assertSee('Slovenskú obchodnú inšpekciu');
        $this->get('/')->assertSee('Vyhlásenie o prístupnosti');
        $this->assertArrayHasKey(Page::ROLE_ACCESSIBILITY, PageResource::roleOptions());
        $this->assertSame(1, DB::table('pages')->where('role', 'accessibility')->count());
    }
}
