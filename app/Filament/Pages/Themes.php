<?php

namespace App\Filament\Pages;

use App\Core\Themes\Theme;
use App\Core\Themes\ThemeChecker;
use App\Core\Themes\ThemeFiles;
use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemePackages;
use App\Filament\AdminGroup;
use App\Http\Middleware\ApplyThemePreview;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use UnitEnum;

/**
 * Šablóny vzhľadu: aktivácia, náhľad, prispôsobenie, nahratie a export ZIP.
 */
class Themes extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'themes';

    protected string $view = 'filament.pages.themes';

    public static function getNavigationLabel(): string
    {
        return __('admin.themes.title');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Content;
    }

    public function getTitle(): string
    {
        return __('admin.themes.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    /**
     * @return list<array{theme: Theme, active: bool, compatible: bool, parent: ?string, deletable: bool}>
     */
    public function themes(): array
    {
        $manager = app(ThemeManager::class);
        $active = $manager->activeName();

        return collect($manager->all())
            ->map(fn (Theme $theme) => [
                'theme' => $theme,
                'active' => $theme->name === $active,
                'compatible' => ThemePackages::compatible($theme->requiredEshopVersion),
                'parent' => $theme->parent ? ($manager->find($theme->parent)?->title() ?? $theme->parent) : null,
                'deletable' => app(ThemePackages::class)->cannotDelete($theme) === null,
            ])
            ->sortByDesc('active')
            ->values()
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createChild')
                ->label(__('admin.themes.create_child'))
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->color('gray')
                ->modalDescription(__('admin.themes.create_child_help'))
                ->schema([
                    Select::make('parent')->label(__('admin.themes.parent'))
                        ->options(fn () => collect(app(ThemeManager::class)->all())->map(fn (Theme $theme) => $theme->title())->all())
                        ->default(fn () => app(ThemeManager::class)->activeName())->required()->selectablePlaceholder(false),
                    TextInput::make('title')->label(__('admin.themes.child_title'))->required()->maxLength(80),
                    TextInput::make('name')->label(__('admin.themes.child_name'))->helperText(__('admin.themes.child_name_help'))
                        ->required()->maxLength(40)->rule('regex:/^[a-z][a-z0-9-]*$/'),
                ])
                ->action(function (array $data): void {
                    try {
                        $theme = app(ThemeFiles::class)->createChild(app(ThemeManager::class)->find($data['parent']) ?? abort(404), $data['name'], $data['title']);
                        Notification::make()->success()->title(__('admin.themes.child_created', ['theme' => $theme->title()]))->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();
                    }
                }),
            Action::make('upload')
                ->label(__('admin.themes.upload'))
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalDescription(__('admin.themes.upload_warning'))
                ->schema([
                    FileUpload::make('zip')->label(__('admin.themes.zip'))
                        ->disk('local')->directory('theme-uploads')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->maxSize(ThemePackages::MAX_SIZE / 1024)->required(),
                    Toggle::make('overwrite')->label(__('admin.themes.overwrite'))->helperText(__('admin.themes.overwrite_help')),
                ])
                ->action(function (array $data): void {
                    $path = Storage::disk('local')->path($data['zip']);

                    try {
                        $theme = app(ThemePackages::class)->install($path, (bool) ($data['overwrite'] ?? false));
                        Notification::make()->success()->title(__('admin.themes.uploaded', ['theme' => $theme->title()]))->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title(__('admin.themes.upload_failed'))->body($e->getMessage())->persistent()->send();
                    } finally {
                        Storage::disk('local')->delete($data['zip']);
                    }
                }),
        ];
    }

    public function activateAction(): Action
    {
        return Action::make('activate')
            ->label(__('admin.themes.activate'))
            ->button()->size('sm')
            ->requiresConfirmation()
            ->modalDescription(fn (array $arguments) => __('admin.themes.activate_confirm', ['theme' => app(ThemeManager::class)->find($arguments['theme'] ?? '')?->title()]))
            ->action(function (array $arguments): void {
                $theme = app(ThemeManager::class)->find($arguments['theme'] ?? '');

                if ($theme === null) {
                    return;
                }

                if (! ThemePackages::compatible($theme->requiredEshopVersion)) {
                    Notification::make()->danger()->title(__('admin.themes.incompatible'))->send();

                    return;
                }

                $settings = app(GeneralSettings::class);
                $settings->theme = $theme->name;
                $settings->save();
                app(ThemeManager::class)->flush();
                session()->forget(ApplyThemePreview::SESSION_THEME);

                Notification::make()->success()->title(__('admin.themes.activated', ['theme' => $theme->title()]))->send();
            });
    }

    public function checkAction(): Action
    {
        return Action::make('check')
            ->label(__('admin.themes.check'))
            ->link()->size('sm')
            ->modalHeading(fn (array $arguments) => __('admin.themes.check_title', ['theme' => app(ThemeManager::class)->find($arguments['theme'] ?? '')?->title()]))
            ->modalContent(fn (array $arguments) => view('filament.pages.theme-check', [
                'results' => ($theme = app(ThemeManager::class)->find($arguments['theme'] ?? '')) ? app(ThemeChecker::class)->check($theme) : [],
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('admin.themes.close'));
    }

    public function exportAction(): Action
    {
        return Action::make('export')
            ->label(__('admin.themes.export'))
            ->link()->size('sm')
            ->action(function (array $arguments): ?BinaryFileResponse {
                $theme = app(ThemeManager::class)->find($arguments['theme'] ?? '');

                return $theme ? response()->download(app(ThemePackages::class)->export($theme))->deleteFileAfterSend() : null;
            });
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('admin.themes.delete'))
            ->link()->size('sm')->color('danger')
            ->requiresConfirmation()
            ->modalDescription(__('admin.themes.delete_confirm'))
            ->action(function (array $arguments): void {
                $theme = app(ThemeManager::class)->find($arguments['theme'] ?? '');

                try {
                    $theme && app(ThemePackages::class)->delete($theme);
                    Notification::make()->success()->title(__('admin.themes.deleted'))->send();
                } catch (Throwable $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();
                }
            });
    }
}
