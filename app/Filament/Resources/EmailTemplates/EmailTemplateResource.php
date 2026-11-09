<?php

namespace App\Filament\Resources\EmailTemplates;

use App\Core\Mail\EmailTemplates;
use App\Filament\AdminGroup;
use App\Filament\Pages\Settings\ManageMailSettings;
use App\Filament\Resources\EmailTemplates\Pages\EditEmailTemplate;
use App\Filament\Resources\EmailTemplates\Pages\ListEmailTemplates;
use App\Models\EmailTemplate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Úprava textov e-mailov, ktoré posiela obchod (potvrdenie objednávky, zmena stavu, doklady…).
 */
class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?int $navigationSort = 41;

    protected static ?string $slug = 'settings/email-templates';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Settings;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.email_templates.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.email_templates.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.email_templates.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof EmailTemplate ? (self::templates()->definition($record->key)?->label() ?? $record->key) : '';
    }

    /**
     * Iba e-maily zapnutých modulov.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('key', array_keys(self::templates()->definitions()));
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('admin.email_templates.'.$key);
        $definition = fn (?EmailTemplate $record) => $record ? self::templates()->definition($record->key) : null;

        return $schema->columns(3)->components([
            Section::make()
                ->columnSpan(2)
                ->schema([
                    TextInput::make('subject')
                        ->label($t('subject'))
                        ->placeholder(fn (?EmailTemplate $record) => $definition($record)?->defaultSubject())
                        ->helperText($t('subject_help'))
                        ->maxLength(255),
                    MarkdownEditor::make('body')
                        ->label($t('body'))
                        ->helperText($t('body_help'))
                        ->toolbarButtons([['bold', 'italic', 'link'], ['heading'], ['bulletList', 'orderedList'], ['undo', 'redo']])
                        ->minHeight('22rem'),
                ]),
            Section::make($t('variables'))
                ->columnSpan(1)
                ->schema([
                    Text::make($t('variables_help')),
                    Text::make(fn (?EmailTemplate $record) => new HtmlString(
                        collect($definition($record)?->variables ?? [])
                            ->map(fn (string $variable) => '<code class="text-sm">{'.e($variable).'}</code> – '.e(__('admin.email_templates.vars.'.$variable)))
                            ->implode('<br>')
                    )),
                    Toggle::make('is_active')
                        ->label($t('is_active'))
                        ->helperText($t('is_active_help'))
                        ->visible(fn (?EmailTemplate $record) => (bool) $definition($record)?->canBeDisabled),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('admin.email_templates.'.$key);

        return $table
            ->columns([
                TextColumn::make('key')
                    ->label($t('email'))
                    ->formatStateUsing(fn (string $state) => self::templates()->definition($state)?->label() ?? $state)
                    ->description(fn (EmailTemplate $record) => self::templates()->definition($record->key)?->defaultSubject()),
                IconColumn::make('customized')
                    ->label($t('customized'))
                    ->state(fn (EmailTemplate $record) => filled($record->getTranslation('body', $record->getLocale(), false))
                        || filled($record->getTranslation('subject', $record->getLocale(), false)))
                    ->boolean(),
                ToggleColumn::make('is_active')
                    ->label($t('is_active'))
                    ->disabled(fn (EmailTemplate $record) => ! self::templates()->definition($record->key)?->canBeDisabled),
            ])
            ->defaultSort('key')
            ->paginated(false)
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailTemplates::route('/'),
            'edit' => EditEmailTemplate::route('/{record}/edit'),
        ];
    }

    private static function templates(): EmailTemplates
    {
        return app(EmailTemplates::class);
    }

    /** V menu pod položkou „ManageMailSettings“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return ManageMailSettings::getNavigationLabel();
    }
}
