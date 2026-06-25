<?php
// includes/admin_header.php
$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Source+Code+Pro:wght@400;600&display=swap" rel="stylesheet"/>
  <style>
    :root{
      --blue-dark:#0f2a4a;--blue-mid:#1565c0;--blue-light:#e3f0ff;
      --teal:#00897b;--teal-light:#e0f2f1;
      --white:#fff;--gray-50:#f8fafc;--gray-100:#f1f5f9;
      --gray-200:#e2e8f0;--gray-400:#94a3b8;--gray-600:#475569;--gray-800:#1e293b;
      --green:#2e7d32;--green-light:#e8f5e9;
      --red:#c62828;--red-light:#ffebee;
      --orange:#e65100;--orange-light:#fff3e0;
      --purple:#6a1b9a;--purple-light:#f3e5f5;
      --radius-sm:6px;--radius-md:12px;--radius-lg:18px;
      --shadow:0 4px 24px rgba(15,42,74,.10);
      --sidebar-w:240px;
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--gray-50);color:var(--gray-800);display:flex;min-height:100vh}

    /* ══════════════════════════════
       SIDEBAR
    ══════════════════════════════ */
    .sidebar{
      width:var(--sidebar-w);
      background:var(--blue-dark);
      color:#fff;
      display:flex;
      flex-direction:column;
      flex-shrink:0;
      position:fixed;
      top:0; left:0;
      height:100vh;
      z-index:300;
      overflow-y:auto;
      overflow-x:hidden;
      transition:transform .26s cubic-bezier(.4,0,.2,1);
      /* Desktop: selalu tampil */
      transform:translateX(0);
    }
    /* Saat sidebar di-collapse (desktop) */
    body.sidebar-collapsed .sidebar{
      transform:translateX(calc(-1 * var(--sidebar-w)));
    }
    .sidebar-logo{
      padding:20px 18px 16px;
      border-bottom:1px solid rgba(255,255,255,.1);
      display:flex;align-items:center;justify-content:space-between;
    }
    .sidebar-logo .shop{font-size:16px;font-weight:800;letter-spacing:-.2px}
    .sidebar-logo .sub{font-size:11px;color:#90caf9;margin-top:2px}
    /* Tombol ✕ di dalam sidebar (muncul selalu) */
    .sidebar-close-btn{
      background:none;border:none;color:#78909c;
      font-size:18px;cursor:pointer;padding:4px;
      border-radius:6px;line-height:1;flex-shrink:0;
    }
    .sidebar-close-btn:hover{background:rgba(255,255,255,.1);color:#fff}
    .sidebar nav{flex:1;padding:10px 0}
    .nav-item{
      display:flex;align-items:center;gap:10px;
      padding:11px 18px;font-size:13.5px;font-weight:500;
      color:#b0bec5;text-decoration:none;
      transition:background .15s,color .15s;
      border-left:3px solid transparent;
      white-space:nowrap;
    }
    .nav-item:hover{background:rgba(255,255,255,.06);color:#fff}
    .nav-item.active{background:rgba(21,101,192,.35);color:#fff;border-left-color:var(--teal)}
    .nav-item .icon{font-size:17px;width:22px;text-align:center;flex-shrink:0}
    .sidebar-footer{
      padding:14px 18px;
      border-top:1px solid rgba(255,255,255,.1);
      font-size:12px;color:#78909c;
    }
    .sidebar-footer a{color:#ef9a9a;text-decoration:none;font-weight:600}
    .sidebar-footer a:hover{color:#fff}

    /* Overlay gelap (mobile) */
    .sidebar-overlay{
      display:none;
      position:fixed;inset:0;
      background:rgba(0,0,0,.5);
      z-index:290;
      opacity:0;
      transition:opacity .26s;
    }
    body.sidebar-open .sidebar-overlay{opacity:1}

    /* ══════════════════════════════
       MAIN WRAP
    ══════════════════════════════ */
    .main-wrap{
      margin-left:var(--sidebar-w);
      flex:1;
      display:flex;
      flex-direction:column;
      min-height:100vh;
      transition:margin-left .26s;
    }
    body.sidebar-collapsed .main-wrap{
      margin-left:0;
    }

    /* ══════════════════════════════
       TOPBAR
    ══════════════════════════════ */
    .topbar{
      background:var(--white);
      padding:0 20px;
      height:56px;
      display:flex;align-items:center;justify-content:space-between;
      border-bottom:1px solid var(--gray-200);
      box-shadow:0 1px 4px rgba(0,0,0,.06);
      position:sticky;top:0;z-index:200;
    }
    .topbar-left{display:flex;align-items:center;gap:10px}

    /* ── HAMBURGER — selalu tampil di semua ukuran layar ── */
    .btn-hamburger{
      display:flex;                /* SELALU flex, tidak pernah none */
      flex-direction:column;
      justify-content:center;
      align-items:center;
      gap:5px;
      width:38px;height:38px;
      background:none;border:none;
      cursor:pointer;
      border-radius:8px;
      padding:6px;
      flex-shrink:0;
    }
    .btn-hamburger:hover{background:var(--gray-100)}
    .btn-hamburger span{
      display:block;
      width:22px;height:2.5px;
      background:var(--gray-800);
      border-radius:3px;
      transition:transform .2s, opacity .2s;
    }
    .topbar h1{font-size:16px;font-weight:700;line-height:1.2}
    .admin-badge{
      background:var(--blue-light);color:var(--blue-mid);
      padding:5px 12px;border-radius:20px;
      font-size:12px;font-weight:600;white-space:nowrap;
    }

    /* ══════════════════════════════
       PAGE BODY
    ══════════════════════════════ */
    .page-body{flex:1;padding:24px}

    /* ── Flash ── */
    .flash{padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:20px;font-size:14px;font-weight:500;display:flex;align-items:center;gap:10px}
    .flash.success{background:var(--green-light);color:var(--green);border-left:4px solid var(--green)}
    .flash.error{background:var(--red-light);color:var(--red);border-left:4px solid var(--red)}
    .flash.info{background:var(--blue-light);color:var(--blue-mid);border-left:4px solid var(--blue-mid)}

    /* ── Cards ── */
    .card{background:var(--white);border-radius:var(--radius-lg);box-shadow:var(--shadow);padding:20px}
    .card-title{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--blue-mid);margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid var(--blue-light)}

    /* ── Stat cards ── */
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px}
    .stat-card{background:var(--white);border-radius:var(--radius-lg);box-shadow:var(--shadow);padding:16px;display:flex;align-items:center;gap:14px}
    .stat-icon{width:46px;height:46px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
    .stat-icon.blue{background:var(--blue-light)}
    .stat-icon.teal{background:var(--teal-light)}
    .stat-icon.orange{background:var(--orange-light)}
    .stat-icon.green{background:var(--green-light)}
    .stat-icon.red{background:var(--red-light)}
    .stat-icon.purple{background:var(--purple-light)}
    .stat-label{font-size:12px;color:var(--gray-600);margin-bottom:3px}
    .stat-value{font-size:20px;font-weight:800;color:var(--gray-800)}
    .stat-sub{font-size:11px;color:var(--gray-400);margin-top:2px}

    /* ── Table ── */
    .table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
    table{width:100%;border-collapse:collapse;font-size:13px;min-width:500px}
    th{background:var(--gray-50);padding:9px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-600);border-bottom:2px solid var(--gray-200)}
    td{padding:10px 12px;border-bottom:1px solid var(--gray-100);vertical-align:middle}
    tr:last-child td{border-bottom:none}
    tr:hover td{background:#fafcff}
    .badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700}
    .badge.pending{background:#fff3e0;color:#e65100}
    .badge.selesai{background:var(--teal-light);color:var(--teal)}
    .badge.diambil{background:var(--green-light);color:var(--green)}

    /* ── Forms & Buttons ── */
    .form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
    .form-group:last-child{margin-bottom:0}
    label.lbl{font-size:13px;font-weight:600;color:var(--gray-600)}
    input[type=text],input[type=number],input[type=date],input[type=month],
    input[type=password],select,textarea{
      width:100%;padding:9px 12px;
      border:1.5px solid var(--gray-200);border-radius:var(--radius-sm);
      font-family:inherit;font-size:14px;color:var(--gray-800);
      background:var(--gray-50);outline:none;transition:border-color .18s;
    }
    input:focus,select:focus,textarea:focus{border-color:var(--blue-mid);background:var(--white)}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;border:none;border-radius:var(--radius-md);font-family:inherit;font-size:13px;font-weight:700;cursor:pointer;transition:opacity .15s,transform .1s;text-decoration:none}
    .btn:active{transform:scale(.97)}
    .btn-primary{background:var(--blue-mid);color:#fff}.btn-primary:hover{opacity:.9}
    .btn-success{background:var(--teal);color:#fff}.btn-success:hover{opacity:.9}
    .btn-danger{background:var(--red);color:#fff}.btn-danger:hover{opacity:.9}
    .btn-warning{background:#f57c00;color:#fff}
    .btn-sm{padding:5px 10px;font-size:12px;border-radius:var(--radius-sm)}
    .btn-outline{background:transparent;border:1.5px solid var(--gray-200);color:var(--gray-600)}.btn-outline:hover{background:var(--gray-100)}
    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}

    /* ══════════════════════════════
       RESPONSIVE — MOBILE ≤768px
    ══════════════════════════════ */
    @media(max-width:768px){
      /* Sidebar tersembunyi di luar layar */
      .sidebar{ transform:translateX(calc(-1 * var(--sidebar-w))); }
      /* Saat body.sidebar-open → sidebar masuk */
      body.sidebar-open .sidebar{ transform:translateX(0); }
      /* Overlay aktif di mobile */
      .sidebar-overlay{ display:block; }
      /* Main wrap full lebar */
      .main-wrap{ margin-left:0 !important; }
      body.sidebar-collapsed .main-wrap{ margin-left:0; }
      /* Page body lebih sempit */
      .page-body{ padding:14px; }
      .topbar{ padding:0 12px; }
      .topbar h1{ font-size:14px; }
      .admin-badge{ display:none; }   /* sembunyikan badge admin di HP kecil */
      /* Grid responsif */
      .stats-grid{ grid-template-columns:1fr 1fr; gap:10px; }
      .stat-card{ padding:12px; gap:10px; }
      .stat-icon{ width:38px; height:38px; font-size:18px; }
      .stat-value{ font-size:17px; }
      .grid-2{ grid-template-columns:1fr; }
      .card{ padding:14px; }
    }
    @media(max-width:420px){
      .stats-grid{ grid-template-columns:1fr; }
    }
  </style>
</head>
<body>

<!-- Overlay gelap (hanya aktif di mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar" id="mainSidebar">
  <div class="sidebar-logo">
    <div>
      <div class="shop">🫧 <?= APP_NAME ?></div>
      <div class="sub">Panel Admin</div>
    </div>
    <!-- Tombol ✕ untuk tutup sidebar dari dalam -->
    <button class="sidebar-close-btn" id="btnSidebarClose" title="Tutup menu">✕</button>
  </div>
  <nav>
    <a href="dashboard.php"   class="nav-item <?= $currentPage==='dashboard.php'  ?'active':'' ?>"><span class="icon">📊</span> Dashboard</a>
    <a href="../index.php"    class="nav-item <?= $currentPage==='index.php'       ?'active':'' ?>"><span class="icon">🧾</span> Transaksi Baru</a>
    <a href="transaksi.php"   class="nav-item <?= $currentPage==='transaksi.php'   ?'active':'' ?>"><span class="icon">📋</span> Data Transaksi</a>
    <a href="layanan.php"     class="nav-item <?= $currentPage==='layanan.php'     ?'active':'' ?>"><span class="icon">⚙️</span> Kelola Layanan</a>
    <a href="pengeluaran.php" class="nav-item <?= $currentPage==='pengeluaran.php' ?'active':'' ?>"><span class="icon">💸</span> Pengeluaran</a>
    <a href="laporan.php"     class="nav-item <?= $currentPage==='laporan.php'     ?'active':'' ?>"><span class="icon">📈</span> Laporan</a>
  </nav>
  <div class="sidebar-footer">
    Login sebagai <strong><?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?></strong><br/>
    <a href="logout.php">🚪 Keluar</a>
  </div>
</aside>

<!-- ══ MAIN WRAP ══ -->
<div class="main-wrap" id="mainWrap">
  <div class="topbar">
    <div class="topbar-left">

      <!-- ☰ HAMBURGER — 3 garis, selalu tampil -->
      <button class="btn-hamburger" id="btnHamburger" title="Buka/tutup menu"
              aria-label="Toggle menu" aria-expanded="true">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <h1><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
    </div>
    <span class="admin-badge">👤 <?= htmlspecialchars($_SESSION['admin_nama'] ?? '') ?></span>
  </div>

  <div class="page-body">
    <?php if($flash): ?>
      <div class="flash <?= $flash['type'] ?>">
        <?= $flash['type']==='success'?'✅':'❌' ?> <?= htmlspecialchars($flash['msg']) ?>
      </div>
    <?php endif; ?>

<script>
/* ════════════════════════════════════════════════
   SIDEBAR TOGGLE — Desktop & Mobile
   ════════════════════════════════════════════════
   Desktop  : sidebar ditampilkan/disembunyikan
              dengan class 'sidebar-collapsed' di body.
              Main-wrap margin menyesuaikan.
   Mobile   : sidebar slide-in dari kiri,
              pakai class 'sidebar-open' di body.
              Overlay gelap muncul di belakang.
   ──────────────────────────────────────────────── */
(function(){
  var MOBILE_BP = 768;
  var body      = document.body;
  var overlay   = document.getElementById('sidebarOverlay');
  var btnHam    = document.getElementById('btnHamburger');
  var btnClose  = document.getElementById('btnSidebarClose');

  function isMobile(){ return window.innerWidth <= MOBILE_BP; }

  /* ── State awal ───────────────────────────────────────── */
  // Desktop: sidebar terbuka (tidak collapsed)
  // Mobile : sidebar tertutup (tidak open)
  function initState(){
    if(isMobile()){
      body.classList.remove('sidebar-collapsed');
      body.classList.remove('sidebar-open');
      if(btnHam) btnHam.setAttribute('aria-expanded','false');
    } else {
      body.classList.remove('sidebar-open');
      body.classList.remove('sidebar-collapsed');
      if(btnHam) btnHam.setAttribute('aria-expanded','true');
    }
  }

  /* ── Buka / tutup ─────────────────────────────────────── */
  function openSidebar(){
    if(isMobile()){
      body.classList.add('sidebar-open');
      body.style.overflow = 'hidden';
      if(btnHam) btnHam.setAttribute('aria-expanded','true');
    } else {
      body.classList.remove('sidebar-collapsed');
      if(btnHam) btnHam.setAttribute('aria-expanded','true');
    }
  }

  function closeSidebar(){
    if(isMobile()){
      body.classList.remove('sidebar-open');
      body.style.overflow = '';
      if(btnHam) btnHam.setAttribute('aria-expanded','false');
    } else {
      body.classList.add('sidebar-collapsed');
      if(btnHam) btnHam.setAttribute('aria-expanded','false');
    }
  }

  function toggleSidebar(){
    if(isMobile()){
      if(body.classList.contains('sidebar-open')) closeSidebar();
      else openSidebar();
    } else {
      if(body.classList.contains('sidebar-collapsed')) openSidebar();
      else closeSidebar();
    }
  }

  /* ── Event listeners ──────────────────────────────────── */
  if(btnHam)   btnHam.addEventListener('click', function(e){ e.stopPropagation(); toggleSidebar(); });
  if(btnClose) btnClose.addEventListener('click', closeSidebar);
  if(overlay)  overlay.addEventListener('click', closeSidebar);

  // Tutup sidebar di mobile saat klik nav item
  document.querySelectorAll('.nav-item').forEach(function(link){
    link.addEventListener('click', function(){
      if(isMobile()) closeSidebar();
    });
  });

  // Saat resize: reset state supaya tidak stuck
  var resizeTimer;
  window.addEventListener('resize', function(){
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function(){
      body.style.overflow = '';
      if(!isMobile()){
        body.classList.remove('sidebar-open');
        // Kalau sebelumnya di-mobile lalu resize ke desktop,
        // pastikan sidebar muncul kembali
        body.classList.remove('sidebar-collapsed');
      }
    }, 100);
  });

  /* ── Init ─────────────────────────────────────────────── */
  initState();
})();
</script>
