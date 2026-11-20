// Client-side validation (task 13) — mirrors the server-side rules in Validator.php.
const checks = {
  required: (v) => v !== '' || 'This field is required.',
  positive: (v) => (!isNaN(v) && parseFloat(v) > 0) || 'Must be greater than 0.',
  nonneg:   (v) => (/^\d+$/.test(v)) || 'Must be a whole number, 0 or more.',
  int:      (v) => (/^-?\d+$/.test(v)) || 'Must be a whole number.',
  email:    (v) => /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v) || 'Enter a valid email address.',
  min6:     (v) => v.length >= 6 || 'Use at least 6 characters.',
};

function validateField(el) {
  const val = el.value.trim();
  let msg = '';
  for (const rule of el.dataset.rule.split('|')) {
    if (rule !== 'required' && val === '') continue;
    const res = checks[rule] ? checks[rule](val) : true;
    if (res !== true) { msg = res; break; }
  }
  if (!msg && el.max && val !== '' && parseFloat(val) > parseFloat(el.max)) msg = `Only ${el.max} available.`;
  const box = el.closest('.field') || el.parentElement;
  const err = box.querySelector('.field-err');
  if (err) { err.textContent = msg; err.style.display = msg ? 'block' : 'none'; }
  el.classList.toggle('invalid', !!msg);
  return !msg;
}

document.querySelectorAll('form[data-validate]').forEach((form) => {
  const fields = () => form.querySelectorAll('[data-rule]');
  form.addEventListener('submit', (e) => {
    let ok = true;
    fields().forEach((el) => { if (!validateField(el)) ok = false; });
    if (!ok) {
      e.preventDefault();
      form.querySelector('.invalid')?.focus();
    }
  });
  fields().forEach((el) => el.addEventListener('blur', () => validateField(el)));
  fields().forEach((el) => el.addEventListener('input', () => { if (el.classList.contains('invalid')) validateField(el); }));
});

// Purchase page: quantity stepper + live total.
document.querySelectorAll('[data-purchase]').forEach((form) => {
  const qty = form.querySelector('input[name="quantity"]');
  const unit = parseFloat(form.dataset.price);
  const max = parseInt(qty.max, 10);
  const out = form.querySelector('[data-total]');
  const fmt = (n) => {
    const s = n.toFixed(3);
    return '$' + (s.endsWith('0') ? s.slice(0, -1) : s);
  };
  const render = () => {
    const q = parseInt(qty.value, 10);
    out.textContent = q > 0 ? fmt(unit * q) : '—';
  };
  form.querySelectorAll('[data-step]').forEach((b) => b.addEventListener('click', () => {
    const q = (parseInt(qty.value, 10) || 0) + parseInt(b.dataset.step, 10);
    qty.value = Math.min(max, Math.max(1, q));
    render();
    validateField(qty);
  }));
  qty.addEventListener('input', render);
  render();
});
