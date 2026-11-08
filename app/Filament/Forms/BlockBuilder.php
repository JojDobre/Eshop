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
    public static function make(string $name = 'blocks'): Builder
    {
        return Builder::make($name)
            ->hiddenLabel()
            ->blocks(array_values(array_map(
                fn (BlockType $type) => Block::make($type->key)->label(($type->label)())->icon($type->icon)->schema(($type->schema)()),
                app(BlockTypes::class)->all(),
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
        return Section::make(__('admin.blocks.title'))->description($description)->collapsed()->schema([self::make()]);
    }
}
