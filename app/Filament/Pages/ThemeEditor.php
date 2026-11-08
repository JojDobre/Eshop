<?php

namespace App\Filament\Pages;

use App\Core\Themes\Theme;
use App\Core\Themes\ThemeFiles;
use App\Core\Themes\ThemeManager;
use App\Models\User;
use App\Settings\GeneralSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Throwable;

/**
 * Editor súborov šablóny. Iba pre hlavného administrátora, predvolene vypnutý,
 * pred každou zmenou sa uloží záloha.
 */
class ThemeEditor extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static ?string $slug = 'themes/{theme}/editor';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.theme-editor';

    public string $theme = '';

    public ?string $file = null;

    public string $content = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isSuperAdmin() && self::enabled();
    }

    public static function enabled(): bool
    {
        try {
            return config('eshop.theme_editor') && app(GeneralSettings::class)->theme_editor_enabled;
        } catch (Throwable) {
            return false;
        }
    }

    public function mount(string $theme): void
    {
        $found = app(ThemeManager::class)->find($theme);
        abort_if($found === null || ! app(ThemeFiles::class)->canEdit($found), 404);

        $this->theme = $theme;
        $this->file = app(ThemeFiles::class)->files($found)[0] ?? null;
        $this->loadFile();
    }

    public function getTitle(): string
    {
        return __('admin.theme_editor.title', ['theme' => $this->themeModel()->title()]);
    }

    public function getSubheading(): ?string
    {
        return __('admin.theme_editor.warning');
    }

    public function themeModel(): Theme
    {
        return app(ThemeManager::class)->find($this->theme) ?? abort(404);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Select::make('file')->label(__('admin.theme_editor.file'))
                    ->options(fn () => collect(app(ThemeFiles::class)->files($this->themeModel()))->mapWithKeys(fn ($file) => [$file => $file])->all())
                    ->searchable()->native(false)->live()
                    ->afterStateUpdated(fn () => $this->loadFile()),
                Textarea::make('content')->hiddenLabel()->rows(28)
                    ->extraInputAttributes(['style' => 'font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; line-height: 1.5;', 'spellcheck' => 'false'])
                    ->visible(fn () => $this->file !== null),
            ]),
        ]);
    }

    public function loadFile(): void
    {
        $this->content = $this->file ? app(ThemeFiles::class)->read($this->themeModel(), $this->file) : '';
    }

    public function save(): void
    {
        if ($this->file === null) {
            return;
        }

        try {
            app(ThemeFiles::class)->write($this->themeModel(), $this->file, $this->content);
            Notification::make()->success()->title(__('admin.theme_editor.saved'))->send();
        } catch (Throwable $e) {
            Notification::make()->danger()->title(__('admin.theme_editor.save_failed'))->body($e->getMessage())->persistent()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        $files = app(ThemeFiles::class);

        return [
            Action::make('back')->label(__('admin.themes.back'))->color('gray')->url(Themes::getUrl()),
            Action::make('copyFromParent')->label(__('admin.theme_editor.copy'))->color('gray')
                ->visible(fn () => $this->themeModel()->parent !== null)
                ->modalDescription(__('admin.theme_editor.copy_help'))
                ->schema([
                    Select::make('path')->label(__('admin.theme_editor.file'))
                        ->options(fn () => collect($files->parentFiles($this->themeModel()))->mapWithKeys(fn ($file) => [$file => $file])->all())
                        ->searchable()->required(),
                ])
                ->action(function (array $data) use ($files): void {
                    $files->copyFromParent($this->themeModel(), $data['path']);
                    $this->file = $data['path'];
                    $this->loadFile();
                }),
            Action::make('newFile')->label(__('admin.theme_editor.new_file'))->color('gray')
                ->schema([TextInput::make('path')->label(__('admin.theme_editor.path'))->helperText(__('admin.theme_editor.path_help'))->required()->maxLength(160)])
                ->action(function (array $data) use ($files): void {
                    try {
                        $files->write($this->themeModel(), $data['path'], '');
                        $this->file = ltrim($data['path'], '/');
                        $this->loadFile();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();
                    }
                }),
            Action::make('restore')->label(__('admin.theme_editor.backups'))->color('gray')
                ->visible(fn () => $this->file !== null && $files->backups($this->themeModel(), $this->file) !== [])
                ->modalDescription(__('admin.theme_editor.restore_help'))
                ->schema([
                    Select::make('stamp')->label(__('admin.theme_editor.backup'))
                        ->options(fn () => $this->file ? $files->backups($this->themeModel(), $this->file) : [])->required(),
                ])
                ->action(function (array $data) use ($files): void {
                    $files->restore($this->themeModel(), (string) $this->file, $data['stamp']);
                    $this->loadFile();
                    Notification::make()->success()->title(__('admin.theme_editor.restored'))->send();
                }),
            Action::make('save')->label(__('admin.themes.save'))->action(fn () => $this->save())->keyBindings(['mod+s']),
        ];
    }
}
