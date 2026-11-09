/*
 * Jednoduchý záťažový test: N súbežných používateľov opakovane načítava stránku a meria
 * p50/p95 času odozvy (ekvivalent scenára k6 z PLAN.md, kapitola 18).
 *
 * Použitie: node scripts/load-test.cjs <adresa> [súbežnosť=10] [sekundy=20] [cookie] -- /cesta1 /cesta2 …
 */
const [base, concurrencyArg, secondsArg, cookie] = process.argv.slice(2);
const paths = process.argv.slice(process.argv.indexOf('--') + 1);
const concurrency = Number(concurrencyArg || 10);
const seconds = Number(secondsArg || 20);

const http = require('http');

// Jedna požiadavka bez keep-alive (vstavaný server PHP ho nepodporuje spoľahlivo).
const get = (url) => new Promise((resolve) => {
    const request = http.get(url, { agent: false, headers: cookie ? { cookie } : {} }, (response) => {
        response.resume();
        response.on('end', () => resolve(response.statusCode));
    });
    request.on('error', () => resolve(0));
    request.setTimeout(30000, () => { request.destroy(); resolve(0); });
});

const percentile = (values, p) => values[Math.min(values.length - 1, Math.floor(values.length * p))];

(async () => {
    console.log(`| Stránka | Požiadaviek | Chyby | p50 (ms) | p95 (ms) |`);
    console.log(`|---|---|---|---|---|`);

    for (const path of paths) {
        const times = [];
        let errors = 0;
        const end = Date.now() + seconds * 1000;

        // Zahriatie (cache, OPcache).
        await get(base + path);

        await Promise.all(Array.from({ length: concurrency }, async () => {
            while (Date.now() < end) {
                const start = performance.now();
                const status = await get(base + path);
                if (status === 0 || status >= 400) errors++;
                times.push(performance.now() - start);
            }
        }));

        times.sort((a, b) => a - b);
        console.log(`| ${path} | ${times.length} | ${errors} | ${percentile(times, 0.5).toFixed(0)} | ${percentile(times, 0.95).toFixed(0)} |`);
    }
})();
