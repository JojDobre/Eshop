<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            Select::make('locale')
                ->label(__('admin.profile.locale'))
                ->options(config('eshop.locales'))
                ->required()
                ->native(false),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getCurrentPasswordFormComponent(),
        ]);
    }

    protected function afterSave(): void
    {
        // Nový jazyk sa prejaví po obnovení stránky.
        $this->redirect(static::getUrl(), navigate: false);
    }
}
