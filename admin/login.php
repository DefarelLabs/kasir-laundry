<?php
// admin/login.php
require_once '../includes/config.php';
startSession();

// Kalau sudah login, langsung ke dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, username, password, nama FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        // Verifikasi password menggunakan password_verify() (bcrypt)
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_nama'] = $admin['nama'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Username dan password wajib diisi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Login Admin — Permana Laundry</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Plus Jakarta Sans',sans-serif;background:linear-gradient(135deg,#0f2a4a 0%,#1565c0 60%,#00897b 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
    .login-box{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);padding:40px 36px;width:100%;max-width:380px}
    .login-logo{text-align:center;margin-bottom:28px}
    .login-logo .icon{font-size:48px;margin-bottom:10px}
    .login-logo h1{font-size:22px;font-weight:800;color:#0f2a4a}
    .login-logo p{font-size:13px;color:#64748b;margin-top:4px}
    .form-group{margin-bottom:16px}
    label{display:block;font-size:13px;font-weight:600;color:#475569;margin-bottom:6px}
    input{width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:15px;outline:none;transition:border-color .18s}
    input:focus{border-color:#1565c0;box-shadow:0 0 0 3px rgba(21,101,192,.12)}
    .btn-login{width:100%;padding:13px;background:#1565c0;color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:16px;font-weight:700;cursor:pointer;margin-top:8px;transition:background .15s}
    .btn-login:hover{background:#1253a3}
    .error-msg{background:#ffebee;color:#c62828;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;border-left:4px solid #c62828}
    .back-link{text-align:center;margin-top:18px;font-size:13px;color:#64748b}
    .back-link a{color:#1565c0;font-weight:600;text-decoration:none}

    /* Memosisikan ikon mata di sebelah kanan */
.eye-icon {
    position: absolute;
    cursor: pointer;
    font-size: 18px;
    user-select: none; /* Mencegah ikon terblok biru saat diklik 2x */
    opacity: 0.7;
    transition: opacity 0.2s;
}

.eye-icon:hover {
    opacity: 1; /* Efek menyala saat di-hover */
}

.password-wrapper {
    position:relative;
    display:flex;
    align-items:center;
    justify-content:flex-end;
}

  </style>
</head>
<body>
  <div class="login-box">
    <div class="login-logo">
      <div class="icon">🫧</div>
      <h1>Permana Laundry</h1>
      <p>Masuk ke Panel Admin</p>
    </div>

    <?php if ($error): ?>
      <div class="error-msg">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               placeholder="admin" autocomplete="username"/>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <div class="password-wrapper">
          <input type="password" id="password" name="password"
                  placeholder="••••••••" autocomplete="current-password"/>
          <span id="toggle-password" class="eye-icon" title="Tampilkan Password" style="right: 10px;">👁️</span>
        </div>
      </div>
      <button type="submit" class="btn-login">🔐 Masuk</button>
    </form>

  </div>
  <script>
        // Tangkap elemen ikon dan input-nya
    const togglePassword = document.getElementById('toggle-password');
    const password = document.getElementById('password');

    // Tambahkan event saat ikon diklik
    togglePassword.addEventListener('click', function () {
        
        // Cek apakah tipenya saat ini adalah 'password'
        if (password.type === 'password') {
            // Jika ya, ubah jadi teks agar terlihat
            password.type = 'text';
            // Ganti ikon menjadi mata tertutup (opsional)
            togglePassword.textContent = '👁️‍🗨️'; 
        } else {
            // Jika tidak, kembalikan menjadi password (titik-titik)
            password.type = 'password';
            // Kembalikan ikon ke mata terbuka
            togglePassword.textContent = '👁️'; 
        }
    });
  </script>
</body>
</html>
