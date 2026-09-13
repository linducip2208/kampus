const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');

const baseUrl = process.env.CAMPUS_BASE_URL || 'http://127.0.0.1:8765';
const outputDir = path.resolve('public/marketing/screens-mobile');
const pages = [
    ['dashboard', '/admin'],
    ['student-list', '/admin/student-profiles'],
    ['student-form', '/admin/student-profiles/create'],
    ['public-home', '/'],
];

async function main() {
    fs.mkdirSync(outputDir, { recursive: true });
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 414, height: 896 }, deviceScaleFactor: 2, isMobile: true });
    const page = await context.newPage();

    await page.goto(`${baseUrl}/admin/login`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(800);
    await page.locator('input[type="email"]').first().fill('');
    await page.type('input[type="email"]', 'admin@kampus.test', { delay: 30 });
    await page.locator('input[type="password"]').first().fill('');
    await page.type('input[type="password"]', 'password', { delay: 30 });
    await page.locator('button[type="submit"]').first().click();
    await page.waitForTimeout(1200);

    for (const [name, uri] of pages) {
        await page.goto(`${baseUrl}${uri}`, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(1000);
        await page.screenshot({ path: path.join(outputDir, `${name}.png`), fullPage: true });
    }

    await browser.close();
    console.log(`Captured ${pages.length} mobile screenshots in ${outputDir}`);
}

main().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
