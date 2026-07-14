<?php
// index.php — Halaman kasir utama (input transaksi, multi-layanan)
require_once 'includes/config.php';
requireLogin('admin/login.php');   // ← proteksi: redirect ke admin/login.php jika belum login

$db = getDB();

// ── Ambil daftar layanan aktif dari DB ────────────────────────
$layananList = $db->query("SELECT * FROM layanan WHERE aktif=1 ORDER BY id")->fetchAll();

// ── HANDLE POST: simpan transaksi (header + banyak detail) ────
$notaData = null;  // Data nota yang baru dibuat, dipakai preview & cetak
$errMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama          = trim($_POST['nama'] ?? '');
    $catatan       = trim($_POST['catatan'] ?? '');
    $keranjangJson = $_POST['keranjang_json'] ?? '[]';
    $keranjangRaw  = json_decode($keranjangJson, true);
    $keranjangRaw  = is_array($keranjangRaw) ? $keranjangRaw : [];

    if (!$nama || empty($keranjangRaw)) {
        $errMsg = 'Nama pelanggan dan minimal 1 layanan di keranjang wajib diisi.';
    } else {
        // ▼ Jangan percaya harga dari client — ambil ulang tiap layanan dari DB
        $items        = [];
        $totalHarga   = 0;
        $maxDurasiJam = 0;

        foreach ($keranjangRaw as $row) {
            $lid    = (int)($row['layanan_id'] ?? 0);
            $jumlah = (float)($row['jumlah'] ?? 0);

            $stmtL = $db->prepare("SELECT * FROM layanan WHERE id=? AND aktif=1");
            $stmtL->execute([$lid]);
            $layanan = $stmtL->fetch();

            if (!$layanan || $jumlah <= 0) continue;
            if ($layanan['tipe_hitungan'] === 'satuan' && $jumlah != (int)$jumlah) continue;

            $subtotal      = $jumlah * $layanan['harga_per_kg'];
            $totalHarga   += $subtotal;
            $maxDurasiJam  = max($maxDurasiJam, (int)$layanan['durasi_jam']);

            $items[] = [
                'layanan_id'     => $lid,
                'nama_layanan'   => $layanan['nama'],
                'label_durasi'   => $layanan['label_durasi'],
                'tipe_hitungan'  => $layanan['tipe_hitungan'],
                'jumlah'         => $jumlah,
                'harga_per_unit' => $layanan['harga_per_kg'],
                'subtotal'       => $subtotal,
            ];
        }

        if (empty($items)) {
            $errMsg = 'Tidak ada layanan valid di keranjang.';
        } else {
            // ── Hitung Deposit & Sisa Bayar di server (jangan percaya client) ──
            $deposit = (float)($_POST['deposit'] ?? 0);
            if ($deposit < 0) {
                $deposit = 0;
            }
            if ($deposit > $totalHarga) {
                $deposit = $totalHarga; // deposit tidak boleh melebihi total
            }
            $sisaBayar = $totalHarga - $deposit;

            $tglMasuk   = new DateTime();
            $tglSelesai = (clone $tglMasuk)->modify("+{$maxDurasiJam} hours");
            $noNota     = generateNoNota($db);

            try {
                $db->beginTransaction();

                $stmtH = $db->prepare("
                    INSERT INTO transaksi (no_nota, nama_pelanggan, total_harga, deposit, sisa_bayar, tanggal_masuk, tanggal_selesai, catatan)
                    VALUES (?,?,?,?,?,?,?,?)
                ");
                $stmtH->execute([
                    $noNota, $nama, $totalHarga, $deposit, $sisaBayar,
                    $tglMasuk->format('Y-m-d H:i:s'), $tglSelesai->format('Y-m-d H:i:s'),
                    $catatan ?: null,
                ]);
                $transaksiId = (int)$db->lastInsertId();

                // ...INSERT transaksi_detail tetap sama seperti sebelumnya...

                $db->commit();

                $notaData = [
                    'id'              => $transaksiId,
                    'no_nota'         => $noNota,
                    'nama_pelanggan'  => $nama,
                    'tanggal_masuk'   => $tglMasuk->format('Y-m-d H:i:s'),
                    'tanggal_selesai' => $tglSelesai->format('Y-m-d H:i:s'),
                    'total_harga'     => $totalHarga,
                    'deposit'         => $deposit,      // ← tambahan
                    'sisa_bayar'      => $sisaBayar,    // ← tambahan
                    'items'           => $items,
                ];
            } catch (Exception $e) {
                $db->rollBack();
                $errMsg = 'Gagal menyimpan transaksi. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Kasir — Permana Laundry</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Source+Code+Pro:wght@400;600&display=swap" rel="stylesheet"/>
  <link rel="icon" href="assets/logo/logo.png"/>
  <style>
    :root{
      --blue-dark:#0f2a4a;--blue-mid:#1565c0;--blue-light:#e3f0ff;
      --teal:#00897b;--teal-light:#e0f2f1;
      --white:#fff;--gray-50:#f8fafc;--gray-100:#f1f5f9;
      --gray-200:#e2e8f0;--gray-400:#94a3b8;--gray-600:#475569;--gray-800:#1e293b;
      --red-light:#ffebee;--red:#c62828;
      --radius-sm:6px;--radius-md:12px;--radius-lg:20px;
      --shadow:0 4px 24px rgba(15,42,74,.10);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Plus Jakarta Sans',sans-serif;background:linear-gradient(135deg,#e3f0ff 0%,#f0faf8 100%);min-height:100vh;color:var(--gray-800)}
    .app-header{background:var(--blue-dark);color:#fff;padding:16px 32px;display:flex;align-items:center;justify-content:space-between;gap:14px;box-shadow:0 2px 12px rgba(0,0,0,.18)}
    .header-left{display:flex;align-items:center;gap:14px}
    .logo-icon{width:42px;height:42px;background:var(--teal);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
    .brand-name{font-size:20px;font-weight:800}
    .brand-sub{font-size:12px;color:#90caf9;margin-top:2px}
    .admin-link{background:rgba(255,255,255,.12);color:#fff;text-decoration:none;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600;transition:background .15s}
    .admin-link:hover{background:rgba(255,255,255,.22)}
    .main-content{display:flex;gap:28px;padding:32px;max-width:1100px;margin:0 auto;width:100%}
    .panel-form{flex:1;display:flex;flex-direction:column;gap:20px}
    .card{background:var(--white);border-radius:var(--radius-lg);box-shadow:var(--shadow);padding:28px}
    .card-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--blue-mid);margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid var(--blue-light)}
    .form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
    .form-group:last-child{margin-bottom:0}
    label{font-size:13px;font-weight:600;color:var(--gray-600)}
    label .req{color:var(--red)}
    input[type=text],input[type=number],select,textarea{width:100%;padding:11px 14px;border:1.5px solid var(--gray-200);border-radius:var(--radius-sm);font-family:inherit;font-size:15px;color:var(--gray-800);background:var(--gray-50);outline:none;transition:border-color .18s}
    input:focus,select:focus,textarea:focus{border-color:var(--blue-mid);background:var(--white)}
    .price-summary{background:linear-gradient(135deg,var(--blue-dark) 0%,#1a3a6b 100%);border-radius:var(--radius-md);padding:20px 22px;color:#fff;display:flex;justify-content:space-between;align-items:center;gap:12px}
    .price-label{font-size:12px;color:#90caf9;margin-bottom:3px}
    .price-total{font-size:26px;font-weight:800}
    .price-break{font-size:12px;color:#bbdefb;margin-top:3px}
    .badge-dur{background:var(--teal);border-radius:var(--radius-sm);padding:8px 14px;text-align:center;flex-shrink:0}
    .badge-dur .dl{font-size:10px;color:#b2dfdb}
    .badge-dur .dv{font-size:18px;font-weight:700}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:12px 22px;border:none;border-radius:var(--radius-md);font-family:inherit;font-size:15px;font-weight:700;cursor:pointer;transition:opacity .15s,transform .1s;text-decoration:none;width:100%}
    .btn:active{transform:scale(.97)}
    .btn-primary{background:var(--blue-mid);color:#fff}
    .btn-primary:hover{opacity:.9}
    .btn-teal{background:var(--teal);color:#fff}
    .btn-teal:hover{opacity:.9}
    .btn-outline{background:transparent;border:1.5px solid var(--gray-200);color:var(--gray-600)}
    .btn-outline:hover{background:var(--gray-100)}
    .btn:disabled{background:var(--gray-200);color:var(--gray-400);cursor:not-allowed}
    .error-msg{background:var(--red-light);color:var(--red);padding:12px 16px;border-radius:var(--radius-sm);border-left:4px solid var(--red);font-size:14px;margin-bottom:16px}
    .success-msg{background:var(--teal-light);color:var(--teal);padding:12px 16px;border-radius:var(--radius-sm);border-left:4px solid var(--teal);font-size:14px;margin-bottom:16px}
    /* ── Receipt panel ── */
    .panel-receipt{width:290px;flex-shrink:0;display:flex;flex-direction:column;gap:14px}
    .receipt-heading{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--gray-600);text-align:center}
    .receipt-wrap{background:#d8e0ea;border-radius:4px 4px 0 0;padding:0 6px;box-shadow:0 8px 32px rgba(15,42,74,.18)}
    #receipt-area{background:var(--white);font-family:'Source Code Pro',monospace;font-size:12px;color:#1a1a1a;padding:18px 14px 20px;line-height:1.6}
    #receipt-area::before,#receipt-area::after{content:'';display:block;height:6px;background:repeating-linear-gradient(90deg,transparent,transparent 6px,#d8e0ea 6px,#d8e0ea 12px);margin:0 -14px}
    #receipt-area::before{margin-bottom:14px}
    #receipt-area::after{margin-top:14px}
    .r-logo{text-align:center;margin-bottom:10px}
    .r-shop{font-size:16px;font-weight:600;letter-spacing:1px}
    .r-tag{font-size:10px;color:#666;margin-top:2px}
    .r-div{border:none;border-top:1px dashed #aaa;margin:9px 0}
    .r-row{display:flex;justify-content:space-between;font-size:11px;gap:4px}
    .r-key{color:#555;flex-shrink:0}
    .r-val{text-align:right;font-weight:600;word-break:break-word}
    .r-total{display:flex;justify-content:space-between;font-size:13px;font-weight:700;margin-top:4px}
    .r-foot{text-align:center;font-size:10px;color:#888;margin-top:10px}
    .r-copy-label{text-align:center;font-size:10px;font-weight:700;letter-spacing:2px;border:1px dashed #aaa;padding:2px 6px;margin-bottom:8px}
    .r-placeholder{text-align:center;padding:30px 14px;color:var(--gray-400);font-family:'Plus Jakarta Sans',sans-serif;font-size:13px}
    .r-placeholder .ph{font-size:34px;margin-bottom:10px}
    .keranjang-item{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--gray-100)}
    .keranjang-item:last-child{border-bottom:none}
    .keranjang-nama{font-weight:600;font-size:13px}
    .keranjang-sub{font-size:12px;color:var(--gray-400)}
    .keranjang-hapus{background:none;border:none;color:var(--red);cursor:pointer;font-size:15px;padding:2px 6px}
    @media(max-width:720px){
      .main-content{flex-direction:column;padding:20px 16px}
      .panel-receipt{width:100%}
    }

    .div-name {
      display: flex;
      justify-content: space-between;
      flex-direction: column;
      align-items: center;
      font-size: 11px;
      margin-top: 4px;
    }

    .r-name {
      font-weight: 600;
      color: #444;
    }

    .r-value {
      font-weight: 900;
      color: #111;
      margin-top: 2px;
      font-size: 20px;
    }

    /* ══════════════════════════════════════════
       @MEDIA PRINT
       Hanya #receipt-area yang tercetak.
       Jika copy=2, dua nota dicetak sekaligus
       dengan page-break di tengah (diatur JS).
    ═════════════════════════════════════════ */
    @media print{
      body *{visibility:hidden}
      #printable-area,#printable-area *{visibility:visible}
      #printable-area{
        position:fixed;top:0;left:0;width:80mm;
        margin:0;padding:0;box-shadow:none
      }
      #printable-area::before,#printable-area::after{display:none}
      .print-copy{page-break-after:always}
      .print-copy:last-child{page-break-after:auto}
      @page{size:80mm auto;margin:0}
    }
  </style>
</head>
<body>

<header class="app-header">
  <div class="header-left">
    <div class="logo-icon">🫧</div>
    <div>
      <div class="brand-name">Permana Laundry</div>
      <div class="brand-sub">Sistem Kasir</div>
    </div>
  </div>
  <a href="admin/login.php" class="admin-link">⚙️ Panel Admin</a>
</header>

<main class="main-content">

  <!-- ═══ FORM ═══ -->
  <div class="panel-form">
    <div class="card">
      <div class="card-title">✏️ Data Transaksi Baru</div>

      <?php if ($errMsg): ?>
        <div class="error-msg">❌ <?= htmlspecialchars($errMsg) ?></div>
      <?php endif; ?>
      <?php if ($notaData): ?>
        <div class="success-msg">✅ Transaksi <strong><?= htmlspecialchars($notaData['no_nota']) ?></strong> berhasil disimpan! Nota siap dicetak.</div>
      <?php endif; ?>

      <form method="POST" id="kasirForm">
        <div class="form-group">
          <label for="nama">Nama Pelanggan <span class="req">*</span></label>
          <input type="text" id="nama" name="nama" placeholder="cth: Budi Santoso"
                 value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" autocomplete="off"/>
        </div>

        <!-- ═══ Baris tambah layanan ke keranjang ═══ -->
        <div class="form-group">
          <label for="layanan_id">Jenis Layanan</label>
          <select id="layanan_id">
            <option value="" disabled selected>— Pilih Layanan —</option>
            <?php foreach ($layananList as $l): ?>
            <?php $unitLbl = $l['tipe_hitungan'] === 'satuan' ? 'pcs' : 'kg'; ?>
            <option value="<?= $l['id'] ?>"
                    data-nama="<?= htmlspecialchars($l['nama']) ?>"
                    data-price="<?= $l['harga_per_kg'] ?>"
                    data-hours="<?= $l['durasi_jam'] ?>"
                    data-label="<?= htmlspecialchars($l['label_durasi']) ?>"
                    data-tipe="<?= $l['tipe_hitungan'] ?>">
              <?= htmlspecialchars($l['nama']) ?> — <?= rupiah($l['harga_per_kg']) ?>/<?= $unitLbl ?> (<?= htmlspecialchars($l['label_durasi']) ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="flex-direction:row;align-items:end;gap:10px">
          <div style="flex:1">
            <label for="jumlah" id="jumlahLabel">Berat/Jumlah</label>
            <input type="number" id="jumlah" step="0.1" min="0.1" placeholder="cth: 3.5"/>
          </div>
          <button type="button" class="btn btn-teal" style="width:auto;white-space:nowrap"
                  onclick="tambahKeKeranjang()">➕ Tambah</button>
        </div>

        <!-- ═══ Daftar keranjang sementara ═══ -->
        <div class="form-group">
          <label>Keranjang Layanan</label>
          <div id="keranjangList" style="border:1.5px solid var(--gray-200);border-radius:var(--radius-sm);padding:4px 12px;min-height:40px"></div>
        </div>

        <div class="form-group">
          <label for="catatan">Catatan (opsional)</label>
          <textarea id="catatan" name="catatan" rows="2"
                    placeholder="cth: Jangan kena pemutih, ada karpet kecil…"><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
        </div>
        <div class="form-group" style="flex-direction:row;gap:10px">
          <div style="flex:1">
            <label for="deposit">Deposit / Uang Muka <span style="color:var(--gray-400);font-weight:400">(opsional)</span></label>
            <input type="number" id="deposit" name="deposit" min="0" step="1"
                  placeholder="cth: 20000" oninput="updateSisaBayar()"/>
          </div>
          <div style="flex:1">
            <label for="sisaBayarView">Sisa Bayar</label>
            <input type="text" id="sisaBayarView" readonly value="Rp 0"
                  style="background:var(--gray-100);font-weight:700;color:var(--blue-mid)"/>
          </div>
        </div>

        <input type="hidden" name="keranjang_json" id="keranjangJsonInput" value="[]"/>
        <button type="submit" class="btn btn-primary" id="btnSubmit" disabled>💾 Simpan &amp; Buat Nota</button>
      </form>
    </div>

    <!-- Ringkasan harga real-time -->
    <div class="card" style="padding:22px 26px">
      <div class="card-title">💰 Ringkasan Harga</div>
      <div class="price-summary">
        <div>
          <div class="price-label">Total Pembayaran</div>
          <div class="price-total" id="totalText">Rp 0</div>
          <div class="price-break" id="breakText">Tambahkan layanan ke keranjang</div>
        </div>
      </div>
    </div>

    <!-- Tombol cetak -->
    <div class="card">
      <div class="card-title">🖨️ Cetak Nota</div>
      <?php if ($notaData): ?>
        <p style="font-size:13px;color:var(--gray-600);margin-bottom:14px">
          Pilih jumlah salinan nota yang ingin dicetak:
        </p>
        <button onclick="cetakNota(1)" class="btn btn-outline" style="margin-bottom:10px">
          🖨️ Cetak 1 Lembar <span style="font-size:12px;font-weight:400">(Untuk Pelanggan)</span>
        </button>
        <button onclick="cetakNota(2)" class="btn btn-teal">
          🖨️🖨️ Cetak 2 Lembar <span style="font-size:12px;font-weight:400">(Pelanggan + Arsip)</span>
        </button>
        <a href="index.php" class="btn btn-outline" style="margin-top:10px">➕ Transaksi Baru</a>
      <?php else: ?>
        <p style="font-size:13px;color:var(--gray-400);text-align:center;padding:16px 0">
          Simpan transaksi terlebih dahulu untuk mencetak nota.
        </p>
        <button class="btn btn-outline" disabled>🖨️ Cetak 1 Lembar</button>
        <button class="btn btn-outline" style="margin-top:10px" disabled>🖨️🖨️ Cetak 2 Lembar</button>
      <?php endif; ?>
    </div>
  </div>

  <!-- ═══ PREVIEW NOTA ═══ -->
  <div class="panel-receipt">
    <div class="receipt-heading">Preview Nota</div>
    <div class="receipt-wrap">
      <div id="receipt-area">
        <?php if ($notaData): ?>
          <!-- Nota terisi dari data PHP setelah POST -->
          <?php renderNota($notaData, 1, 1) ?>
        <?php else: ?>
          <div class="r-placeholder">
            <div class="ph">🧾</div>
            Nota akan muncul di sini setelah transaksi disimpan.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</main>

<!-- Area tersembunyi khusus untuk dicetak (1 atau 2 copy) -->
<div id="printable-area" style="display:none"></div>

<script>
// ── Data layanan dari PHP (untuk real-time hitung di JS) ───────
const layananData = <?php
  $jsData = [];
  foreach ($layananList as $l) {
    $jsData[$l['id']] = [
      'nama'  => $l['nama'],
      'harga' => (int)$l['harga_per_kg'],
      'label' => $l['label_durasi'],
      'tipe'  => $l['tipe_hitungan'],
    ];
  }
  echo json_encode($jsData);
?>;

// ── Format Rupiah (JS) ─────────────────────────────────────────
function fRp(n) {
  return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0}).format(n);
}

// ── Keranjang layanan (state sementara di client) ───────────────
let keranjang = [];

let totalKeseluruhan = 0;

function updateSisaBayar() {
  const depositInput = document.getElementById('deposit');
  let deposit = parseFloat(depositInput.value) || 0;

  if (deposit < 0) deposit = 0;
  // Jangan biarkan deposit melebihi total (opsional, tapi mencegah minus di UI)
  if (deposit > totalKeseluruhan) deposit = totalKeseluruhan;

  const sisa = totalKeseluruhan - deposit;
  document.getElementById('sisaBayarView').value = fRp(sisa);
}

// Sesuaikan step/label input jumlah sesuai tipe layanan terpilih
document.getElementById('layanan_id').addEventListener('change', function () {
  const d = layananData[this.value];
  const jumlahInput = document.getElementById('jumlah');
  const lbl = document.getElementById('jumlahLabel');
  if (!d) return;
  if (d.tipe === 'satuan') {
    jumlahInput.step = '1'; jumlahInput.min = '1';
    lbl.textContent = 'Jumlah (pcs)';
  } else {
    jumlahInput.step = '0.1'; jumlahInput.min = '0.1';
    lbl.textContent = 'Berat (kg)';
  }
});

function tambahKeKeranjang() {
  const sel = document.getElementById('layanan_id');
  const lid = sel.value;
  const jumlahInput = document.getElementById('jumlah');
  const jumlah = parseFloat(jumlahInput.value);

  if (!lid) { alert('Pilih layanan terlebih dahulu.'); return; }
  const d = layananData[lid];
  if (!jumlah || jumlah <= 0) { alert('Isi berat/jumlah dengan benar.'); return; }
  if (d.tipe === 'satuan' && jumlah !== Math.floor(jumlah)) {
    alert('Untuk layanan Satuan, jumlah harus bulat (cth: 5, bukan 5.5).');
    return;
  }

  keranjang.push({
    layanan_id: lid,
    nama: d.nama,
    tipe: d.tipe,
    jumlah: jumlah,
    harga: d.harga,
    subtotal: jumlah * d.harga,
  });

  jumlahInput.value = '';
  renderKeranjang();
}

function hapusDariKeranjang(idx) {
  keranjang.splice(idx, 1);
  renderKeranjang();
}

function renderKeranjang() {
  const wrap = document.getElementById('keranjangList');
  const btnSubmit = document.getElementById('btnSubmit');
  const totEl = document.getElementById('totalText');
  const brkEl = document.getElementById('breakText');

  if (keranjang.length === 0) {
    wrap.innerHTML = '<p style="color:var(--gray-400);font-size:13px;text-align:center;padding:10px 0">Keranjang kosong</p>';
    totEl.textContent = 'Rp 0';
    brkEl.textContent = 'Tambahkan layanan ke keranjang';
    btnSubmit.disabled = true;
    document.getElementById('keranjangJsonInput').value = '[]';
    totalKeseluruhan = 0;
    updateSisaBayar();
    return;
  }

  let html = '';
  let total = 0;
  keranjang.forEach((it, idx) => {
    total += it.subtotal;
    const unit = it.tipe === 'satuan' ? 'pcs' : 'kg';
    html += `<div class="keranjang-item">
      <div>
        <div class="keranjang-nama">${it.nama}</div>
        <div class="keranjang-sub">${it.jumlah} ${unit} × ${fRp(it.harga)}</div>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <strong style="font-size:13px">${fRp(it.subtotal)}</strong>
        <button type="button" class="keranjang-hapus" onclick="hapusDariKeranjang(${idx})" title="Hapus">🗑️</button>
      </div>
    </div>`;
  });

  wrap.innerHTML = html;
  totEl.textContent = fRp(total);
  brkEl.textContent = keranjang.length + ' layanan di keranjang';
  btnSubmit.disabled = false;

  document.getElementById('keranjangJsonInput').value = JSON.stringify(
    keranjang.map(it => ({ layanan_id: it.layanan_id, jumlah: it.jumlah }))
  );

  totalKeseluruhan = total;      // ← tambahan
  updateSisaBayar();             // ← tambahan
}
renderKeranjang(); // set state awal (keranjang kosong)

document.getElementById('kasirForm').addEventListener('submit', function (e) {
  if (keranjang.length === 0) {
    e.preventDefault();
    alert('Tambahkan minimal 1 layanan ke keranjang sebelum menyimpan!');
  }
});


// ── Fungsi cetak nota ──────────────────────────────────────────
// copy = 1: satu lembar (untuk pelanggan)
// copy = 2: dua lembar (pelanggan + arsip/pemilik)
function cetakNota(copy) {
  <?php if ($notaData): ?>
    window.open('print_nota.php?id=<?= $notaData['id'] ?>&copy=' + copy, '_blank');
  <?php else: ?>
    alert('Simpan transaksi terlebih dahulu!');
  <?php endif; ?>
}
</script>

</body>
</html>
<?php
// ── Helper: render HTML nota (dipakai di preview) ──────────────
// $d harus berbentuk seperti $notaData di atas, dengan key 'items'
// berisi array layanan yang dipesan (nama_layanan, tipe_hitungan,
// jumlah, harga_per_unit, subtotal).
function renderNota(array $d, int $copyNum, int $totalCopy): void {
    $isLast  = $copyNum >= $totalCopy;
    $labels  = [1 => 'PELANGGAN', 2 => 'ARSIP / PEMILIK'];
    $label   = $labels[$copyNum] ?? "COPY $copyNum";
    $tglMsk  = tglIndo($d['tanggal_masuk']);
    $tglSls  = tglIndo($d['tanggal_selesai']);
    ?>
    <div class="print-copy" style="<?= !$isLast ? 'border-bottom:2px dashed #aaa;padding-bottom:12px;margin-bottom:12px' : '' ?>">
      <?php if ($totalCopy > 1): ?>
      <div class="r-copy-label"><?= $label ?></div>
      <?php endif; ?>

      <div class="r-logo">
        <div class="r-shop">PERMANA LAUNDRY</div>
        <div class="r-tag">Bersih · Rapi · Tepat Waktu</div>
        <div class="r-tag">☎ 0896-9150-2028</div>
      </div>

      <hr class="r-div"/>

      <div class="r-row"><span class="r-key">No. Nota</span><span class="r-val"><?= htmlspecialchars($d['no_nota']) ?></span></div>
      <div class="r-row"><span class="r-key">Tgl Masuk</span><span class="r-val"><?= $tglMsk ?></span></div>
      <div class="r-row"><span class="r-key">Tgl Selesai</span><span class="r-val"><?= $tglSls ?></span></div>

      <hr class="r-div"/>

      <div class="r-row"><span class="r-key">Pelanggan</span><span class="r-val"><?= htmlspecialchars($d['nama_pelanggan']) ?></span></div>

      <hr class="r-div"/>

      <!-- ── Daftar layanan (bisa lebih dari 1) ── -->
      <?php foreach ($d['items'] as $it): ?>
        <?php $unit = $it['tipe_hitungan'] === 'satuan' ? 'pcs' : 'kg'; ?>
        <div class="r-row" style="margin-top:6px">
          <span class="r-key" style="font-weight:700"><?= htmlspecialchars($it['nama_layanan']) ?></span>
        </div>
        <div class="r-row">
          <span class="r-key">
            <?= $unit === 'pcs' ? (int)$it['jumlah'] : number_format($it['jumlah'], 2) ?> <?= $unit ?>
            × <?= rupiah($it['harga_per_unit']) ?>
          </span>
          <span class="r-val"><?= rupiah($it['subtotal']) ?></span>
        </div>
      <?php endforeach; ?>

      <hr class="r-div"/>

      <div class="r-total"><span>TOTAL</span><span><?= rupiah($d['total_harga']) ?></span></div>

      <hr class="r-div"/>

      <!-- Nama Pelanggan Bawah -->
      <div class="div-name">
        <span class="r-name">Pelanggan</span>
        <span class="r-value"><?= htmlspecialchars($d['nama_pelanggan']) ?></span>
      </div>

      <hr class="r-div"/>

      <div class="r-foot">Terima kasih! Tunjukkan nota ini saat pengambilan.<br/>— Permana Laundry —</div>
    </div>
<?php } ?>
