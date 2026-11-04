import { createIcons, Layers2, Building2, CalendarRange, Files, BookOpen, FileCheck2, LogIn, UserPlus, LogOut, CircleCheck, Plus, Search, ArrowUpRight, Bookmark, Pencil, ArrowLeft, Settings2, FileText, Check, ArrowRight, ArrowDownToLine, Trash2, CircleAlert, GraduationCap, RotateCcw, Menu, Printer, ChartNoAxesColumn, Users, History } from 'lucide';
import './thai-address.js';

const appIcons = { Layers2, Building2, CalendarRange, Files, BookOpen, FileCheck2, LogIn, UserPlus, LogOut, CircleCheck, Plus, Search, ArrowUpRight, Bookmark, Pencil, ArrowLeft, Settings2, FileText, Check, ArrowRight, ArrowDownToLine, Trash2, CircleAlert, GraduationCap, RotateCcw, Menu, Printer, ChartNoAxesColumn, Users, History };
createIcons({ icons: appIcons });
document.querySelector('[data-print-ticket]')?.addEventListener('click', () => window.print());

const navToggle = document.querySelector('.mobile-nav-toggle');
const navigationPanel = document.getElementById('navigation-panel');
function toggleNavigation(open) {
  navigationPanel?.classList.toggle('is-open', open);
  navToggle?.setAttribute('aria-expanded', String(open));
  navToggle?.setAttribute('aria-label', open ? 'ปิดเมนูหลัก' : 'เปิดเมนูหลัก');
  navToggle?.setAttribute('title', open ? 'ปิดเมนูหลัก' : 'เปิดเมนูหลัก');
}
navToggle?.addEventListener('click', () => toggleNavigation(navToggle.getAttribute('aria-expanded') !== 'true'));
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && navToggle?.getAttribute('aria-expanded') === 'true') {
    toggleNavigation(false);
    navToggle.focus();
  }
});

document.querySelectorAll('[data-filter]').forEach(input => {
  const table = document.getElementById(input.dataset.filter);
  const segments = document.querySelector(`[data-filter-tabs="${input.dataset.filter}"]`);
  function filterRows() {
    const query = input.value.trim().toLocaleLowerCase('th');
    const state = segments?.querySelector('[aria-pressed="true"]')?.dataset.roundState || 'all';
    const rows = [...(table?.querySelectorAll('[data-search-row]') || [])];
    rows.forEach(row => {
      row.hidden = !row.textContent.toLocaleLowerCase('th').includes(query) || state !== 'all' && row.dataset.state !== state;
    });
    const count = rows.filter(row => !row.hidden).length;
    const empty = table?.querySelector('[data-filter-empty]');
    if (empty) empty.hidden = count !== 0;
    const summary = document.querySelector(`[data-filter-count="${input.dataset.filter}"]`);
    if (summary) summary.textContent = `แสดง ${count} จาก ${rows.length} รอบ`;
  }
  input.addEventListener('input', filterRows);
  segments?.querySelectorAll('button').forEach(button => button.addEventListener('click', () => {
    segments.querySelectorAll('button').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
    filterRows();
  }));
});

const application = document.getElementById('application-form');
if (application) {
  const choices = [...application.querySelectorAll('input[type="checkbox"][data-fee]')];
  function calculate() {
    const checked = choices.filter(input => input.checked);
    const cents = checked.reduce((sum, input) => sum + Math.round(Number(input.dataset.fee) * 100), 0);
    document.getElementById('selected-count').textContent = `${checked.length} วิชา`;
    document.getElementById('selected-total').textContent = (cents / 100).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const paymentTotal = document.getElementById('payment-total');
    if (paymentTotal) paymentTotal.textContent = document.getElementById('selected-total').textContent;
  }
  choices.forEach(input => input.addEventListener('change', calculate));
  calculate();
}

const programSelect = document.getElementById('admission-program');
if (programSelect) {
  function updateProgram() {
    const option = programSelect.querySelector('input[type="radio"]:checked');
    const selected = Boolean(option);
    const subjects = [...document.querySelectorAll('[data-admission-subject]')];
    subjects.forEach(row => { row.hidden = !selected || !row.dataset.programIds.split(',').includes(option.value); });
    document.getElementById('selected-count').textContent = `${subjects.filter(row => !row.hidden).length} วิชา`;
    document.getElementById('selected-total').textContent = Number(option?.dataset.fee || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const paymentTotal = document.getElementById('payment-total');
    if (paymentTotal) paymentTotal.textContent = document.getElementById('selected-total').textContent;
    document.getElementById('gpa-basis').textContent = selected ? `${option.dataset.gpaBasis} · ขั้นต่ำ ${option.dataset.minGpa}` : 'เลือกโครงการเพื่อดูช่วงผลการเรียนที่ใช้';
    document.getElementById('program-requirements').textContent = selected ? `รับเข้าศึกษา ${option.dataset.intake} คน · เกรดเฉลี่ย ${option.dataset.gpaBasis} ไม่น้อยกว่า ${option.dataset.minGpa}` : 'เกณฑ์คุณสมบัติและวิชาสอบจะแสดงตามโครงการที่เลือก';
    const foreign = document.getElementById('admissionform-foreign_gpa');
    foreign.required = selected && option.dataset.minForeign !== '';
    document.getElementById('foreign-gpa-rule').textContent = foreign.required ? `ต้องไม่ต่ำกว่า ${option.dataset.minForeign}` : 'กรอกเมื่อโครงการกำหนด';
    document.getElementById('enrollment-fee').textContent = selected && option.dataset.enrollment ? `เงินบำรุงวันมอบตัว ${Number(option.dataset.enrollment).toLocaleString('th-TH')} บาท (ไม่รวมในยอดชำระครั้งนี้)` : '';
  }
  programSelect.addEventListener('change', updateProgram);
  updateProgram();
}

const scheduleRows = document.getElementById('schedule-rows');
if (scheduleRows) {
  const addButton = document.getElementById('add-schedule');
  const preset = document.getElementById('schedule-preset');
  let nextIndex = scheduleRows.children.length;
  function refreshRows() {
    const rows = [...scheduleRows.querySelectorAll('[data-schedule-row]')];
    rows.forEach((row, index) => { row.querySelector('[data-row-number]').textContent = index + 1; });
    document.getElementById('schedule-count').textContent = `${rows.length} / 50 รายการ`;
    document.getElementById('schedule-empty').hidden = rows.length !== 0;
    addButton.disabled = rows.length >= 50;
    preset.disabled = rows.length >= 50;
  }
  function addRow(title = '') {
    if (scheduleRows.children.length >= 50) return;
    const template = document.createElement('template');
    template.innerHTML = document.getElementById('schedule-row-template').innerHTML.replaceAll('__INDEX__', String(nextIndex++));
    const row = template.content.firstElementChild;
    row.querySelector('input[name$="[title]"]').value = title;
    scheduleRows.append(row);
    createIcons({ icons: appIcons });
    refreshRows();
    row.querySelector(title ? 'input[name$="[occurs_at]"]' : 'input[name$="[title]"]').focus();
  }
  addButton.addEventListener('click', () => addRow());
  preset.addEventListener('change', () => { if (preset.value) addRow(preset.value); preset.value = ''; });
  scheduleRows.addEventListener('click', event => {
    const remove = event.target.closest('[data-remove-schedule]');
    if (remove) { remove.closest('[data-schedule-row]').remove(); refreshRows(); }
  });
  refreshRows();
}
