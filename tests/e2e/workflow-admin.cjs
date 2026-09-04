/*
 * SIMAPAN E2E Workflow Test (Playwright, remote browser via CDP).
 * Alur: tambah item manifest -> submit manifest -> serah terima -> penugasan dokumen.
 * Jalankan: node tests/e2e/workflow-admin.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.E2E_BASE_URL || 'http://127.0.0.1:8000';
const CDP = process.env.E2E_CDP_URL || 'http://127.0.0.1:9222';
const ARTIFACTS = path.join(__dirname, 'artifacts');
fs.mkdirSync(ARTIFACTS, { recursive: true });

// Kredensial: env proses menang, lalu .env lokal, lalu dummy dev.
function loadEnvFile() {
    const envPath = path.join(__dirname, '..', '..', '.env');
    try {
        const text = fs.readFileSync(envPath, 'utf8');
        return Object.fromEntries(
            text.split(/\r?\n/).filter((l) => l.includes('=') && !l.trim().startsWith('#')).map((l) => {
                const i = l.indexOf('=');
                return [l.slice(0, i).trim(), l.slice(i + 1).trim().replace(/^"|"$/g, '')];
            }),
        );
    } catch {
        return {};
    }
}

const envFile = loadEnvFile();
function envValue(key, fallback) {
    return process.env[key] || envFile[key] || fallback;
}

const results = [];

function record(step, ok, note) {
    results.push({ step, ok, note: note || '' });
    process.stdout.write(`${ok ? 'OK  ' : 'FAIL'} ${step} ${note || ''}\n`);
}

// Sambung ke browser remote via CDP bila tersedia; kalau tidak, launch headless lokal (mode CI).
async function connectBrowser() {
    try {
        const withTimeout = Promise.race([
            chromium.connectOverCDP(CDP),
            new Promise((_, reject) => setTimeout(() => reject(new Error('CDP timeout')), 4000)),
        ]);
        const browser = await withTimeout;
        process.stdout.write(`Browser remote tersambung via CDP: ${CDP}\n`);
        return browser;
    } catch {
        process.stdout.write('CDP tidak tersedia, meluncurkan Chromium headless lokal (mode CI).\n');
        return chromium.launch({ headless: true });
    }
}

(async () => {
    const browser = await connectBrowser();
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();

    const errors = [];
    page.on('pageerror', (e) => errors.push(String(e).slice(0, 200)));
    page.on('response', (r) => {
        if (r.status() >= 500) errors.push(`${r.status()} ${r.url()}`);
    });

    // Login admin
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="email"]', envValue('SIMAPAN_ADMIN_EMAIL', 'admin@simapan.test'));
    await page.fill('input[name="password"]', envValue('SIMAPAN_ADMIN_PASSWORD', 'Simapan-Dev-2026'));
    await Promise.all([
        page.waitForURL((u) => !String(u).includes('/login'), { timeout: 15000 }),
        page.click('button[type="submit"]'),
    ]);
    record('login administrator', true);

    // 1. Halaman penugasan dokumen (regresi bug 500) — harus 200 dan punya opsi petugas
    await page.goto(`${BASE}/dokumen/1/penugasan`, { waitUntil: 'domcontentloaded' });
    const officerOptions = await page.$$eval('#officer_id option', (os) => os.length);
    record('penugasan dokumen 200 + dropdown petugas', officerOptions > 1, `opsi=${officerOptions}`);

    // Tugaskan petugas pertama (alur nyata)
    const officerValue = await page.$eval('#officer_id option:nth-child(2)', (o) => o.value);
    if (officerValue) {
        await page.selectOption('#officer_id', officerValue);
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            page.click('form[action$="penugasan"] button[type="submit"]'),
        ]);
        record('tugaskan petugas pengolahan', true, `officer_id=${officerValue}`);
    }

    // 2. Tambah item ke manifest draft
    await page.goto(`${BASE}/manifest/1`, { waitUntil: 'domcontentloaded' });
    const docOptions = await page.$$eval('select[name="document_id"] option', (os) => os.length);
    if (docOptions > 1) {
        await page.selectOption('select[name="document_id"]', { index: 1 });
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            page.click('form[action*="item"] button[type="submit"]'),
        ]);
        record('tambah item manifest', true);
    } else {
        record('tambah item manifest', false, 'tidak ada dokumen REGISTERED untuk dipilih');
    }

    // 3. Submit manifest
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        page.click('form[action$="submit"] button[type="submit"]'),
    ]);
    const statusText = await page.textContent('body');
    record('submit manifest', statusText.includes('SUBMITTED'), 'status SUBMITTED terlihat di halaman');

    // 4. Serah terima kini dapat diakses (regresi bug 404)
    const linkExists = await page.$('a[href$="/serah-terima"]');
    record('tautan serah terima muncul setelah submit', Boolean(linkExists));
    const resp = await page.goto(`${BASE}/manifest/1/serah-terima`, { waitUntil: 'domcontentloaded' });
    record('halaman serah terima 200', resp.status() === 200, `status=${resp.status()}`);

    // 5. Periksa (edit) dan terima item
    await page.goto(`${BASE}/manifest/1/serah-terima/periksa`, { waitUntil: 'domcontentloaded' });
    const qtyInputs = await page.$$('input[name$="[qty_received]"]');
    for (const input of qtyInputs) {
        const max = await input.getAttribute('max');
        await input.fill(max || '1');
    }
    const condSelects = await page.$$('select[name$="[condition_received]"]');
    for (const select of condSelects) {
        const value = await select.$eval('option:nth-child(2)', (o) => o.value);
        await select.selectOption(value);
    }
    const statusSelects = await page.$$('select[name$="[receipt_status]"]');
    for (const select of statusSelects) {
        const value = await select.$eval('option:nth-child(2)', (o) => o.value);
        await select.selectOption(value);
    }
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        page.click('button[type="submit"]'),
    ]);
    record('terima manifest (serah terima)', true);

    await page.screenshot({ path: path.join(ARTIFACTS, 'workflow-final.png') });

    // Hasil
    const failures = results.filter((r) => !r.ok);
    fs.writeFileSync(path.join(ARTIFACTS, 'workflow-report.json'), JSON.stringify({ results, errors }, null, 2));
    process.stdout.write(`\nTotal langkah: ${results.length}, gagal: ${failures.length}, error teknis: ${errors.length}\n`);
    for (const e of errors) process.stdout.write(`  [ERR] ${e}\n`);

    await context.close();
    await browser.close();
    process.exit(failures.length > 0 || errors.length > 0 ? 1 : 0);
})().catch((e) => {
    process.stderr.write(String(e));
    process.exit(1);
});
