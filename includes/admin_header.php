<?php
// ============================================================
//  includes/admin_header.php
//  Layout header + sidebar — dipanggil dari setiap halaman admin/
// ============================================================
$flash       = getFlash();
$currentPage = basename($_SERVER['PHP_SELF']);

/*
 * BASE_URL_ASSETS: path ke folder assets/ relatif dari lokasi
 * file PHP yang me-require header ini.
 *
 * Semua file di admin/ satu level lebih dalam dari root,
 * sehingga kita naik satu level dengan "../".
 *
 * Jika suatu saat ada halaman di subfolder lebih dalam,
 * definisikan BASE_URL_ASSETS di file tersebut sebelum
 * memanggil require admin_header.php.
 */
if (!defined('BASE_URL_ASSETS')) {
    define('BASE_URL_ASSETS', '../assets');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> — <?= APP_NAME ?></title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Source+Code+Pro:wght@400;600&display=swap" rel="stylesheet"/>

  <!-- ✅ External stylesheet (ekstrak dari <style> yang dulu inline) -->
  <link rel="stylesheet" href="<?= BASE_URL_ASSETS ?>/css/style.css"/>
  <link rel="icon" href="../assets/logo/logo.png"/>
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
    <button class="sidebar-close-btn" id="btnSidebarClose" title="Tutup menu">✕</button>
  </div>

  <nav>
    <a href="dashboard.php"   class="nav-item <?= $currentPage === 'dashboard.php'   ? 'active' : '' ?>"><span class="icon">📊</span> Dashboard</a>
    <a href="../index.php"    class="nav-item <?= $currentPage === 'index.php'        ? 'active' : '' ?>"><span class="icon">🧾</span> Transaksi Baru</a>
    <a href="transaksi.php"   class="nav-item <?= $currentPage === 'transaksi.php'   ? 'active' : '' ?>"><span class="icon">📋</span> Data Transaksi</a>
    <a href="layanan.php"     class="nav-item <?= $currentPage === 'layanan.php'     ? 'active' : '' ?>"><span class="icon">⚙️</span> Kelola Layanan</a>
    <a href="pengeluaran.php" class="nav-item <?= $currentPage === 'pengeluaran.php' ? 'active' : '' ?>"><span class="icon">💸</span> Pengeluaran</a>
    <a href="laporan.php"     class="nav-item <?= $currentPage === 'laporan.php'     ? 'active' : '' ?>"><span class="icon">📈</span> Laporan</a>
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
      <button class="btn-hamburger" id="btnHamburger"
              title="Buka/tutup menu" aria-label="Toggle menu" aria-expanded="true">
        <span></span>
        <span></span>
        <span></span>
      </button>
      <h1><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
    </div>
    <span class="admin-badge">👤 <?= htmlspecialchars($_SESSION['admin_nama'] ?? '') ?></span>
  </div>

  <div class="page-body">

    <?php if ($flash): ?>
      <div class="flash <?= htmlspecialchars($flash['type']) ?>">
        <?= $flash['type'] === 'success' ? '✅' : ($flash['type'] === 'error' ? '❌' : 'ℹ️') ?>
        <?= htmlspecialchars($flash['msg']) ?>
      </div>
    <?php endif; ?>

    <!-- ✅ External script dimuat di sini agar DOM sudah siap -->
    <script src="<?= BASE_URL_ASSETS ?>/js/script.js"></script>
