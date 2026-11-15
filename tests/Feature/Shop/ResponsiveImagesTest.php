<?php

namespace Tests\Feature\Shop;

use App\Core\Media\ResponsiveImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\InstalledTestCase;

class ResponsiveImagesTest extends InstalledTestCase
{
    private string $path = 'products/test-responsive.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        $image = imagecreatetruecolor(1600, 1000);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 40, 40));
        File::ensureDirectoryExists(dirname(Storage::disk('public')->path($this->path)));
        imagejpeg($image, Storage::disk('public')->path($this->path), 90);
    }

    protected function tearDown(): void
    {
        app(ResponsiveImage::class)->forget($this->path);
        Storage::disk('public')->delete($this->path);
        parent::tearDown();
    }

    public function test_srcset_lists_smaller_webp_versions_and_the_original(): void
    {
        $url = Storage::disk('public')->url($this->path);
        // Kým verzie neexistujú, ponúka sa iba originál a obrázok čaká vo fronte plánovanej úlohy.
        $this->assertNull(image_srcset($url));
        $this->assertSame(1, app(ResponsiveImage::class)->processQueue());

        $srcset = image_srcset($url);

        $this->assertStringContainsString('/uploads/_v/320/products/test-responsive.jpg.webp 320w', $srcset);
        $this->assertStringContainsString('/uploads/_v/1440/products/test-responsive.jpg.webp 1440w', $srcset);
        // Najväčšia možnosť je originál, nič sa nezväčšuje.
        $this->assertStringEndsWith($url.' 1600w', $srcset);
        $this->assertStringNotContainsString('1920w', $srcset);
        $this->assertNull(image_srcset('https://cdn.example.com/a.jpg'));
        $this->assertNull(image_srcset(Storage::disk('public')->url('branding/logo.svg')));
        $this->assertFileExists(Storage::disk('public')->path('_v/800/products/test-responsive.jpg.webp'));
    }

    public function test_variant_is_generated_on_first_request_and_saved_as_static_file(): void
    {
        $this->get('/uploads/_v/640/products/test-responsive.jpg.webp')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp');

        $file = Storage::disk('public')->path('_v/640/products/test-responsive.jpg.webp');
        $this->assertFileExists($file);
        $this->assertSame([640, 400], array_slice(getimagesize($file), 0, 2));
    }

    public function test_invalid_variant_requests_are_rejected(): void
    {
        $this->get('/uploads/_v/333/products/test-responsive.jpg.webp')->assertNotFound();
        $this->get('/uploads/_v/640/products/missing.jpg.webp')->assertNotFound();
        $this->get('/uploads/_v/640/_v/640/products/test-responsive.jpg.webp.webp')->assertNotFound();
        $this->assertNull(app(ResponsiveImage::class)->generate('../.env', 640));
    }
}
