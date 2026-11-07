<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('invoices.invoice_prefix', '');
        $this->migrator->add('invoices.proforma_prefix', 'ZF');
        $this->migrator->add('invoices.credit_note_prefix', 'D');
        $this->migrator->add('invoices.due_days', 14);
        $this->migrator->add('invoices.issue_proforma', true);
        $this->migrator->add('invoices.issue_invoice_when_paid', true);
        $this->migrator->add('invoices.footer', null);
    }
};
