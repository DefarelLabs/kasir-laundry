<?php
// admin/pengeluaran.php
require_once '../includes/config.php';
requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi       = $_POST['aksi'] ?? '';
    $tanggal    = $_POST['tanggal'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $jumlah     = (int)str_replace(['.', ','], '', $_POST['jumlah'] ?? 0);
    $catatan    = trim($_POST['catatan'] ?? '');

    if (!$tanggal || !$keterangan || $jumlah <= 0) {
        setFlash('error', 'Tanggal, keterangan, dan jumlah wajib diisi dengan benar.');
    } elseif ($aksi === 'tambah') {
        $db->prepare("INSERT INTO pengeluaran (tanggal,keterangan,jumlah,catatan) VALUES (?,?,?,?)")
           ->execute([$tanggal, $keterangan, $jumlah, $catatan ?: null]);
        setFlash('success', "Pengeluaran \"$keterangan\" berhasil ditambahkan.");
    } elseif ($aksi === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("UPDATE pengeluaran SET tanggal=?,keterangan=?,jumlah=?,catatan=? WHERE id=?")
           ->execute([$tanggal, $keterangan, $jumlah, $catatan ?: null, $id]);
        setFlash('success', 'Pengeluaran berhasil diperbarui.');
    }
    $bln = date('Y-m', strtotime($_POST['tanggal'] ?? 'now'));
    header('Location: pengeluaran.php?bulan=' . $bln);
    exit;
}

if (isset($_GET['hapus'])) {
    $db->prepare("DELETE FROM pengeluaran WHERE id=?")->execute([(int)$_GET['hapus']]);
    setFlash('success', 'Pengeluaran berhasil dihapus.');
    header('Location: pengeluaran.php');
    exit;
}

$editData = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM pengeluaran WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editData = $s->fetch();
}

$filterMode  = $_GET['mode']  ?? 'bulan';
$filterBulan = $_GET['bulan'] ?? date('Y-m');
$filterTgl   = $_GET['tgl']   ?? date('Y-m-d');

if ($filterMode === 'tanggal') {
    $stmtList = $db->prepare("SELECT * FROM pengeluaran WHERE tanggal=? ORDER BY id DESC");
    $stmtList->execute([$filterTgl]);
    $periodeLabel = tglIndoDate($filterTgl);
} else {
    $stmtList = $db->prepare("SELECT * FROM pengeluaran WHERE DATE_FORMAT(tanggal,'%Y-%m')=? ORDER BY tanggal DESC,id DESC");
    $stmtList->execute([$filterBulan]);
    $periodeLabel = $filterBulan;
}
$pengeluaranList = $stmtList->fetchAll();
$totalPeriode    = array_sum(array_column($pengeluaranList,'jumlah'));

$pageTitle = 'Pengeluaran';
require_once '../includes/admin_header.php';
?>

<style>
.pengeluaran-layout{display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start}
/*  Agar tidak nembus ke kanan  */
.pengeluaran-layout > div {
  min-width: 0;
}
@media(max-width:900px){.pengeluaran-layout{grid-template-columns:1fr}}
@media(max-width:768px){
  .tab-row{flex-wrap:nowrap;overflow-x:auto}
  .filter-row{flex-direction:column;gap:8px}
  .filter-row input,.filter-row select{width:100%!important;max-width:100%}
  /* Form sidebar ke bawah dan tidak sticky */
  .form-sidebar{position:static!important}
  /* Tabel scroll horizontal */
  .pengeluaran-table-wrap{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    width:100%;
  }
  .pengeluaran-table-wrap table{
    min-width:500px;
    font-size:12px;
  }
  /* Stats grid tidak overflow */
  .stats-grid{grid-template-columns:1fr 1fr!important}
  /* Contoh chips tidak overflow */
  .chip-container{flex-wrap:wrap!important}
}
</style>

<div class="pengeluaran-layout">

  <!-- Kiri: filter + tabel -->
  <div style="display:flex;flex-direction:column;gap:16px">

    <!-- Filter -->
    <div class="card" style="padding:14px 16px">
      <div class="tab-row" style="display:flex;gap:8px;margin-bottom:12px">
        <a href="?mode=bulan&bulan=<?= htmlspecialchars($filterBulan) ?>"
           class="btn <?= $filterMode==='bulan'?'btn-primary':'btn-outline' ?>" style="padding:6px 14px;font-size:13px">📅 Per Bulan</a>
        <a href="?mode=tanggal&tgl=<?= htmlspecialchars($filterTgl) ?>"
           class="btn <?= $filterMode==='tanggal'?'btn-primary':'btn-outline' ?>" style="padding:6px 14px;font-size:13px">🗓️ Per Tanggal</a>
      </div>
      <form method="GET">
        <div class="filter-row" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <input type="hidden" name="mode" value="<?= htmlspecialchars($filterMode) ?>"/>
          <?php if ($filterMode==='tanggal'): ?>
            <input type="date" name="tgl" value="<?= htmlspecialchars($filterTgl) ?>" style="width:auto"/>
          <?php else: ?>
            <input type="month" name="bulan" value="<?= htmlspecialchars($filterBulan) ?>" style="width:auto"/>
          <?php endif; ?>
          <button type="submit" class="btn btn-primary" style="padding:8px 16px">Tampilkan</button>
          <a href="pengeluaran.php" class="btn btn-outline" style="padding:8px 16px">Reset</a>
        </div>
      </form>
    </div>

    <!-- Stat -->
    <div class="stats-grid" style="margin-bottom:0">
      <div class="stat-card">
        <div class="stat-icon red">💸</div>
        <div><div class="stat-label">Total Pengeluaran</div><div class="stat-value" style="font-size:16px;color:var(--red)"><?= rupiah($totalPeriode) ?></div><div class="stat-sub"><?= $periodeLabel ?></div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange">📝</div>
        <div><div class="stat-label">Jumlah Catatan</div><div class="stat-value"><?= count($pengeluaranList) ?></div><div class="stat-sub"><?= $periodeLabel ?></div></div>
      </div>
    </div>

    <!-- Tabel -->
    <div class="card">
      <div class="card-title">💸 Daftar Pengeluaran — <?= htmlspecialchars($periodeLabel) ?></div>
      <?php if (empty($pengeluaranList)): ?>
        <div style="text-align:center;padding:32px;color:var(--gray-400)"><div style="font-size:32px;margin-bottom:10px">📭</div>Belum ada pengeluaran.</div>
      <?php else: ?>
      <div class="table-wrap pengeluaran-table-wrap">
        <table>
          <thead><tr><th>#</th><th>Tanggal</th><th>Keterangan</th><th>Jumlah</th><th>Catatan</th><th>Aksi</th></tr></thead>
          <tbody id="tbodyDaftarPengeluaran">
            <?php foreach ($pengeluaranList as $i => $p): ?>
            <tr class="row-collapsible <?= $i >= 5 ? 'is-hidden' : '' ?>">
              <td style="color:var(--gray-400);font-size:12px"><?= $i+1 ?></td>
              <td style="white-space:nowrap;font-size:13px"><strong><?= tglIndoDate($p['tanggal']) ?></strong></td>
              <td><strong><?= htmlspecialchars($p['keterangan']) ?></strong></td>
              <td style="color:var(--red);font-weight:700"><?= rupiah($p['jumlah']) ?></td>
              <td style="font-size:12px;color:var(--gray-600)"><?= $p['catatan'] ? htmlspecialchars($p['catatan']) : '<span style="color:var(--gray-400)">—</span>' ?></td>
              <td style="white-space:nowrap">
                <a href="?edit=<?= $p['id'] ?>&mode=<?= $filterMode ?>&bulan=<?= htmlspecialchars($filterBulan) ?>&tgl=<?= htmlspecialchars($filterTgl) ?>"
                  class="btn btn-warning btn-sm">✏️</a>
                <a href="?hapus=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                  onclick="return confirm('Hapus pengeluaran ini?')">🗑️</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="background:var(--red-light)">
              <td colspan="3" style="font-weight:700;padding:10px 12px">TOTAL</td>
              <td style="font-weight:800;color:var(--red)"><?= rupiah($totalPeriode) ?></td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <?php if (count($pengeluaranList) > 5): ?>
        <button type="button" class="btn-toggle-rows" id="btnToggleDaftarPengeluaran"
                onclick="toggleRowsGeneric('tbodyDaftarPengeluaran','btnToggleDaftarPengeluaran','pengeluaran')">
          ⬇️ Tampilkan Semua (<?= count($pengeluaranList) - 5 ?> pengeluaran lagi)
        </button>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Kanan: Form -->
  <div class="card form-sidebar" style="position:sticky;top:20px">
    <div class="card-title"><?= $editData ? '✏️ Edit Pengeluaran' : '➕ Tambah Pengeluaran' ?></div>
    <form method="POST">
      <input type="hidden" name="aksi" value="<?= $editData ? 'edit' : 'tambah' ?>"/>
      <?php if ($editData): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"/><?php endif; ?>

      <div class="form-group">
        <label class="lbl">Tanggal <span style="color:red">*</span></label>
        <input type="date" name="tanggal" value="<?= htmlspecialchars($editData['tanggal'] ?? date('Y-m-d')) ?>"/>
      </div>
      <div class="form-group">
        <label class="lbl">Keterangan <span style="color:red">*</span></label>
        <input type="text" name="keterangan" placeholder="cth: Beli parfum laundry"
               value="<?= htmlspecialchars($editData['keterangan'] ?? '') ?>"/>
      </div>
      <div class="form-group">
        <label class="lbl">Jumlah (Rp) <span style="color:red">*</span></label>
        <input type="number" name="jumlah" placeholder="cth: 50000" min="1"
               value="<?= $editData['jumlah'] ?? '' ?>"/>
      </div>
      <div class="form-group">
        <label class="lbl">Catatan <span style="color:var(--gray-400)">(opsional)</span></label>
        <textarea name="catatan" rows="3" placeholder="Catatan tambahan…" style="resize:vertical"><?= htmlspecialchars($editData['catatan'] ?? '') ?></textarea>
      </div>
      <div style="display:flex;gap:8px;margin-top:4px">
        <button type="submit" class="btn btn-primary" style="flex:1"><?= $editData ? '💾 Simpan' : '➕ Tambah' ?></button>
        <?php if ($editData): ?><a href="pengeluaran.php" class="btn btn-outline">Batal</a><?php endif; ?>
      </div>
    </form>

    <?php if (!$editData): ?>
    <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--gray-200)">
      <p style="font-size:12px;font-weight:600;color:var(--gray-600);margin-bottom:8px">💡 Contoh:</p>
      <div style="display:flex;flex-wrap:wrap;gap:5px">
        <?php foreach (['Parfum laundry','Plastik kresek','Solasi','Detergen','Pelembut','Hanger','Kantong laundry','Listrik','Air','Bensin'] as $c): ?>
          <span onclick="document.querySelector('[name=keterangan]').value='<?= $c ?>'"
                style="background:var(--gray-100);color:var(--gray-600);padding:3px 9px;border-radius:20px;font-size:12px;cursor:pointer;border:1px solid var(--gray-200)"
                onmouseover="this.style.background='var(--blue-light)'"
                onmouseout="this.style.background='var(--gray-100)'"><?= $c ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once '../includes/admin_footer.php'; ?>
