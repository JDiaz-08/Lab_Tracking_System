/* ===========================
   ADMIN SHARED JS — admin.js
   =========================== */

// ---- Hamburger ----
const adminHam  = document.getElementById('adminHamburger');
const adminMenu = document.getElementById('adminMobileMenu');
if (adminHam && adminMenu) {
  adminHam.addEventListener('click', (e) => {
    e.stopPropagation();
    adminHam.classList.toggle('open');
    if (adminMenu.style.display === 'flex') {
      adminMenu.style.display = 'none';
    } else {
      adminMenu.style.display = 'flex';
    }
  });

  document.addEventListener('click', (e) => {
    if (adminMenu.style.display === 'flex' && !adminMenu.contains(e.target) && e.target !== adminHam) {
      adminMenu.style.display = 'none';
      adminHam.classList.remove('open');
    }
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

  const tableEl = document.getElementById(tableId);
  if (tableEl) {
    tableEl.getFilteredRows = () => filtered;
  }

  render();
}

/* ============================================================
   EXPORT TABLE HELPERS (CSV & PDF)
   ============================================================ */

function exportTableToCSV(tableId, filename = 'export.csv') {
  try {
    const table = document.getElementById(tableId);
    if (!table) throw new Error('Table element with ID "' + tableId + '" not found.');

    let rowsToExport = [];
    if (typeof table.getFilteredRows === 'function') {
      rowsToExport = table.getFilteredRows();
    } else {
      rowsToExport = Array.from(table.querySelectorAll('tbody tr.a-data-row'));
    }

    // If there is an empty table message, don't export anything
    if (rowsToExport.length === 1 && rowsToExport[0].classList.contains('a-table-empty')) {
      rowsToExport = [];
    }

    const csvRows = [];
    
    // Headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
      // Expose plain text, remove standard sort symbols via unicode hex
      let text = th.textContent.replace(/\u21c5/g, '').trim();
      if (text.toLowerCase() === 'actions') return; // skip action column if present
      headers.push('"' + text.replace(/"/g, '""') + '"');
    });
    csvRows.push(headers.join(','));

    // Body
    rowsToExport.forEach(row => {
      const cols = [];
      const cells = Array.from(row.querySelectorAll('td'));
      
      // Check if the table has an Action column (last cell containing buttons)
      const headerCols = Array.from(table.querySelectorAll('thead th'));
      const actionColIndex = headerCols.findIndex(th => th.textContent.toLowerCase().includes('action'));

      cells.forEach((td, idx) => {
        if (idx === actionColIndex) return; // skip action column
        let text = td.textContent.trim().replace(/\s+/g, ' ');
        cols.push('"' + text.replace(/"/g, '""') + '"');
      });
      csvRows.push(cols.join(','));
    });

    const blob = new Blob([csvRows.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (err) {
    console.error('CSV Export Error: ', err);
    alert('Failed to export CSV: ' + err.message);
  }
}

function loadImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(img);
    img.onerror = () => reject(new Error('Failed to load image: ' + src));
    img.src = src;
  });
}

function getAssetsPath() {
  const path = window.location.pathname;
  if (path.includes('/pages/admin/')) {
    return '../../assets/images/';
  } else if (path.includes('/pages/')) {
    return '../assets/images/';
  }
  return 'assets/images/';
}

async function exportTableToPDF(tableId, title = 'Report', filename = 'report.pdf') {
  try {
    const table = document.getElementById(tableId);
    if (!table) throw new Error('Table element with ID "' + tableId + '" not found.');

    let rowsToExport = [];
    if (typeof table.getFilteredRows === 'function') {
      rowsToExport = table.getFilteredRows();
    } else {
      rowsToExport = Array.from(table.querySelectorAll('tbody tr.a-data-row'));
    }

    // If empty table message, don't export
    if (rowsToExport.length === 1 && rowsToExport[0].classList.contains('a-table-empty')) {
      rowsToExport = [];
    }

    if (typeof window.jspdf === 'undefined') {
      throw new Error('The PDF generation library (jsPDF) has not loaded yet. Please verify you have an internet connection and refresh the page.');
    }

    const { jsPDF } = window.jspdf;
    
    // Explicitly bind to window.jsPDF to ensure UMD plugins like jsPDF-AutoTable can register correctly
    window.jsPDF = jsPDF;
    
    const doc = new jsPDF('p', 'pt', 'a4');

    if (typeof doc.autoTable !== 'function') {
      throw new Error('The jsPDF-AutoTable plugin failed to load. Please refresh the page and try again.');
    }

    // Headers
    const headers = [];
    const headerCols = Array.from(table.querySelectorAll('thead th'));
    const actionColIndex = headerCols.findIndex(th => th.textContent.toLowerCase().includes('action'));

    headerCols.forEach((th, idx) => {
      if (idx === actionColIndex) return;
      headers.push(th.textContent.replace(/\u21c5/g, '').trim());
    });

    // Body
    const bodyData = [];
    rowsToExport.forEach(row => {
      const rowData = [];
      const cells = Array.from(row.querySelectorAll('td'));
      cells.forEach((td, idx) => {
        if (idx === actionColIndex) return;
        rowData.push(td.textContent.trim().replace(/\s+/g, ' '));
      });
      bodyData.push(rowData);
    });

    // --- Premium Header with Logos (Centered Layout) ---
    const pageWidth = doc.internal.pageSize.getWidth();
    const centerX = pageWidth / 2;
    const assetsDir = getAssetsPath();

    // Asynchronously load logos
    let ucLogoImg = null;
    let ccsLogoImg = null;

    try {
      ucLogoImg = await loadImage(assetsDir + 'uc-logo-white.png');
    } catch (e) {
      console.warn("Could not load UC logo, skipping in PDF:", e);
    }

    try {
      ccsLogoImg = await loadImage(assetsDir + 'ccs-logo.png');
    } catch (e) {
      console.warn("Could not load CCS logo, skipping in PDF:", e);
    }

    // Draw UC Logo (Left-aligned)
    if (ucLogoImg) {
      const ucHeight = 35; // Target height in pt
      const ucWidth = ucHeight * (ucLogoImg.width / ucLogoImg.height);
      // Position UC logo at x=40, y=22
      doc.addImage(ucLogoImg, 'PNG', 40, 22, ucWidth, ucHeight);
    }

    // Draw CCS Logo (Right-aligned)
    if (ccsLogoImg) {
      const ccsHeight = 35; // Target height in pt
      const ccsWidth = ccsHeight * (ccsLogoImg.width / ccsLogoImg.height);
      // Position CCS logo aligned to the right margin
      doc.addImage(ccsLogoImg, 'PNG', pageWidth - 40 - ccsWidth, 22, ccsWidth, ccsHeight);
    }

    // Title Line 1: UCMAIN
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.setTextColor(26, 58, 107); // --a-navy (#1a3a6b)
    doc.text("UCMAIN", centerX, 32, { align: 'center' });

    // Title Line 2: Subtitle / Report Type
    const displayTitle = title.includes('—') ? title.split('—')[1].trim() : title;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10.5);
    doc.setTextColor(30, 41, 59); // Slate-800
    doc.text(displayTitle, centerX, 46, { align: 'center' });

    // Title Line 3: Generated date/time matching YYYY-MM-DD HH:MM:SS format
    const now = new Date();
    const formattedDate = now.getFullYear() + '-' +
      String(now.getMonth() + 1).padStart(2, '0') + '-' +
      String(now.getDate()).padStart(2, '0') + ' ' +
      String(now.getHours()).padStart(2, '0') + ':' +
      String(now.getMinutes()).padStart(2, '0') + ':' +
      String(now.getSeconds()).padStart(2, '0');

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(100, 116, 139); // Slate-500
    doc.text("Generated: " + formattedDate, centerX, 58, { align: 'center' });

    // Solid blue horizontal bar under the header block
    doc.setFillColor(26, 58, 107); // --a-navy (#1a3a6b)
    doc.rect(40, 68, pageWidth - 80, 4, 'F');

    // Generate Table using jsPDF-AutoTable
    doc.autoTable({
      startY: 85,
      head: [headers],
      body: bodyData,
      theme: 'striped',
      headStyles: { 
        fillColor: [26, 58, 107], 
        textColor: [255, 255, 255], 
        fontSize: 8.5, 
        fontStyle: 'bold',
        halign: 'left'
      },
      bodyStyles: { 
        fontSize: 8, 
        textColor: [30, 41, 59], 
        cellPadding: 6 
      },
      alternateRowStyles: { 
        fillColor: [248, 250, 252] 
      },
      margin: { top: 85, left: 40, right: 40, bottom: 40 },
      didDrawPage: function(data) {
        // Footer page numbers
        const str = "Page " + doc.internal.getNumberOfPages();
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(148, 163, 184); // Slate-400
        doc.text(str, data.settings.margin.left, doc.internal.pageSize.height - 30);
      }
    });

    doc.save(filename);
  } catch (err) {
    console.error('PDF Export Error: ', err);
    alert('Failed to export PDF: ' + err.message);
  }
}