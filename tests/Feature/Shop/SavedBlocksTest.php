<?php

namespace Tests\Feature\Shop;

use App\Core\Blocks\BlockTypes;
use App\Core\Blocks\SectionPresets;
use App\Models\BlockLayout;
use App\Models\SavedBlock;
use Tests\Feature\InstalledTestCase;

class SavedBlocksTest extends InstalledTestCase
{
    private function saved(string $title): SavedBlock
    {
        return SavedBlock::query()->create(['name' => 'Výhody', 'blocks' => [
            ['type' => 'heading', 'data' => ['title' => $title, 'size' => 'medium', 'align' => 'left']],
        ]]);
    }

    public function test_saved_section_renders_everywhere_and_edit_shows_up_everywhere(): void
    {
        $saved = $this->saved('Spoločný nadpis');
        BlockLayout::query()->create(['key' => BlockLayout::HOME, 'blocks' => [
            ['type' => 'saved', 'data' => ['saved_block_id' => $saved->id]],
        ]]);

        $this->get('/')->assertOk()->assertSee('Spoločný nadpis');

        $saved->update(['blocks' => [['type' => 'heading', 'data' => ['title' => 'Upravený nadpis', 'size' => 'medium', 'align' => 'left']]]]);

        $this->get('/')->assertOk()->assertSee('Upravený nadpis')->assertDontSee('Spoločný nadpis');
    }

    public function test_saved_section_cannot_loop_forever(): void
    {
        $saved = SavedBlock::query()->create(['name' => 'Cyklus', 'blocks' => []]);
        $saved->update(['blocks' => [
            ['type' => 'heading', 'data' => ['title' => 'Vnútri cyklu']],
            ['type' => 'saved', 'data' => ['saved_block_id' => $saved->id]],
        ]]);

        $html = (string) app(BlockTypes::class)->render([['type' => 'saved', 'data' => ['saved_block_id' => $saved->id]]]);

        $this->assertSame(3, substr_count($html, 'Vnútri cyklu'));
    }

    public function test_blocks_can_be_limited_to_desktop_or_mobile(): void
    {
        $html = (string) app(BlockTypes::class)->render([
            ['type' => 'heading', 'data' => ['title' => 'Všade', 'visibility' => 'all']],
            ['type' => 'heading', 'data' => ['title' => 'Len počítač', 'visibility' => 'desktop']],
            ['type' => 'heading', 'data' => ['title' => 'Len mobil', 'visibility' => 'mobile']],
        ]);

        $this->assertStringContainsString('<div class="block-only-desktop">', $html);
        $this->assertStringContainsString('<div class="block-only-mobile">', $html);
        $this->assertSame(2, substr_count($html, 'block-only-'));
    }

    public function test_every_section_preset_renders(): void
    {
        foreach (array_keys(SectionPresets::options()) as $preset) {
            $blocks = SectionPresets::blocks($preset);
            $this->assertNotEmpty($blocks, $preset);

            // Produkty sa bez produktov v obchode nevykreslia.
            if (! in_array($preset, ['products', 'sale'], true)) {
                $this->assertNotSame('', trim((string) app(BlockTypes::class)->render($blocks)), $preset);
            }
        }

        $this->assertArrayNotHasKey('features', SectionPresets::options(withoutSections: true));
    }

    public function test_saved_sections_admin_pages_load(): void
    {
        $saved = $this->saved('Admin nadpis');
        $this->actingAs($this->superAdmin(['is_active' => true]));

        $this->get('/admin/saved-blocks')->assertOk()->assertSee('Výhody');
        $this->get('/admin/saved-blocks/create')->assertOk();
        $this->get('/admin/saved-blocks/'.$saved->id.'/edit')->assertOk()->assertSee(__('admin.section_presets.insert'));
    }
}
