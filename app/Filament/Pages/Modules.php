<?php

namespace App\Filament\Pages;

use App\Core\Modules\Exceptions\ModuleException;
use App\Core\Modules\Module;
use App\Core\Modules\ModuleManager;
use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Prehľad modulov so zapínaním a vypínaním.
 */
class Modules extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static ?int $navigationSort = 95;

    protected static ?string $slug = 'system/modules';

    protected string $view = 'filament.pages.modules';

    public static function getNavigationLabel(): string
    {
        return __('admin.modules.title');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::System;
    }

    public function getTitle(): string
    {
        return __('admin.modules.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('modules.manage') ?? false;
    }

    public function isSafeMode(): bool
    {
        return app(ModuleManager::class)->isSafeMode();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => $this->moduleRecords())
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.modules.module'))
                    ->weight('medium')
                    // Dlhý popis sa zalomí, aby sa tabuľka zmestila na šírku okna.
                    ->description(fn (array $record) => $record['description'])
                    ->wrap()
                    ->extraAttributes(['class' => 'max-w-xl']),
                TextColumn::make('version')
                    ->label(__('admin.modules.version')),
                TextColumn::make('requires')
                    ->label(__('admin.modules.requires'))
                    ->placeholder('—')
                    ->wrap()
                    ->extraAttributes(['class' => 'max-w-48'])
                    ->visibleFrom('lg'),
                TextColumn::make('status')
                    ->label(__('admin.modules.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => __('admin.modules.'.$state))
                    ->color(fn (string $state) => match ($state) {
                        'enabled', 'core' => 'success',
                        'problem' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip(fn (array $record) => $record['problem']),
            ])
            ->recordActions([
                Action::make('enable')
                    ->label(__('admin.modules.enable'))
                    ->visible(fn (array $record) => ! $record['core'] && ! $record['enabled'])
                    ->action(fn (array $record) => $this->toggle($record['key'], true)),
                Action::make('disable')
                    ->label(__('admin.modules.disable'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (array $record) => ! $record['core'] && $record['enabled'])
                    ->action(fn (array $record) => $this->toggle($record['key'], false)),
            ])
            ->paginated(false);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function moduleRecords(): array
    {
        $manager = app(ModuleManager::class);

        return collect($manager->all())
            ->mapWithKeys(fn (Module $module) => [$module->name => [
                'key' => $module->name,
                'title' => $module->title(),
                'description' => $module->description(),
                'version' => $module->version,
                'requires' => implode(', ', array_map(
                    fn (string $name) => $manager->find($name)?->title() ?? $name,
                    array_keys($module->requiredModules),
                )) ?: null,
                'core' => $module->core,
                'enabled' => $manager->isEnabled($module->name),
                'problem' => $manager->problem($module->name),
                'status' => match (true) {
                    $manager->problem($module->name) !== null => 'problem',
                    $module->core => 'core',
                    $manager->isEnabled($module->name) => 'enabled',
                    default => 'disabled',
                },
            ]])
            ->all();
    }

    private function toggle(string $name, bool $enable): void
    {
        $manager = app(ModuleManager::class);
        $title = $manager->find($name)?->title() ?? $name;

        try {
            $enable ? $manager->enable($name) : $manager->disable($name);
        } catch (ModuleException $e) {
            Notification::make()
                ->danger()
                ->title(__('admin.modules.error_notification'))
                ->body($this->explain($e, $manager))
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__($enable ? 'admin.modules.enabled_notification' : 'admin.modules.disabled_notification', ['module' => $title]))
            ->send();

        // Zmena sa prejaví pri ďalšej požiadavke (moduly sa načítavajú pri štarte aplikácie).
        $this->redirect(static::getUrl());
    }

    private function explain(ModuleException $e, ModuleManager $manager): string
    {
        $titles = implode(', ', array_map(fn (string $name) => $manager->find($name)?->title() ?? $name, $e->related));

        return match ($e->reason) {
            ModuleException::MISSING_DEPENDENCIES => __('admin.modules.missing_dependencies', ['modules' => $titles]),
            ModuleException::HAS_DEPENDENTS => __('admin.modules.has_dependents', ['modules' => $titles]),
            ModuleException::CORE_MODULE => __('admin.modules.core_module'),
            ModuleException::INCOMPATIBLE => __('admin.modules.incompatible'),
            default => $e->getMessage(),
        };
    }
}
