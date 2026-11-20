// Validácia formulára „Nový produkt“ v administrácii.
// Každý riadok tabuľky PRIPADY je samostatný test: vyplní platný produkt, pokazí jedno pole a overí chybu.
import { test, expect } from '@playwright/test';
import { field } from './helpers.js';

const NOVY_PRODUKT = '/admin/products/create';

const pole = field;

// Zbalené sekcie formulára treba pred vyplnením otvoriť.
const ZBALENE = {
  'Titulok pre vyhľadávače': 'Vyhľadávače (SEO)',
  'Popis pre vyhľadávače': 'Vyhľadávače (SEO)',
  'Bezpečnostné informácie a upozornenia': 'Bezpečnosť produktu (GPSR)',
};

async function otvorSekciu(page, popisPola) {
  const sekcia = ZBALENE[popisPola];
  if (!sekcia) return;
  const nadpis = page.getByRole('heading', { name: sekcia });
  if (!(await pole(page, popisPola).isVisible())) await nadpis.click();
}

async function vypln(page, hodnoty) {
  for (const [popis, hodnota] of Object.entries(hodnoty)) {
    await otvorSekciu(page, popis);
    await pole(page, popis).fill(hodnota);
  }
}

async function uloz(page) {
  await page.getByRole('button', { name: 'Vytvoriť', exact: true }).click();
}

// Pole, ktoré odmietol už prehliadač (required, min, max) – formulár sa vôbec neodošle.
async function odmietnutePrehliadacom(page, popis) {
  const vstup = pole(page, popis);
  expect(await vstup.evaluate((el) => el.checkValidity())).toBe(false);
  expect(await vstup.evaluate((el) => el.validationMessage)).not.toBe('');
}

// Platný produkt – každý test z neho vychádza a mení iba jedno pole.
const platny = () => ({
  'Názov': `Test validácie ${Date.now()}`,
  'Predajná cena': '12.50',
});

const PRIPADY = [
  // Povinné polia
  // Povinné polia a rozsahy čísel kontroluje už prehliadač (bublina pri poli), na server sa nič neodošle.
  { nazov: 'chýba názov', zmena: { 'Názov': '' }, prehliadac: 'Názov' },
  { nazov: 'chýba cena', zmena: { 'Predajná cena': '' }, prehliadac: 'Predajná cena' },

  // Cena: max. 9 číslic a 2 desatinné miesta, bez písmen a záporných čísel
  { nazov: 'cena s písmenami', zmena: { 'Predajná cena': 'abc' }, chyba: 'Predajná cena má neplatný formát.' },
  { nazov: 'záporná cena', zmena: { 'Predajná cena': '-5' }, chyba: 'Predajná cena má neplatný formát.' },
  { nazov: 'cena s 3 desatinnými miestami', zmena: { 'Predajná cena': '1.999' }, chyba: 'Predajná cena má neplatný formát.' },
  { nazov: 'pôvodná cena s písmenami', zmena: { 'Pôvodná cena': '12 eur' }, chyba: 'Pôvodná cena má neplatný formát.' },

  // Adresa (slug): iba malé písmená, číslice a pomlčky
  { nazov: 'slug s veľkými písmenami', zmena: { 'Adresa (slug)': 'Vajcia-XL' }, chyba: 'Adresa (slug) má neplatný formát.' },
  { nazov: 'slug s medzerou', zmena: { 'Adresa (slug)': 'cerstve vajcia' }, chyba: 'Adresa (slug) má neplatný formát.' },
  { nazov: 'slug s diakritikou', zmena: { 'Adresa (slug)': 'čerstvé-vajcia' }, chyba: 'Adresa (slug) má neplatný formát.' },
  { nazov: 'slug s dvojitou pomlčkou', zmena: { 'Adresa (slug)': 'vajcia--xl' }, chyba: 'Adresa (slug) má neplatný formát.' },

  // EAN: 8 až 14 číslic
  { nazov: 'EAN príliš krátky', zmena: { 'EAN (čiarový kód)': '1234567' }, chyba: 'EAN (čiarový kód) má neplatný formát.' },
  { nazov: 'EAN s písmenami', zmena: { 'EAN (čiarový kód)': '12345678A' }, chyba: 'EAN (čiarový kód) má neplatný formát.' },

  // Sklad a množstvá (celé čísla, minimálne hodnoty)
  { nazov: 'upozornenie pri zápornom počte', zmena: { 'Upozorniť pri počte': '-1' }, prehliadac: 'Upozorniť pri počte' },
  { nazov: 'minimálne množstvo 0', zmena: { 'Minimálne množstvo v objednávke': '0' }, prehliadac: 'Minimálne množstvo v objednávke' },
  { nazov: 'minimálne množstvo nad 9999', zmena: { 'Minimálne množstvo v objednávke': '10000' }, prehliadac: 'Minimálne množstvo v objednávke' },
  { nazov: 'záporná hmotnosť', zmena: { 'Hmotnosť': '-100' }, prehliadac: 'Hmotnosť' },
  { nazov: 'sklad s desatinným číslom', zmena: { 'Na sklade (ks)': '2.5' }, prehliadac: 'Na sklade (ks)' },
];

test.describe('Nový produkt – validácia', () => {
  for (const pripad of PRIPADY) {
    test(pripad.nazov, async ({ page }) => {
      await page.goto(NOVY_PRODUKT);
      await vypln(page, { ...platny(), ...pripad.zmena });
      await uloz(page);

      if (pripad.prehliadac) {
        await odmietnutePrehliadacom(page, pripad.prehliadac);
      } else {
        await expect(page.getByText(pripad.chyba)).toBeVisible();
      }
      // Produkt sa neuložil – zostali sme na stránke vytvárania.
      await expect(page).toHaveURL(new RegExp(NOVY_PRODUKT + '$'));
    });
  }

  // Dĺžka textu: pole nedovolí napísať viac znakov, než je povolené.
  const DLZKY = [
    ['Názov', 200],
    ['Adresa (slug)', 220],
    ['Katalógové číslo (SKU)', 64],
    ['EAN (čiarový kód)', 14],
    ['Titulok pre vyhľadávače', 70],
    ['Popis pre vyhľadávače', 170],
  ];

  for (const [popis, max] of DLZKY) {
    test(`${popis}: najviac ${max} znakov`, async ({ page }) => {
      await page.goto(NOVY_PRODUKT);
      await otvorSekciu(page, popis);
      const vstup = pole(page, popis);
      await expect(vstup).toHaveAttribute('maxlength', String(max));
      await vstup.fill('1'.repeat(max + 10));
      expect((await vstup.inputValue()).length).toBe(max);
    });
  }

  test('platný produkt sa uloží, rovnaké SKU druhýkrát nie', async ({ page }) => {
    const sku = `TEST-${Date.now()}`;

    await page.goto(NOVY_PRODUKT);
    await vypln(page, { ...platny(), 'Katalógové číslo (SKU)': sku, 'Adresa (slug)': `test-${Date.now()}` });
    await uloz(page);
    await expect(page).toHaveURL(/\/admin\/products\/\d+\/edit/);

    await page.goto(NOVY_PRODUKT);
    await vypln(page, { ...platny(), 'Katalógové číslo (SKU)': sku });
    await uloz(page);
    await expect(page.getByText('Katalógové číslo (SKU) už existuje.')).toBeVisible();
    await expect(page).toHaveURL(new RegExp(NOVY_PRODUKT + '$'));
  });

  test('povinné polia sú označené a vyžadované', async ({ page }) => {
    await page.goto(NOVY_PRODUKT);
    await expect(pole(page, 'Názov')).toHaveAttribute('required');
    await expect(pole(page, 'Predajná cena')).toHaveAttribute('required');
    await expect(page.getByRole('textbox', { name: 'Názov*' })).toBeVisible();
  });
});
