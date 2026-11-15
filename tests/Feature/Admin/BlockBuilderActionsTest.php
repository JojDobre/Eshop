<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\SavedBlocks\Pages\CreateSavedBlock;
use App\Models\SavedBlock;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Modules\Pages\Filament\Resources\Pages\Pages\CreatePage;
use Tests\Feature\InstalledTestCase;

class BlockBuilderActionsTest extends InstalledTestCase
{
    public function test_section_preset_with_columns_is_inserted_into_builder(): void
    {
        $this->actingAs($this->superAdmin(['is_active' => true]));

        $blocks = Livewire::test(CreateSavedBlock::class)
            ->callAction(TestAction::make('insertSectionPreset')->schemaComponent('blocks'), data: ['preset' => 'features'])
            ->assertHasNoErrors()
            ->get('data.blocks');

        $this->assertSame(['heading', 'section'], array_values(array_column($blocks, 'type')));
        $section = array_values($blocks)[1]['data'];
        $this->assertSame('thirds', $section['layout']);
        $this->assertCount(1, $section['column_3']);
        $this->assertSame('feature', array_values($section['column_3'])[0]['type']);
    }

    public function test_block_can_be_saved_to_library_and_replaced_by_reference(): void
    {
        $this->actingAs($this->superAdmin(['is_active' => true]));

        $component = Livewire::test(CreatePage::class)
            ->set('data.blocks', ['item-1' => ['type' => 'heading', 'data' => ['title' => 'Do knižnice', 'size' => 'medium', 'align' => 'left', 'visibility' => 'mobile', 'hidden' => false]]])
            ->callAction(TestAction::make('saveToLibrary')->schemaComponent('blocks')->arguments(['item' => 'item-1']), data: ['name' => 'Nadpis', 'replace' => true])
            ->assertHasNoErrors();

        $saved = SavedBlock::query()->sole();
        $this->assertSame('Nadpis', $saved->name);
        $this->assertSame('Do knižnice', $saved->blockList()[0]['data']['title']);
        $this->assertSame('mobile', $saved->blockList()[0]['data']['visibility']);

        $item = $component->get('data.blocks')['item-1'];
        $this->assertSame('saved', $item['type']);
        $this->assertEquals($saved->id, $item['data']['saved_block_id']);
    }
}
