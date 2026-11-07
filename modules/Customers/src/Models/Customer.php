<?php

namespace Modules\Customers\Models;

use App\Core\Content\LocalizedUrls;
use App\Core\Content\ShopLocales;
use App\Core\Mail\TemplateMail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Modules\Orders\Models\Order;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $locale
 * @property array<string, mixed>|null $billing_address
 * @property array<string, mixed>|null $shipping_address
 */
class Customer extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword, Notifiable;

    /** Polia adresy uložené v billing_address / shipping_address. */
    public const ADDRESS_FIELDS = ['name', 'company', 'company_id', 'tax_id', 'vat_id', 'street', 'city', 'zip', 'country'];

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'last_login_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'legal_consents' => 'array',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('id');
    }

    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = mb_strtolower(trim($value));
    }

    /**
     * Odkaz na nové heslo v upraviteľnom e-maile (nie predvolená notifikácia Laravelu).
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        Mail::to($this->email, $this->name)->send(new TemplateMail('customers.password_reset', [
            'customer_name' => $this->name,
            'reset_url' => app(LocalizedUrls::class)->in($this->locale ?? app(ShopLocales::class)->default(),
                fn () => route('customer.password.reset', ['token' => $token, 'email' => $this->email])),
        ], $this->locale, ['reset_url']));
    }
}
