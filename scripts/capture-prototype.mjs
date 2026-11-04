import { chromium } from '@playwright/test';
import { readFileSync, writeFileSync, mkdirSync, copyFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';

const out = resolve('output/prototype');
mkdirSync(`${out}/screenshots`, { recursive: true });
mkdirSync('output/pdf', { recursive: true });
copyFileSync('web/images/thasala-crest.png', `${out}/crest.png`);
const demo = JSON.parse(execFileSync('php', ['-r', 'require "config/bootstrap.php"; new yii\\console\\Application(require "config/console.php"); $a=app\\models\\Application::find()->where(["like","reference","DEMO-R5-%",false])->orderBy("id DESC")->one(); echo json_encode(["applicationId"=>$a?->id]);'], { encoding: 'utf8' }));
if (!demo.applicationId) throw new Error('A labeled demo application is required.');
const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1600, height: 1050 }, deviceScaleFactor: 1.5 });
const page = await context.newPage();
const base = 'http://127.0.0.1:8080';
const shots = [];
const escape = value => value.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('"', '&quot;');
async function capture(name, title, subtitle, url, selector, prepare) {
  await page.goto(`${base}${url}`, { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  if (prepare) await prepare();
  await page.evaluate(() => { document.activeElement?.blur(); window.scrollTo(0, 0); });
  if (selector) await page.locator(selector).evaluate(el => window.scrollTo(0, window.scrollY + el.getBoundingClientRect().top - 32));
  await page.waitForTimeout(180);
  const image = `screenshots/${name}.png`;
  await page.screenshot({ path: `${out}/${image}`, fullPage: name === '01-admissions' });
  shots.push({ name, title, subtitle, image, url });
  console.log(`Captured ${name}`);
}
try {
  await capture('01-admissions', 'เลือกรอบรับสมัคร', 'การ์ดแยก ม.1 และ ม.4 พร้อมโครงการ จำนวนรับ ค่าสมัคร และช่วงรับสมัคร', '/site/index');
  await capture('02-programs', 'เลือกระดับชั้นและโครงการ', 'เลือกโครงการด้วยการ์ด Radio พร้อมเกณฑ์คุณสมบัติและค่าสมัคร', '/applicant/apply?id=4', '#admission-program', async () => page.locator('#admission-program input').first().check());
  await capture('03-education', 'แบบฟอร์มสมัครออนไลน์', 'ข้อมูลโรงเรียนเดิม ผลการเรียน และผู้ปกครอง อยู่ในใบสมัครเดียวกัน', '/applicant/apply?id=4', '#education-details', async () => {
    await page.locator('#admission-program input').first().check();
    await page.locator('#admissionform-school_name').fill('โรงเรียนต้นสังกัด (ข้อมูลทดสอบ)');
    await page.locator('#admissionform-school_province').fill('นครศรีธรรมราช');
    await page.locator('#admissionform-gpa').fill('3.50');
  });
  await capture('05-status', 'ตรวจสอบสถานะและพิมพ์บัตร', 'ยืนยันตัวตนด้วยเลขบัตรประชาชนและวันเกิด 8 หลัก พ.ศ. โดยไม่ต้องสมัครสมาชิกแยก', '/site/status');
  const credentials = readFileSync('runtime/dev-access.txt', 'utf8');
  await page.goto(`${base}/site/staff-login`);
  await page.getByLabel('อีเมล', { exact: true }).fill(credentials.match(/Email: (.+)/)[1]);
  await page.getByLabel('รหัสผ่าน', { exact: true }).fill(credentials.match(/Password: (.+)/)[1]);
  await page.getByRole('button', { name: 'เข้าสู่ระบบ', exact: true }).click();
  await page.waitForURL('**/admin/index');
  await capture('04-payment', 'ตั้งค่าบัญชีรับเงินรายรอบ', 'โรงเรียนกำหนดธนาคาร เลขบัญชี ชื่อบัญชี และข้อความแจ้งผู้สมัครได้เอง • ยังไม่มีบัญชีจริงในภาพ', '/admin/edit?entity=round&id=4', '#payment-settings');
  await capture('06-admin', 'ภาพรวมสำหรับเจ้าหน้าที่', 'ค้นหารอบ แยกสถานะ และเข้าสู่งานรับสมัครจากหน้าหลัก', '/admin/index');
  await capture('07-applications', 'รายการใบสมัคร', 'ติดตามผู้สมัคร โครงการ และสถานะ พร้อมเปิดรายละเอียดเพื่อพิจารณา', '/admin/applications?round=5');
  await capture('08-review', 'ตรวจคุณสมบัติและชำระเงิน', 'แยกผลพิจารณาคุณสมบัติออกจากผลตรวจรับชำระเงิน พร้อมเหตุผลประกอบ', `/applicant/view?id=${demo.applicationId}`, '#review-section');
  await capture('09-exam-operations', 'จัดสอบและรันเลขอัตโนมัติ', 'ตั้งรูปแบบเลขประจำตัวสอบ ห้อง และความจุ แยกตามรอบสอบ', '/operations/rooms?round=5', '#exam-number-settings');
  await capture('10-results', 'บันทึกและประกาศผลสอบ', 'บันทึกผลคัดเลือกก่อนเปิดประกาศ • ชุดข้อมูลนี้ยังไม่ได้บันทึกผลสอบ', '/operations/outcomes?round=5');
  await capture('11-reports', 'รายงานสรุป', 'จำนวนใบสมัคร ผลตรวจรับเงิน และรายรับ แยกตามรอบและโครงการ • ยอดในภาพเป็นข้อมูลทดสอบ', '/operations/reports?round=5');
  await capture('12-mobile', 'ใช้งานบนโทรศัพท์', 'หน้ารับสมัครปรับเป็นคอลัมน์เดียว รองรับผู้สมัครจากมือถือ', '/site/index', '.public-rounds', async () => page.setViewportSize({ width: 390, height: 1000 }));
  shots.sort((a, b) => a.name.localeCompare(b.name));
  writeFileSync(`${out}/manifest.json`, JSON.stringify(shots, null, 2));
  const flow = ['เลือกรอบ / โครงการ', 'กรอกข้อมูลและแนบสลิป', 'ตรวจสอบคุณสมบัติ / เงิน', 'จัดห้องและเลขประจำตัวสอบ', 'เปิดพิมพ์บัตรสอบ', 'บันทึก / ประกาศผล', 'รายงานสรุป'];
  const slides = [
    `<section class="slide cover"><div class="brand"><img src="crest.png"><span>โรงเรียนท่าศาลาประสิทธิ์ศึกษา</span></div><div class="cover-body"><span class="eyebrow">SYSTEM PROTOTYPE / 04 NOVEMBER 2026</span><h1>ระบบรับสมัคร<br>และจัดสอบออนไลน์</h1><p>ภาพหน้าจอระบบสำหรับนำเสนอและทบทวนการใช้งาน</p><div class="cover-rule"></div><div class="cover-meta"><span>ผู้สมัครสอบ</span><span>เจ้าหน้าที่รับสมัคร / การเงิน / จัดสอบ</span><span>ผู้บริหารและคณะกรรมการ</span></div></div><footer><span>เอกสารนำเสนอ • ใช้ข้อมูลทดสอบ ไม่ใช่ข้อมูลผู้สมัครจริง</span><b>01</b></footer></section>`,
    `<section class="slide flow"><header><span class="eyebrow">WORKFLOW</span><h2>ตั้งแต่รับสมัครจนถึงประกาศผล</h2><p>กระบวนการหลักที่แสดงในชุดภาพหน้าจอ</p></header><ol>${flow.map((step, i) => `<li><b>${String(i + 1).padStart(2, '0')}</b><span>${step}</span></li>`).join('')}</ol><div class="note">สถานะต้นแบบปัจจุบัน: ตั้งค่าบัญชีรับเงินรายรอบก่อนเปิดรับเงินจริง และยังไม่เปิดประกาศผลสอบในชุดข้อมูลทดสอบ</div><footer><span>โรงเรียนท่าศาลาประสิทธิ์ศึกษา • ภาพรวมกระบวนการ</span><b>02</b></footer></section>`,
    ...shots.map((shot, i) => `<section class="slide screen ${shot.name === '12-mobile' ? 'mobile' : ''}"><header><span class="eyebrow">${shot.url.startsWith('/site') || shot.url.startsWith('/applicant/apply') ? 'APPLICANT EXPERIENCE' : 'STAFF EXPERIENCE'} / ${String(i + 1).padStart(2, '0')}</span><h2>${escape(shot.title)}</h2><p>${escape(shot.subtitle)}</p></header><figure><img src="${shot.image}" alt="${escape(shot.title)}"></figure><footer><span>ภาพหน้าจอจากระบบที่พัฒนา • ข้อมูลทดสอบ • 4 พฤศจิกายน 2569</span><b>${String(i + 3).padStart(2, '0')}</b></footer></section>`),
  ];
  const html = `<!doctype html><html lang="th"><head><meta charset="utf-8"><title>Prototype - โรงเรียนท่าศาลาประสิทธิ์ศึกษา</title><style>
@font-face{font-family:Noto;src:url(data:font/woff2;base64,${readFileSync('web/fonts/thai-400.woff2').toString('base64')})}*{box-sizing:border-box}body{margin:0;background:#e6e8ec;color:#292c32;font-family:Noto,Arial,sans-serif;letter-spacing:0}.slide{position:relative;width:1600px;height:1120px;background:#f7f8fa;margin:24px auto;padding:48px 68px 68px;border-top:8px solid #f5ca42;overflow:hidden;page-break-after:always}header{height:148px}.eyebrow{font-family:Arial,sans-serif;font-size:16px;font-weight:700;color:#785b12}h2{font-size:38px;line-height:1.5;margin:8px 0 2px}header p{font-size:20px;color:#626975;margin:0;line-height:1.6}figure{margin:0;height:822px;display:flex;align-items:flex-start;justify-content:center}figure img{display:block;max-width:100%;max-height:100%;object-fit:contain;border:1px solid #d8dce2;border-radius:6px}.mobile figure{justify-content:center}footer{position:absolute;bottom:24px;left:68px;right:68px;display:flex;justify-content:space-between;align-items:center;border-top:1px solid #d8dce2;padding-top:16px;color:#626975;font-size:15px}footer b{font-family:Arial;font-size:18px;color:#292c32}.cover{background:#303238;color:#fff}.brand{display:flex;align-items:center;gap:24px;font-size:24px}.brand img{width:76px;height:86px;object-fit:contain;background:#fff;padding:6px;border-radius:6px}.cover-body{margin-top:115px}.cover .eyebrow{color:#f5ca42;font-size:18px}.cover h1{font-size:74px;line-height:1.5;margin:22px 0 16px;font-weight:600}.cover-body>p{font-size:28px;color:#d9dce3}.cover-rule{width:120px;height:6px;background:#f5ca42;margin:44px 0}.cover-meta{display:flex;gap:44px;font-size:20px;color:#d9dce3}.cover footer{color:#c4c8d1;border-color:#626975}.cover footer b{color:#f5ca42}.flow ol{display:grid;grid-template-columns:1fr 1fr;gap:0 64px;margin:40px 0 24px;padding:0;list-style:none}.flow li{padding:28px 0;display:flex;align-items:center;gap:28px;border-bottom:1px solid #d8dce2;font-size:26px}.flow li b{font-family:Arial;color:#785b12;font-size:22px}.note{padding:24px 0;font-size:21px;color:#626975;max-width:1200px;line-height:1.8}@page{size:1600px 1120px;margin:0}@media print{body{background:#fff}.slide{margin:0;break-inside:avoid}.slide:last-child{page-break-after:auto}}@media screen and (max-width:1600px){body{width:1600px}}
</style></head><body>${slides.join('')}</body></html>`;
  writeFileSync(`${out}/index.html`, html);
  await page.setViewportSize({ width: 1600, height: 1120 });
  await page.goto(`file://${out}/index.html`, { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  await page.pdf({ path: resolve('output/pdf/thasala-admissions-prototype.pdf'), printBackground: true, preferCSSPageSize: true });
  for (const [name, index] of [['cover', 0], ['workflow', 1], ['homepage', 2], ['review', 9], ['mobile', 13]]) {
    await page.locator('.slide').nth(index).screenshot({ path: `${out}/preview-${name}.png` });
  }
  console.log(`Created ${slides.length}-page prototype PDF and ${shots.length} screenshots.`);
} finally {
  await browser.close();
}
