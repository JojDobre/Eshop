<?php

namespace Tests\Feature\Shop;

use App\Core\Demo\DemoException;
use App\Core\Demo\DemoExporter;
use App\Core\Demo\DemoImporter;
use App\Models\BlockLayout;
use App\Models\MenuItem;
use App\Models\SavedBlock;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Modules\Blog\Models\Post;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\VariantGenerator;
use Modules\Forms\Models\Form;
use Modules\Orders\Models\Order;
use Modules\Pages\Models\Page;
use ZipArchive;

class DemoPackageTest extends ShopTestCase
{
    private string $image = 'products/demo-test-image.jpg';

    protected function setUp(): void
    {
        parent::setUp();
        config(['eshop.backup_path' => sys_get_temp_dir().'/demo-test-backups-'.uniqid()]);
        $image = imagecreatetruecolor(20, 20);
        File::ensureDirectoryExists(dirname(Storage::disk('public')->path($this->image)));
        imagejpeg($image, Storage::disk('public')->path($this->image));
    }

    protected function tearDown(): void
    {
        Storage::disk('public')->delete($this->image);
        File::deleteDirectory((string) config('eshop.backup_path'));
        parent::tearDown();
    }

    /** Obchod s obsahom, ktorý sa navzájom odkazuje. */
    private function seedShop(): void
    {
        $parent = $this->category('Keramika');
        $child = $this->category('Hrnčeky', $parent);
        $mug = $this->product('Hrnček', '18.00', 5, $child, ['images' => [$this->image],
            'description' => '<p><img src="'.Storage::disk('public')->url($this->image).'"></p>']);
        $size = $mug->options()->create(['name' => 'Veľkosť', 'position' => 1]);
        $size->values()->createMany([['value' => 'Malý', 'position' => 1], ['value' => 'Veľký', 'position' => 2]]);
        app(VariantGenerator::class)->generate($mug);

        $form = Form::query()->sole();
        $saved = SavedBlock::query()->create(['name' => 'Výhody', 'blocks' => [['type' => 'heading', 'data' => ['title' => 'Ručná výroba']]]]);
        BlockLayout::query()->create(['key' => BlockLayout::HOME, 'blocks' => [
            ['type' => 'products', 'data' => ['source' => 'category', 'category_id' => $child->id]],
            ['type' => 'form', 'data' => ['form_id' => $form->id]],
            ['type' => 'saved', 'data' => ['saved_block_id' => $saved->id]],
        ]]);
        Page::query()->create(['name' => 'O nás', 'content' => '<p>Náš príbeh</p>', 'is_visible' => true]);
        $post = Post::query()->create(['name' => 'Ako vzniká hrnček', 'status' => Post::PUBLISHED, 'published_at' => now()->subDay(),
            'cover_image' => $this->image, 'blocks' => [['type' => 'text', 'data' => ['content' => '<p>Postup</p>']]]]);
        $post->products()->attach($mug->id);
        MenuItem::query()->create(['location' => 'header', 'type' => 'category', 'target' => (string) $parent->id]);
    }

    public function test_exported_demo_is_imported_with_remapped_references_and_images(): void
    {
        $this->seedShop();
        $zip = app(DemoExporter::class)->exportZip(['sk' => 'Keramika demo']);

        $archive = new ZipArchive;
        $archive->open($zip);
        $this->assertNotFalse($archive->locateName('media/'.$this->image));
        $json = (string) $archive->getFromName('demo.json');
        $archive->close();
        $this->assertStringContainsString('{{uploads}}/products/demo-test-image.jpg', $json);
        $this->assertSame('Keramika demo', app(DemoImporter::class)->inspect($zip)['title']['sk']);

        // Import nahradí obsah – všetko dostane nové ID.
        $oldProductId = Product::query()->value('id');
        Storage::disk('public')->delete($this->image);
        $counts = app(DemoImporter::class)->import($zip, replace: true, backup: false);
        File::delete($zip);

        $this->assertSame(1, $counts['products']);
        $this->assertSame(2, $counts['categories']);
        $product = Product::query()->sole();
        $this->assertNotSame($oldProductId, $product->id);
        $this->assertSame(2, $product->variants()->count());
        $this->assertSame('Hrnčeky', $product->categories()->sole()->name);
        $this->assertSame('Keramika', $product->categories()->sole()->parent->name);
        $this->assertTrue(Storage::disk('public')->exists($this->image), 'Obrázok sa skopíroval z balíka.');
        $this->assertStringContainsString(Storage::disk('public')->url($this->image), (string) $product->description);

        $blocks = BlockLayout::query()->where('key', BlockLayout::HOME)->sole()->blocks;
        $this->assertSame(Category::query()->where('name->sk', 'Hrnčeky')->value('id'), $blocks[0]['data']['category_id']);
        $this->assertSame(Form::query()->sole()->id, $blocks[1]['data']['form_id']);
        $this->assertSame(SavedBlock::query()->sole()->id, $blocks[2]['data']['saved_block_id']);
        $this->assertSame((string) Category::query()->where('name->sk', 'Keramika')->value('id'), (string) MenuItem::query()->sole()->target);
        $this->assertSame([$product->id], Post::query()->sole()->products()->pluck('products.id')->all());
        $this->assertSame(1, Page::query()->where('name->sk', 'O nás')->count());
        $this->assertSame(1, Page::query()->where('role', Page::ROLE_CONTACT)->count(), 'Právne a systémové stránky sa neduplikujú.');

        $this->get($product->url())->assertOk()->assertSee('Hrnček');
        $this->get('/')->assertOk();
    }

    public function test_adding_demo_keeps_existing_content_and_unique_addresses(): void
    {
        $this->seedShop();
        $zip = app(DemoExporter::class)->exportZip();

        app(DemoImporter::class)->import($zip, replace: false, backup: false);
        File::delete($zip);

        $this->assertSame(2, Product::query()->count());
        $this->assertSame(2, Product::query()->pluck('slug')->map(fn ($slug) => json_encode($slug))->unique()->count());
    }

    public function test_replace_is_refused_when_shop_has_orders_and_invalid_package_is_rejected(): void
    {
        $this->seedShop();
        $zip = app(DemoExporter::class)->exportZip();
        $this->post(route('cart.add'), ['product_id' => $this->product('Tanier', '12.00')->id]);
        $this->post(route('checkout.store'), $this->checkoutData());
        $this->assertSame(1, Order::query()->count());

        try {
            app(DemoImporter::class)->import($zip, replace: true, backup: false);
            $this->fail('Obchod s objednávkami nesmie prísť o produkty.');
        } catch (DemoException) {
            $this->assertSame(2, Product::query()->count());
        } finally {
            File::delete($zip);
        }

        $bad = sys_get_temp_dir().'/bad-demo-'.uniqid().'.zip';
        $archive = new ZipArchive;
        $archive->open($bad, ZipArchive::CREATE);
        $archive->addFromString('demo.json', '{"format":99}');
        $archive->addFromString('../evil.php', '<?php');
        $archive->close();

        $this->expectException(DemoException::class);

        try {
            app(DemoImporter::class)->import($bad, backup: false);
        } finally {
            File::delete($bad);
        }
    }

    public function test_themes_page_offers_demo_export_and_import(): void
    {
        $this->actingAs($this->superAdmin(['is_active' => true]))
            ->get('/admin/themes')
            ->assertOk()
            ->assertSee(__('admin.demo.export'))
            ->assertSee(__('admin.demo.import_file'));
    }
}
