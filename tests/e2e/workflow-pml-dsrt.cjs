/*
 * SIMAPAN E2E Workflow PML (Playwright, remote browser via CDP).
 * Alur interaktif Pengawas Pendataan Lapangan (PML):
 *   login -> daftar alokasi -> DSRT alokasi Susenas -> isi DSRT baru
 *   -> buka detail -> verifikasi DSRT.
 * Jalankan: node tests/e2e/workflow-pml-dsrt.cjs
 * Prasyarat: DB ter-seed (DevelopmentRoleUserSeeder memberi izin level-user
 * dev-only kepada field_supervisor: allocation.view, dsrt.view/manage/verify).
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.E2E_BASE_URL || 'http://127.0.0.1:8000';
const CDP = process.env.E2E_CDP_URL || 'http://127.0.0.1:9222';
const ARTIFACTS = path.join(__dirname, 'artifacts');
fs.mkdirSync(ARTIFACTS, { recursive: true });

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

const PML_EMAIL = 'field_supervisor@simapan.test';
const PML_PASSWORD = 'Simapan-Dev-2026';

// NUS/NURT unik agar skrip dapat diulang pada DB yang sama.
const STAMP = String(Date.now()).slice(-9);
const NUS = `NUS-E2E-${STAMP}`;
const NURT = `NURT-E2E-${STAMP}`;
const KRT_NAME = 'KRT Dummy E2E PML';

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

    // Login PML
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="email"]', PML_EMAIL);
    await page.fill('input[name="password"]', PML_PASSWORD);
    await Promise.all([
        page.waitForURL((u) => !String(u).includes('/login'), { timeout: 15000 }),
        page.click('button[type="submit"]'),
    ]);
    record('login field_supervisor (PML)', true);

    // 1. Daftar alokasi (butuh allocation.view level-user dev)
    const alokasiResp = await page.goto(`${BASE}/alokasi`, { waitUntil: 'domcontentloaded' });
    record('halaman alokasi 200 untuk PML', alokasiResp.status() === 200, `status=${alokasiResp.status()}`);

    // 2. Temukan alokasi Susenas yang punya modul DSRT aktif
    const alokasiLinks = await page.$$eval('a[href]', (as) => as
        .map((a) => new URL(a.getAttribute('href'), window.location.origin).pathname)
        .filter((p) => /^\/alokasi\/\d+$/.test(p)));
    const unique = [...new Set(alokasiLinks)];
    record('tautan detail alokasi ditemukan', unique.length > 0, `jumlah=${unique.length}`);

    let dsrtUrl = null;
    for (const alokasiPath of unique) {
        const resp = await page.goto(`${BASE}${alokasiPath}/dsrt`, { waitUntil: 'domcontentloaded' });
        if (resp.status() === 200) {
            dsrtUrl = `${alokasiPath}/dsrt`;
            break;
        }
    }
    record('modul DSRT alokasi Susenas terbuka (200)', Boolean(dsrtUrl), dsrtUrl || 'tidak ditemukan');


    // 3. Buka form tambah DSRT
    await page.goto(`${BASE}${dsrtUrl}`, { waitUntil: 'domcontentloaded' });
    const createHref = await page.$eval('a[href$="/dsrt/create"]', (a) => new URL(a.href, window.location.origin).pathname);
    record('tautan tambah DSRT tersedia', Boolean(createHref), createHref);

    await page.goto(`${BASE}${createHref}`, { waitUntil: 'domcontentloaded' });
    await page.fill('#nus', NUS);
    await page.fill('#nurt', NURT);
    await page.fill('#krt_name', KRT_NAME);
    await page.selectOption('#enumeration_status', 'COMPLETED');
    await page.fill('#family_number', '0001');
    await page.fill('#building_number', '10');

    // 4. Simpan DSRT baru
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        page.click('button[type="submit"]'),
    ]);
    const indexBody = await page.textContent('body');
    record('DSRT baru tersimpan dan muncul di daftar', indexBody.includes(KRT_NAME), `${NUS} / ${NURT}`);

    // 5. Buka detail DSRT yang BARU dibuat (cari baris berisi NUS, ambil tautannya)
    const detailLink = await page.evaluate((nus) => {
        for (const row of document.querySelectorAll('tr, li')) {
            if (row.textContent.includes(nus)) {
                const a = row.querySelector('a[href*="/dsrt/"]');
                if (a) return new URL(a.getAttribute('href'), window.location.origin).pathname;
            }
        }
        return null;
    }, NUS);
    record('tautan detail DSRT ditemukan', Boolean(detailLink), detailLink || 'tidak ada');

    const showResp = await page.goto(`${BASE}${detailLink}`, { waitUntil: 'domcontentloaded' });
    record('detail DSRT 200', showResp.status() === 200, `status=${showResp.status()}`);

    // 6. Verifikasi DSRT (DRAFT -> VERIFIED); toleran re-run bila sudah VERIFIED
    const showBodyBefore = await page.textContent('body');
    const verifyForm = await page.$('form[action$="/verify"]');

    if (verifyForm) {
        record('form verifikasi tersedia untuk PML', true);
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            verifyForm.evaluate((f) => f.submit()),
        ]);
        const showBody = await page.textContent('body');
        record('DSRT terverifikasi (VERIFIED)', showBody.includes('VERIFIED'), NURT);
    } else if (showBodyBefore.includes('VERIFIED')) {
        record('form verifikasi tersedia untuk PML', true, 're-run: sampel sudah VERIFIED sebelumnya');
        record('DSRT terverifikasi (VERIFIED)', true, 're-run');
    } else {
        record('form verifikasi tersedia untuk PML', false, 'form tidak ada dan status bukan VERIFIED');
        record('DSRT terverifikasi (VERIFIED)', false, 'dilewati');
    }

    await page.screenshot({ path: path.join(ARTIFACTS, 'workflow-pml-final.png') });

    // Hasil
    const failures = results.filter((r) => !r.ok);
    fs.writeFileSync(path.join(ARTIFACTS, 'workflow-pml-report.json'), JSON.stringify({ results, errors }, null, 2));
    process.stdout.write(`\nTotal langkah: ${results.length}, gagal: ${failures.length}, error teknis: ${errors.length}\n`);
    for (const e of errors) process.stdout.write(`  [ERR] ${e}\n`);

    await context.close();
    await browser.close();
    process.exit(failures.length > 0 || errors.length > 0 ? 1 : 0);
})().catch((e) => {
    process.stderr.write(String(e));
    process.exit(1);
});

