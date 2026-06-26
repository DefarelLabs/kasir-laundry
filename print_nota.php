<?php
// print_nota.php — Halaman khusus cetak nota (1 atau 2 lembar)
// Dipanggil via window.open() dari index.php atau admin
require_once 'includes/config.php';

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);
$copy = (int)($_GET['copy'] ?? 1);
$copy = max(1, min(2, $copy)); // Batasi 1-2

if (!$id) { die('ID tidak valid.'); }

// Ambil data transaksi lengkap
$stmt = $db->prepare("SELECT * FROM v_transaksi_lengkap WHERE id = ?");
$stmt->execute([$id]);
$d = $stmt->fetch();

if (!$d) { die('Transaksi tidak ditemukan.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Nota <?= htmlspecialchars($d['no_nota']) ?> — Permana Laundry</title>
  <link href="https://fonts.googleapis.com/css2?family=Source+Code+Pro:wght@400;600&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet"/>
  <style>
    /* ── Screen styles: tampilan preview sebelum cetak ── */
    :root {
      --teal:#00897b;
    }
    * { box-sizing:border-box; margin:0; padding:0; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: #e8edf2;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 24px 16px;
    }

    .screen-toolbar {
      width: 100%;
      max-width: 420px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 18px;
    }
    .screen-toolbar h2 {
      font-size: 15px;
      font-weight: 700;
      color: #1e293b;
    }
    .screen-toolbar p {
      font-size: 12px;
      color: #64748b;
      margin-top: 2px;
    }
    .btn-print-now {
      background: var(--teal);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 10px 20px;
      font-family: inherit;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 7px;
    }
    .btn-print-now:hover { opacity: .9; }
    .btn-close {
      background: #fff;
      color: #475569;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      padding: 10px 16px;
      font-family: inherit;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      margin-right: 8px;
    }

    /* Pembungkus nota (efek kertas) */
    .paper-outer {
      background: #c8d0da;
      border-radius: 3px 3px 0 0;
      padding: 0 7px;
      box-shadow: 0 10px 40px rgba(0,0,0,.22);
    }

    /* ── Area nota utama ── */
    .nota-area {
      background: #ffffff;
      font-family: 'Source Code Pro', monospace;
      font-size: 12px;
      color: #111;
      line-height: 1.65;
      width: 280px;
      padding: 18px 14px;
    }

    /* Perforasi atas/bawah */
    .nota-area::before, .nota-area::after {
      content: '';
      display: block;
      height: 7px;
      background: repeating-linear-gradient(
        90deg, transparent, transparent 6px,
        #c8d0da 6px, #c8d0da 12px
      );
      margin: 0 -14px;
    }
    .nota-area::before { margin-bottom: 14px; }
    .nota-area::after  { margin-top: 14px; }

    /* Garis pemisah antar copy saat screen preview */
    .copy-separator {
      text-align: center;
      font-size: 11px;
      color: #888;
      padding: 10px 0;
      letter-spacing: 2px;
      border-top: 1px dashed #bbb;
      margin-top: 8px;
    }

    /* ── Elemen nota ── */
    .r-logo     { text-align: center; margin-bottom: 10px; }
    .r-shop     { font-size: 15px; font-weight: 600; letter-spacing: 1px; }
    .r-tag      { font-size: 10px; color: #555; margin-top: 1px; }
    .r-div      { border: none; border-top: 1px dashed #999; margin: 8px 0; }
    .r-row      { display: flex; justify-content: space-between; font-size: 11px; gap: 4px; }
    .r-key      { color: #444; flex-shrink: 0; }
    .r-val      { text-align: right; font-weight: 600; word-break: break-word; }
    .r-total    { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; margin-top: 4px; }
    .r-foot     { text-align: center; font-size: 10px; color: #777; margin-top: 10px; line-height: 1.5; }
    .r-copy-label {
      text-align: center;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 2px;
      border: 1px dashed #999;
      padding: 2px 8px;
      margin-bottom: 10px;
      color: #333;
    }
    .copy-block { }
    /* Tambah border bawah dashed antara 2 copy saat di layar */
    .copy-block + .copy-block {
      margin-top: 12px;
      padding-top: 4px;
    }

    /* ══════════════════════════════════════════════
       @MEDIA PRINT
       Sembunyikan semua elemen UI browser.
       Hanya .nota-area yang keluar di kertas.

       Untuk 2 copy: setiap .copy-block di-break
       menggunakan page-break-after: always
       kecuali yang terakhir.
    ══════════════════════════════════════════════ */
    @media print {
      body        { background: none; padding: 0; display: block; }
      .screen-toolbar, .btn-print-now, .btn-close, .copy-separator { display: none !important; }
      .paper-outer { background: none; padding: 0; box-shadow: none; }

      .nota-area {
        width: 80mm;          /* Kertas thermal 80mm */
        padding: 5mm 4mm;
        font-size: 10px;
        box-shadow: none;
      }
      /* Hapus perforasi saat cetak */
      .nota-area::before, .nota-area::after { display: none; }

      /* Setiap copy dicetak di halaman/section baru */
      .copy-block {
        page-break-after: always;
        page-break-inside: avoid;
      }
      .copy-block:last-child {
        page-break-after: auto;
      }

      @page {
        size: 80mm auto;
        margin: 0;
      }
    }
  </style>
</head>
<body>

<!-- Toolbar (hanya tampil di layar, disembunyikan saat print) -->
<div class="screen-toolbar">
  <div>
    <h2>🧾 Preview Nota — <?= htmlspecialchars($d['no_nota']) ?></h2>
    <p><?= $copy === 2 ? '2 lembar: Pelanggan + Arsip Pemilik' : '1 lembar: Untuk Pelanggan' ?></p>
  </div>
  <div>
    <button class="btn-close" onclick="window.close()">✕ Tutup</button>
    <button class="btn-print-now" onclick="window.print()">🖨️ Cetak Sekarang</button>
  </div>
</div>

<!-- Area nota (yang akan dicetak) -->
<div class="paper-outer">
  <div class="nota-area">

    <?php
    // ── Render 1 atau 2 copy nota ──────────────────────────────
    // copy=1 → hanya 1 blok nota (untuk pelanggan)
    // copy=2 → 2 blok nota (pelanggan + arsip pemilik),
    //          terpisah oleh page-break saat dicetak.

    $copyLabels = [1 => 'PELANGGAN', 2 => 'ARSIP / PEMILIK'];

    for ($c = 1; $c <= $copy; $c++):
      $isLast = ($c === $copy);
    ?>

    <div class="copy-block">

      <?php if ($copy > 1): ?>
      <div class="r-copy-label">— <?= $copyLabels[$c] ?? "COPY $c" ?> —</div>
      <?php endif; ?>

      <!-- Header Toko -->
      <div class="r-logo">
        <div class="r-shop">PERMANA LAUNDRY</div>
        <div class="r-tag">Bersih · Rapi · Tepat Waktu</div>
        <div class="r-tag">Jl. Contoh No. 1, Kota Anda</div>
        <div class="r-tag">☎ 0896-9150-2028</div>
      </div>

      <hr class="r-div"/>

      <!-- Info Transaksi -->
      <div class="r-row">
        <span class="r-key">No. Nota</span>
        <span class="r-val"><?= htmlspecialchars($d['no_nota']) ?></span>
      </div>
      <div class="r-row">
        <span class="r-key">Tgl Masuk</span>
        <span class="r-val"><?= tglIndo($d['tanggal_masuk']) ?></span>
      </div>
      <div class="r-row">
        <span class="r-key">Tgl Selesai</span>
        <span class="r-val"><?= tglIndo($d['tanggal_selesai']) ?></span>
      </div>

      <hr class="r-div"/>

      <!-- Detail Order -->
      <div class="r-row">
        <span class="r-key">Pelanggan</span>
        <span class="r-val"><?= htmlspecialchars($d['nama_pelanggan']) ?></span>
      </div>
      <div class="r-row">
        <span class="r-key">Layanan</span>
        <span class="r-val"><?= htmlspecialchars($d['nama_layanan']) ?></span>
      </div>
      <div class="r-row">
        <span class="r-key">Durasi</span>
        <span class="r-val"><?= htmlspecialchars($d['label_durasi']) ?></span>
      </div>
      <div class="r-row">
        <span class="r-key">Berat</span>
        <span class="r-val"><?= number_format($d['berat_kg'], 2) ?> kg</span>
      </div>
      <div class="r-row">
        <span class="r-key">Harga/kg</span>
        <span class="r-val"><?= rupiah($d['harga_per_kg']) ?></span>
      </div>

      <?php if (!empty($d['catatan'])): ?>
      <div class="r-row" style="margin-top:4px">
        <span class="r-key">Catatan</span>
        <span class="r-val" style="font-style:italic"><?= htmlspecialchars($d['catatan']) ?></span>
      </div>
      <?php endif; ?>

      <hr class="r-div"/>

      <!-- Total -->
      <div class="r-total">
        <span>TOTAL</span>
        <span><?= rupiah($d['total_harga']) ?></span>
      </div>

      <hr class="r-div"/>

      <!-- Footer -->
      <div class="r-foot">
        Terima kasih atas kepercayaan Anda!<br/>
        Harap tunjukkan nota ini saat pengambilan.<br/>
        — Permana Laundry —
      </div>

    </div><!-- /copy-block -->

    <?php if (!$isLast): ?>
      <!-- Tanda pemisah antar copy di tampilan layar -->
      <div class="copy-separator">✂ ✂ ✂ POTONG DI SINI ✂ ✂ ✂</div>
    <?php endif; ?>

    <?php endfor; ?>

  </div><!-- /nota-area -->
</div><!-- /paper-outer -->

<script>
  // Auto-trigger print dialog saat halaman terbuka
  // Beri jeda 400ms agar font sempat dimuat
  window.addEventListener('load', function() {
    setTimeout(function() { window.print(); }, 400);
  });
</script>

</body>
</html>
