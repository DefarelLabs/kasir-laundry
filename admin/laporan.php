<?php
// admin/laporan.php — Laporan rekap dengan range tanggal & pengeluaran
require_once '../includes/config.php';
requireLogin();

$db = getDB();

// ── Tentukan preset & range tanggal ───────────────────────────
$preset   = $_GET['preset'] ?? 'hari_ini';
$today    = date('Y-m-d');

switch ($preset) {
    case 'hari_ini':
        $tglMulai = $tglAkhir = $today;
        break;
    case '1_minggu':
        $tglMulai = date('Y-m-d', strtotime('-6 days'));
        $tglAkhir = $today;
        break;
    case '2_minggu':
        $tglMulai = date('Y-m-d', strtotime('-13 days'));
        $tglAkhir = $today;
        break;
    case '3_minggu':
        $tglMulai = date('Y-m-d', strtotime('-20 days'));
        $tglAkhir = $today;
        break;
    case '1_bulan':
        $tglMulai = date('Y-m-d', strtotime('-29 days'));
        $tglAkhir = $today;
        break;
    case 'custom':
        $tglMulai = $_GET['tgl_mulai'] ?? $today;
        $tglAkhir = $_GET['tgl_akhir'] ?? $today;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglMulai)) $tglMulai = $today;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAkhir))  $tglAkhir = $today;
        if ($tglMulai > $tglAkhir) [$tglMulai, $tglAkhir] = [$tglAkhir, $tglMulai];
        break;
    default:
        $preset   = 'hari_ini';
        $tglMulai = $tglAkhir = $today;
}

$periodeLabel = match($preset) {
    'hari_ini' => 'Hari Ini — ' . tglIndoDate($tglMulai),
    '1_minggu' => '1 Minggu — ' . tglIndoDate($tglMulai) . ' s/d ' . tglIndoDate($tglAkhir),
    '2_minggu' => '2 Minggu — ' . tglIndoDate($tglMulai) . ' s/d ' . tglIndoDate($tglAkhir),
    '3_minggu' => '3 Minggu — ' . tglIndoDate($tglMulai) . ' s/d ' . tglIndoDate($tglAkhir),
    '1_bulan'  => '1 Bulan — ' . tglIndoDate($tglMulai) . ' s/d ' . tglIndoDate($tglAkhir),
    'custom'   => tglIndoDate($tglMulai) . ' s/d ' . tglIndoDate($tglAkhir),
    default    => '',
};

// ── QUERY: Ringkasan transaksi (dari header) ──────────────────
$stmtRingkas = $db->prepare("
    SELECT
        COUNT(*)                              AS jml_order,
        COALESCE(SUM(total_harga), 0)         AS total_pendapatan,
        COALESCE(SUM(deposit), 0)             AS total_deposit,
        COALESCE(SUM(sisa_bayar), 0)          AS total_piutang,
        SUM(status='diambil')                 AS sudah_diambil,
        COALESCE(SUM(
            CASE WHEN status='diambil' THEN total_harga ELSE deposit END
        ), 0) AS uang_diterima
    FROM transaksi
    WHERE DATE(tanggal_masuk) BETWEEN ? AND ?
");
$stmtRingkas->execute([$tglMulai, $tglAkhir]);
$ringkas = $stmtRingkas->fetch();

// ── QUERY: Total berat & satuan (dari transaksi_detail) ───────
// Dipisah dari query header supaya SUM(total_harga) tidak
// dobel-hitung akibat JOIN 1-ke-banyak dengan transaksi_detail.
$stmtBeratRingkas = $db->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN d.tipe_hitungan='kilo'   THEN d.jumlah ELSE 0 END),0) AS total_berat,
        COALESCE(SUM(CASE WHEN d.tipe_hitungan='satuan' THEN d.jumlah ELSE 0 END),0) AS total_satuan
    FROM transaksi_detail d
    JOIN transaksi t ON t.id = d.transaksi_id
    WHERE DATE(t.tanggal_masuk) BETWEEN ? AND ?
");
$stmtBeratRingkas->execute([$tglMulai, $tglAkhir]);
$beratRingkas = $stmtBeratRingkas->fetch();
$ringkas['total_berat']  = $beratRingkas['total_berat'];
$ringkas['total_satuan'] = $beratRingkas['total_satuan'];

// ── QUERY: Pengeluaran periode ─────────────────────────────────
$stmtPengeluaran = $db->prepare("
    SELECT COALESCE(SUM(jumlah),0) AS total_pengeluaran, COUNT(*) AS jml_pengeluaran
    FROM pengeluaran WHERE tanggal BETWEEN ? AND ?
");
$stmtPengeluaran->execute([$tglMulai, $tglAkhir]);
$dataPengeluaran = $stmtPengeluaran->fetch();

$totalPendapatan  = (float)$ringkas['total_pendapatan'];
$totalDeposit     = (float)$ringkas['total_deposit'];
$totalPiutang     = (float)$ringkas['total_piutang'];
$uangDiterima     = (float)$ringkas['uang_diterima'];   // ← ganti nama dari $pendapatanDiambil
$totalPengeluaran = (float)$dataPengeluaran['total_pengeluaran'];
$labaBersih       = $uangDiterima - $totalPengeluaran;

// ── QUERY: Rekap per hari (order & pendapatan dari header) ────
$stmtHarianTx = $db->prepare("
    SELECT DATE(tanggal_masuk) AS tgl, COUNT(*) AS jml_order, SUM(total_harga) AS total_pendapatan
    FROM transaksi WHERE DATE(tanggal_masuk) BETWEEN ? AND ?
    GROUP BY DATE(tanggal_masuk) ORDER BY tgl DESC
");
$stmtHarianTx->execute([$tglMulai, $tglAkhir]);
$dataHarian = $stmtHarianTx->fetchAll();

// ── QUERY: Berat/satuan per hari (dari detail, digabung manual) ─
$stmtHarianDet = $db->prepare("
    SELECT DATE(t.tanggal_masuk) AS tgl,
           COALESCE(SUM(CASE WHEN d.tipe_hitungan='kilo'   THEN d.jumlah ELSE 0 END),0) AS total_berat,
           COALESCE(SUM(CASE WHEN d.tipe_hitungan='satuan' THEN d.jumlah ELSE 0 END),0) AS total_satuan
    FROM transaksi_detail d
    JOIN transaksi t ON t.id = d.transaksi_id
    WHERE DATE(t.tanggal_masuk) BETWEEN ? AND ?
    GROUP BY DATE(t.tanggal_masuk)
");
$stmtHarianDet->execute([$tglMulai, $tglAkhir]);
$beratPerHari = [];
foreach ($stmtHarianDet->fetchAll() as $r) {
    $beratPerHari[$r['tgl']] = $r;
}
$dataHarian = array_map(function ($h) use ($beratPerHari) {
    $b = $beratPerHari[$h['tgl']] ?? ['total_berat' => 0, 'total_satuan' => 0];
    $h['total_berat']  = $b['total_berat'];
    $h['total_satuan'] = $b['total_satuan'];
    return $h;
}, $dataHarian);

// ── QUERY: Rekap per layanan (dari transaksi_detail) ───────────
$stmtLayanan = $db->prepare("
    SELECT d.nama_layanan AS nama, d.label_durasi, d.tipe_hitungan, COUNT(*) AS jml,
           SUM(CASE WHEN d.tipe_hitungan='kilo'   THEN d.jumlah ELSE 0 END) AS total_berat,
           SUM(CASE WHEN d.tipe_hitungan='satuan' THEN d.jumlah ELSE 0 END) AS total_satuan,
           SUM(d.subtotal) AS total_harga
    FROM transaksi_detail d
    JOIN transaksi t ON t.id = d.transaksi_id
    WHERE DATE(t.tanggal_masuk) BETWEEN ? AND ?
    GROUP BY d.nama_layanan, d.label_durasi, d.tipe_hitungan
    ORDER BY total_harga DESC
");
$stmtLayanan->execute([$tglMulai, $tglAkhir]);
$dataLayanan = $stmtLayanan->fetchAll();

// ── QUERY: Rekap pengeluaran DIKELOMPOKKAN PER TANGGAL ─────────
// Hanya menampilkan Tanggal & Total (tanpa rincian keterangan/catatan)
$stmtDetPengeluaran = $db->prepare("
    SELECT tanggal, COALESCE(SUM(jumlah),0) AS total_hari, COUNT(*) AS jml_item
    FROM pengeluaran
    WHERE tanggal BETWEEN ? AND ?
    GROUP BY DATE(tanggal)
    ORDER BY tanggal DESC
");
$stmtDetPengeluaran->execute([$tglMulai, $tglAkhir]);
$detailPengeluaran = $stmtDetPengeluaran->fetchAll();

// ── QUERY: Top 5 pelanggan (dari header) ────────────────────────
$stmtTop = $db->prepare("
    SELECT nama_pelanggan, COUNT(*) AS jml, SUM(total_harga) AS total
    FROM transaksi WHERE DATE(tanggal_masuk) BETWEEN ? AND ?
    GROUP BY nama_pelanggan ORDER BY jml DESC LIMIT 5
");
$stmtTop->execute([$tglMulai, $tglAkhir]);
$topPelanggan = $stmtTop->fetchAll();

// ── QUERY: Daftar transaksi yang masih ada sisa tagihan ────────
$stmtPiutang = $db->prepare("
    SELECT no_nota, nama_pelanggan, total_harga, deposit, sisa_bayar, tanggal_masuk, status
    FROM transaksi
    WHERE DATE(tanggal_masuk) BETWEEN ? AND ? AND sisa_bayar > 0
    ORDER BY tanggal_masuk DESC
");
$stmtPiutang->execute([$tglMulai, $tglAkhir]);
$daftarPiutang = $stmtPiutang->fetchAll();

// ── QUERY: Semua ITEM (bukan header) untuk export CSV ───────────
// 1 baris CSV = 1 layanan (bukan 1 nota), supaya subtotal per
// layanan tetap akurat walau 1 nota berisi banyak layanan.
$stmtExport = $db->prepare("
    SELECT t.no_nota, t.nama_pelanggan, d.nama_layanan AS layanan,
           d.jumlah, d.tipe_hitungan, d.harga_per_unit, d.subtotal,
           t.deposit, t.sisa_bayar,
           t.tanggal_masuk, t.status
    FROM transaksi_detail d
    JOIN transaksi t ON t.id = d.transaksi_id
    WHERE DATE(t.tanggal_masuk) BETWEEN ? AND ?
    ORDER BY t.tanggal_masuk DESC, d.id
");
$stmtExport->execute([$tglMulai, $tglAkhir]);
$dataExport = $stmtExport->fetchAll();

$pageTitle = 'Laporan';
require_once '../includes/admin_header.php';
?>

<style>
/* Responsive laporan */
.laporan-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
.keuangan-grid{display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr;gap:0;border:1.5px solid var(--gray-200);border-radius:10px;overflow:hidden}
.keuangan-cell{padding:16px 18px}
.keuangan-cell+.keuangan-cell{border-left:1.5px solid var(--gray-200)}
.preset-btns{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
.preset-btn{padding:8px 14px;font-size:13px;border:1.5px solid var(--gray-200);border-radius:var(--radius-md);background:var(--white);color:var(--gray-600);font-family:inherit;font-weight:600;cursor:pointer;text-decoration:none;transition:all .15s;display:inline-block;text-align:center}
.preset-btn:hover{background:var(--blue-light);border-color:var(--blue-mid);color:var(--blue-mid)}
.preset-btn.active{background:var(--blue-mid);border-color:var(--blue-mid);color:#fff}
.export-btns{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.filter-card{overflow:visible!important;max-width:100%}
#customRange form{flex-wrap:wrap}
#customRange input[type=date]{width:100%;max-width:160px}

/* ── Collapse Detail Pengeluaran ── */
.row-collapsible.is-hidden { display: none; }
.btn-toggle-rows {
  display: block;
  width: 100%;
  text-align: center;
  padding: 10px;
  background: var(--gray-50);
  border: 1px dashed var(--gray-200);
  border-radius: 8px;
  color: var(--blue-mid);
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  margin-top: 10px;
}
.btn-toggle-rows:hover { background: var(--blue-light); }

@media(max-width:768px){
  .laporan-grid-2{grid-template-columns:1fr}
  .keuangan-grid{grid-template-columns:1fr}
  .keuangan-cell+.keuangan-cell{border-left:none;border-top:1.5px solid var(--gray-200)}
  .preset-btn{font-size:11px;padding:6px 9px}
  .preset-btns{gap:5px}
  .keuangan-cell{padding:12px 14px}
  .keuangan-cell div[style*="font-size:19px"]{font-size:15px!important}
  #customRange form{gap:6px!important}
  #customRange input[type=date]{width:auto;flex:1;min-width:0}
  .export-btns{gap:6px}
  .export-btns .btn{font-size:11px!important;padding:6px 10px!important}
  .filter-card p{font-size:12px;word-break:break-word}
}
@media print{
  .sidebar,.topbar,.filter-card,.export-btns,.btn-hamburger{display:none!important}
  .main-wrap{margin-left:0!important}
  .page-body{padding:8px!important}
  .card{box-shadow:none!important;border:1px solid #ddd;margin-bottom:12px}
  .laporan-grid-2{grid-template-columns:1fr 1fr}
}
</style>

<!-- ── Filter Periode ── -->
<div class="card filter-card" style="margin-bottom:20px">

  <div class="preset-btns">
    <a href="laporan.php?preset=hari_ini"  class="preset-btn <?= $preset==='hari_ini'?'active':'' ?>">📅 Hari Ini</a>
    <a href="laporan.php?preset=1_minggu"  class="preset-btn <?= $preset==='1_minggu'?'active':'' ?>">1️⃣ 1 Minggu</a>
    <a href="laporan.php?preset=2_minggu"  class="preset-btn <?= $preset==='2_minggu'?'active':'' ?>">2️⃣ 2 Minggu</a>
    <a href="laporan.php?preset=3_minggu"  class="preset-btn <?= $preset==='3_minggu'?'active':'' ?>">3️⃣ 3 Minggu</a>
    <a href="laporan.php?preset=1_bulan"   class="preset-btn <?= $preset==='1_bulan'?'active':'' ?>">🗓️ 1 Bulan</a>
    <a href="#"                            class="preset-btn <?= $preset==='custom'?'active':'' ?>"
       onclick="toggleCustom(event)">✏️ Custom</a>
  </div>

  <div id="customRange" style="<?= $preset==='custom'?'':'display:none' ?>;padding-top:4px">
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <input type="hidden" name="preset" value="custom"/>
      <label style="font-size:13px;font-weight:600;color:var(--gray-600)">Dari:</label>
      <input type="date" name="tgl_mulai" value="<?= htmlspecialchars($tglMulai) ?>"
             style="width:160px"/>
      <label style="font-size:13px;font-weight:600;color:var(--gray-600)">Sampai:</label>
      <input type="date" name="tgl_akhir" value="<?= htmlspecialchars($tglAkhir) ?>"
             style="width:160px"/>
      <button type="submit" class="btn btn-primary">Tampilkan</button>
    </form>
  </div>

  <p style="margin-top:12px;font-size:13px;color:var(--gray-600)">
    📊 Menampilkan: <strong><?= $periodeLabel ?></strong>
  </p>

  <div class="export-btns">
    <button onclick="exportCSV()" class="btn btn-success" style="font-size:12px;padding:7px 14px">
      📥 Export CSV
    </button>
    <button onclick="window.print()" class="btn btn-outline" style="font-size:12px;padding:7px 14px">
      🖨️ Cetak Laporan
    </button>
  </div>
</div>

<!-- ── Stat Cards Utama ── -->
<div class="stats-grid" style="margin-bottom:20px">
  <div class="stat-card">
    <div class="stat-icon blue">🧺</div>
    <div>
      <div class="stat-label">Total Order</div>
      <div class="stat-value"><?= number_format($ringkas['jml_order']) ?></div>
      <div class="stat-sub"><?= $ringkas['sudah_diambil'] ?> sudah diambil</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon teal">💰</div>
    <div>
      <div class="stat-label">Pendapatan Kotor</div>
      <div class="stat-value" style="font-size:15px"><?= rupiah($totalPendapatan) ?></div>
      <div class="stat-sub">dari transaksi</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red">💸</div>
    <div>
      <div class="stat-label">Total Pengeluaran</div>
      <div class="stat-value" style="font-size:15px;color:var(--red)"><?= rupiah($totalPengeluaran) ?></div>
      <div class="stat-sub"><?= $dataPengeluaran['jml_pengeluaran'] ?> item</div>
    </div>
  </div>
  <div class="stat-card" style="border:2px solid <?= $labaBersih >= 0 ? 'var(--green)' : 'var(--red)' ?>">
    <div class="stat-icon <?= $labaBersih >= 0 ? 'green' : 'red' ?>"><?= $labaBersih >= 0 ? '✅' : '⚠️' ?></div>
    <div>
      <div class="stat-label">Laba Bersih</div>
      <div class="stat-value" style="font-size:15px;color:<?= $labaBersih >= 0 ? 'var(--green)' : 'var(--red)' ?>">
        <?= ($labaBersih < 0 ? '−' : '') . rupiah(abs($labaBersih)) ?>
      </div>
      <div class="stat-sub"><?= $labaBersih >= 0 ? 'Untung' : 'Rugi' ?></div>
    </div>
  </div>
</div>

<!-- ── Ringkasan Keuangan ── -->
<div class="card" style="margin-bottom:20px">
  <div class="card-title">💵 Ringkasan Keuangan — <?= $periodeLabel ?></div>
  <div class="keuangan-grid">
    <div class="keuangan-cell">
      <div style="font-size:12px;color:var(--gray-600);margin-bottom:6px">💰 Pendapatan Kotor</div>
      <div style="font-size:19px;font-weight:800;color:var(--teal)"><?= rupiah($totalPendapatan) ?></div>
      <div style="font-size:11px;color:var(--gray-400);margin-top:4px"><?= $ringkas['jml_order'] ?> transaksi · <?= number_format($ringkas['total_berat'],1) ?> kg · <?= number_format($ringkas['total_satuan'],0) ?> pcs</div>
    </div>
    <div class="keuangan-cell">
      <div style="font-size:12px;color:var(--gray-600);margin-bottom:6px">💵 Deposit Diterima</div>
      <div style="font-size:19px;font-weight:800;color:var(--blue-mid)"><?= rupiah($totalDeposit) ?></div>
      <div style="font-size:11px;color:var(--gray-400);margin-top:4px">Uang muka terkumpul</div>
    </div>
    <div class="keuangan-cell" style="background:<?= $totalPiutang>0?'var(--red-light)':'var(--green-light)' ?>">
      <div style="font-size:12px;color:var(--gray-600);margin-bottom:6px"><?= $totalPiutang>0?'⚠️':'✅' ?> Piutang Belum Lunas</div>
      <div style="font-size:19px;font-weight:800;color:<?= $totalPiutang>0?'var(--red)':'var(--green)' ?>"><?= rupiah($totalPiutang) ?></div>
      <div style="font-size:11px;color:var(--gray-400);margin-top:4px">Sisa tagihan pelanggan</div>
    </div>
    <div class="keuangan-cell">
      <div style="font-size:12px;color:var(--gray-600);margin-bottom:6px">💸 Total Pengeluaran</div>
      <div style="font-size:19px;font-weight:800;color:var(--red)"><?= rupiah($totalPengeluaran) ?></div>
      <div style="font-size:11px;color:var(--gray-400);margin-top:4px"><?= $dataPengeluaran['jml_pengeluaran'] ?> item pengeluaran</div>
    </div>
    <div class="keuangan-cell" style="background:<?= $labaBersih >= 0 ? 'var(--green-light)' : 'var(--red-light)' ?>">
      <div style="font-size:12px;color:var(--gray-600);margin-bottom:6px"><?= $labaBersih >= 0 ? '✅' : '⚠️' ?> Pendapatan Bersih</div>
      <div style="font-size:19px;font-weight:800;color:<?= $labaBersih >= 0 ? 'var(--green)' : 'var(--red)' ?>">
        <?= ($labaBersih < 0 ? '−' : '') . rupiah(abs($labaBersih)) ?>
      </div>
      <div style="font-size:11px;color:var(--gray-400);margin-top:4px">
        <?= (int)$ringkas['sudah_diambil'] ?> transaksi Diambil (dihitung penuh) + deposit dari transaksi lain − Pengeluaran
      </div>
    </div>
    <div class="keuangan-cell" style="background:<?= $labaBersih >= 0 ? 'var(--green-light)' : 'var(--red-light)' ?>">
      <div style="font-size:12px;color:var(--gray-600);margin-bottom:6px"><?= $labaBersih >= 0 ? '✅' : '⚠️' ?> Laba Bersih</div>
      <div style="font-size:19px;font-weight:800;color:<?= $labaBersih >= 0 ? 'var(--green)' : 'var(--red)' ?>">
        <?= ($labaBersih < 0 ? '−' : '') . rupiah(abs($labaBersih)) ?>
      </div>
      <div style="font-size:11px;color:var(--gray-400);margin-top:4px">Pendapatan − Pengeluaran</div>
    </div>
  </div>
</div>

<!-- ── Grid Rekap ── -->
<div class="laporan-grid-2">

  <!-- Rekap per hari -->
  <div class="card">
    <div class="card-title">📅 Rekap Per Hari</div>
    <?php if (empty($dataHarian)): ?>
      <p style="color:var(--gray-400);text-align:center;padding:20px">Tidak ada data.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tanggal</th><th>Order</th><th>Berat</th><th>Satuan</th><th>Pendapatan</th></tr></thead>
          <tbody>
            <?php foreach ($dataHarian as $h): ?>
            <tr>
              <td><strong><?= tglIndoDate($h['tgl']) ?></strong></td>
              <td><?= $h['jml_order'] ?></td>
              <td><?= number_format($h['total_berat'],1) ?> kg</td>
              <td><?= number_format($h['total_satuan'],0) ?> pcs</td>
              <td><?= rupiah($h['total_pendapatan']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="background:var(--blue-light);font-weight:700">
              <td>TOTAL</td>
              <td><?= array_sum(array_column($dataHarian,'jml_order')) ?></td>
              <td><?= number_format(array_sum(array_column($dataHarian,'total_berat')),1) ?> kg</td>
              <td><?= number_format(array_sum(array_column($dataHarian,'total_satuan')),0) ?> pcs</td>
              <td><?= rupiah(array_sum(array_column($dataHarian,'total_pendapatan'))) ?></td>
            </tr>
          </tfoot>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Rekap per layanan -->
  <div class="card">
    <div class="card-title">🧺 Rekap Per Layanan</div>
    <?php if (empty($dataLayanan)): ?>
      <p style="color:var(--gray-400);text-align:center;padding:20px">Tidak ada data.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Layanan</th><th>Order</th><th>Berat</th><th>Total</th></tr></thead>
        <tbody>
          <?php foreach ($dataLayanan as $l): ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($l['nama']) ?></strong>
              <br/><span style="font-size:11px;color:var(--gray-400)"><?= htmlspecialchars($l['label_durasi']) ?></span>
            </td>
            <td><?= $l['jml'] ?></td>
            <td><?= $l['tipe_hitungan'] === 'satuan'
                ? number_format($l['total_satuan'],0) . ' pcs'
                : number_format($l['total_berat'],1) . ' kg' ?></td>
            <td><?= rupiah($l['total_harga']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── Rekap Pengeluaran per Tanggal ── -->
<div class="card" style="margin-bottom:20px">
  <div class="card-title">💸 Rekap Pengeluaran per Tanggal — <?= $periodeLabel ?></div>
  <?php if (empty($detailPengeluaran)): ?>
    <div style="text-align:center;padding:24px;color:var(--gray-400)">
      <div style="font-size:28px;margin-bottom:8px">🎉</div>Tidak ada pengeluaran pada periode ini.
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Tanggal</th><th>Total Pengeluaran</th></tr></thead>
      <tbody id="tbodyPengeluaran">
        <?php foreach ($detailPengeluaran as $i => $p): ?>
        <tr class="row-collapsible <?= $i >= 5 ? 'is-hidden' : '' ?>" data-idx="<?= $i ?>">
          <td style="white-space:nowrap;font-size:13px"><strong><?= tglIndoDate($p['tanggal']) ?></strong></td>
          <td style="color:var(--red);font-weight:700"><?= rupiah($p['total_hari']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="background:var(--red-light);font-weight:700">
          <td style="padding:10px 12px">TOTAL PENGELUARAN</td>
          <td style="color:var(--red);font-weight:800"><?= rupiah($totalPengeluaran) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php if (count($detailPengeluaran) > 5): ?>
    <button type="button" class="btn-toggle-rows" id="btnToggleRows" onclick="toggleRowsPengeluaran()">
      ⬇️ Tampilkan Semua (<?= count($detailPengeluaran) - 5 ?> lagi)
    </button>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ── Daftar Piutang Belum Lunas ── -->
<div class="card" style="margin-bottom:20px">
  <div class="card-title">🧾 Daftar Piutang Belum Lunas — <?= $periodeLabel ?></div>
  <?php if (empty($daftarPiutang)): ?>
    <div style="text-align:center;padding:24px;color:var(--gray-400)">
      <div style="font-size:28px;margin-bottom:8px">🎉</div>Semua transaksi pada periode ini sudah lunas.
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>No. Nota</th><th>Pelanggan</th><th>Total</th><th>Deposit</th><th>Sisa Tagihan</th><th>Tgl Masuk</th></tr></thead>
      <tbody>
        <?php foreach ($daftarPiutang as $p): ?>
        <tr>
          <td><code style="font-size:11px;background:var(--gray-100);padding:2px 5px;border-radius:4px"><?= htmlspecialchars($p['no_nota']) ?></code></td>
          <td><strong><?= htmlspecialchars($p['nama_pelanggan']) ?></strong></td>
          <td><?= rupiah($p['total_harga']) ?></td>
          <td><?= rupiah($p['deposit']) ?></td>
          <td style="color:var(--red);font-weight:700"><?= rupiah($p['sisa_bayar']) ?></td>
          <td style="font-size:12px"><?= tglIndo($p['tanggal_masuk']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="background:var(--red-light);font-weight:700">
          <td colspan="4" style="padding:10px 12px">TOTAL PIUTANG</td>
          <td style="color:var(--red)"><?= rupiah(array_sum(array_column($daftarPiutang,'sisa_bayar'))) ?></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ── Top Pelanggan ── -->
<div class="card">
  <div class="card-title">⭐ Top 5 Pelanggan</div>
  <?php if (empty($topPelanggan)): ?>
    <p style="color:var(--gray-400);text-align:center;padding:20px">Tidak ada data.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Nama Pelanggan</th><th>Jumlah Order</th><th>Total Belanja</th></tr></thead>
      <tbody>
        <?php foreach ($topPelanggan as $i => $p): ?>
        <tr>
          <td><?= ['🥇','🥈','🥉','4️⃣','5️⃣'][$i] ?? ($i+1) ?></td>
          <td><strong><?= htmlspecialchars($p['nama_pelanggan']) ?></strong></td>
          <td><?= $p['jml'] ?> kali</td>
          <td><?= rupiah($p['total']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php
// ── Data export (1 baris CSV = 1 layanan) ────────────────────
$exportTransaksi = array_map(fn($r) => [
    'no_nota'        => $r['no_nota'],
    'nama_pelanggan' => $r['nama_pelanggan'],
    'layanan'        => $r['layanan'],
    'jumlah'         => $r['jumlah'],
    'satuan'         => $r['tipe_hitungan'] === 'satuan' ? 'pcs' : 'kg',
    'harga_per_unit' => $r['harga_per_unit'],
    'total_harga'    => $r['subtotal'],
    'deposit'        => $r['deposit'],
    'sisa_bayar'     => $r['sisa_bayar'],
    'tanggal_masuk'  => date('d/m/Y H:i', strtotime($r['tanggal_masuk'])),
    'status'         => $r['status'],
], $dataExport);

// ── Data export CSV Pengeluaran: sekarang direkap per tanggal ──
$exportPengeluaran = array_map(fn($p) => [
    'tanggal' => $p['tanggal'],
    'jumlah'  => $p['total_hari'],
    'catatan' => $p['jml_item'] . ' item',
], $detailPengeluaran);
?>

<script>
window.laporanData = <?= json_encode([
    'periodeLabel'      => $periodeLabel,
    'preset'            => $preset,
    'tanggalCetak'      => date('Ymd'),
    'totalPendapatan'   => $totalPendapatan,
    'totalDeposit'      => $totalDeposit,
    'totalPiutang'      => $totalPiutang,
    'uangDiterima'      => $uangDiterima,    
    'totalPengeluaran'  => $totalPengeluaran,
    'labaBersih'        => $labaBersih,
    'transaksi'         => $exportTransaksi,
    'pengeluaran'       => $exportPengeluaran,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
</script>

<?php require_once '../includes/admin_footer.php'; ?>
