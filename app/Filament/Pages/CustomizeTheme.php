<?php

namespace App\Filament\Pages;

use App\Core\Themes\Theme;
use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeOptions;
use App\Filament\Forms\RichText;
use App\Http\Middleware\ApplyThemePreview;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Route;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

/**
 * Prispôsobenie šablóny: vľavo nastavenia, vpravo stránka, ktorá sa mení hneď pri úprave.
 * Neuložené hodnoty sú iba v relácii administrátora, zákazníci ich nevidia.
 */
class CustomizeTheme extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static ?string $slug = 'themes/{theme}/customize';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.customize-theme';

    public string $theme = '';

    /** Stránka v náhľade. */
    public string $previewPath = '/';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function mount(string $theme): void
    {
        abort_if(app(ThemeManager::class)->find($theme) === null, 404);

        $this->theme = $theme;
        $values = app(ThemeOptions::class)->values($this->themeModel());
        session()->put(ApplyThemePreview::SESSION_VALUES.'.'.$theme, $values);
        $this->form->fill($values);
    }

    public function getTitle(): string
    {
        return __('admin.themes.customize_title', ['theme' => $this->themeModel()->title()]);
    }

    public function themeModel(): Theme
    {
        return app(ThemeManager::class)->find($this->theme) ?? abort(404);
    }

    public function form(Schema $schema): Schema
    {
        $groups = [];

        foreach (app(ThemeManager::class)->settingDefinitions($this->themeModel()) as $key => $definition) {
            $groups[$definition['group'] ?? 'general'][] = $this->field($key, $definition);
        }

        $sections = [];

        foreach ($groups as $group => $fields) {
            $label = __('admin.themes.groups.'.$group);
            $sections[] = Section::make($label === 'admin.themes.groups.'.$group ? ucfirst($group) : $label)->schema($fields)->compact();
        }

        return $schema->statePath('data')->components($sections === [] ? [] : $sections);
    }

    /**
     * Pole formulára podľa typu nastavenia v theme.json.
     *
     * @param  array<string, mixed>  $definition
     */
    private function field(string $key, array $definition): mixed
    {
        $label = Theme::label($definition['label'], $key);
        $help = $definition['help'] !== [] ? Theme::label($definition['help']) : null;

        $field = match ($definition['type']) {
            'color' => ColorPicker::make($key)->live(debounce: 400),
            'select' => Select::make($key)->options(collect($definition['options'])->map(fn ($labels, $value) => Theme::label($labels, (string) $value))->all())->selectablePlaceholder(false)->native(false)->live(),
            'toggle' => Toggle::make($key)->live(),
            'number' => TextInput::make($key)->numeric()->live(debounce: 600),
            'textarea' => Textarea::make($key)->rows(3)->maxLength(5000)->live(debounce: 600),
            'richtext' => RichText::make($key, 'theme')->live(debounce: 1000),
            'sections' => Repeater::make($key)
                ->schema([
                    Select::make('section')->hiddenLabel()->required()->selectablePlaceholder(false)
                        ->options(collect($definition['options'])->map(fn ($labels, $value) => Theme::label($labels, (string) $value))->all()),
                    Select::make('layout')->hiddenLabel()->selectablePlaceholder(false)
                        ->options(collect($definition['layouts'])->map(fn ($labels, $value) => Theme::label($labels, (string) $value))->all())
                        ->default(array_key_first($definition['layouts']))
                        ->visible($definition['layouts'] !== []),
                ])
                ->columns(2)->reorderable()->reorderableWithButtons()->defaultItems(0)
                ->addActionLabel(__('admin.themes.add_section'))
                ->live(),
            'image' => FileUpload::make($key)->disk('public')->directory('theme')->visibility('public')->image()->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])->maxSize(4096)->live(),
            default => TextInput::make($key)->maxLength(500)->live(debounce: 600),
        };

        return $field->label($label)->helperText($help)->afterStateUpdated(fn () => $this->refreshPreview());
    }

    /** Neuložené hodnoty do relácie a obnovenie náhľadu. */
    public function refreshPreview(): void
    {
        session()->put(ApplyThemePreview::SESSION_VALUES.'.'.$this->theme, $this->form->getRawState());
        $this->dispatch('theme-preview-refresh');
    }

    public function previewUrl(): string
    {
        $active = app(ThemeManager::class)->activeName() === $this->theme;
        $path = str_starts_with($this->previewPath, '/') ? $this->previewPath : '/';

        return url($path).'?theme_customize=1&theme_preview='.($active ? '0' : $this->theme);
    }

    /**
     * Stránky na výber v náhľade.
     *
     * @return array<string, string>
     */
    public function previewPages(): array
    {
        $pages = ['/' => __('admin.themes.preview_pages.home')];

        if (class_exists(Category::class)) {
            $category = Category::query()->where('is_visible', true)->first();
            $product = Product::query()->where('is_visible', true)->first();
            $category && $pages[parse_url($category->url(), PHP_URL_PATH) ?: '/'] = __('admin.themes.preview_pages.category');
            $product && $pages[parse_url($product->url(), PHP_URL_PATH) ?: '/'] = __('admin.themes.preview_pages.product');
        }

        if (Route::has('cart.show')) {
            $pages[parse_url(route('cart.show'), PHP_URL_PATH) ?: '/'] = __('admin.themes.preview_pages.cart');
        }

        // Pri obchode v podpriečinku sa cesta počíta od koreňa obchodu.
        $base = rtrim((string) parse_url(url('/'), PHP_URL_PATH), '/');

        return collect($pages)->mapWithKeys(fn ($label, $path) => [$base !== '' && str_starts_with($path, $base) ? (substr($path, strlen($base)) ?: '/') : $path => $label])->all();
    }

    public function save(): void
    {
        app(ThemeOptions::class)->save($this->themeModel(), $this->form->getState());
        session()->put(ApplyThemePreview::SESSION_VALUES.'.'.$this->theme, app(ThemeOptions::class)->values($this->themeModel()));

        Notification::make()->success()->title(__('admin.themes.saved'))->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')->label(__('admin.themes.back'))->color('gray')->url(Themes::getUrl()),
            Action::make('reset')->label(__('admin.themes.reset'))->color('gray')->requiresConfirmation()
                ->modalDescription(__('admin.themes.reset_confirm'))
                ->action(function (): void {
                    app(ThemeOptions::class)->reset($this->themeModel());
                    $values = app(ThemeOptions::class)->values($this->themeModel());
                    $this->form->fill($values);
                    $this->refreshPreview();
                }),
            Action::make('save')->label(__('admin.themes.save'))->action(fn () => $this->save()),
        ];
    }
}
