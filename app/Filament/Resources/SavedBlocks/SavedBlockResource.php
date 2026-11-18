<?php

namespace App\Filament\Resources\SavedBlocks;

use App\Filament\AdminGroup;
use App\Filament\Forms\BlockBuilder;
use App\Filament\Pages\ManageBlocks;
use App\Filament\Resources\SavedBlocks\Pages\CreateSavedBlock;
use App\Filament\Resources\SavedBlocks\Pages\EditSavedBlock;
use App\Filament\Resources\SavedBlocks\Pages\ListSavedBlocks;
use App\Models\SavedBlock;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * Knižnica uložených sekcií – bloky, ktoré sa vkladajú na viac miest naraz.
 */
class SavedBlockResource extends Resource
{
    protected static ?string $model = SavedBlock::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmark;

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'saved-blocks';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Content;
    }

    /** V menu pod položkou „Bloky“. */
    public static function getNavigationParentItem(): ?string
    {
        return ManageBlocks::getNavigationLabel();
    }

    public static function getModelLabel(): string
    {
        return __('admin.saved_blocks.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.saved_blocks.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('name')->label(__('admin.saved_blocks.name'))->helperText(__('admin.saved_blocks.name_help'))->required()->maxLength(120),
            Section::make(__('admin.blocks.section'))->description(__('admin.saved_blocks.intro'))
                // Uložená sekcia nemôže obsahovať ďalšiu uloženú sekciu (zabráni cyklom).
                ->schema([BlockBuilder::make('blocks', ['saved'])]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('admin.saved_blocks.'.$key);

        return $table
            ->columns([
                TextColumn::make('name')->label($t('name'))->searchable()->sortable(),
                TextColumn::make('blocks_count')->label($t('blocks'))->state(fn (SavedBlock $record) => count($record->blockList()))->alignEnd(),
                TextColumn::make('usages')->label($t('usages'))->state(fn (SavedBlock $record) => self::usageCount($record))->alignEnd(),
                TextColumn::make('updated_at')->label($t('updated'))->since()->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ReplicateAction::make()->excludeAttributes(['blocks'])
                        ->beforeReplicaSaved(fn (SavedBlock $replica, SavedBlock $record) => $replica->setTranslations('blocks', $record->getTranslations('blocks'))->forceFill(['name' => $record->name.' (2)'])),
                    DeleteAction::make(),
                ])->icon(Heroicon::EllipsisHorizontal)->color('gray'),
            ])
            ->emptyStateHeading($t('empty'))
            ->emptyStateDescription($t('intro'));
    }

    /**
     * Na koľkých miestach je sekcia vložená (úvodná stránka, oblasti, stránky, kategórie, články).
     */
    public static function usageCount(SavedBlock $record): int
    {
        $needles = ['"saved_block_id":'.$record->id.',', '"saved_block_id":'.$record->id.'}', '"saved_block_id":"'.$record->id.'"'];
        $count = 0;

        foreach (['block_layouts', 'pages', 'categories', 'blog_posts'] as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table) || ! DB::getSchemaBuilder()->hasColumn($table, 'blocks')) {
                continue;
            }

            $count += DB::table($table)->where(function ($query) use ($needles): void {
                foreach ($needles as $needle) {
                    $query->orWhere('blocks', 'like', '%'.$needle.'%');
                }
            })->count();
        }

        return $count;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSavedBlocks::route('/'),
            'create' => CreateSavedBlock::route('/create'),
            'edit' => EditSavedBlock::route('/{record}/edit'),
        ];
    }
}
