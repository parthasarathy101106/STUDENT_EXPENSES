// Confirm before deleting
document.querySelectorAll('form[data-confirm]').forEach(f =>
  f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));
// Apply month/category filters immediately
document.querySelectorAll('.filters input[type=month], .filters select').forEach(el =>
  el.addEventListener('change', () => el.form.submit()));
