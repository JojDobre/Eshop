<?php

namespace App\Filament\Pages\Settings;

use App\Core\Features\FeatureToggles;
use App\Core\Modules\ModuleManager;
use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Throwable;
use UnitEnum;

/**
 * Všetky funkcie obchodu, ktoré sa dajú zapnúť a vypnúť, na jednom mieste.
 * Vypnutím sa nič nemaže – nastavenia aj dáta ostanú pre opätovné zapnutie.
 */
class ManageFeatures extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'settings/features';

    protected string $view = 'filament.pages.settings.features';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Settings;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.features.title');
    }

    public function getTitle(): string
    {
        return __('admin.features.title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.features.intro');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill($this->currentState());
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];

        foreach (app(FeatureToggles::class)->grouped() as $group) {
            $sections[] = Section::make($group['label'])->columns(2)->schema(array_map(
                fn ($toggle) => Toggle::make('features.'.self::field($toggle->key))
                    ->label(($toggle->label)())
                    ->helperText(function () use ($toggle) {
                        $url = $toggle->settingsUrl ? ($toggle->settingsUrl)() : null;
                        $text = e(($toggle->description)());

                        return new HtmlString($url ? $text.' <a href="'.e($url).'" class="fi-link" style="text-decoration: underline;">'.e(__('admin.features.settings')).'</a>' : $text);
                    }),
                $group['toggles'],
            ));
        }

        $modules = collect(app(ModuleManager::class)->all())->reject(fn ($module) => $module->core);

        if ($modules->isNotEmpty()) {
            $sections[] = Section::make(__('admin.features.modules'))->description(__('admin.features.modules_help'))->columns(2)->schema(
                $modules->map(fn ($module) => Toggle::make('modules.'.$module->name)
                    ->label($module->title())
                    ->helperText($module->description()))->values()->all(),
            );
        }

        return $schema->statePath('data')->components($sections);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $toggles = app(FeatureToggles::class);
        $errors = [];

        foreach ($toggles->grouped() as $group) {
            foreach ($group['toggles'] as $toggle) {
                $value = (bool) ($state['features'][self::field($toggle->key)] ?? false);

                if ($value !== ($toggle->isEnabled)()) {
                    try {
                        ($toggle->set)($value);
                    } catch (Throwable $e) {
                        $errors[] = ($toggle->label)().': '.$e->getMessage();
                    }
                }
            }
        }

        $modules = app(ModuleManager::class);
        $changedModules = false;

        // Najprv vypnutie (závislé moduly), potom zapnutie (závislosti).
        foreach ([false, true] as $target) {
            foreach ($state['modules'] ?? [] as $name => $enabled) {
                if ((bool) $enabled === $target && $modules->isEnabled($name) !== $target) {
                    try {
                        $target ? $modules->enable($name) : $modules->disable($name);
                        $changedModules = true;
                    } catch (Throwable $e) {
                        $errors[] = $e->getMessage();
                    }
                }
            }
        }

        foreach ($errors as $error) {
            Notification::make()->danger()->title(__('admin.features.error'))->body($error)->persistent()->send();
        }

        if ($errors === []) {
            Notification::make()->success()->title(__('admin.features.saved'))->send();
        }

        // Zmena modulov mení menu administrácie – stránka sa načíta znova.
        if ($changedModules) {
            $this->redirect(static::getUrl());

            return;
        }

        $this->form->fill($this->currentState());
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))->submit('save')];
    }

    /**
     * @return array<string, mixed>
     */
    private function currentState(): array
    {
        $features = [];

        foreach (app(FeatureToggles::class)->grouped() as $group) {
            foreach ($group['toggles'] as $toggle) {
                $features[self::field($toggle->key)] = ($toggle->isEnabled)();
            }
        }

        $modules = [];

        foreach (app(ModuleManager::class)->all() as $module) {
            if (! $module->core) {
                $modules[$module->name] = app(ModuleManager::class)->isEnabled($module->name);
            }
        }

        return ['features' => $features, 'modules' => $modules];
    }

    private static function field(string $key): string
    {
        return str_replace('.', '__', $key);
    }
}
