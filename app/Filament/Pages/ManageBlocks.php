<?php

namespace App\Filament\Pages;

use App\Core\Content\ShopLocales;
use App\Core\Themes\Theme;
use App\Core\Themes\ThemeManager;
use App\Filament\AdminGroup;
use App\Filament\Forms\BlockBuilder;
use App\Models\BlockLayout;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Úvodná stránka a oblasti šablóny poskladané z blokov, v každom jazyku obchodu.
 */
class ManageBlocks extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'blocks';

    protected string $view = 'filament.pages.manage-blocks';

    /** Upravovaná časť: „home“ alebo „area:{názov}“. */
    public string $layoutKey = BlockLayout::HOME;

    public string $locale = '';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('admin.blocks.nav');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Content;
    }

    public function getTitle(): string
    {
        return __('admin.blocks.nav');
    }

    public function getSubheading(): ?string
    {
        return __('admin.blocks.intro');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function mount(): void
    {
        $this->locale = app(ShopLocales::class)->default();
        $this->load();
    }

    /**
     * @return array<string, string>
     */
    public function layouts(): array
    {
        $layouts = [BlockLayout::HOME => __('admin.blocks.home')];

        foreach (app(ThemeManager::class)->areas() as $key => $labels) {
            $layouts['area:'.$key] = Theme::label($labels, $key);
        }

        return $layouts;
    }

    public function updatedLayoutKey(): void
    {
        $this->load();
    }

    public function updatedLocale(): void
    {
        $this->load();
    }

    public function form(Schema $schema): Schema
    {
        $locales = app(ShopLocales::class);

        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('layoutKey')->label(__('admin.blocks.layout'))->options(fn () => $this->layouts())
                    ->selectablePlaceholder(false)->native(false)->live(),
                Select::make('locale')->label(__('admin.translations.language_short'))
                    ->options(collect($locales->enabled())->mapWithKeys(fn ($locale) => [$locale => $locales->label($locale)])->all())
                    ->selectablePlaceholder(false)->native(false)->live()
                    ->visible(fn () => $locales->isMultilingual()),
            ]),
            Section::make()->schema([BlockBuilder::make()])->statePath('data'),
        ]);
    }

    private function load(): void
    {
        $layout = BlockLayout::query()->where('key', $this->layoutKey)->first();
        $blocks = $layout?->getTranslation('blocks', $this->locale, false);

        $this->data = ['blocks' => is_array($blocks) ? $blocks : []];
        $this->form->fill(['layoutKey' => $this->layoutKey, 'locale' => $this->locale, 'data' => $this->data]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $blocks = array_values($state['data']['blocks'] ?? $state['blocks'] ?? []);

        $layout = BlockLayout::query()->firstOrNew(['key' => $this->layoutKey]);
        $blocks === [] ? $layout->forgetTranslation('blocks', $this->locale) : $layout->setTranslation('blocks', $this->locale, $blocks);
        $layout->save();

        Notification::make()->success()->title(__('admin.blocks.saved'))->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')->label(__('admin.themes.preview'))->color('gray')->url(url('/'), shouldOpenInNewTab: true),
            Action::make('save')->label(__('admin.themes.save'))->action(fn () => $this->save()),
        ];
    }

    /** V menu pod položkou „Vzhľad (šablóny)“ – bloky úvodnej stránky sa upravujú aj v prispôsobení šablóny. */
    public static function getNavigationParentItem(): ?string
    {
        return Themes::getNavigationLabel();
    }
}
