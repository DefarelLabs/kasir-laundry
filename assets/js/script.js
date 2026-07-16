/* ============================================================
   assets/js/script.js
   Global JavaScript — Permana Laundry Admin Panel
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  /* ══════════════════════════════════════════════════════════
     SIDEBAR TOGGLE — Desktop & Mobile
  ══════════════════════════════════════════════════════════ */
  var MOBILE_BP = 768;
  var body      = document.body;
  var overlay   = document.getElementById('sidebarOverlay');
  var btnHam    = document.getElementById('btnHamburger');
  var btnClose  = document.getElementById('btnSidebarClose');

  // Guard: komponen sidebar mungkin tidak ada di semua halaman
  if (btnHam) {

    function isMobile() { return window.innerWidth <= MOBILE_BP; }

    /* ── State awal ────────────────────────────────────────── */
    function initState() {
      if (isMobile()) {
        body.classList.remove('sidebar-collapsed');
        body.classList.remove('sidebar-open');
        btnHam.setAttribute('aria-expanded', 'false');
      } else {
        body.classList.remove('sidebar-open');
        body.classList.remove('sidebar-collapsed');
        btnHam.setAttribute('aria-expanded', 'true');
      }
    }

    /* ── Buka sidebar ──────────────────────────────────────── */
    function openSidebar() {
      if (isMobile()) {
        body.classList.add('sidebar-open');
        body.style.overflow = 'hidden';
        btnHam.setAttribute('aria-expanded', 'true');
      } else {
        body.classList.remove('sidebar-collapsed');
        btnHam.setAttribute('aria-expanded', 'true');
      }
    }

    /* ── Tutup sidebar ─────────────────────────────────────── */
    function closeSidebar() {
      if (isMobile()) {
        body.classList.remove('sidebar-open');
        body.style.overflow = '';
        btnHam.setAttribute('aria-expanded', 'false');
      } else {
        body.classList.add('sidebar-collapsed');
        btnHam.setAttribute('aria-expanded', 'false');
      }
    }

    /* ── Toggle ────────────────────────────────────────────── */
    function toggleSidebar() {
      if (isMobile()) {
        body.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
      } else {
        body.classList.contains('sidebar-collapsed') ? openSidebar() : closeSidebar();
      }
    }

    /* ── Event listeners ───────────────────────────────────── */
    btnHam.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      toggleSidebar();
    });

    if (btnClose) {
      btnClose.addEventListener('click', function (e) {
        e.preventDefault();
        closeSidebar();
      });
    }

    if (overlay) {
      overlay.addEventListener('click', closeSidebar);
    }

    /* Tutup sidebar di mobile saat klik nav item */
    document.querySelectorAll('.nav-item').forEach(function (link) {
      link.addEventListener('click', function () {
        if (isMobile()) closeSidebar();
      });
    });

    /* Saat resize: reset state agar tidak stuck */
    var resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        body.style.overflow = '';
        if (!isMobile()) {
          body.classList.remove('sidebar-open');
          body.classList.remove('sidebar-collapsed');
        }
      }, 100);
    });

    initState();
  }

  /* ══════════════════════════════════════════════════════════
     AUTO-DISMISS FLASH MESSAGES
     Flash message hilang otomatis setelah 4 detik.
  ══════════════════════════════════════════════════════════ */
  var flash = document.querySelector('.flash');
  if (flash) {
    setTimeout(function () {
      flash.style.transition = 'opacity .5s';
      flash.style.opacity    = '0';
      setTimeout(function () { flash.remove(); }, 500);
    }, 4000);
  }

});

/* ════════════════════════════════════════════════════════════
   LAPORAN — Toggle custom date range
   Dipanggil inline dari laporan.php (onclick="toggleCustom(e)")
   Karena butuh akses ke elemen DOM yang ada di halaman
   spesifik, fungsi ini dibuat global (bukan di dalam DOMContentLoaded).
════════════════════════════════════════════════════════════ */
function toggleCustom(e) {
  e.preventDefault();
  var el = document.getElementById('customRange');
  if (!el) return;
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

/* ════════════════════════════════════════════════════════════
   LAPORAN — Toggle "Tampilkan Semua" pada Detail Pengeluaran
════════════════════════════════════════════════════════════ */
function toggleRowsPengeluaran() {
  var rows = document.querySelectorAll('#tbodyPengeluaran .row-collapsible.is-hidden, #tbodyPengeluaran .row-collapsible[data-expanded="1"]');
  var btn  = document.getElementById('btnToggleRows');
  var allRows = document.querySelectorAll('#tbodyPengeluaran .row-collapsible');
  var isExpanded = btn.dataset.expanded === '1';

  allRows.forEach(function (row, idx) {
    if (idx >= 5) {
      row.classList.toggle('is-hidden', isExpanded);
    }
  });

  if (isExpanded) {
    btn.textContent = '⬇️ Tampilkan Semua (' + (allRows.length - 5) + ' lagi)';
    btn.dataset.expanded = '0';
  } else {
    btn.textContent = '⬆️ Sembunyikan';
    btn.dataset.expanded = '1';
  }
}

/* ════════════════════════════════════════════════════════════
   LAPORAN — Export CSV
   Data transaksi & pengeluaran di-inject oleh PHP ke variabel
   global `window.laporanData` sebelum script ini dipanggil.
   Lihat contoh di laporan.php (bagian <script> data injection).
════════════════════════════════════════════════════════════ */
function exportCSV() {
  if (typeof window.laporanData === 'undefined') {
    console.error('exportCSV: window.laporanData tidak ditemukan.');
    return;
  }

  var d    = window.laporanData;
  rows.push(
  ['Laporan Kasir Permana Laundry'],
  ['Periode: ' + (d.periodeLabel || '')],
  ['Dicetak: ' + new Date().toLocaleString('id-ID')],
  [],
  ['=== TRANSAKSI ==='],
  ['No. Nota', 'Pelanggan', 'Layanan', 'Jumlah', 'Satuan', 'Harga/Unit', 'Total', 'Deposit', 'Sisa Bayar', 'Tgl Masuk', 'Status']
);

(d.transaksi || []).forEach(function (r) {
  rows.push([r.no_nota, r.nama_pelanggan, r.layanan, r.jumlah, r.satuan,
             r.harga_per_unit, r.total_harga, r.deposit, r.sisa_bayar, r.tanggal_masuk, r.status]);
});

  rows.push([], ['=== PENGELUARAN ==='],
    ['Tanggal', 'Keterangan', 'Jumlah', 'Catatan']);

  (d.pengeluaran || []).forEach(function (p) {
  rows.push([p.tanggal, '', p.jumlah, p.catatan || '']); // kolom "Keterangan" dikosongkan karena sudah direkap per tanggal
  });

  rows.push(
  [],
  ['=== RINGKASAN ==='],
  ['Pendapatan Kotor',  d.totalPendapatan  || 0],
  ['Total Deposit Diterima', d.totalDeposit || 0],
  ['Total Piutang Belum Lunas', d.totalPiutang || 0],
  ['Total Pengeluaran', d.totalPengeluaran || 0],
  ['Laba Bersih',       d.labaBersih       || 0],
  ['Uang Diterima (Diambil penuh + Deposit pending)', d.uangDiterima || 0]
);

  var csv = rows.map(function (row) {
    return row.map(function (cell) {
      var s = String(cell == null ? '' : cell).replace(/"/g, '""');
      return '"' + s + '"';
    }).join(',');
  }).join('\r\n');

  /* BOM agar Excel bisa baca karakter Indonesia */
  var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  var url  = URL.createObjectURL(blob);
  var a    = document.createElement('a');
  a.href     = url;
  a.download = 'laporan_' + (d.preset || 'custom') + '_' + d.tanggalCetak + '.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}
