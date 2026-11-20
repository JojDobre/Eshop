// Testy v prehliadači (Playwright): npm run e2e. Návod: docs/TESTING.md.
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    outputDir: './storage/e2e/results',
    snapshotPathTemplate: '{testDir}/__snapshots__/{testFilePath}/{arg}-{projectName}{ext}',
    timeout: 45_000,
    expect: { timeout: 10_000, toHaveScreenshot: { maxDiffPixelRatio: 0.02, animations: 'disabled' } },
    fullyParallel: false,
    workers: process.env.CI ? 1 : 2,
    retries: process.env.CI ? 1 : 0,
    reporter: [['list'], ['html', { outputFolder: 'storage/e2e/report', open: 'never' }]],
    use: {
        baseURL: process.env.E2E_BASE || 'http://127.0.0.1:8000',
        locale: 'sk-SK',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        // Inštalácia čistého obchodu (iba s E2E_INSTALL=1) a prihlásenie správcu.
        { name: 'setup', testMatch: /setup\/.*\.setup\.js/ },
        {
            name: 'desktop',
            testIgnore: /(setup|visual)\//,
            use: { ...devices['Desktop Chrome'], storageState: 'storage/e2e/admin.json' },
            dependencies: ['setup'],
        },
        {
            name: 'mobil',
            testMatch: /(shop|mobile)\.spec\.js/,
            use: { ...devices['Pixel 7'], storageState: 'storage/e2e/admin.json' },
            dependencies: ['setup'],
        },
        {
            name: 'vzhlad',
            testMatch: /visual\/.*\.spec\.js/,
            use: { ...devices['Desktop Chrome'], storageState: 'storage/e2e/admin.json' },
            dependencies: ['setup'],
        },
    ],
});
