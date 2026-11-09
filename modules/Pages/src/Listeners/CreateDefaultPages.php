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

        foreach ([Page::ROLE_TERMS, Page::ROLE_PRIVACY, Page::ROLE_SHIPPING, Page::ROLE_CONTACT, Page::ROLE_ACCESSIBILITY] as $position => $role) {
            Page::query()->create([
                'name' => [$event->locale => $texts[$role]['name']],
                'content' => [$event->locale => '<p><strong>'.$texts['notice'].'</strong></p>'.$texts[$role]['content']],
                'role' => $role,
                'position' => $position + 1,
            ]);
        }
    }

    /** Kostra vyhlásenia podľa WCAG 2.1 AA / EN 301 549 (e-shopy podliehajú Aktu o prístupnosti od 28. 6. 2025). */
    public const ACCESSIBILITY = [
        'sk' => '<h2>Náš záväzok</h2><p>Chceme, aby náš obchod mohli používať všetci vrátane ľudí so zdravotným postihnutím. Obchod sa snažíme prevádzkovať v súlade s normou EN 301 549 a pravidlami WCAG 2.1 na úrovni AA.</p><h2>Stav súladu</h2><p>Obchod je čiastočne v súlade. Ovládanie je možné klávesnicou, obrázky majú textové popisy, formuláre majú popisky a texty spĺňajú požiadavky na kontrast.</p><h2>Nedostupný obsah</h2><p>[Doplňte, napr. staršie PDF dokumenty alebo obsah tretích strán.]</p><h2>Spätná väzba a kontakt</h2><p>Ak narazíte na problém s prístupnosťou, napíšte nám na [e-mail]. Odpovieme do [počet] dní.</p><h2>Dozorný orgán</h2><p>Ak nie ste spokojní s odpoveďou, môžete sa obrátiť na Slovenskú obchodnú inšpekciu (www.soi.sk).</p><p>Vyhlásenie bolo vypracované dňa [dátum].</p>',
        'cs' => '<h2>Náš závazek</h2><p>Chceme, aby náš obchod mohli používat všichni včetně lidí se zdravotním postižením. Obchod se snažíme provozovat v souladu s normou EN 301 549 a pravidly WCAG 2.1 na úrovni AA.</p><h2>Stav souladu</h2><p>Obchod je částečně v souladu. Ovládání je možné klávesnicí, obrázky mají textové popisy, formuláře mají popisky a texty splňují požadavky na kontrast.</p><h2>Nepřístupný obsah</h2><p>[Doplňte, např. starší PDF dokumenty nebo obsah třetích stran.]</p><h2>Zpětná vazba a kontakt</h2><p>Pokud narazíte na problém s přístupností, napište nám na [e-mail]. Odpovíme do [počet] dnů.</p><h2>Dozorový orgán</h2><p>Pokud nejste spokojeni s odpovědí, můžete se obrátit na Českou obchodní inspekci (www.coi.cz).</p><p>Prohlášení bylo vypracováno dne [datum].</p>',
        'en' => '<h2>Our commitment</h2><p>We want everyone, including people with disabilities, to be able to use our shop. We aim to comply with EN 301 549 and WCAG 2.1 level AA.</p><h2>Compliance status</h2><p>The shop is partially compliant. It can be operated with a keyboard, images have text alternatives, forms have labels and texts meet contrast requirements.</p><h2>Non-accessible content</h2><p>[Fill in, e.g. older PDF documents or third-party content.]</p><h2>Feedback and contact</h2><p>If you encounter an accessibility problem, write to us at [e-mail]. We will reply within [number] days.</p><h2>Enforcement</h2><p>If you are not satisfied with our reply, you can contact the national market surveillance authority.</p><p>This statement was prepared on [date].</p>',
    ];

    private const TEXTS = [
        'sk' => [
            'notice' => 'Toto je iba vzorová kostra. Doplňte vlastný text a nechajte ho skontrolovať právnikom.',
            'terms' => ['name' => 'Obchodné podmienky', 'content' => '<h2>1. Úvodné ustanovenia</h2><p>Predávajúci: [obchodné meno, sídlo, IČO, zápis v registri].</p><h2>2. Objednávka a uzavretie zmluvy</h2><p>[Doplňte.]</p><h2>3. Cena a platobné podmienky</h2><p>[Doplňte.]</p><h2>4. Dodanie tovaru</h2><p>[Doplňte.]</p><h2>5. Odstúpenie od zmluvy</h2><p>Spotrebiteľ má právo odstúpiť od zmluvy bez udania dôvodu do 14 dní od prevzatia tovaru. [Doplňte postup.]</p><h2>6. Reklamácie</h2><p>[Doplňte reklamačný poriadok.]</p><h2>7. Alternatívne riešenie sporov</h2><p>[Doplňte.]</p>'],
            'privacy' => ['name' => 'Ochrana osobných údajov', 'content' => '<h2>Prevádzkovateľ</h2><p>[Doplňte.]</p><h2>Aké údaje spracúvame a prečo</h2><p>[Doplňte.]</p><h2>Ako dlho údaje uchovávame</h2><p>[Doplňte.]</p><h2>Vaše práva</h2><p>[Doplňte.]</p>'],
            'accessibility' => ['name' => 'Vyhlásenie o prístupnosti', 'content' => self::ACCESSIBILITY['sk']],
            'shipping' => ['name' => 'Doprava a platba', 'content' => '<p>[Popíšte spôsoby dopravy, ich ceny a možnosti platby.]</p>'],
            'contact' => ['name' => 'Kontakt', 'content' => '<p>[Doplňte adresu, telefón, e-mail a otváracie hodiny.]</p>'],
        ],
        'cs' => [
            'notice' => 'Toto je pouze vzorová kostra. Doplňte vlastní text a nechte ho zkontrolovat právníkem.',
            'terms' => ['name' => 'Obchodní podmínky', 'content' => '<h2>1. Úvodní ustanovení</h2><p>Prodávající: [obchodní firma, sídlo, IČO, zápis v rejstříku].</p><h2>2. Objednávka a uzavření smlouvy</h2><p>[Doplňte.]</p><h2>3. Cena a platební podmínky</h2><p>[Doplňte.]</p><h2>4. Dodání zboží</h2><p>[Doplňte.]</p><h2>5. Odstoupení od smlouvy</h2><p>Spotřebitel má právo odstoupit od smlouvy bez udání důvodu do 14 dnů od převzetí zboží. [Doplňte postup.]</p><h2>6. Reklamace</h2><p>[Doplňte reklamační řád.]</p><h2>7. Mimosoudní řešení sporů</h2><p>[Doplňte.]</p>'],
            'privacy' => ['name' => 'Ochrana osobních údajů', 'content' => '<h2>Správce</h2><p>[Doplňte.]</p><h2>Jaké údaje zpracováváme a proč</h2><p>[Doplňte.]</p><h2>Jak dlouho údaje uchováváme</h2><p>[Doplňte.]</p><h2>Vaše práva</h2><p>[Doplňte.]</p>'],
            'accessibility' => ['name' => 'Prohlášení o přístupnosti', 'content' => self::ACCESSIBILITY['cs']],
            'shipping' => ['name' => 'Doprava a platba', 'content' => '<p>[Popište způsoby dopravy, jejich ceny a možnosti platby.]</p>'],
            'contact' => ['name' => 'Kontakt', 'content' => '<p>[Doplňte adresu, telefon, e-mail a otevírací dobu.]</p>'],
        ],
        'en' => [
            'notice' => 'This is only a template outline. Add your own text and have it reviewed by a lawyer.',
            'terms' => ['name' => 'Terms and conditions', 'content' => '<h2>1. Introduction</h2><p>Seller: [company name, address, company ID, registration].</p><h2>2. Orders and contract</h2><p>[Fill in.]</p><h2>3. Prices and payment</h2><p>[Fill in.]</p><h2>4. Delivery</h2><p>[Fill in.]</p><h2>5. Withdrawal from the contract</h2><p>Consumers may withdraw from the contract without giving a reason within 14 days of receiving the goods. [Describe the procedure.]</p><h2>6. Complaints</h2><p>[Fill in.]</p><h2>7. Dispute resolution</h2><p>[Fill in.]</p>'],
            'privacy' => ['name' => 'Privacy policy', 'content' => '<h2>Controller</h2><p>[Fill in.]</p><h2>What data we process and why</h2><p>[Fill in.]</p><h2>How long we keep data</h2><p>[Fill in.]</p><h2>Your rights</h2><p>[Fill in.]</p>'],
            'accessibility' => ['name' => 'Accessibility statement', 'content' => self::ACCESSIBILITY['en']],
            'shipping' => ['name' => 'Shipping and payment', 'content' => '<p>[Describe shipping methods, their prices and payment options.]</p>'],
            'contact' => ['name' => 'Contact', 'content' => '<p>[Add your address, phone, e-mail and opening hours.]</p>'],
        ],
    ];
}
