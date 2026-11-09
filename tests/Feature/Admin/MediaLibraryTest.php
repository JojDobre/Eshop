<?php

namespace Tests\Feature\Admin;

use App\Core\Content\MediaLibrary;
use App\Filament\Resources\MediaFiles\Pages\ManageMediaFiles;
use App\Models\MediaFile;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Tests\Feature\Shop\ShopTestCase;

class MediaLibraryTest extends ShopTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_existing_shop_images_are_synced_and_used_files_are_protected(): void
    {
        Storage::disk('public')->put('products/kava.png', UploadedFile::fake()->image('kava.png', 40, 30)->getContent());
        Storage::disk('public')->put('blocks/banner.jpg', UploadedFile::fake()->image('banner.jpg')->getContent());
        Storage::disk('public')->put('imports/data.csv', 'a;b');
        $product = $this->product('Káva', attributes: ['images' => ['products/kava.png']]);

        $library = app(MediaLibrary::class);
        $this->assertSame(2, $library->sync());
        $this->assertSame(0, $library->sync());

        $image = MediaFile::query()->where('path', 'products/kava.png')->firstOrFail();
        $this->assertSame(40, $image->width);
        $this->assertTrue($library->isUsed($image));
        $this->assertFalse($library->isUsed(MediaFile::query()->where('path', 'blocks/banner.jpg')->firstOrFail()));

        // Používaný obrázok sa zmazať nedá, nepoužívaný áno.
        $this->actingAs($this->superAdmin());
        Livewire::test(ManageMediaFiles::class)
            ->callAction(TestAction::make('delete')->table($image));
        $this->assertModelExists($image);
        Storage::disk('public')->assertExists('products/kava.png');

        Livewire::test(ManageMediaFiles::class)
            ->selectTableRecords(MediaFile::query()->pluck('id')->all())
            ->callAction(TestAction::make('deleteUnused')->table()->bulk());
        $this->assertSame(['products/kava.png'], MediaFile::query()->pluck('path')->all());
        Storage::disk('public')->assertMissing('blocks/banner.jpg');

        // Súbor zmazaný zo servera zmizne aj z knižnice.
        $product->update(['images' => []]);
        Storage::disk('public')->delete('products/kava.png');
        $library->sync();
        $this->assertSame(0, MediaFile::query()->count());
    }

    public function test_product_image_can_be_picked_from_library(): void
    {
        Storage::disk('public')->put('media/2026/11/med.png', UploadedFile::fake()->image('med.png')->getContent());
        $file = app(MediaLibrary::class)->register('media/2026/11/med.png', 'Med');
        $product = $this->product('Med');

        $this->actingAs($this->superAdmin());
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->callAction(TestAction::make('pickFromMediaLibrary')->schemaComponent('images'), ['files' => [$file->id]])
            ->assertHasNoActionErrors()
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['media/2026/11/med.png'], $product->fresh()->images);
    }
}
