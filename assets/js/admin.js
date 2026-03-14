/* ===========================
   ADMIN SHARED JS — admin.js
   =========================== */

// ---- Hamburger ----
const adminHam  = document.getElementById('adminHamburger');
const adminMenu = document.getElementById('adminMobileMenu');
if (adminHam && adminMenu) {
  adminHam.addEventListener('click', () => {
    adminHam.classList.toggle('open');
    adminMenu.classList.toggle('open');
  });
}

// ---- Search Modal ----
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

document.getElementById('adminSearchBtn')
  ?.addEventListener('click', e => { e.preventDefault(); openModal('searchModalOverlay'); });
document.getElementById('adminSearchBtnMobile')
  ?.addEventListener('click', e => { e.preventDefault(); openModal('searchModalOverlay'); });
document.getElementById('searchModalClose')
  ?.addEventListener('click', () => closeModal('searchModalOverlay'));
document.getElementById('searchModalOverlay')
  ?.addEventListener('click', e => { if (e.target.id === 'searchModalOverlay') closeModal('searchModalOverlay'); });

// ---- Generic modal close buttons ----
document.querySelectorAll('[data-close-modal]').forEach(btn => {
  btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
});
document.querySelectorAll('[data-open-modal]').forEach(btn => {
  btn.addEventListener('click', e => { e.preventDefault(); openModal(btn.dataset.openModal); });
});
// Close on overlay click
document.querySelectorAll('.a-modal-overlay').forEach(ov => {
  ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('open'); });
});

/* ===========================
   DATATABLE FACTORY
   Creates a sortable, searchable, paginated table.
   Usage:
     initAdminTable({
       tableId: 'myTable',
       bodyId:  'myBody',
       infoId:  'myInfo',
       pagId:   'myPag',
       searchId:'mySearch',
       selectId:'mySelect',
     });
   =========================== */
function initAdminTable({ tableId, bodyId, infoId, pagId, searchId, selectId }) {
  const allRows = Array.from(document.querySelectorAll('#' + bodyId + ' .a-data-row'));
  const info    = document.getElementById(infoId);
  const pag     = document.getElementById(pagId);
  const search  = document.getElementById(searchId);
  const sel     = document.getElementById(selectId);

  if (!allRows.length && info) {
    info.textContent = 'Showing 1 to 1 of 1 entry';
    renderPag(0, 1, 10, pag);
    return;
  }

  let page    = 1;
  let perPage = sel ? parseInt(sel.value) : 10;
  let filtered = [...allRows];
  let sortCol  = -1, sortDir = 1;

  function cellText(row, col) {
    return (row.cells[col]?.textContent || '').trim().toLowerCase();
  }

  function applySearch(term) {
    const q = term.toLowerCase().trim();
    filtered = allRows.filter(row => {
      if (!q) return true;
      for (let i = 0; i < row.cells.length; i++)
        if (cellText(row, i).includes(q)) return true;
      return false;
    });
    page = 1;
    render();
  }

  function render() {
    const total = filtered.length;
    const start = (page - 1) * perPage;
    const end   = Math.min(start + perPage, total);
    allRows.forEach(r => r.style.display = 'none');
    filtered.slice(start, end).forEach(r => r.style.display = '');

    const emptyRow = document.querySelector('#' + bodyId + ' .a-table-empty');
    if (emptyRow) emptyRow.style.display = total === 0 ? '' : 'none';

    if (info) {
      info.textContent = total === 0
        ? 'Showing 0 to 0 of 0 entries'
        : `Showing ${start+1} to ${end} of ${total} entr${total===1?'y':'ies'}`;
    }
    if (pag) renderPag(total, page, perPage, pag);
  }

  function renderPag(total, curPage, pp, container) {
    container.innerHTML = '';
    const pages = Math.max(1, Math.ceil(total / pp));
    const mk = (label, p, disabled, active) => {
      const b = document.createElement('button');
      b.textContent = label;
      b.className = 'a-pg-btn' + (active?' pg-active':'') + (disabled?' disabled':'');
      b.disabled = disabled;
      b.onclick = () => { page = p; render(); };
      return b;
    };
    container.appendChild(mk('«', 1, curPage===1, false));
    container.appendChild(mk('‹', curPage-1, curPage===1, false));
    let s = Math.max(1, curPage-2), e = Math.min(pages, s+4);
    if (e-s<4) s = Math.max(1,e-4);
    for (let p = s; p <= e; p++) container.appendChild(mk(p, p, false, p===curPage));
    container.appendChild(mk('›', curPage+1, curPage===pages, false));
    container.appendChild(mk('»', pages, curPage===pages, false));
  }

  // Sorting
  document.querySelectorAll('#'+tableId+' thead th.a-sortable').forEach(th => {
    th.addEventListener('click', () => {
      const col = parseInt(th.dataset.col);
      sortDir = sortCol === col ? -sortDir : 1;
      sortCol = col;
      document.querySelectorAll('#'+tableId+' thead th').forEach(h => h.classList.remove('asc','desc'));
      th.classList.add(sortDir===1?'asc':'desc');
      filtered.sort((a,b) => {
        const av = cellText(a,col), bv = cellText(b,col);
        return av<bv ? -sortDir : av>bv ? sortDir : 0;
      });
      page = 1;
      render();
    });
  });

  if (search) search.addEventListener('input', () => applySearch(search.value));
  if (sel)    sel.addEventListener('change',   () => { perPage = parseInt(sel.value); page=1; render(); });

  render();
}