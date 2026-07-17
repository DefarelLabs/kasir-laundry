<?php
// setup.php — Jalankan SEKALI untuk inisialisasi admin
// URL: http://localhost/permana-laundry/setup.php
// HAPUS file ini setelah selesai setup!

require_once 'includes/config.php';

$db = getDB();

// Buat hash password yang benar
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $db->prepare("UPDATE admin SET password = ? WHERE username = 'admin'");
$stmt->execute([$hash]);

echo '<div style="font-family:monospace;padding:30px;max-width:500px;margin:40px auto;background:#e8f5e9;border-radius:12px;border:2px solid #4caf50;">';
echo '<h2 style="color:#2e7d32">✅ Setup Selesai!</h2>';
echo '<p style="margin-top:12px">Password admin berhasil diatur.</p>';
echo '<table style="margin-top:16px;border-collapse:collapse;width:100%">';
echo '<tr><td style="padding:6px;font-weight:bold">Username</td><td>admin</td></tr>';
echo '<tr><td style="padding:6px;font-weight:bold">Password</td><td>' . htmlspecialchars($password) . '</td></tr>';
echo '</table>';
echo '<p style="margin-top:20px;color:#c62828;font-weight:bold">⚠️ Segera hapus file setup.php ini setelah selesai!</p>';
echo '<p style="margin-top:12px"><a href="admin/login.php" style="background:#1565c0;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:bold">→ Pergi ke Halaman Login</a></p>';
echo '</div>';
