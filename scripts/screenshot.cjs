const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');

const baseUrl = process.env.CAMPUS_BASE_URL || 'http://127.0.0.1:8765';
const email = process.env.CAMPUS_DEMO_EMAIL || 'admin@kampus.test';
const password = process.env.CAMPUS_DEMO_PASSWORD || 'password';
const outputDir = path.resolve('public/marketing/screens');

const pages = [
    ['home', '/'],
    ['docs', '/docs'],
    ['blog', '/blog'],
    ['login', '/login'],
    ['admin-dashboard', '/admin'],
    ['universities', '/admin/universities'],
    ['study-programs', '/admin/study-programs'],
    ['courses', '/admin/courses'],
    ['curricula', '/admin/curricula'],
    ['course-offerings', '/admin/course-offerings'],
    ['class-sections', '/admin/class-sections'],
    ['students', '/admin/student-profiles'],
    ['enrollments', '/admin/student-enrollments'],
    ['study-plans', '/admin/study-plans'],
    ['invoices', '/admin/student-invoices'],
    ['payments', '/admin/payments'],
    ['audit-log', '/admin/audit-logs'],
    ['reports', '/reports'],
];

async function main() {
    fs.mkdirSync(outputDir, { recursive: true });
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 });
    const page = await context.newPage();

    await page.goto(`${baseUrl}/admin/login`, { waitUntil: 'networkidle' });
    await page.locator('input[type="email"]').first().fill('');
    await page.type('input[type="email"]', email, { delay: 30 });
    await page.locator('input[type="password"]').first().fill('');
    await page.type('input[type="password"]', password, { delay: 30 });
    await page.locator('button[type="submit"]').first().click();
    await page.waitForTimeout(1800);

    for (const [name, uri] of pages) {
        await page.goto(`${baseUrl}${uri}`, { waitUntil: 'networkidle' });
        await page.screenshot({ path: path.join(outputDir, `${name}.png`), fullPage: true });
    }

    await browser.close();
    console.log(`Captured ${pages.length} Campus ERP screenshots in ${outputDir}`);
}

main().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
