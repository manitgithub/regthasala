import { chromium } from '@playwright/test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
const token = Date.now().toString(36);
const fixture = JSON.parse(execFileSync('php', ['tests/operations-fixture.php', token], { encoding: 'utf8' }));
const base = 'http://127.0.0.1:8080';
const browser = await chromium.launch();
const contexts = {};
const errors = [];
async function staff(role, email, password) {
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
  const page = await context.newPage();
  page.on('pageerror', error => errors.push(error.message));
  page.on('response', r => { if (r.status() >= 500) errors.push(`${r.status()} ${r.url()}`); });
  await page.goto(`${base}/site/staff-login`);
  await page.getByLabel('อีเมล', { exact: true }).fill(email);
  await page.getByLabel('รหัสผ่าน', { exact: true }).fill(password);
  await page.getByRole('button', { name: 'เข้าสู่ระบบ', exact: true }).click();
  await page.waitForURL('**/admin/index'); contexts[role] = context; return page;
}
async function review(page, kind, decision, reason = '') {
  await page.goto(`${base}/applicant/view?id=${fixture.applicationId}`);
  const form = page.locator(`form:has(input[name="kind"][value="${kind}"])`).first();
  await form.locator('select[name="decision"]').selectOption(decision);
  await form.locator('input[name="reason"]').fill(reason);
  await form.locator('button[type="submit"]').click();
  await page.waitForURL('**/applicant/view?id=*#review-section');
}
try {
  const creds = readFileSync('runtime/dev-access.txt', 'utf8');
  const admin = await staff('admin', creds.match(/Email: (.+)/)[1], creds.match(/Password: (.+)/)[1]);
  const admissions = await staff('admissions', `ops-admissions-${token}@example.test`, fixture.password);
  const finance = await staff('finance', `ops-finance-${token}@example.test`, fixture.password);
  const exam = await staff('exam', `ops-exam-${token}@example.test`, fixture.password);
  const committee = await staff('committee', `ops-committee-${token}@example.test`, fixture.password);
  const ownerContext = await browser.newContext(); const owner = await ownerContext.newPage();
  await owner.goto(`${base}/site/status`); await owner.getByLabel('เลขบัตรประชาชน', { exact: true }).fill(fixture.nationalId); await owner.getByLabel('วันเกิด 8 หลัก (พ.ศ.)', { exact: true }).fill('01012553'); await owner.getByRole('button', { name: 'ตรวจสอบสถานะ', exact: true }).click(); await owner.waitForURL('**/applicant/index');
  for (const path of ['/operations/users', '/operations/audit', '/operations/rooms', '/operations/scores']) assert.equal((await contexts.finance.request.get(base + path)).status(), 403, `Finance denied ${path}`);
  assert.equal((await contexts.finance.request.get(`${base}/applicant/document?id=${fixture.documents.photo}`)).status(), 404);
  assert.equal((await contexts.admissions.request.get(`${base}/applicant/document?id=${fixture.documents.payment_slip}`)).status(), 404);
  await review(admissions, 'qualification', 'correction');
  assert(await admissions.getByText('กรุณาระบุเหตุผลการส่งกลับแก้ไขหรือไม่อนุมัติ', { exact: true }).isVisible());
  await review(admissions, 'document', 'accepted');
  await review(admissions, 'qualification', 'correction', 'กรุณาตรวจชื่อโรงเรียนเดิม');
  await owner.goto(`${base}/applicant/view?id=${fixture.applicationId}`);
  await owner.getByRole('link', { name: 'แก้ไขและส่งใบสมัครใหม่', exact: true }).click();
  await owner.getByLabel('โรงเรียนเดิม / โรงเรียนที่กำลังศึกษา', { exact: true }).fill('โรงเรียนทดสอบแก้ไขแล้ว');
  await owner.getByLabel('รับรองความถูกต้องของข้อมูลที่แก้ไข', { exact: true }).check();
  await owner.getByRole('button', { name: 'ส่งข้อมูลแก้ไข', exact: true }).click(); await owner.waitForURL('**/applicant/view?id=*');
  assert(await owner.getByText('ส่งข้อมูลแก้ไขแล้ว รอเจ้าหน้าที่ตรวจสอบอีกครั้ง', { exact: true }).isVisible());
  await review(admissions, 'qualification', 'approved');
  await review(finance, 'payment', 'verified');
  assert(await finance.getByText('มีสิทธิ์สอบ', { exact: true }).first().isVisible());
  await exam.goto(`${base}/operations/rooms?round=${fixture.roundId}`);
  await exam.getByLabel('สนามสอบ / อาคาร', { exact: true }).fill('อาคารทดสอบ'); await exam.getByLabel('ห้องสอบ', { exact: true }).fill('101'); await exam.getByLabel('จำนวนที่นั่ง', { exact: true }).fill('1'); await exam.getByRole('button', { name: 'บันทึกห้องสอบ', exact: true }).click(); await exam.waitForURL('**/operations/rooms?round=*');
  await exam.getByRole('button', { name: 'จัดที่นั่งผู้มีสิทธิ์สอบที่ยังไม่มีบัตร', exact: true }).click(); await exam.waitForURL('**/operations/rooms?round=*'); assert(await exam.getByText('จัดที่นั่งเพิ่ม 1 คน', { exact: true }).isVisible());
  await exam.getByRole('button', { name: 'จัดที่นั่งผู้มีสิทธิ์สอบที่ยังไม่มีบัตร', exact: true }).click(); await exam.waitForURL('**/operations/rooms?round=*'); assert(await exam.getByText('จัดที่นั่งเพิ่ม 0 คน', { exact: true }).isVisible());
  await exam.getByRole('button', { name: 'เปิดพิมพ์บัตรสอบ', exact: true }).click(); await exam.waitForURL('**/operations/rooms?round=*');
  await owner.goto(`${base}/applicant/tickets`); await owner.getByRole('link', { name: 'ดูและพิมพ์บัตรประจำตัวสอบ', exact: true }).click(); assert(await owner.getByText('101', { exact: true }).isVisible());
  await owner.goto(`${base}/applicant/results`); assert(await owner.getByText('ยังไม่เปิดประกาศผลสอบ', { exact: true }).isVisible());
  await committee.goto(`${base}/operations/outcomes?round=${fixture.roundId}`); await committee.getByRole('button', { name: 'เปิดประกาศผลให้ผู้สมัคร', exact: true }).click(); await committee.waitForURL('**/operations/outcomes?round=*'); assert(await committee.getByText(/ยังระบุผลคัดเลือกไม่ครบ/).isVisible());
  for (const subjectId of fixture.subjectIds) {
    const r = await exam.goto(`${base}/operations/scores?round=${fixture.roundId}&subject=${subjectId}`); assert.equal(r.status(), 200);
    await exam.locator('select[name^="Scores"]').selectOption('present'); await exam.locator('input[name^="Scores"]').fill('35.50');
    await exam.getByRole('button', { name: 'บันทึกคะแนนหน้านี้', exact: true }).click(); await exam.waitForURL('**/operations/scores?round=*');
    assert(await exam.getByText('บันทึกคะแนนและการเข้าสอบแล้ว', { exact: true }).isVisible());
  }
  await exam.goto(`${base}/operations/outcomes?round=${fixture.roundId}`); await exam.locator('select[name^="Outcomes"]').selectOption('pass'); await exam.getByRole('button', { name: 'บันทึกผลคัดเลือกหน้านี้', exact: true }).click(); await exam.waitForURL('**/operations/outcomes?round=*');
  await committee.goto(`${base}/operations/outcomes?round=${fixture.roundId}`); await committee.getByRole('button', { name: 'เปิดประกาศผลให้ผู้สมัคร', exact: true }).click(); await committee.waitForURL('**/operations/outcomes?round=*'); assert(await committee.getByRole('heading', { name: 'ประกาศผลแล้ว', exact: true }).isVisible());
  await owner.goto(`${base}/applicant/results`); assert(await owner.getByText('ผ่านการคัดเลือก', { exact: true }).isVisible()); assert.equal(await owner.getByText('35.50', { exact: true }).count(), fixture.subjectIds.length);
  await committee.goto(`${base}/operations/reports?round=${fixture.roundId}`); assert(await committee.getByText('100.00', { exact: true }).first().isVisible()); const csv = await contexts.committee.request.get(`${base}/operations/export?round=${fixture.roundId}`); assert.equal(csv.status(), 200); assert((await csv.text()).includes('100'));
  for (const path of [`/operations/rooms?round=${fixture.roundId}`, `/operations/scores?round=${fixture.roundId}`, `/operations/outcomes?round=${fixture.roundId}`, `/operations/reports?round=${fixture.roundId}`, '/operations/users', '/operations/audit', `/applicant/view?id=${fixture.applicationId}`]) {
    for (const width of [1440, 390]) { await admin.setViewportSize({ width, height: 1000 }); const r = await admin.goto(base + path); assert.equal(r.status(), 200, path); assert(await admin.evaluate(() => document.documentElement.scrollWidth <= innerWidth), path); await admin.screenshot({ path: `tests/artifacts/operations-${path.split('?')[0].split('/').pop()}-${width}.png`, fullPage: true }); }
  }
  await admin.setViewportSize({ width: 1440, height: 1000 }); await admin.goto(`${base}/operations/audit?event=results.publish`); assert(await admin.locator('td').getByText('results.publish', { exact: true }).first().isVisible());
  const logText = await admin.locator('main').innerText(); assert(!logText.includes(fixture.password)); assert(!logText.includes('birth_date_hash'));
  await admin.goto(`${base}/operations/users?edit=${fixture.staffIds.finance}`); await admin.getByLabel('เปิดใช้งานบัญชี', { exact: true }).uncheck(); await admin.getByRole('button', { name: 'บันทึกผู้ใช้งาน', exact: true }).click(); await admin.waitForURL('**/operations/users');
  const blocked = await contexts.finance.request.get(`${base}/operations/reports`, { maxRedirects: 0 }); assert.equal(blocked.status(), 302);
  await admin.goto(`${base}/operations/users`);
  await admin.getByLabel('ชื่อผู้ใช้งาน', { exact: true }).fill(`เจ้าหน้าที่สร้างผ่านหน้าเว็บ ${token}`);
  await admin.getByLabel('อีเมลเจ้าหน้าที่', { exact: true }).fill(`ops-created-${token}@example.test`);
  await admin.getByLabel('บทบาท / สิทธิ์', { exact: true }).selectOption('admissions');
  await admin.getByLabel('รหัสผ่าน (อย่างน้อย 12 ตัวอักษร)', { exact: true }).fill(fixture.password);
  await admin.getByRole('button', { name: 'บันทึกผู้ใช้งาน', exact: true }).click(); await admin.waitForURL('**/operations/users');
  assert(await admin.getByRole('row').filter({ hasText: `เจ้าหน้าที่สร้างผ่านหน้าเว็บ ${token}` }).isVisible());
  await staff('created', `ops-created-${token}@example.test`, fixture.password);
  assert.equal((await contexts.created.request.get(`${base}/operations/users`)).status(), 403, 'New staff account gets only the assigned role');
  assert.equal(errors.length, 0, errors.join('\n'));
  console.log('Operations passed: review/reason/correction, role/document boundaries, room allocation/idempotency, ticket release, score entry, publish gates, owner results, reports/CSV, audit privacy and account deactivation.');
} finally { await browser.close(); execFileSync('php', ['tests/cleanup.php', token], { stdio: 'inherit' }); }
