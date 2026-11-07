<?php

namespace Modules\Customers\Filament\Resources\Customers\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Password;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\HandlesCustomFields;
use Modules\Customers\Actions\DeleteCustomer;
use Modules\Customers\Filament\Resources\Customers\CustomerResource;
use Modules\Customers\Models\Customer;

/**
 * @property Customer $record
 */
class EditCustomer extends EditRecord
{
    use HandlesCustomFields;

    protected static string $resource = CustomerResource::class;

    protected function customFieldEntity(): string
    {
        return CustomField::CUSTOMER;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->withCustomFields($data, $this->record);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->withoutCustomFields($data);
    }

    protected function afterSave(): void
    {
        $this->saveCustomFields($this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendReset')
                ->label(__('customers::admin.send_reset'))
                ->icon(Heroicon::OutlinedKey)
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    Password::broker('customers')->sendResetLink(['email' => $this->record->email]);
                    Notification::make()->success()->title(__('customers::admin.reset_sent'))->send();
                }),
            DeleteAction::make()
                ->modalDescription(__('customers::admin.delete_help'))
                ->using(fn (Customer $record) => app(DeleteCustomer::class)->handle($record) ?? true),
        ];
    }
}
