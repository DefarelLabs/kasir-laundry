<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();

$filterTgl = $_GET['tgl'] ?? date('Y-m-d');
$filterTglFormatted = date('d/m/Y', strtotime($filterTgl));

$stmtHari = $db->prepare("
    SELECT
        COUNT(*) AS jumlah_order,
        COALESCE(SUM(total_harga),0) AS total_pendapatan,
        COALESCE(SUM(CASE WHEN tipe_hitungan='kilo'   THEN berat_kg ELSE 0 END),0) AS total_berat,
        COALESCE(SUM(CASE WHEN tipe_hitungan='satuan' THEN berat_kg ELSE 0 END),0) AS total_satuan,
        SUM(status='pending') AS pending,
        SUM(status='selesai') AS selesai,
        SUM(status='diambil') AS diambil
    FROM transaksi WHERE DATE(tanggal_masuk)=?
");
$stmtHari->execute([$filterTgl]);
$statHari = $stmtHari->fetch();

$stmtBulan = $db->prepare("SELECT COUNT(*) AS jml, COALESCE(SUM(total_harga),0) AS total FROM transaksi WHERE YEAR(tanggal_masuk)=YEAR(?) AND MONTH(tanggal_masuk)=MONTH(?)");
$stmtBulan->execute([$filterTgl, $filterTgl]);
$statBulan = $stmtBulan->fetch();

$stmtList = $db->prepare("SELECT * FROM v_transaksi_lengkap WHERE DATE(tanggal_masuk)=? ORDER BY tanggal_masuk DESC");
$stmtList->execute([$filterTgl]);
$transaksiHari = $stmtList->fetchAll();

$pageTitle = 'Dashboard';
require_once '../includes/admin_header.php';
?>

<form method="GET" style="display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap">
  <label style="font-weight:600;color:var(--gray-600);font-size:14px">📅 Lihat Tanggal:</label>
  <input type="date" name="tgl" value="<?= htmlspecialchars($filterTgl) ?>" style="width:auto;max-width:100%"/>
  <button type="submit" class="btn btn-primary" style="padding:8px 16px">Tampilkan</button>
  <?php if($filterTgl !== date('Y-m-d')): ?>
    <a href="dashboard.php" class="btn btn-outline" style="padding:8px 16px">Hari Ini</a>
  <?php endif; ?>
</form>

<style>
@media(max-width:768px){
  /* Tabel dashboard scroll horizontal */
  .dashboard-table-wrap{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    width:100%;
  }
  .dashboard-table-wrap table{
    min-width:700px;
  }
  /* Badge status lebih compact */
  .dashboard-table-wrap .badge{font-size:10px;padding:2px 7px}
}
</style>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-icon blue">🧺</div><div><div class="stat-label">Order Masuk</div><div class="stat-value"><?= $statHari['jumlah_order'] ?></div><div class="stat-sub"><?= $filterTglFormatted ?></div></div></div>
  <div class="stat-card"><div class="stat-icon teal">💰</div><div><div class="stat-label">Pendapatan</div><div class="stat-value" style="font-size:15px"><?= rupiah($statHari['total_pendapatan']) ?></div><div class="stat-sub"><?= $filterTglFormatted ?></div></div></div>
  <div class="stat-card"><div class="stat-icon orange">⚖️</div><div><div class="stat-label">Total Berat</div><div class="stat-value"><?= number_format($statHari['total_berat'],1) ?> kg</div><div class="stat-sub"><?= $filterTglFormatted ?></div></div></div>
  <div class="stat-card"><div class="stat-icon purple">🔢</div><div><div class="stat-label">Total Satuan</div><div class="stat-value"><?= number_format($statHari['total_satuan'],0) ?> pcs</div><div class="stat-sub"><?= $filterTglFormatted ?></div></div></div>
  <div class="stat-card"><div class="stat-icon green">📅</div><div><div class="stat-label">Order Bulan Ini</div><div class="stat-value"><?= $statBulan['jml'] ?></div><div class="stat-sub"><?= rupiah($statBulan['total']) ?></div></div></div>
</div>

<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
  <div style="background:var(--orange-light);color:var(--orange);padding:7px 14px;border-radius:20px;font-size:13px;font-weight:700">🕐 Pending: <?= $statHari['pending'] ?></div>
  <div style="background:var(--teal-light);color:var(--teal);padding:7px 14px;border-radius:20px;font-size:13px;font-weight:700">✅ Selesai: <?= $statHari['selesai'] ?></div>
  <div style="background:var(--green-light);color:var(--green);padding:7px 14px;border-radius:20px;font-size:13px;font-weight:700">🏠 Diambil: <?= $statHari['diambil'] ?></div>
</div>

<div class="card">
  <div class="card-title">📋 Daftar Order — <?= $filterTglFormatted ?></div>
  <?php if (empty($transaksiHari)): ?>
    <div style="text-align:center;padding:36px;color:var(--gray-400)"><div style="font-size:36px;margin-bottom:10px">📭</div>Tidak ada transaksi pada <?= $filterTglFormatted ?></div>
  <?php else: ?>
    <div class="table-wrap dashboard-table-wrap">
      <table>
        <thead><tr><th>No. Nota</th><th>Pelanggan</th><th>Layanan</th><th>Berat/Pcs</th><th>Total</th><th>Tgl Masuk</th><th>Tgl Selesai</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($transaksiHari as $t): ?>
          <tr>
            <td><code style="font-size:11px;background:var(--gray-100);padding:2px 5px;border-radius:4px"><?= htmlspecialchars($t['no_nota']) ?></code></td>
            <td><strong><?= htmlspecialchars($t['nama_pelanggan']) ?></strong></td>
            <td><?= htmlspecialchars($t['nama_layanan']) ?> <span style="color:var(--gray-400);font-size:11px">(<?= $t['label_durasi'] ?>)</span></td>
            <td><?= $t['tipe_hitungan'] === 'satuan' ? (int)$t['berat_kg'] . ' pcs' : number_format($t['berat_kg'],2) . ' kg' ?></td>
            <td><strong><?= rupiah($t['total_harga']) ?></strong></td>
            <td style="font-size:12px"><?= tglIndo($t['tanggal_masuk']) ?></td>
            <td style="font-size:12px"><?= tglIndo($t['tanggal_selesai']) ?></td>
            <td><span class="badge <?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
            <td>
              <a href="../print_nota.php?id=<?= $t['id'] ?>&copy=2" target="_blank" class="btn btn-success btn-sm">🖨️</a>
              <a href="transaksi.php?update_status=<?= $t['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><td colspan="4" style="font-weight:700;padding:10px 12px">TOTAL</td><td style="font-weight:800;color:var(--teal)"><?= rupiah($statHari['total_pendapatan']) ?></td><td colspan="4"></td></tr>
        </tfoot>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
