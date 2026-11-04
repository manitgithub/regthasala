import { chromium } from '@playwright/test';
import assert from 'node:assert/strict';
import { readFileSync, mkdirSync } from 'node:fs';

const base = process.env.TEST_BASE_URL || 'http://127.0.0.1:8080';
const credentials = readFileSync('runtime/dev-access.txt', 'utf8');
const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const page = await context.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
page.on('response', response => { if (response.status() >= 500) errors.push(`${response.status()} ${response.url()}`); });
mkdirSync('tests/artifacts', { recursive: true });
try {
  await page.goto(`${base}/site/staff-login`);
  await page.getByLabel('อีเมล', { exact: true }).fill(credentials.match(/Email: (.+)/)[1]);
  await page.getByLabel('รหัสผ่าน', { exact: true }).fill(credentials.match(/Password: (.+)/)[1]);
  await page.getByRole('button', { name: 'เข้าสู่ระบบ', exact: true }).click();
  await page.waitForURL('**/admin/index');
  const total = await page.locator('#round-list [data-search-row]').count();
  for (const state of ['setup', 'open', 'draft', 'closed', 'all']) {
    await page.locator(`[data-round-state="${state}"]`).click();
    assert.equal(await page.locator(`[data-round-state="${state}"]`).getAttribute('aria-pressed'), 'true');
    const expected = state === 'all' ? total : await page.locator(`#round-list [data-state="${state}"]`).count();
    assert.equal(await page.locator('#round-list [data-search-row]:visible').count(), expected);
    assert.equal(await page.locator('[data-filter-empty]').isVisible(), expected === 0);
  }
  await page.getByLabel('ค้นหารอบสอบ', { exact: true }).fill('NO-MATCH-UX-CHECK');
  assert.equal(await page.locator('#round-list [data-search-row]:visible').count(), 0);
  assert(await page.getByText('ไม่พบรอบสอบ', { exact: true }).isVisible());
  await page.getByLabel('ค้นหารอบสอบ', { exact: true }).fill('');
  assert.equal(await page.locator('#round-list [data-search-row]:visible').count(), total);
  const setup = page.getByRole('link', { name: 'ตั้งค่าบัญชี', exact: true }).first();
  if (await setup.count()) {
    await setup.click();
    assert.equal(new URL(page.url()).hash, '#payment-settings');
    assert(await page.getByRole('heading', { name: 'บัญชีรับชำระค่าสมัครของรอบสอบนี้', exact: true }).isVisible());
  }
  await page.goto(`${base}/admin/applications`);
  const reference = await page.locator('.applicant-name-cell .cell-secondary').first().textContent().catch(() => null);
  if (reference) {
    await page.getByLabel('ชื่อผู้สมัคร / เลขที่ใบสมัคร', { exact: true }).fill(reference.trim());
    await page.getByRole('button', { name: 'ค้นหา', exact: true }).click();
    await page.waitForURL(url => url.searchParams.get('q') === reference.trim());
    assert.equal(await page.locator('.applicant-name-cell').count(), 1);
    await page.getByRole('link', { name: 'ตรวจใบสมัคร', exact: true }).click();
    assert(new URL(page.url()).pathname === '/applicant/view');
    assert(await page.getByRole('link', { name: 'ใบสมัครทั้งหมด', exact: true }).getAttribute('aria-current') === 'page');
  }
  await page.goto(`${base}/admin/applications?status=payment_review`);
  const statuses = await page.locator('td[data-label="สถานะ"]').allTextContents();
  assert(statuses.every(text => text.includes('รอตรวจสอบการชำระเงิน')));
  await page.getByLabel('ชื่อผู้สมัคร / เลขที่ใบสมัคร', { exact: true }).fill('NO-MATCH-UX-CHECK');
  await page.getByRole('button', { name: 'ค้นหา', exact: true }).click();
  await page.waitForURL(url => url.searchParams.get('q') === 'NO-MATCH-UX-CHECK');
  assert(await page.getByText('ไม่พบใบสมัครตามตัวกรอง', { exact: true }).isVisible());
  await page.getByRole('link', { name: 'ดูใบสมัครทั้งหมด', exact: true }).click();
  assert.equal(new URL(page.url()).search, '');
  await page.goto(`${base}/admin/applications?q[]=x&status[]=x&round[]=x`);
  assert(await page.getByRole('heading', { name: 'ใบสมัครทั้งหมด', exact: true }).isVisible());
  for (const width of [1440, 1024, 390, 320]) {
    await page.setViewportSize({ width, height: 1000 });
    for (const [path, name] of [['/admin/index', 'dashboard'], ['/admin/applications', 'applications'], ['/admin/round?id=5', 'round'], ['/site/index', 'public'], ['/applicant/apply?id=5', 'apply']]) {
      const response = await page.goto(base + path);
      assert.equal(response.status(), 200);
      await page.evaluate(() => document.fonts.ready);
      assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name} at ${width}px overflows`);
      if (['dashboard', 'applications'].includes(name)) await page.screenshot({ path: `tests/artifacts/ux-${name}-${width}.png`, fullPage: true });
    }
  }
  await page.goto(`${base}/admin/index`);
  const menu = page.getByRole('button', { name: 'เปิดเมนูหลัก', exact: true });
  assert.equal(await page.locator('#navigation-panel').isVisible(), false);
  await menu.click();
  assert(await page.getByRole('link', { name: 'ใบสมัครทั้งหมด', exact: true }).isVisible());
  await page.getByRole('button', { name: 'ปิดเมนูหลัก', exact: true }).press('Escape');
  assert.equal(await page.locator('#navigation-panel').isVisible(), false);
  assert.equal(errors.length, 0, errors.join('\n'));
  console.log('Admin UX checks passed: round filters/search/empty states, account shortcut, application search/status filters/detail links, mobile navigation and responsive pages. No data modified.');
} finally {
  await browser.close();
}
