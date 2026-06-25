<?php
// ============================================================
//  includes/config.php
//  Satu-satunya file yang perlu di-require oleh semua halaman.
//  Tanggung jawab file ini:
//    1. Timezone
//    2. Koneksi database (PDO, singleton)
//    3. Session helper
//    4. Fungsi helper global (format, generate, flash)
// ============================================================

// ── 1. Timezone ──────────────────────────────────────────────
date_default_timezone_set('Asia/Jakarta');

// ── 2. Konfigurasi Database ──────────────────────────────────
define('DB_HOST',  'localhost');
define('DB_USER',  'root');
define('DB_PASS',  '');           // Kosong = default XAMPP
define('DB_NAME',  'db-kasir-laundry');

// ── 3. Konstanta Aplikasi ─────────────────────────────────────
define('APP_NAME',      'Permana Laundry');
define('SESSION_NAME',  'pl_admin_session');

/*
 * BASE_URL: URL absolut root aplikasi.
 * Digunakan untuk redirect atau generate link aset.
 * Sesuaikan jika nama folder berbeda dari 'permana-laundry'.
 *
 * Contoh hasil:  http://localhost/permana-laundry
 */
define('BASE_URL',
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . '/permana-laundry'
);

// ── 4. Koneksi PDO (Singleton) ────────────────────────────────
/**
 * Mengembalikan instance PDO yang sama di setiap pemanggilan.
 * Gagal → tampilkan pesan ramah dan hentikan eksekusi.
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                DB_HOST, DB_NAME
            );
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            $msg = htmlspecialchars($e->getMessage());
            die(<<<HTML
                <div style="font-family:monospace;padding:20px;background:#fee;color:#c00;max-width:600px;margin:40px auto;border-radius:8px">
                    <strong>Koneksi Database Gagal!</strong><br><br>
                    {$msg}<br><br>
                    Pastikan MySQL XAMPP sudah berjalan dan database
                    <em>db-kasir-laundry</em> sudah dibuat via <code>database.sql</code>.
                </div>
            HTML);
        }
    }

    return $pdo;
}

// ── 5. Session Helper ─────────────────────────────────────────
/**
 * Memulai sesi dengan nama khusus (jika belum aktif).
 */
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

/**
 * Cek apakah admin sudah login.
 */
function isLoggedIn(): bool
{
    startSession();
    return !empty($_SESSION['admin_id']);
}

/**
 * Redirect ke login jika belum login.
 * Dipanggil di awal setiap halaman admin yang perlu proteksi.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// ── 6. Format Helper ─────────────────────────────────────────
/**
 * Format angka ke format Rupiah.
 * Contoh: 7000 → "Rp 7.000"
 */
function rupiah(int|float $angka): string
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Format datetime ke format Indonesia dengan jam.
 * Contoh: "2024-07-15 14:30:00" → "15 Jul 2024 14:30"
 */
function tglIndo(string $datetime): string
{
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                  'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime($datetime);
    return date('d', $ts) . ' ' . $bulan[(int)date('m', $ts)] . ' ' . date('Y H:i', $ts);
}

/**
 * Format date ke format Indonesia tanpa jam.
 * Contoh: "2024-07-15" → "15 Juli 2024"
 */
function tglIndoDate(string $date): string
{
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($date);
    return date('d', $ts) . ' ' . $bulan[(int)date('m', $ts)] . ' ' . date('Y', $ts);
}

// ── 7. Generate Nomor Nota ────────────────────────────────────
/**
 * Generate nomor nota unik per hari.
 * Format: PL-YYYYMMDD-001
 */
function generateNoNota(PDO $db): string
{
    $hari = date('Ymd');
    $stmt = $db->prepare('SELECT COUNT(*) FROM transaksi WHERE DATE(tanggal_masuk) = CURDATE()');
    $stmt->execute();
    $count = (int)$stmt->fetchColumn();
    return 'PL-' . $hari . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

// ── 8. Flash Message ─────────────────────────────────────────
/**
 * Simpan pesan flash ke session.
 * @param string $type  'success' | 'error' | 'info'
 * @param string $msg   Teks pesan
 */
function setFlash(string $type, string $msg): void
{
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

/**
 * Ambil & hapus pesan flash dari session.
 * @return array|null  ['type' => ..., 'msg' => ...] atau null
 */
function getFlash(): ?array
{
    startSession();
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
