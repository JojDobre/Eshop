<?php

namespace App\Filament\Forms;

use App\Core\Blocks\BlockType;
use App\Core\Blocks\BlockTypes;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Schemas\Components\Section;

/**
 * Skladanie stránky z blokov (banner, produkty, text…) – typy blokov registrujú jadro a moduly.
 */
final class BlockBuilder
{
    /**
     * @param  list<string>  $exclude  Typy blokov, ktoré sa nedajú pridať (napr. sekcia v sekcii).
     */
    public static function make(string $name = 'blocks', array $exclude = []): Builder
    {
        return Builder::make($name)
            ->hiddenLabel()
            ->blocks(array_values(array_map(
                fn (BlockType $type) => Block::make($type->key)->label(($type->label)())->icon($type->icon)->schema(($type->schema)()),
                array_diff_key(app(BlockTypes::class)->all(), array_flip($exclude)),
            )))
            ->addActionLabel(__('admin.blocks.add'))
            ->blockPickerColumns(2)
            ->collapsible()
            ->cloneable()
            ->reorderableWithButtons();
    }

    /** Sekcia „Bloky“ do formulára stránky alebo kategórie. */
    public static function section(string $description): Section
    {
        return Section::make(__('admin.blocks.section'))->description($description)->collapsed()->schema([self::make()]);
    }
}
