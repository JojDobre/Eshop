<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Používateľ administrácie (zamestnanec obchodu).
 *
 * Zákazníci budú mať samostatnú tabuľku a prihlásenie, aby sa účty obchodu
 * a účty zákazníkov nikdy nemiešali.
 */
#[Fillable(['name', 'email', 'password', 'locale', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super-admin';

    /**
     * Predvolené hodnoty (rovnaké ako v databáze), aby boli dostupné aj hneď po vytvorení modelu.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'locale' => 'sk',
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->roles()->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }
}
