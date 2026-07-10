<?php
// admin/transaksi.php
require_once '../includes/config.php';
requireLogin();

$db = getDB();

// ── HANDLE POST: edit transaksi (level header saja) ─────────────
// Catatan: karena 1 transaksi sekarang bisa berisi BANYAK layanan,
// edit di sini hanya untuk Nama Pelanggan & Catatan. Kalau ingin
// mengubah daftar layanan/berat, hapus transaksi ini lalu buat ulang
// dari halaman Kasir (index.php).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'edit_transaksi') {
    $id      = (int)($_POST['id'] ?? 0);
    $nama    = trim($_POST['nama_pelanggan'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    if (!$id || !$nama) {
        setFlash('error', 'Nama pelanggan wajib diisi.');
    } else {
        $db->prepare("UPDATE transaksi SET nama_pelanggan=?, catatan=? WHERE id=?")
           ->execute([$nama, $catatan ?: null, $id]);
        setFlash('success', "Transaksi \"$nama\" berhasil diperbarui.");
    }
    header('Location: transaksi.php');
    exit;
}

// ── HANDLE: hapus transaksi ───────────────────────────────────────
// transaksi_detail otomatis ikut terhapus (ON DELETE CASCADE)
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $db->prepare("DELETE FROM transaksi WHERE id = ?")->execute([$id]);
    setFlash('success', 'Transaksi berhasil dihapus.');
    header('Location: transaksi.php');
    exit;
}

if (isset($_GET['update_status'])) {
    $id     = (int)$_GET['update_status'];
    $status = $_GET['status'] ?? '';
    if (in_array($status, ['pending','selesai','diambil'])) {
        $db->prepare("UPDATE transaksi SET status=? WHERE id=?")->execute([$status, $id]);
        setFlash('success', 'Status transaksi diperbarui.');
    }
    header('Location: transaksi.php');
    exit;
}

$filterMode   = $_GET['mode']   ?? 'bulan';
$filterBulan  = $_GET['bulan']  ?? date('Y-m');
$filterTgl    = $_GET['tgl']    ?? date('Y-m-d');
$filterStatus = $_GET['status'] ?? '';
$search       = trim($_GET['q'] ?? '');

$where  = [];
$params = [];

if ($filterMode === 'tanggal') {
    $where[]        = "DATE(tanggal_masuk) = :tgl";
    $params[':tgl'] = $filterTgl;
} else {
    $where[]          = "DATE_FORMAT(tanggal_masuk,'%Y-%m') = :bulan";
    $params[':bulan'] = $filterBulan;
}
if ($filterStatus) { $where[] = "status = :status"; $params[':status'] = $filterStatus; }
if ($search) {
    $where[] = "(nama_pelanggan LIKE :q OR no_nota LIKE :q2)";
    $params[':q'] = "%$search%"; $params[':q2'] = "%$search%";
}

$whereSQL = implode(' AND ', $where);

// ── Daftar transaksi (header) sesuai filter ───────────────────────
$stmt = $db->prepare("SELECT * FROM transaksi WHERE $whereSQL ORDER BY tanggal_masuk DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// ── Ambil semua detail layanan untuk baris-baris di atas sekaligus ──
$detailByTransaksi = [];
$idsFiltered = array_column($rows, 'id');
if ($idsFiltered) {
    $placeholders = implode(',', array_fill(0, count($idsFiltered), '?'));
    $stmtDet = $db->prepare("SELECT * FROM transaksi_detail WHERE transaksi_id IN ($placeholders) ORDER BY id");
    $stmtDet->execute($idsFiltered);
    foreach ($stmtDet->fetchAll() as $row) {
        $detailByTransaksi[$row['transaksi_id']][] = $row;
    }
}

// ── Total ringkasan (order, pendapatan, berat, satuan) ────────────
if ($filterMode === 'tanggal') {
    $stmtTot = $db->prepare("
        SELECT COUNT(*) AS jml, COALESCE(SUM(total_harga),0) AS total
        FROM transaksi WHERE DATE(tanggal_masuk)=?
    ");
    $stmtTot->execute([$filterTgl]);
    $stmtTotBerat = $db->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN d.tipe_hitungan='kilo'   THEN d.jumlah ELSE 0 END),0) AS berat,
            COALESCE(SUM(CASE WHEN d.tipe_hitungan='satuan' THEN d.jumlah ELSE 0 END),0) AS satuan
        FROM transaksi_detail d
        JOIN transaksi t ON t.id = d.transaksi_id
        WHERE DATE(t.tanggal_masuk)=?
    ");
    $stmtTotBerat->execute([$filterTgl]);
} else {
    $stmtTot = $db->prepare("
        SELECT COUNT(*) AS jml, COALESCE(SUM(total_harga),0) AS total
        FROM transaksi WHERE DATE_FORMAT(tanggal_masuk,'%Y-%m')=?
    ");
    $stmtTot->execute([$filterBulan]);
    $stmtTotBerat = $db->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN d.tipe_hitungan='kilo'   THEN d.jumlah ELSE 0 END),0) AS berat,
            COALESCE(SUM(CASE WHEN d.tipe_hitungan='satuan' THEN d.jumlah ELSE 0 END),0) AS satuan
        FROM transaksi_detail d
        JOIN transaksi t ON t.id = d.transaksi_id
        WHERE DATE_FORMAT(t.tanggal_masuk,'%Y-%m')=?
    ");
    $stmtTotBerat->execute([$filterBulan]);
}
$totals = $stmtTot->fetch();
$totalsBerat = $stmtTotBerat->fetch();
$totals['berat']  = $totalsBerat['berat'];
$totals['satuan'] = $totalsBerat['satuan'];

$periodeLabel = $filterMode === 'tanggal' ? tglIndoDate($filterTgl) : $filterBulan;

$pageTitle = 'Data Transaksi';
require_once '../includes/admin_header.php';
?>

<style>
@media(max-width:768px){
  .filter-card-inner{flex-direction:column;gap:8px}
  .filter-card-inner input,.filter-card-inner select{width:100%!important;max-width:100%}
  .tab-btns{flex-wrap:nowrap;overflow-x:auto}
  .transaksi-table-wrap{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    width:100%;
  }
  .transaksi-table-wrap table{
    min-width:750px;
  }
  .transaksi-table-wrap select{min-width:100px}
}
.item-line{margin-bottom:2px}
.item-line:last-child{margin-bottom:0}

/* ── Modal ── */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 42, 74, .55);
  z-index: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.modal-box {
  background: var(--white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  width: 100%;
  max-width: 420px;
  padding: 22px;
  max-height: 90vh;
  overflow-y: auto;
}
.modal-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
.modal-head h3 { font-size: 16px; font-weight: 700; color: var(--gray-800); }
.modal-close { background:none; border:none; font-size:16px; cursor:pointer; color:var(--gray-400); }
.modal-close:hover { color: var(--gray-800); }
</style>

<!-- Filter Bar -->
<div class="card" style="padding:16px 18px;margin-bottom:18px">
  <div class="tab-btns" style="display:flex;gap:8px;margin-bottom:14px">
    <a href="?mode=bulan&bulan=<?= htmlspecialchars($filterBulan) ?>&status=<?= htmlspecialchars($filterStatus) ?>&q=<?= urlencode($search) ?>"
       class="btn <?= $filterMode==='bulan'?'btn-primary':'btn-outline' ?>" style="padding:7px 14px;font-size:13px">
      📅 Per Bulan
    </a>
    <a href="?mode=tanggal&tgl=<?= htmlspecialchars($filterTgl) ?>&status=<?= htmlspecialchars($filterStatus) ?>&q=<?= urlencode($search) ?>"
       class="btn <?= $filterMode==='tanggal'?'btn-primary':'btn-outline' ?>" style="padding:7px 14px;font-size:13px">
      🗓️ Per Tanggal
    </a>
  </div>
  <form method="GET">
    <input type="hidden" name="mode" value="<?= htmlspecialchars($filterMode) ?>"/>
    <div class="filter-card-inner" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <?php if ($filterMode === 'tanggal'): ?>
        <input type="date" name="tgl" value="<?= htmlspecialchars($filterTgl) ?>" style="width:auto"/>
      <?php else: ?>
        <input type="month" name="bulan" value="<?= htmlspecialchars($filterBulan) ?>" style="width:auto"/>
      <?php endif; ?>
      <select name="status" style="width:auto">
        <option value="">— Semua Status —</option>
        <option value="pending"  <?= $filterStatus==='pending'?'selected':'' ?>>Pending</option>
        <option value="selesai"  <?= $filterStatus==='selesai'?'selected':'' ?>>Selesai</option>
        <option value="diambil"  <?= $filterStatus==='diambil'?'selected':'' ?>>Diambil</option>
      </select>
      <input type="text" name="q" placeholder="🔍 Cari nama / nota…" value="<?= htmlspecialchars($search) ?>" style="width:200px"/>
      <button type="submit" class="btn btn-primary" style="padding:9px 16px">Cari</button>
      <a href="transaksi.php" class="btn btn-outline" style="padding:9px 16px">Reset</a>
    </div>
  </form>
</div>

<!-- Stat ringkasan -->
<div class="stats-grid" style="margin-bottom:18px">
  <div class="stat-card">
    <div class="stat-icon blue">🧺</div>
    <div><div class="stat-label">Total Order</div><div class="stat-value"><?= $totals['jml'] ?></div><div class="stat-sub"><?= $periodeLabel ?></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon teal">💰</div>
    <div><div class="stat-label">Pendapatan</div><div class="stat-value" style="font-size:15px"><?= rupiah($totals['total']) ?></div><div class="stat-sub"><?= $periodeLabel ?></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange">⚖️</div>
    <div><div class="stat-label">Total Berat</div><div class="stat-value"><?= number_format($totals['berat'],1) ?> kg</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple">🔢</div>
    <div><div class="stat-label">Total Satuan</div><div class="stat-value"><?= number_format($totals['satuan'],0) ?> pcs</div></div>
  </div>
</div>

<!-- Tabel -->
<div class="card">
  <div class="card-title">📋 Transaksi — <?= htmlspecialchars($periodeLabel) ?>
    <span style="font-size:12px;color:var(--gray-400);font-weight:400;margin-left:8px">(<?= count($rows) ?> data)</span>
  </div>
  <?php if (empty($rows)): ?>
    <div style="text-align:center;padding:36px;color:var(--gray-400)"><div style="font-size:32px;margin-bottom:10px">📭</div>Tidak ada transaksi.</div>
  <?php else: ?>
  <div class="table-wrap transaksi-table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th><th>No. Nota</th><th>Pelanggan</th><th>Layanan</th>
          <th>Berat/Pcs</th><th>Total</th><th>Tgl Masuk</th><th>Tgl Selesai</th>
          <th>Status</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $i => $t): ?>
        <?php $items = $detailByTransaksi[$t['id']] ?? []; ?>
        <tr>
          <td style="color:var(--gray-400);font-size:12px"><?= $i+1 ?></td>
          <td><code style="font-size:11px;background:var(--gray-100);padding:2px 5px;border-radius:4px"><?= htmlspecialchars($t['no_nota']) ?></code></td>
          <td><strong><?= htmlspecialchars($t['nama_pelanggan']) ?></strong></td>
          <td style="font-size:13px">
            <?php foreach ($items as $it): ?>
              <div class="item-line"><?= htmlspecialchars($it['nama_layanan']) ?><br/><span style="color:var(--gray-400);font-size:11px"><?= htmlspecialchars($it['label_durasi']) ?></span></div>
            <?php endforeach; ?>
          </td>
          <td>
            <?php foreach ($items as $it): ?>
              <div class="item-line"><?= $it['tipe_hitungan'] === 'satuan' ? (int)$it['jumlah'] . ' pcs' : number_format($it['jumlah'],2) . ' kg' ?></div>
            <?php endforeach; ?>
          </td>
          <td><strong><?= rupiah($t['total_harga']) ?></strong></td>
          <td style="font-size:12px;white-space:nowrap"><?= tglIndo($t['tanggal_masuk']) ?></td>
          <td style="font-size:12px;white-space:nowrap"><?= tglIndo($t['tanggal_selesai']) ?></td>
          <td>
            <form method="GET" style="display:inline-flex;gap:4px">
              <input type="hidden" name="update_status" value="<?= $t['id'] ?>"/>
              <select name="status" onchange="this.form.submit()" style="padding:4px 6px;border:1.5px solid var(--gray-200);border-radius:6px;font-size:12px;font-family:inherit">
                <option value="pending" <?= $t['status']==='pending'?'selected':'' ?>>🕐 Pending</option>
                <option value="selesai" <?= $t['status']==='selesai'?'selected':'' ?>>✅ Selesai</option>
                <option value="diambil" <?= $t['status']==='diambil'?'selected':'' ?>>🏠 Diambil</option>
              </select>
            </form>
          </td>
          <td style="white-space:nowrap">
            <a href="../print_nota.php?id=<?= $t['id'] ?>&copy=1" target="_blank" class="btn btn-outline btn-sm"
              title="Cetak 1">🖨️×1</a>
            <a href="../print_nota.php?id=<?= $t['id'] ?>&copy=2" target="_blank" class="btn btn-success btn-sm"
              title="Cetak 2">🖨️×2</a>
            <button type="button" class="btn btn-warning btn-sm" title="Edit Nama/Catatan"
              onclick="openEditModal(<?= $t['id'] ?>, '<?= htmlspecialchars($t['nama_pelanggan'], ENT_QUOTES) ?>', '<?= htmlspecialchars($t['catatan'] ?? '', ENT_QUOTES) ?>')">✏️</button>
            <a href="?hapus=<?= $t['id'] ?>" class="btn btn-danger btn-sm" title="Hapus"
              onclick="return confirm('Hapus transaksi <?= htmlspecialchars($t['no_nota'], ENT_QUOTES) ?> milik <?= htmlspecialchars($t['nama_pelanggan'], ENT_QUOTES) ?>?\nData tidak bisa dikembalikan!')">🗑️</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="background:var(--blue-light)">
          <td colspan="5" style="font-weight:700;padding:10px 12px">TOTAL</td>
          <td style="font-weight:800;color:var(--blue-mid)"><?= rupiah(array_sum(array_column($rows,'total_harga'))) ?></td>
          <td colspan="4"></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ══ MODAL EDIT TRANSAKSI (Nama Pelanggan & Catatan saja) ══ -->
<div id="modalEditTransaksi" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="modal-head">
      <h3>✏️ Edit Transaksi</h3>
      <button type="button" onclick="closeEditModal()" class="modal-close">✕</button>
    </div>
    <p style="font-size:12px;color:var(--gray-400);margin-bottom:14px">
      Untuk mengubah daftar layanan/berat, hapus transaksi ini lalu buat ulang lewat halaman Kasir.
    </p>
    <form method="POST">
      <input type="hidden" name="aksi" value="edit_transaksi"/>
      <input type="hidden" name="id" id="edit_id"/>

      <div class="form-group">
        <label class="lbl">Nama Pelanggan</label>
        <input type="text" name="nama_pelanggan" id="edit_nama"/>
      </div>

      <div class="form-group">
        <label class="lbl">Catatan</label>
        <textarea name="catatan" id="edit_catatan" rows="2"></textarea>
      </div>

      <div style="display:flex;gap:8px;margin-top:8px">
        <button type="submit" class="btn btn-primary" style="flex:1">💾 Simpan Perubahan</button>
        <button type="button" class="btn btn-outline" onclick="closeEditModal()">Batal</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(id, nama, catatan) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_nama').value = nama;
  document.getElementById('edit_catatan').value = catatan;
  document.getElementById('modalEditTransaksi').style.display = 'flex';
}
function closeEditModal() {
  document.getElementById('modalEditTransaksi').style.display = 'none';
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>
