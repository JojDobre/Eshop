<?php

namespace Modules\Invoices\Filament\Pages;

use App\Filament\Pages\Settings\BaseSettingsPage;
use App\Settings\InvoiceSettings;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Tax\Filament\Pages\ManageTaxSettings;

class ManageInvoiceSettings extends BaseSettingsPage
{
    protected static string $settings = InvoiceSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 45;

    protected static ?string $slug = 'settings/invoices';

    public static function getNavigationLabel(): string
    {
        return __('invoices::admin.settings.title');
    }

    public function getTitle(): string
    {
        return __('invoices::admin.settings.title');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('invoices::admin.settings.'.$key);
        $prefix = fn (string $name) => TextInput::make($name)->maxLength(10)->regex('/^[A-Za-z0-9\-\/]*$/');

        return $schema->components([
            Section::make($t('section_numbering'))->description($t('section_numbering_help'))->columns(3)->schema([
                $prefix('invoice_prefix')->label($t('invoice_prefix')),
                $prefix('proforma_prefix')->label($t('proforma_prefix')),
                $prefix('credit_note_prefix')->label($t('credit_note_prefix')),
            ]),
            Section::make($t('section_automation'))->schema([
                Toggle::make('issue_proforma')->label($t('issue_proforma')),
                Toggle::make('issue_invoice_when_paid')->label($t('issue_invoice_when_paid')),
                TextInput::make('due_days')->label($t('due_days'))->integer()->minValue(0)->maxValue(365)->required(),
                Textarea::make('footer')->label($t('footer'))->helperText($t('footer_help'))->rows(2)->maxLength(500),
            ]),
        ]);
    }

    /** V menu pod položkou „ManageTaxSettings“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return ManageTaxSettings::getNavigationLabel();
    }
}
