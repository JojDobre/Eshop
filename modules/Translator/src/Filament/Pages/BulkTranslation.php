<?php

namespace Modules\Translator\Filament\Pages;

use App\Core\Content\ShopLocales;
use App\Core\Content\TranslatableModels;
use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Translator\MachineTranslationService;
use Modules\Translator\TranslationQueue;
use UnitEnum;

/**
 * Hromadný preklad všetkého, čo v jazyku chýba. Preklad beží po dávkach: na tejto stránke,
 * kým je otvorená, a cez plánovač aj po jej zatvorení.
 */
class BulkTranslation extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?int $navigationSort = 95;

    protected static ?string $slug = 'translations';

    protected string $view = 'translator::filament.bulk-translation';

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** @var array{records: int, characters: int}|null */
    public ?array $estimate = null;

    public static function getNavigationLabel(): string
    {
        return __('translator::admin.bulk.title');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Content;
    }

    public function getTitle(): string
    {
        return __('translator::admin.bulk.title');
    }

    public static function canAccess(): bool
    {
        return (bool) (auth()->user()?->can('translations.manage') || auth()->user()?->can('settings.manage'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return app(ShopLocales::class)->isMultilingual();
    }

    public function mount(): void
    {
        $this->form->fill([
            'locale' => collect(app(ShopLocales::class)->enabled())->skip(1)->first(),
            'models' => array_keys(app(TranslatableModels::class)->all()),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('translator::admin.bulk.'.$key);
        $locales = app(ShopLocales::class);

        return $schema->statePath('data')->components([
            Select::make('locale')->label($t('locale'))
                ->options(collect($locales->enabled())->skip(1)->mapWithKeys(fn ($locale) => [$locale => $locales->label($locale)])->all())
                ->required()->native(false)->live()->afterStateUpdated(fn () => $this->estimate = null),
            CheckboxList::make('models')->label($t('content'))
                ->options(app(TranslatableModels::class)->all())
                ->required()->columns(3)->live()->afterStateUpdated(fn () => $this->estimate = null),
        ]);
    }

    public function pendingCount(): int
    {
        return app(TranslationQueue::class)->pending();
    }

    public function lastError(): ?string
    {
        return app(TranslationQueue::class)->lastError();
    }

    public function remaining(): ?int
    {
        return app(MachineTranslationService::class)->remaining();
    }

    public function isConfigured(): bool
    {
        return app(MachineTranslationService::class)->isConfigured();
    }

    /**
     * Spracovanie dávky počas otvorenej stránky (wire:poll).
     */
    public function processBatch(): void
    {
        if ($this->pendingCount() > 0) {
            app(TranslationQueue::class)->process(5);
        }
    }

    protected function getHeaderActions(): array
    {
        $t = fn (string $key, array $replace = []) => __('translator::admin.bulk.'.$key, $replace);

        return [
            Action::make('estimate')
                ->label($t('estimate'))
                ->icon(Heroicon::OutlinedCalculator)
                ->color('gray')
                ->action(function (): void {
                    $data = $this->form->getState();
                    $this->estimate = app(TranslationQueue::class)->estimate($data['models'], $data['locale']);
                }),
            Action::make('start')
                ->label($t('start'))
                ->icon(Heroicon::OutlinedPlay)
                ->disabled(fn () => ! $this->isConfigured())
                ->requiresConfirmation()
                ->modalDescription(fn () => $this->estimate ? $t('start_confirm', ['records' => $this->estimate['records'], 'characters' => number_format($this->estimate['characters'], 0, ',', ' ')]) : $t('start_confirm_unknown'))
                ->action(function () use ($t): void {
                    $data = $this->form->getState();
                    $count = app(TranslationQueue::class)->enqueue($data['models'], $data['locale']);
                    Notification::make()->success()->title($t('queued', ['count' => $count]))->send();
                }),
        ];
    }
}
