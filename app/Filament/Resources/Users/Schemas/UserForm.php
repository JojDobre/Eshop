<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('admin.users.name'))
                        ->required()
                        ->maxLength(120),
                    TextInput::make('email')
                        ->label(__('admin.users.email'))
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('password')
                        ->label(__('admin.users.password'))
                        ->password()
                        ->revealable()
                        ->rule(Password::min(10)->letters()->numbers())
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->helperText(fn (string $operation) => $operation === 'edit' ? __('admin.users.password_help_edit') : null)
                        ->autocomplete('new-password'),
                    Select::make('locale')
                        ->label(__('admin.users.locale'))
                        ->options(config('eshop.locales'))
                        ->default(fn () => app()->getLocale())
                        ->required()
                        ->native(false),
                    Select::make('roles')
                        ->label(__('admin.users.roles'))
                        ->relationship('roles', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Role $record) => __('admin.roles.'.$record->name))
                        ->multiple()
                        ->preload()
                        ->required(),
                    Toggle::make('is_active')
                        ->label(__('admin.users.is_active'))
                        ->helperText(__('admin.users.is_active_help'))
                        ->default(true),
                ]),
        ]);
    }
}
