<?php
// admin/layanan.php — Kelola jenis layanan
require_once '../includes/config.php';
requireLogin();

$db = getDB();

// ── HANDLE POST: tambah / edit layanan ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi          = $_POST['aksi'] ?? '';
    $nama          = trim($_POST['nama'] ?? '');
    $kode          = trim($_POST['kode'] ?? '');
    $harga         = (int)($_POST['harga_per_kg'] ?? 0);
    $durasi_jam    = (int)($_POST['durasi_jam'] ?? 0);
    $label_durasi  = trim($_POST['label_durasi'] ?? '');
    $aktif         = isset($_POST['aktif']) ? 1 : 0;

    if (!$nama || !$kode || $harga <= 0 || $durasi_jam <= 0 || !$label_durasi) {
        setFlash('error', 'Semua field wajib diisi dengan benar.');
    } elseif ($aksi === 'tambah') {
        try {
            $stmt = $db->prepare("INSERT INTO layanan (kode,nama,harga_per_kg,durasi_jam,label_durasi,aktif) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$kode, $nama, $harga, $durasi_jam, $label_durasi, $aktif]);
            setFlash('success', "Layanan \"$nama\" berhasil ditambahkan.");
        } catch (PDOException $e) {
            setFlash('error', 'Kode layanan sudah digunakan. Gunakan kode lain.');
        }
    } elseif ($aksi === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("UPDATE layanan SET kode=?,nama=?,harga_per_kg=?,durasi_jam=?,label_durasi=?,aktif=? WHERE id=?");
        $stmt->execute([$kode, $nama, $harga, $durasi_jam, $label_durasi, $aktif, $id]);
        setFlash('success', "Layanan \"$nama\" berhasil diperbarui.");
    }
    header('Location: layanan.php');
    exit;
}

// ── HANDLE: toggle aktif / hapus ─────────────────────────────
if (isset($_GET['toggle'])) {
    $id   = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE layanan SET aktif = 1 - aktif WHERE id=?");
    $stmt->execute([$id]);
    setFlash('info', 'Status layanan diperbarui.');
    header('Location: layanan.php');
    exit;
}
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Cek apakah sudah ada transaksi
    $cek = $db->prepare("SELECT COUNT(*) FROM transaksi WHERE layanan_id=?");
    $cek->execute([$id]);
    if ($cek->fetchColumn() > 0) {
        setFlash('error', 'Layanan tidak bisa dihapus karena sudah memiliki transaksi. Nonaktifkan saja.');
    } else {
        $db->prepare("DELETE FROM layanan WHERE id=?")->execute([$id]);
        setFlash('success', 'Layanan berhasil dihapus.');
    }
    header('Location: layanan.php');
    exit;
}

// ── Data edit (jika ada parameter ?edit=id) ───────────────────
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM layanan WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editData = $stmt->fetch();
}

// ── Ambil semua layanan ───────────────────────────────────────
$layananList = $db->query("SELECT * FROM layanan ORDER BY id")->fetchAll();

$pageTitle = 'Kelola Layanan';
require_once '../includes/admin_header.php';
?>

<style>
.layanan-layout{display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start}
@media(max-width:900px){
  .layanan-layout{grid-template-columns:1fr}
}
@media(max-width:768px){
  /* Tabel layanan scroll horizontal */
  .layanan-table-wrap{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    width:100%;
  }
  .layanan-table-wrap table{
    min-width:560px;
    font-size:12px;
  }
  /* Form sidebar tidak sticky di mobile */
  .form-sidebar-layanan{position:static!important}
}
</style>

<div class="layanan-layout">

  <!-- Tabel layanan -->
  <div class="card">
    <div class="card-title">⚙️ Daftar Layanan</div>
    <div class="table-wrap layanan-table-wrap">
      <table>
        <thead>
          <tr>
            <th>Kode</th><th>Nama Layanan</th><th>Harga/kg</th>
            <th>Durasi (Jam)</th><th>Label</th><th>Status</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($layananList as $l): ?>
          <tr>
            <td><code style="font-size:12px;background:var(--gray-100);padding:2px 6px;border-radius:4px"><?= htmlspecialchars($l['kode']) ?></code></td>
            <td><strong><?= htmlspecialchars($l['nama']) ?></strong></td>
            <td><?= rupiah($l['harga_per_kg']) ?></td>
            <td><?= $l['durasi_jam'] ?> jam</td>
            <td><?= htmlspecialchars($l['label_durasi']) ?></td>
            <td>
              <a href="?toggle=<?= $l['id'] ?>">
                <span class="badge <?= $l['aktif'] ? 'selesai' : 'pending' ?>">
                  <?= $l['aktif'] ? '✅ Aktif' : '⏸ Nonaktif' ?>
                </span>
              </a>
            </td>
            <td style="white-space:nowrap">
              <a href="?edit=<?= $l['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
              <a href="?hapus=<?= $l['id'] ?>" class="btn btn-danger btn-sm"
                 onclick="return confirm('Hapus layanan ini?')">🗑️</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Form tambah / edit -->
  <div class="card form-sidebar-layanan">
    <div class="card-title"><?= $editData ? '✏️ Edit Layanan' : '➕ Tambah Layanan' ?></div>
    <form method="POST">
      <input type="hidden" name="aksi" value="<?= $editData ? 'edit' : 'tambah' ?>"/>
      <?php if ($editData): ?>
        <input type="hidden" name="id" value="<?= $editData['id'] ?>"/>
      <?php endif; ?>

      <div class="form-group">
        <label class="lbl">Kode Layanan <span style="color:red">*</span></label>
        <input type="text" name="kode" placeholder="cth: reguler"
               value="<?= htmlspecialchars($editData['kode'] ?? '') ?>"
               <?= $editData ? 'readonly style="background:#f1f5f9"' : '' ?>/>
        <small style="color:var(--gray-400);font-size:12px">Huruf kecil, tanpa spasi. Tidak bisa diubah setelah dibuat.</small>
      </div>

      <div class="form-group">
        <label class="lbl">Nama Layanan <span style="color:red">*</span></label>
        <input type="text" name="nama" placeholder="cth: Cuci Reguler"
               value="<?= htmlspecialchars($editData['nama'] ?? '') ?>"/>
      </div>

      <div class="form-group">
        <label class="lbl">Harga per kg (Rp) <span style="color:red">*</span></label>
        <input type="number" name="harga_per_kg" placeholder="cth: 7000" min="1"
               value="<?= $editData['harga_per_kg'] ?? '' ?>"/>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label class="lbl">Durasi (Jam) <span style="color:red">*</span></label>
          <input type="number" name="durasi_jam" placeholder="cth: 72" min="1"
                 value="<?= $editData['durasi_jam'] ?? '' ?>"/>
        </div>
        <div class="form-group">
          <label class="lbl">Label Durasi <span style="color:red">*</span></label>
          <input type="text" name="label_durasi" placeholder="cth: 3 Hari"
                 value="<?= htmlspecialchars($editData['label_durasi'] ?? '') ?>"/>
        </div>
      </div>

      <div class="form-group" style="flex-direction:row;align-items:center;gap:10px">
        <input type="checkbox" name="aktif" id="aktif" value="1"
               <?= ($editData['aktif'] ?? 1) ? 'checked' : '' ?> style="width:auto"/>
        <label for="aktif" class="lbl" style="margin:0">Layanan Aktif</label>
      </div>

      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-primary" style="flex:1">
          <?= $editData ? '💾 Simpan Perubahan' : '➕ Tambah Layanan' ?>
        </button>
        <?php if ($editData): ?>
          <a href="layanan.php" class="btn btn-outline">Batal</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
