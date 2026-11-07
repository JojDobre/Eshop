<?php

namespace Modules\Pages\Listeners;

use App\Core\Events\EshopInstalled;
use Modules\Pages\Models\Page;

/**
 * Predvolené stránky po inštalácii. Obsahujú iba kostru textu s výzvou na doplnenie –
 * nie sú to hotové právne dokumenty.
 */
class CreateDefaultPages
{
    public function handle(EshopInstalled $event): void
    {
        if (Page::query()->exists()) {
            return;
        }

        $locale = in_array($event->locale, ['sk', 'cs', 'en'], true) ? $event->locale : 'en';
        $texts = self::TEXTS[$locale];

        foreach ([Page::ROLE_TERMS, Page::ROLE_PRIVACY, Page::ROLE_SHIPPING, Page::ROLE_CONTACT] as $position => $role) {
            Page::query()->create([
                'name' => [$event->locale => $texts[$role]['name']],
                'content' => [$event->locale => '<p><strong>'.$texts['notice'].'</strong></p>'.$texts[$role]['content']],
                'role' => $role,
                'position' => $position + 1,
            ]);
        }
    }

    private const TEXTS = [
        'sk' => [
            'notice' => 'Toto je iba vzorová kostra. Doplňte vlastný text a nechajte ho skontrolovať právnikom.',
            'terms' => ['name' => 'Obchodné podmienky', 'content' => '<h2>1. Úvodné ustanovenia</h2><p>Predávajúci: [obchodné meno, sídlo, IČO, zápis v registri].</p><h2>2. Objednávka a uzavretie zmluvy</h2><p>[Doplňte.]</p><h2>3. Cena a platobné podmienky</h2><p>[Doplňte.]</p><h2>4. Dodanie tovaru</h2><p>[Doplňte.]</p><h2>5. Odstúpenie od zmluvy</h2><p>Spotrebiteľ má právo odstúpiť od zmluvy bez udania dôvodu do 14 dní od prevzatia tovaru. [Doplňte postup.]</p><h2>6. Reklamácie</h2><p>[Doplňte reklamačný poriadok.]</p><h2>7. Alternatívne riešenie sporov</h2><p>[Doplňte.]</p>'],
            'privacy' => ['name' => 'Ochrana osobných údajov', 'content' => '<h2>Prevádzkovateľ</h2><p>[Doplňte.]</p><h2>Aké údaje spracúvame a prečo</h2><p>[Doplňte.]</p><h2>Ako dlho údaje uchovávame</h2><p>[Doplňte.]</p><h2>Vaše práva</h2><p>[Doplňte.]</p>'],
            'shipping' => ['name' => 'Doprava a platba', 'content' => '<p>[Popíšte spôsoby dopravy, ich ceny a možnosti platby.]</p>'],
            'contact' => ['name' => 'Kontakt', 'content' => '<p>[Doplňte adresu, telefón, e-mail a otváracie hodiny.]</p>'],
        ],
        'cs' => [
            'notice' => 'Toto je pouze vzorová kostra. Doplňte vlastní text a nechte ho zkontrolovat právníkem.',
            'terms' => ['name' => 'Obchodní podmínky', 'content' => '<h2>1. Úvodní ustanovení</h2><p>Prodávající: [obchodní firma, sídlo, IČO, zápis v rejstříku].</p><h2>2. Objednávka a uzavření smlouvy</h2><p>[Doplňte.]</p><h2>3. Cena a platební podmínky</h2><p>[Doplňte.]</p><h2>4. Dodání zboží</h2><p>[Doplňte.]</p><h2>5. Odstoupení od smlouvy</h2><p>Spotřebitel má právo odstoupit od smlouvy bez udání důvodu do 14 dnů od převzetí zboží. [Doplňte postup.]</p><h2>6. Reklamace</h2><p>[Doplňte reklamační řád.]</p><h2>7. Mimosoudní řešení sporů</h2><p>[Doplňte.]</p>'],
            'privacy' => ['name' => 'Ochrana osobních údajů', 'content' => '<h2>Správce</h2><p>[Doplňte.]</p><h2>Jaké údaje zpracováváme a proč</h2><p>[Doplňte.]</p><h2>Jak dlouho údaje uchováváme</h2><p>[Doplňte.]</p><h2>Vaše práva</h2><p>[Doplňte.]</p>'],
            'shipping' => ['name' => 'Doprava a platba', 'content' => '<p>[Popište způsoby dopravy, jejich ceny a možnosti platby.]</p>'],
            'contact' => ['name' => 'Kontakt', 'content' => '<p>[Doplňte adresu, telefon, e-mail a otevírací dobu.]</p>'],
        ],
        'en' => [
            'notice' => 'This is only a template outline. Add your own text and have it reviewed by a lawyer.',
            'terms' => ['name' => 'Terms and conditions', 'content' => '<h2>1. Introduction</h2><p>Seller: [company name, address, company ID, registration].</p><h2>2. Orders and contract</h2><p>[Fill in.]</p><h2>3. Prices and payment</h2><p>[Fill in.]</p><h2>4. Delivery</h2><p>[Fill in.]</p><h2>5. Withdrawal from the contract</h2><p>Consumers may withdraw from the contract without giving a reason within 14 days of receiving the goods. [Describe the procedure.]</p><h2>6. Complaints</h2><p>[Fill in.]</p><h2>7. Dispute resolution</h2><p>[Fill in.]</p>'],
            'privacy' => ['name' => 'Privacy policy', 'content' => '<h2>Controller</h2><p>[Fill in.]</p><h2>What data we process and why</h2><p>[Fill in.]</p><h2>How long we keep data</h2><p>[Fill in.]</p><h2>Your rights</h2><p>[Fill in.]</p>'],
            'shipping' => ['name' => 'Shipping and payment', 'content' => '<p>[Describe shipping methods, their prices and payment options.]</p>'],
            'contact' => ['name' => 'Contact', 'content' => '<p>[Add your address, phone, e-mail and opening hours.]</p>'],
        ],
    ];
}
