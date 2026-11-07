<?php

namespace Modules\Translator\Filament\Pages;

use App\Core\Content\ShopLocales;
use App\Filament\Pages\Settings\BaseSettingsPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Translator\MachineTranslationService;
use Modules\Translator\TranslatorSettings;
use Throwable;

class ManageTranslatorSettings extends BaseSettingsPage
{
    protected static string $settings = TranslatorSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'settings/translator';

    public static function getNavigationLabel(): string
    {
        return __('translator::admin.title');
    }

    public function getTitle(): string
    {
        return __('translator::admin.title');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key, array $replace = []) => __('translator::admin.'.$key, $replace);
        $hasProvider = fn (Get $get) => in_array($get('provider'), ['deepl', 'google'], true);

        return $schema->components([
            Section::make()->description($t('intro'))->schema([
                Select::make('provider')->label($t('provider'))
                    ->options(['none' => $t('provider_none'), 'deepl' => 'DeepL', 'google' => 'Google Cloud Translation'])
                    ->required()->live()->native(false),
                TextInput::make('api_key')->label($t('api_key'))->helperText($t('api_key_help'))
                    ->password()->revealable()->autocomplete('off')->maxLength(255)
                    ->visible($hasProvider)->required($hasProvider),
                TextInput::make('monthly_limit')->label($t('monthly_limit'))->helperText($t('monthly_limit_help'))
                    ->integer()->minValue(0)->suffix($t('characters'))->visible($hasProvider),
                Text::make(fn () => $t('usage', [
                    'used' => number_format(app(TranslatorSettings::class)->usage_month === now()->format('Y-m') ? app(TranslatorSettings::class)->used_characters : 0, 0, ',', ' '),
                ]))->visible($hasProvider),
            ]),
            Section::make($t('glossary'))->description($t('glossary_help'))->collapsible()->schema([
                Repeater::make('glossary')->hiddenLabel()
                    ->schema([
                        TextInput::make('term')->label($t('term'))->required()->maxLength(120),
                        KeyValue::make('translations')->label($t('term_translations'))
                            ->keyLabel($t('language'))->valueLabel($t('translation'))
                            ->default(fn () => collect(app(ShopLocales::class)->enabled())->skip(1)->mapWithKeys(fn ($locale) => [$locale => ''])->all()),
                    ])
                    ->columns(2)->defaultItems(0)->addActionLabel($t('add_term')),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')
                ->label(__('translator::admin.test'))
                ->icon(Heroicon::OutlinedSignal)
                ->color('gray')
                ->action(function (MachineTranslationService $translator): void {
                    if (! $translator->isConfigured()) {
                        Notification::make()->warning()->title(__('translator::admin.not_configured'))->send();

                        return;
                    }

                    try {
                        $result = $translator->translate(['Dobrý deň'], 'sk', 'en');
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title(__('translator::admin.test_failed'))->body($e->getMessage())->persistent()->send();

                        return;
                    }

                    Notification::make()->success()->title(__('translator::admin.test_ok', ['result' => $result[0] ?? '']))->send();
                }),
        ];
    }
}
