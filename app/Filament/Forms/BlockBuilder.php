<?php

namespace App\Filament\Forms;

use App\Core\Blocks\BlockType;
use App\Core\Blocks\BlockTypes;
use App\Core\Blocks\SectionPresets;
use App\Core\Content\ContentLocale;
use App\Models\SavedBlock;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * Skladanie stránky z blokov (banner, produkty, text…) – typy blokov registrujú jadro a moduly.
 *
 * Každý blok má navyše voľbu zobrazenia podľa zariadenia a dá sa uložiť do knižnice uložených sekcií.
 * Pod blokmi je tlačidlo na vloženie hotovej šablóny sekcie.
 */
final class BlockBuilder
{
    public const VISIBILITY = ['all', 'desktop', 'mobile'];

    /**
     * @param  list<string>  $exclude  Typy blokov, ktoré sa nedajú pridať (napr. sekcia v sekcii).
     */
    public static function make(string $name = 'blocks', array $exclude = [], bool $presets = true): Builder
    {
        return Builder::make($name)
            ->hiddenLabel()
            ->blocks(array_values(array_map(
                fn (BlockType $type) => Block::make($type->key)->label(($type->label)())->icon($type->icon)
                    ->schema([...($type->schema)(), self::visibility()]),
                array_diff_key(app(BlockTypes::class)->all(), array_flip($exclude)),
            )))
            ->addActionLabel(__('admin.blocks.add'))
            ->blockPickerColumns(2)
            ->collapsible()
            ->cloneable()
            ->reorderableWithButtons()
            ->extraItemActions(in_array('saved', $exclude, true) ? [] : [self::saveToLibraryAction()])
            ->belowContent($presets ? [self::insertPresetAction(in_array('section', $exclude, true))] : []);
    }

    /** Sekcia „Bloky“ do formulára stránky alebo kategórie. */
    public static function section(string $description): Section
    {
        return Section::make(__('admin.blocks.section'))->description($description)->collapsed()->schema([self::make()]);
    }

    /** Zobrazenie bloku podľa zariadenia (predvolene všade). */
    public static function visibility(): Select
    {
        return Select::make('visibility')
            ->label(__('admin.blocks.visibility'))
            ->selectablePlaceholder(false)
            ->default('all')
            ->options(collect(self::VISIBILITY)->mapWithKeys(fn (string $value) => [$value => __('admin.blocks.visibility_'.$value)])->all());
    }

    /**
     * Uloží blok do knižnice uložených sekcií a voliteľne ho nahradí odkazom (úprava na jednom mieste).
     */
    private static function saveToLibraryAction(): Action
    {
        return Action::make('saveToLibrary')
            ->label(__('admin.saved_blocks.save'))
            ->tooltip(__('admin.saved_blocks.save'))
            ->icon(Heroicon::OutlinedBookmark)
            ->iconButton()
            ->color('gray')
            ->size('sm')
            ->visible(fn (array $arguments, Builder $component) => (($component->getRawState()[$arguments['item'] ?? ''] ?? [])['type'] ?? null) !== 'saved')
            ->modalHeading(__('admin.saved_blocks.save'))
            ->modalDescription(__('admin.saved_blocks.save_help'))
            ->schema([
                TextInput::make('name')->label(__('admin.saved_blocks.name'))->required()->maxLength(120),
                Toggle::make('replace')->label(__('admin.saved_blocks.replace'))->helperText(__('admin.saved_blocks.replace_help'))->default(true),
            ])
            ->action(function (array $arguments, array $data, Builder $component): void {
                $uuid = (string) $arguments['item'];
                $items = $component->getRawState();
                $type = (string) ($items[$uuid]['type'] ?? '');

                if ($type === '') {
                    return;
                }

                $saved = new SavedBlock(['name' => $data['name']]);
                $saved->setTranslation('blocks', app(ContentLocale::class)->default(), [['type' => $type, 'data' => $component->getItemState($uuid)]]);
                $saved->save();

                if ($data['replace'] ?? false) {
                    $items[$uuid] = ['type' => 'saved', 'data' => []];
                    $component->rawState($items);
                    $component->getChildSchema($uuid)->fill(['saved_block_id' => $saved->id, 'visibility' => 'all']);
                    $component->callAfterStateUpdated();
                }

                Notification::make()->success()->title(__('admin.saved_blocks.saved', ['name' => $saved->name]))->send();
            });
    }

    /**
     * Vloží hotovú šablónu sekcie (bloky s ukážkovými textmi, ktoré si správca upraví).
     */
    private static function insertPresetAction(bool $withoutSections): Action
    {
        return Action::make('insertSectionPreset')
            ->label(__('admin.section_presets.insert'))
            ->icon(Heroicon::OutlinedSquares2x2)
            ->link()
            ->modalHeading(__('admin.section_presets.insert'))
            ->modalDescription(__('admin.section_presets.help'))
            ->modalSubmitActionLabel(__('admin.section_presets.insert_submit'))
            ->modalWidth('2xl')
            ->schema([
                Radio::make('preset')->hiddenLabel()->required()->columns(['default' => 1, 'sm' => 2])
                    ->options(fn () => SectionPresets::options($withoutSections)),
            ])
            ->action(function (array $data, Builder $component) use ($withoutSections): void {
                $blocks = SectionPresets::blocks((string) $data['preset'], $withoutSections);
                foreach ($blocks as $block) {
                    if ($component->getBlock($block['type']) === null) {
                        continue;
                    }

                    $uuid = (string) Str::uuid();
                    $items = $component->getRawState() ?? [];
                    $items[$uuid] = ['type' => $block['type'], 'data' => []];
                    $component->rawState($items);
                    $component->getChildSchema($uuid)->fill($block['data']);
                }

                $component->callAfterStateUpdated();
            });
    }
}
