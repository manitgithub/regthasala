const checks = document.querySelectorAll('.check-row input');
const total = document.querySelector('.total-row strong');
const prices = [250, 300, 350, 350];
const subjectForm = document.querySelector('#subjectForm');
const showSubjectForm = document.querySelector('#showSubjectForm');
const cancelSubject = document.querySelector('#cancelSubject');
const subjectRows = document.querySelector('#subjectRows');

function updateTotal() {
  const sum = Array.from(checks).reduce((amount, checkbox, index) => {
    return amount + (checkbox.checked ? prices[index] : 0);
  }, 0);
  total.textContent = `${sum.toLocaleString('th-TH')} บาท`;
}

checks.forEach((checkbox) => checkbox.addEventListener('change', updateTotal));

showSubjectForm.addEventListener('click', () => {
  subjectForm.classList.add('visible');
  document.querySelector('#subjectCode').focus();
});

cancelSubject.addEventListener('click', () => {
  subjectForm.classList.remove('visible');
});

subjectForm.addEventListener('submit', (event) => {
  event.preventDefault();

  const code = document.querySelector('#subjectCode').value.trim();
  const name = document.querySelector('#subjectName').value.trim();
  const date = document.querySelector('#subjectDate').value;
  const time = document.querySelector('#subjectTime').value.trim();
  const seats = document.querySelector('#subjectSeats').value.trim();
  const fee = Number(document.querySelector('#subjectFee').value || 0);

  if (!code || !name || !date || !time || !seats || !fee) return;

  const row = document.createElement('tr');
  const displayDate = new Date(`${date}T00:00:00`).toLocaleDateString('th-TH', {
    day: 'numeric',
    month: 'short',
  });

  row.innerHTML = `
    <td>${code}</td>
    <td>${name}</td>
    <td>${displayDate} ${time}</td>
    <td>${Number(seats).toLocaleString('th-TH')}</td>
    <td>${fee.toLocaleString('th-TH')} บาท</td>
    <td><span class="tag draft">ร่าง</span></td>
  `;

  subjectRows.append(row);
  subjectForm.classList.remove('visible');
});
