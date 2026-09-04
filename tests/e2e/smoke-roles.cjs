/*
 * SIMAPAN E2E Role Smoke Test (Playwright, remote browser via CDP).
 * Jalankan: node tests/e2e/smoke-roles.cjs
 * Prasyarat: browser Chromium headless berjalan di http://127.0.0.1:9222
 *            server app di http://127.0.0.1:8000, DB ter-seed (DevelopmentRoleUserSeeder).
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

const ADMIN_PASSWORD = envValue('SIMAPAN_ADMIN_PASSWORD', 'Simapan-Dev-2026');
const DEV_PASSWORD = 'Simapan-Dev-2026';

const ROLES = [
    { slug: 'administrator', email: envValue('SIMAPAN_ADMIN_EMAIL', 'admin@simapan.test'), password: ADMIN_PASSWORD },
    { slug: 'field_officer', email: 'field_officer@simapan.test', password: DEV_PASSWORD },
    { slug: 'field_supervisor', email: 'field_supervisor@simapan.test', password: DEV_PASSWORD },
    { slug: 'processing_officer', email: 'processing_officer@simapan.test', password: DEV_PASSWORD },
    { slug: 'processing_supervisor', email: 'processing_supervisor@simapan.test', password: DEV_PASSWORD },
    { slug: 'social_operator', email: 'social_operator@simapan.test', password: DEV_PASSWORD },
    { slug: 'ipds_operator', email: 'ipds_operator@simapan.test', password: DEV_PASSWORD },
    { slug: 'viewer', email: 'viewer@simapan.test', password: DEV_PASSWORD },
];

const START_PAGES = [
    '/login',
    '/dashboard',
    '/profile',
    '/admin/users',
    '/admin/roles',
    '/audit-logs',
    '/master/unit-kerja',
    '/master/jenis-survei',
    '/master/periode-survei',
    '/master/wilayah',
    '/master/petugas',
    '/alokasi',
    '/dokumen',
    '/dokumen/jenis',
    '/dokumen/lokasi',
    '/manifest',
    '/manifest/create',
    '/dokumen/jenis/create',
    '/dokumen/lokasi/create',
];


async function attachCollector(context) {
    const findings = [];
    context.on('page', (page) => {
        page.on('pageerror', (err) => findings.push({ kind: 'pageerror', detail: String(err).slice(0, 300) }));
        page.on('console', (msg) => {
            if (msg.type() === 'error') findings.push({ kind: 'console', detail: msg.text().slice(0, 300) });
        });
        page.on('requestfailed', (req) => findings.push({ kind: 'requestfailed', detail: `${req.method()} ${req.url()} :: ${req.failure()?.errorText}`.slice(0, 300) }));
        page.on('response', (res) => {
            const url = res.url();
            if (res.status() >= 400) {
                findings.push({ kind: 'http', detail: `${res.status()} ${url}`.slice(0, 300) });
            }
        });
    });
    return findings;
}

async function login(page, email, password) {
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await Promise.all([
        page.waitForURL((u) => !String(u).includes('/login'), { timeout: 15000 }),
        page.click('button[type="submit"]'),
    ]);
}

async function crawlRole(browser, role) {
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const findings = await attachCollector(context);
    const page = await context.newPage();
    const visited = new Map();
    const queue = [...START_PAGES];

    try {
        await login(page, role.email, role.password);
        visited.set('/login', { status: 200, ok: true, note: 'login-form' });
    } catch (e) {
        visited.set('/login', { status: 'LOGIN_FAILED', ok: false, note: String(e).slice(0, 300) });
        await context.close();
        return { role, findings, visited: [...visited.entries()].map(([p, r]) => ({ page: p, ...r })) };
    }

    let guard = 0;
    while (queue.length > 0 && guard < 150) {
        guard++;
        const target = queue.shift();
        if (visited.has(target)) continue;

        try {
            const resp = await page.goto(`${BASE}${target}`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            const status = resp ? resp.status() : 'NO_RESPONSE';
            const ok = OK_STATUSES.has(status);
            visited.set(target, { status, ok });

            if (status === 200) {
                // Kumpulkan tautan internal (absolut maupun relatif) agar tidak ada tautan mati
                const links = await page.$$eval('a[href]', (as, base) => as
                    .map((a) => a.getAttribute('href'))
                    .filter((h) => {
                        if (!h || h.includes('/logout') || h.startsWith('#') || h.startsWith('javascript:') || h.startsWith('mailto:')) return false;
                        try {
                            return new URL(h, base).origin === new URL(base).origin;
                        } catch {
                            return false;
                        }
                    })
                    .map((h) => new URL(h, base).pathname), BASE);
                for (const link of links) {
                    if (!visited.has(link) && !queue.includes(link)) queue.push(link);
                }
                if (target === '/dashboard') {
                    await page.screenshot({ path: path.join(ARTIFACTS, `dashboard-${role.slug}.png`) });
                }
            }
        } catch (e) {
            visited.set(target, { status: 'ERROR', ok: false, note: String(e).slice(0, 200) });
        }
    }

    await context.close();
    return { role, findings, visited: [...visited.entries()].map(([p, r]) => ({ page: p, ...r })) };
}

// 403 adalah kontrak RBAC Fase 1/2 (bukan bug); redirect juga wajar.
const OK_STATUSES = new Set([200, 301, 302, 303, 307, 403]);



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
    const report = { base: BASE, startedAt: new Date().toISOString(), roles: [] };

    for (const role of ROLES) {
        process.stdout.write(`Menjalankan role ${role.slug}...\n`);
        report.roles.push(await crawlRole(browser, role));
    }

    // Ringkasan
    let bugs = 0;
    for (const r of report.roles) {
        const bad = r.visited.filter((v) => v.ok === false);
        bugs += bad.length + r.findings.length;
        process.stdout.write(`\n=== ${r.role.slug} (${r.role.email}) ===\n`);
        process.stdout.write(`Halaman diuji: ${r.visited.length}, bermasalah: ${bad.length}, temuan teknis: ${r.findings.length}\n`);
        for (const b of bad) process.stdout.write(`  [PAGE] ${b.page} -> ${b.status} ${b.note || ''}\n`);
        for (const f of r.findings.slice(0, 20)) process.stdout.write(`  [${f.kind}] ${f.detail}\n`);
    }

    report.totalSuspectFindings = bugs;
    fs.writeFileSync(path.join(ARTIFACTS, 'report.json'), JSON.stringify(report, null, 2));
    process.stdout.write(`\nLaporan lengkap: ${path.join(ARTIFACTS, 'report.json')}\n`);

    await browser.close();
})().catch((e) => {
    process.stderr.write(String(e));
    process.exit(1);
});
