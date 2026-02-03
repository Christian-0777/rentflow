// public/assets/js/table.js
// Enhances tables with client-side sorting and simple pagination

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('table.table').forEach(initTable);
});

function initTable(table) {
  const headers = table.querySelectorAll('thead th');
  headers.forEach((th, idx) => {
    th.style.cursor = 'pointer';
    th.addEventListener('click', () => sortTable(table, idx));
  });
}

function sortTable(table, colIndex) {
  const tbody = table.querySelector('tbody');
  const rows = Array.from(tbody.querySelectorAll('tr'));
  const asc = table.dataset.sortAsc === 'true' ? false : true;
  rows.sort((a,b) => {
    const av = a.children[colIndex].innerText.trim();
    const bv = b.children[colIndex].innerText.trim();
    const an = parseFloat(av.replace(/[^\d.-]/g,'')); const bn = parseFloat(bv.replace(/[^\d.-]/g,''));
    if (!isNaN(an) && !isNaN(bn)) return asc ? an-bn : bn-an;
    return asc ? av.localeCompare(bv) : bv.localeCompare(av);
  });
  tbody.innerHTML = '';
  rows.forEach(r => tbody.appendChild(r));
  table.dataset.sortAsc = asc ? 'true' : 'false';
}
