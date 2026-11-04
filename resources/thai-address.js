import ThaiAddress from './vendor/thai-address.cjs';

let databasePromise;
function loadDatabase() {
  if (!databasePromise) databasePromise = ThaiAddress.load({ baseUrl: '/data/thai-address/', cache: false, retries: 0, timeout: 8000 }).then(database => {
    if (database.partial) throw new Error('Incomplete address data');
    return database;
  }).catch(error => { databasePromise = null; throw error; });
  return databasePromise;
}

document.querySelectorAll('[data-thai-address]').forEach(root => {
  const input = root.querySelector('[data-address-query]');
  const list = root.querySelector('[role="listbox"]');
  const status = root.querySelector('[data-address-status]');
  const retry = root.querySelector('[data-address-retry]');
  const parts = Object.fromEntries([...root.querySelectorAll('[data-address-part]')].map(field => [field.dataset.addressPart, field]));
  let database, results = [], active = -1;
  let selected = parts.subdistrict_code.value;
  function positionList() {
    if (list.hidden) return;
    const bounds = input.getBoundingClientRect();
    const viewportTop = window.visualViewport?.offsetTop || 0;
    const viewportHeight = window.visualViewport?.height || window.innerHeight;
    const below = viewportTop + viewportHeight - bounds.bottom - 8;
    const above = bounds.top - viewportTop - 8;
    const openAbove = below < 200 && above > below;
    list.classList.toggle('is-above', openAbove);
    list.style.maxHeight = `${Math.max(80, Math.min(300, openAbove ? above : below))}px`;
  }
  function close() {
    list.hidden = true;
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
    active = -1;
  }
  function choose(address) {
    const values = { subdistrict_code: address.subdistrict.code, subdistrict: address.subdistrict.th, district: address.district.th, province: address.province.th, postal_code: address.zip };
    Object.entries(values).forEach(([key, value]) => { parts[key].value = String(value); });
    selected = String(address.subdistrict.code);
    input.value = address.text;
    input.setCustomValidity('');
    status.textContent = 'เลือกพื้นที่แล้ว';
    close();
    input.focus({ preventScroll: true });
  }
  function setActive(index) {
    active = index;
    [...list.children].forEach((option, i) => option.setAttribute('aria-selected', String(i === active)));
    if (active >= 0) {
      input.setAttribute('aria-activedescendant', list.children[active].id);
      list.children[active].scrollIntoView({ block: 'nearest' });
    } else input.removeAttribute('aria-activedescendant');
  }
  function search() {
    if (!database) return;
    results = database.search(input.value, { limit: 8 });
    active = -1;
    list.replaceChildren();
    results.forEach((address, index) => {
      const option = document.createElement('li');
      option.id = `${list.id}-${index}`;
      option.setAttribute('role', 'option');
      option.setAttribute('aria-selected', 'false');
      const title = document.createElement('strong');
      title.textContent = `${address.province.code === 10 ? 'แขวง' : 'ตำบล'}${address.subdistrict.th}`;
      const detail = document.createElement('span');
      detail.textContent = `${address.province.code === 10 ? 'เขต' : 'อำเภอ'}${address.district.th} · ${address.province.th} · ${address.zip}`;
      option.append(title, detail);
      option.addEventListener('mousedown', event => event.preventDefault());
      option.addEventListener('click', () => choose(address));
      list.append(option);
    });
    list.hidden = !results.length;
    positionList();
    input.setAttribute('aria-expanded', String(results.length > 0));
    input.removeAttribute('aria-activedescendant');
    status.textContent = !input.value.trim() ? '' : results.length ? `พบพื้นที่ ${results.length} รายการ` : 'ไม่พบพื้นที่ที่ตรงกับคำค้น';
  }
  input.addEventListener('input', () => {
    selected = '';
    Object.values(parts).forEach(field => { field.value = ''; });
    input.setCustomValidity('กรุณาเลือกตำบล อำเภอ จังหวัดจากรายการ');
    search();
  });
  input.addEventListener('focus', () => { if (!selected && input.value.trim()) search(); });
  input.addEventListener('keydown', event => {
    if (event.isComposing) return;
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      if (list.hidden) search();
      if (results.length) setActive(event.key === 'ArrowDown' ? Math.min(active + 1, results.length - 1) : Math.max(active - 1, 0));
    } else if (event.key === 'Enter' && !list.hidden && results.length) {
      event.preventDefault();
      choose(results[Math.max(active, 0)]);
    } else if (event.key === 'Escape' || event.key === 'Tab') close();
  });
  input.addEventListener('blur', () => setTimeout(close, 150));
  document.addEventListener('pointerdown', event => { if (!root.contains(event.target)) close(); });
  window.addEventListener('resize', positionList, { passive: true });
  window.addEventListener('scroll', positionList, { passive: true });
  window.visualViewport?.addEventListener('resize', positionList, { passive: true });
  async function boot() {
    retry.hidden = true;
    status.textContent = 'กำลังโหลดข้อมูลพื้นที่...';
    try {
      database = await loadDatabase();
      status.textContent = selected ? 'เลือกพื้นที่แล้ว' : '';
      if (!selected && input.value.trim()) search();
    } catch {
      status.textContent = 'โหลดข้อมูลพื้นที่ไม่สำเร็จ';
      retry.hidden = false;
    }
  }
  retry.addEventListener('click', boot);
  boot();
});
