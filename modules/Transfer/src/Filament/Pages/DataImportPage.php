<?php

namespace Modules\Transfer\Filament\Pages;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Transfer\Imports\Importers;
use Modules\Transfer\Imports\ImportRunner;
use Modules\Transfer\Imports\SpreadsheetReader;
use Modules\Transfer\Models\DataImport;
use UnitEnum;

/**
 * Import produktov a zákazníkov z CSV/XLSX: nahratie, priradenie stĺpcov s náhľadom a kontrolou chýb,
 * potom spracovanie po častiach (stránka sa sama obnovuje, kým import nie je hotový).
 *
 * @property-read Schema $form
 */
class DataImportPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?int $navigationSort = 81;

    protected static ?string $slug = 'import';

    protected string $view = 'transfer::import';

    /** @var array<string, mixed> */
    public array $data = [];

    public ?int $importId = null;

    /** @var array<string, string> stĺpec súboru → pole */
    public array $mapping = [];

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::System;
    }

    public static function getNavigationLabel(): string
    {
        return __('transfer::admin.import.title');
    }

    public function getTitle(): string
    {
        return __('transfer::admin.import.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('transfer.manage') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(['entity' => 'products']);
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('transfer::admin.import.'.$key);

        return $schema->statePath('data')->components([
            Section::make()->description($t('help'))->columns(2)->schema([
                Select::make('entity')->label($t('entity'))->required()->selectablePlaceholder(false)
                    ->options(collect(app(Importers::class)->all())->map(fn ($importer) => $importer->label())->all()),
                FileUpload::make('file')->label($t('file'))->required()->disk('local')->directory('imports')->visibility('private')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->maxSize(51200)->preserveFilenames(),
            ]),
        ]);
    }

    public function getImportProperty(): ?DataImport
    {
        return $this->importId ? DataImport::query()->find($this->importId) : null;
    }

    /** Po nahratí: zistí stĺpce a navrhne priradenie. */
    public function upload(): void
    {
        $state = $this->form->getState();
        $runner = app(ImportRunner::class);
        $importer = app(Importers::class)->find($state['entity']);
        $path = storage_path('app/private/'.$state['file']);
        $header = SpreadsheetReader::header($path);

        if ($header === []) {
            Notification::make()->danger()->title(__('transfer::admin.import.empty_file'))->send();

            return;
        }

        $import = DataImport::query()->create([
            'user_id' => auth()->id(),
            'entity' => $importer->key(),
            'file_path' => $state['file'],
            'file_name' => basename($state['file']),
            'total_rows' => SpreadsheetReader::count($path),
            'mapping' => $runner->guessMapping($importer, $header),
        ]);

        $this->importId = $import->id;
        $this->mapping = $import->mapping;
    }

    /**
     * @return array<string, string>
     */
    public function getFieldsProperty(): array
    {
        return $this->import ? app(Importers::class)->find($this->import->entity)->fields() : [];
    }

    /**
     * @return list<array{row: int, data: array<string, string>, errors: list<string>}>
     */
    public function getPreviewProperty(): array
    {
        if (! $this->import) {
            return [];
        }

        $this->import->mapping = $this->mapping;

        return app(ImportRunner::class)->preview($this->import);
    }

    public function start(): void
    {
        $import = $this->import;
        $importer = app(Importers::class)->find($import->entity);

        if (! in_array($importer->keyField(), $this->mapping, true)) {
            Notification::make()->danger()->title(__('transfer::admin.import.key_required', ['field' => $importer->fields()[$importer->keyField()] ?? $importer->keyField()]))->send();

            return;
        }

        $import->update(['mapping' => $this->mapping, 'status' => 'running']);
        $this->continueImport();
    }

    /** Ďalšia časť importu (volá sa opakovane, kým nie je hotový). */
    public function continueImport(): void
    {
        $import = $this->import;

        if ($import === null || $import->status !== 'running') {
            return;
        }

        if (app(ImportRunner::class)->process($import, 8.0)) {
            @unlink(app(ImportRunner::class)->path($import));
            Notification::make()->success()->title(__('transfer::admin.import.finished', ['created' => $import->created_count, 'updated' => $import->updated_count]))->send();
        }
    }

    public function restart(): void
    {
        $this->importId = null;
        $this->mapping = [];
        $this->form->fill(['entity' => 'products']);
    }
}
