# 🫧 Permana Laundry — Sistem Kasir Web

Sistem kasir berbasis PHP + MySQL untuk XAMPP.

---

## 📁 Struktur Folder

```
permana-laundry/
├── index.php              ← Halaman kasir (input transaksi)
├── print_nota.php         ← Halaman cetak nota (1 atau 2 lembar)
├── setup.php              ← Script setup awal (hapus setelah dijalankan!)
├── database.sql           ← File SQL untuk membuat database
│
├── includes/
│   ├── config.php         ← Koneksi DB & fungsi helper
│   ├── admin_header.php   ← Layout header sidebar admin
│   └── admin_footer.php   ← Layout footer admin
│
└── admin/
    ├── login.php          ← Halaman login admin
    ├── logout.php         ← Proses logout
    ├── dashboard.php      ← Dashboard (order per tanggal)
    ├── transaksi.php      ← Semua data transaksi + filter
    ├── layanan.php        ← Kelola jenis layanan & harga
    └── laporan.php        ← Laporan bulanan & tahunan
```

---

## 🚀 Cara Instalasi

### Langkah 1 — Salin folder ke XAMPP

Salin seluruh folder `permana-laundry` ke:

```
C:\xampp\htdocs\permana-laundry\
```

### Langkah 2 — Buat database

1. Buka browser, akses: `http://localhost/phpmyadmin`
2. Klik tab **"SQL"** di bagian atas
3. Buka file `database.sql` dengan teks editor
4. **Copy semua isi** file tersebut
5. **Paste** ke kotak SQL di phpMyAdmin
6. Klik tombol **"Go"** / **"Kirim"**

Atau bisa juga:
- Klik **"Import"** di phpMyAdmin
- Pilih file `database.sql`
- Klik **"Go"**

### Langkah 3 — Setup password admin

Buka browser, akses:
```
http://localhost/permana-laundry/setup.php
```

Ini akan membuat hash bcrypt yang benar untuk password admin.

> ⚠️ **PENTING:** Hapus file `setup.php` setelah langkah ini selesai!

### Langkah 4 — Akses aplikasi

| Halaman | URL |
|---|---|
| Kasir (input transaksi) | `http://localhost/permana-laundry/` |
| Login Admin | `http://localhost/permana-laundry/admin/login.php` |
| Dashboard Admin | `http://localhost/permana-laundry/admin/dashboard.php` |

---

## 🔐 Akun Admin Default

| Field | Value |
|---|---|
| Username | `admin` |
| Password | `admin123` |

> Ganti password setelah login pertama kali melalui phpMyAdmin:
> ```sql
> UPDATE admin SET password = '[hash_baru]' WHERE username = 'admin';
> ```
> Hash bisa dibuat di: `https://bcrypt-generator.com/` (rounds: 10)

---

## 🖨️ Pengaturan Printer Thermal

Aplikasi ini dirancang untuk printer thermal **80mm** (standar).

Untuk printer **58mm**, ubah di 2 file:
- `print_nota.php` baris `width: 80mm` → `width: 58mm`
- `print_nota.php` baris `size: 80mm auto` → `size: 58mm auto`

Saat mencetak di browser:
1. Klik **"Cetak 1 Lembar"** atau **"Cetak 2 Lembar"**
2. Dialog print browser akan muncul otomatis
3. Pilih printer thermal Anda
4. Pastikan **"Paper Size"** sesuai (80mm atau 58mm)
5. **Matikan "Headers and footers"** di pengaturan print
6. **Matikan "Margins"** (set ke None)

---

## ✨ Fitur Lengkap

### Kasir (index.php)
- Input nama pelanggan, berat cucian, jenis layanan
- Kalkulasi harga **real-time** (Berat × Harga/kg)
- Simpan transaksi ke database
- Cetak nota **1 lembar** (untuk pelanggan) atau **2 lembar** (pelanggan + arsip pemilik)
- Nomor nota otomatis: format `PL-YYYYMMDD-001`
- Tanggal selesai dihitung otomatis dari durasi layanan

### Admin Dashboard
- Filter transaksi **per tanggal** (bisa lihat tanggal sebelumnya)
- Statistik: jumlah order, pendapatan, total berat
- Status order: Pending → Selesai → Diambil
- Ringkasan order per hari

### Admin Transaksi
- Filter per **bulan** dan **status**
- Pencarian nama pelanggan / nomor nota
- Update status langsung dari tabel (dropdown)
- Cetak ulang nota dari sini

### Admin Kelola Layanan
- Tambah jenis layanan baru
- Edit nama, harga, durasi layanan
- Aktifkan / nonaktifkan layanan
- Hapus layanan (jika belum ada transaksi)

### Admin Laporan
- Rekap **per bulan** dalam satu tahun
- Rekap **per jenis layanan**
- **Top 5 pelanggan** terbanyak order
- Total pendapatan, order, berat per tahun
- Tombol **cetak laporan** (CSS print-friendly)

---

## 🗄️ Struktur Database

### Tabel `admin`
Menyimpan akun admin dengan password bcrypt.

### Tabel `layanan`
Jenis-jenis layanan laundry yang bisa dikelola admin:
- kode, nama, harga_per_kg, durasi_jam, label_durasi, aktif

### Tabel `transaksi`
Setiap baris = 1 order pelanggan:
- no_nota (unik), nama_pelanggan, layanan_id, berat_kg
- harga_per_kg (snapshot saat transaksi), total_harga
- tanggal_masuk, tanggal_selesai, status, catatan

### View `v_transaksi_lengkap`
JOIN antara transaksi dan layanan untuk query lebih mudah.

---

## ⚙️ Konfigurasi Database

Edit file `includes/config.php` jika pengaturan XAMPP berbeda:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // username MySQL XAMPP
define('DB_PASS', '');       // password MySQL (default kosong)
define('DB_NAME', 'permana_laundry');
```

---

## 🛠️ Teknologi

- **Backend:** PHP 8+ (PDO untuk koneksi database)
- **Database:** MySQL 5.7+ / MariaDB (via XAMPP)
- **Frontend:** HTML5, CSS3 (Flexbox/Grid), Vanilla JavaScript
- **Font:** Plus Jakarta Sans, Source Code Pro (Google Fonts)
- **Keamanan:** Password hashing bcrypt, prepared statements PDO

---

*Dibuat untuk Permana Laundry — Sistem Kasir Sederhana*
