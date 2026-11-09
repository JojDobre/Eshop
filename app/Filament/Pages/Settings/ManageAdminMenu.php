<?php

namespace App\Filament\Pages\Settings;

use App\Filament\AdminGroup;
use App\Filament\Navigation\EshopNavigationManager;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Navigation\NavigationManager;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * Výber položiek menu administrácie, ktoré sa nemajú zobrazovať (napr. Výrobcovia, keď ich obchod nepoužíva).
 *
 * @property-read Schema $form
 */
class ManageAdminMenu extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'settings/admin-menu';

    protected string $view = 'filament.pages.settings.admin-menu';

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Settings;
    }

    public static function getNavigationParentItem(): ?string
    {
        return ManageGeneralSettings::getNavigationLabel();
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.admin_menu.title');
    }

    public function getTitle(): string
    {
        return __('admin.admin_menu.title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.admin_menu.intro');
    }

    public function mount(): void
    {
        $hidden = app(GeneralSettings::class)->admin_hidden_navigation;

        $this->form->fill(['hidden' => collect($this->groups())->mapWithKeys(fn (array $options, string $group) => [md5($group) => array_values(array_intersect(array_keys($options), $hidden))])->all()]);
    }

    /**
     * Položky menu podľa skupín: [skupina => [kľúč => názov]].
     *
     * @return array<string, array<string, string>>
     */
    private function groups(): array
    {
        $manager = app(NavigationManager::class);
        $groups = [];

        foreach ($manager instanceof EshopNavigationManager ? $manager->getAllNavigationItems() : [] as $item) {
            $key = EshopNavigationManager::key($item);

            if (in_array($key, EshopNavigationManager::ALWAYS_VISIBLE, true)) {
                continue;
            }

            $group = $item->getGroup();
            $groupLabel = $group instanceof UnitEnum && method_exists($group, 'label') ? $group->label() : (string) $group;
            $label = $item->getLabel();
            $label = $label instanceof Htmlable ? strip_tags($label->toHtml()) : (string) $label;
            $groups[$groupLabel ?: __('admin.admin_menu.other')][$key] = $item->getParentItem() ? $item->getParentItem().' → '.$label : $label;
        }

        // Skupiny v poradí ako v menu, položky abecedne.
        $order = array_map(fn (AdminGroup $group) => $group->label(), AdminGroup::cases());
        uksort($groups, fn ($a, $b) => (array_search($a, $order, true) === false ? 99 : array_search($a, $order, true)) <=> (array_search($b, $order, true) === false ? 99 : array_search($b, $order, true)));

        return array_map(function (array $options): array {
            asort($options, SORT_NATURAL | SORT_FLAG_CASE);

            return $options;
        }, $groups);
    }

    public function form(Schema $schema): Schema
    {
        $components = [];

        foreach ($this->groups() as $group => $options) {
            $components[] = Section::make($group)->compact()->schema([
                CheckboxList::make('hidden.'.md5($group))->hiddenLabel()->options($options)->columns(3)->bulkToggleable(),
            ]);
        }

        return $schema->statePath('data')->components($components);
    }

    public function save(): void
    {
        // Každá sekcia má vlastný zoznam – spoja sa do jedného.
        $hidden = collect($this->form->getRawState()['hidden'] ?? [])->flatten()->filter()->unique()->values()->all();
        $settings = app(GeneralSettings::class);
        $settings->admin_hidden_navigation = $hidden;
        $settings->save();

        Notification::make()->success()->title(__('admin.admin_menu.saved'))->send();
        $this->redirect(self::getUrl());
    }
}
