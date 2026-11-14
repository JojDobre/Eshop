<?php

namespace Modules\Forms\Filament\Resources\Forms;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Modules\Forms\Filament\Resources\Forms\Pages\CreateForm;
use Modules\Forms\Filament\Resources\Forms\Pages\EditForm;
use Modules\Forms\Filament\Resources\Forms\Pages\ListForms;
use Modules\Forms\Filament\Resources\Submissions\FormSubmissionResource;
use Modules\Forms\Models\Form;
use UnitEnum;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Content;
    }

    public static function getModelLabel(): string
    {
        return __('forms::admin.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('forms::admin.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('forms.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('forms::admin.'.$key);

        return $schema->columns(3)->components([
            Section::make()->columnSpan(2)->schema([
                TextInput::make('name')->label($t('name'))->required()->maxLength(160),
                Repeater::make('fields')->label($t('fields'))
                    ->schema([
                        TextInput::make('label')->label($t('field_label'))->required()->maxLength(120),
                        Select::make('type')->label($t('field_type'))->required()->default('text')->live()->selectablePlaceholder(false)
                            ->options(collect(Form::TYPES)->mapWithKeys(fn (string $type) => [$type => $t('types.'.$type)])->all()),
                        Select::make('width')->label($t('field_width'))->default('full')->selectablePlaceholder(false)
                            ->options(['full' => $t('width_full'), 'half' => $t('width_half')]),
                        TextInput::make('placeholder')->label($t('placeholder'))->maxLength(120)
                            ->visible(fn (Get $get) => ! in_array($get('type'), ['select', 'checkbox', 'date'], true)),
                        Textarea::make('options')->label($t('options'))->helperText($t('options_help'))->rows(3)
                            ->visible(fn (Get $get) => $get('type') === 'select')->required(fn (Get $get) => $get('type') === 'select'),
                        Toggle::make('required')->label($t('required'))->inline(false),
                    ])
                    ->columns(3)
                    ->reorderable()->collapsible()
                    ->itemLabel(fn (array $state) => $state['label'] ?? null)
                    ->addActionLabel($t('add_field'))
                    ->minItems(1)
                    ->defaultItems(1),
            ]),
            Section::make()->schema([
                Toggle::make('is_active')->label($t('is_active'))->default(true),
                TextInput::make('recipient')->label($t('recipient'))->helperText($t('recipient_help'))->email()->maxLength(255),
                TextInput::make('submit_label')->label($t('submit_label'))->placeholder(__('forms::shop.submit'))->maxLength(60),
                Textarea::make('success_message')->label($t('success_message'))->placeholder(__('forms::shop.sent'))->rows(3)->maxLength(500),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('forms::admin.'.$key);

        return $table
            ->columns([
                TextColumn::make('name')->label($t('name'))->weight('medium')
                    ->description(fn (Form $record) => $t('block_hint')),
                TextColumn::make('submissions_count')->label($t('submissions'))->counts('submissions'),
                ToggleColumn::make('is_active')->label($t('is_active')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
            'create' => CreateForm::route('/create'),
            'edit' => EditForm::route('/{record}/edit'),
        ];
    }

    /** V menu pod položkou „FormSubmissionResource“ (zobrazí sa po jej otvorení), aby bolo hlavné menu krátke. */
    public static function getNavigationParentItem(): ?string
    {
        return FormSubmissionResource::getNavigationLabel();
    }
}
