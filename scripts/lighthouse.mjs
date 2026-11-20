/*
 * Rýchlosť a kvalita stránok obchodu (Lighthouse, mobilné zobrazenie).
 * Použitie: E2E_BASE=http://127.0.0.1:8000 node scripts/lighthouse.mjs [/cesta …]
 * Správy uloží do storage/e2e/lighthouse, pri skóre pod limitom skončí chybou.
 */
import fs from 'node:fs';
import { chromium } from '@playwright/test';
import * as chromeLauncher from 'chrome-launcher';
import lighthouse from 'lighthouse';

const base = (process.env.E2E_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const seed = fs.existsSync('storage/e2e/seed.json') ? JSON.parse(fs.readFileSync('storage/e2e/seed.json', 'utf8')) : {};
const paths = process.argv.slice(2).length ? process.argv.slice(2) : ['/', seed.white || '/kosik'];

// Minimálne skóre (0–100). Výkon na zdieľanom stroji kolíše, preto je limit nižší.
const limits = {
    performance: Number(process.env.LH_PERFORMANCE || 60),
    accessibility: Number(process.env.LH_ACCESSIBILITY || 90),
    'best-practices': Number(process.env.LH_BEST_PRACTICES || 85),
    seo: Number(process.env.LH_SEO || 90),
};

fs.mkdirSync('storage/e2e/lighthouse', { recursive: true });
const chrome = await chromeLauncher.launch({ chromePath: chromium.executablePath(), chromeFlags: ['--headless=new', '--no-sandbox'] });
let failed = false;

try {
    for (const path of paths) {
        const result = await lighthouse(base + path, { port: chrome.port, output: 'html', logLevel: 'error' });
        const name = path === '/' ? 'uvod' : path.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '');
        fs.writeFileSync(`storage/e2e/lighthouse/${name}.html`, result.report);

        const scores = Object.fromEntries(Object.keys(limits).map((key) => [key, Math.round((result.lhr.categories[key]?.score ?? 0) * 100)]));
        const below = Object.entries(scores).filter(([key, score]) => score < limits[key]);
        console.log(`${below.length ? '✗' : '✓'} ${path}  ` + Object.entries(scores).map(([key, score]) => `${key} ${score}`).join(' · '));
        failed ||= below.length > 0;
    }
} finally {
    await chrome.kill();
}

process.exit(failed ? 1 : 0);
