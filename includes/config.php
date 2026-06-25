<?php
// ============================================================
//  includes/config.php
//  Konfigurasi koneksi database & fungsi helper global
// ============================================================

// Timezone WIB - wajib agar jam PHP sesuai waktu Indonesia
date_default_timezone_set('Asia/Jakarta');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db-kasir-laundry');
define('APP_NAME', 'Permana Laundry');
define('SESSION_NAME', 'pl_admin_session');

// ── Koneksi PDO ─────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:monospace;padding:20px;background:#fee;color:#c00;">
                <strong>Koneksi Database Gagal!</strong><br>
                ' . htmlspecialchars($e->getMessage()) . '<br><br>
                Pastikan XAMPP MySQL sudah berjalan dan database <em>db-kasir-laundry</em> sudah dibuat.
                </div>');
        }
    }
    return $pdo;
}

// ── Session helper ───────────────────────────────────────────
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['admin_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// ── Format helpers ───────────────────────────────────────────
function rupiah(int|float $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function tglIndo(string $datetime): string {
    $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
    $ts    = strtotime($datetime);
    return date('d', $ts) . ' ' . $bulan[(int)date('m', $ts)] . ' ' . date('Y H:i', $ts);
}

function tglIndoDate(string $date): string {
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($date);
    return date('d', $ts) . ' ' . $bulan[(int)date('m', $ts)] . ' ' . date('Y', $ts);
}

// ── Generate nomor nota unik ──────────────────────────────────
function generateNoNota(PDO $db): string {
    $hari = date('Ymd');
    $stmt = $db->prepare("SELECT COUNT(*) FROM transaksi WHERE DATE(tanggal_masuk) = CURDATE()");
    $stmt->execute();
    $count = (int)$stmt->fetchColumn();
    return 'PL-' . $hari . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

// ── Flash message ────────────────────────────────────────────
function setFlash(string $type, string $msg): void {
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    startSession();
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
