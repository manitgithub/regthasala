import { chromium } from '@playwright/test';
import assert from 'node:assert/strict';
import { mkdirSync } from 'node:fs';

const base = 'http://127.0.0.1:8080';
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
mkdirSync('tests/artifacts', { recursive: true });
try {
  await page.goto(`${base}/site/index`);
  assert(!(await page.locator('main').innerText()).includes('ตัวอย่าง'), 'Public list must not contain example labels');
  await page.screenshot({ path: 'tests/artifacts/open-admission-rounds.png', fullPage: true });
  for (const grade of ['ม.1', 'ม.4']) {
    await page.goto(`${base}/site/index`);
    const article = page.locator('article').filter({ hasText: `รับนักเรียน ${grade} · ห้องเรียนพิเศษ` });
    assert.equal(await article.count(), 1);
    assert(await article.getByText('เปิดรับสมัคร', { exact: true }).isVisible());
    const href = await article.getByRole('link', { name: 'สมัครสอบ', exact: true }).getAttribute('href');
    await article.getByRole('link', { name: 'สมัครสอบ', exact: true }).click();
    assert(!(await page.locator('main').innerText()).includes('ตัวอย่าง'));
    const paymentReady = await page.locator('#paymentform-slip').count() > 0;
    assert.equal(await page.getByRole('button', { name: 'ยืนยันใบสมัคร', exact: true }).isEnabled(), paymentReady, 'Submission requires a configured payment account');
    await page.locator('#admission-program input[type="radio"]').first().check();
    assert.equal(await page.locator('#selected-total').textContent(), '100.00');
    assert((await page.locator('[data-admission-subject]:visible').count()) > 0);
    const id = new URL(base + href).searchParams.get('id');
    await page.goto(`${base}/site/admission-preview?id=${id}`);
    assert.equal(new URL(page.url()).pathname, '/applicant/apply');
    assert.equal(new URL(page.url()).searchParams.get('id'), id);
  }
  console.log('Public admission checks passed: published rounds, payment readiness, program selection and legacy links.');
} finally { await browser.close(); }
