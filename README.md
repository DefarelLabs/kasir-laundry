# 🫧 Permana Laundry — Sistem Kasir Web

> Aplikasi manajemen kasir berbasis **PHP Native + MySQL** untuk usaha laundry skala kecil-menengah. Dibangun tanpa framework — ringan, mudah dikustomisasi, dan siap dijalankan di XAMPP.

---

## ✨ Fitur Utama

### 🧾 Kasir (Transaksi Baru)
- Input nama pelanggan, berat cucian, dan jenis layanan
- Kalkulasi harga **real-time** (Berat × Harga/kg)
- Nomor nota otomatis: format `PL-YYYYMMDD-001`
- Tanggal selesai dihitung otomatis dari durasi layanan
- Cetak nota **1 lembar** (pelanggan) atau **2 lembar** (pelanggan + arsip)

### 📊 Dashboard Admin
- Filter transaksi per tanggal — bisa mundur ke hari sebelumnya
- Statistik harian: jumlah order, pendapatan, total berat
- Ringkasan status: Pending / Selesai / Diambil

### 📋 Data Transaksi
- Filter per **bulan** atau **tanggal tertentu**
- Pencarian nama pelanggan / nomor nota
- Update status langsung dari tabel (dropdown inline)
- Cetak ulang nota dari halaman ini

### ⚙️ Kelola Layanan
- Tambah, edit, dan nonaktifkan jenis layanan
- Atur nama, harga/kg, dan durasi estimasi pengerjaan
- Hapus layanan jika belum memiliki riwayat transaksi

### 💸 Pengeluaran
- Catat pengeluaran operasional (parfum, plastik, detergen, dll.)
- Filter per bulan atau per tanggal
- Ringkasan total pengeluaran per periode

### 📈 Laporan
- Preset cepat: Hari Ini, 1 Minggu, 2 Minggu, 1 Bulan, atau Custom
- Ringkasan keuangan: Pendapatan Kotor — Total Pengeluaran — **Laba Bersih**
- Rekap per hari dan per jenis layanan
- Top 5 pelanggan terbanyak order
- **Export CSV** untuk dianalisis di Excel (BOM UTF-8, siap dibuka langsung)
- Tampilan print-friendly

---

## 🛠️ Tech Stack

| Layer      | Teknologi                                                    |
|------------|--------------------------------------------------------------|
| Backend    | PHP 8+ (PDO, Prepared Statements)                            |
| Database   | MySQL 5.7+ / MariaDB (via XAMPP)                             |
| Frontend   | HTML5, CSS3 (Flexbox/Grid), Vanilla JavaScript (ES5 compat.) |
| Fonts      | [Plus Jakarta Sans](https://fonts.google.com/specimen/Plus+Jakarta+Sans), [Source Code Pro](https://fonts.google.com/specimen/Source+Code+Pro) (Google Fonts) |
| Print      | CSS `@media print`, kertas thermal 80mm                      |
| Keamanan   | bcrypt password hash, PDO prepared statements                |

---

## 📁 Struktur Folder

```
permana-laundry/
│
├── index.php                   ← Halaman kasir (input transaksi baru)
├── print_nota.php              ← Halaman cetak nota thermal (1 / 2 lembar)
├── setup.php                   ← Setup awal password admin (hapus setelah dipakai!)
├── database.sql                ← SQL untuk membuat database & data awal
│
├── assets/
│   ├── css/
│   │   └── style.css           ← Semua stylesheet global admin panel
│   └── js/
│       └── script.js           ← Semua JavaScript global (sidebar, CSV export)
│
├── includes/
│   ├── config.php              ← DB connection, session, helper functions
│   ├── admin_header.php        ← Layout: sidebar + topbar (load CSS & JS eksternal)
│   └── admin_footer.php        ← Layout: penutup tag HTML
│
└── admin/
    ├── login.php               ← Halaman login admin
    ├── logout.php              ← Proses logout (destroy session)
    ├── dashboard.php           ← Dashboard dengan filter tanggal
    ├── transaksi.php           ← Semua data transaksi + filter + update status
    ├── layanan.php             ← CRUD jenis layanan & harga
    ├── pengeluaran.php         ← Catat & kelola pengeluaran operasional
    └── laporan.php             ← Laporan periode + export CSV
```

---

## 🚀 Cara Instalasi

### Prasyarat
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8+)
- Browser modern (Chrome, Firefox, Edge)

### Langkah 1 — Salin folder ke XAMPP

```bash
# Salin folder proyek ke direktori htdocs XAMPP
C:\xampp\htdocs\permana-laundry\
```

### Langkah 2 — Buat database

1. Jalankan XAMPP, aktifkan **Apache** dan **MySQL**
2. Buka `http://localhost/phpmyadmin`
3. Klik tab **Import** → pilih file `database.sql` → klik **Go**

Atau via tab **SQL**, paste seluruh isi `database.sql` lalu klik **Go**.

### Langkah 3 — Sesuaikan konfigurasi

Buka `includes/config.php` dan sesuaikan jika perlu:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // username MySQL XAMPP
define('DB_PASS', '');       // password MySQL (default: kosong)
define('DB_NAME', 'db-kasir-laundry');
```

### Langkah 4 — Setup password admin

```
http://localhost/permana-laundry/setup.php
```

> ⚠️ **Penting:** Hapus `setup.php` dari server setelah langkah ini selesai!

### Langkah 5 — Akses aplikasi

| Halaman              | URL                                                   |
|----------------------|-------------------------------------------------------|
| Kasir (input order)  | `http://localhost/permana-laundry/`                   |
| Login Admin          | `http://localhost/permana-laundry/admin/login.php`    |
| Dashboard Admin      | `http://localhost/permana-laundry/admin/dashboard.php`|

---

## 🔐 Akun Admin Default

| Field    | Value      |
|----------|------------|
| Username | `admin`    |
| Password | `admin123` |

> Ganti password setelah login pertama via phpMyAdmin:
> ```sql
> UPDATE admin SET password = '[hash_baru]' WHERE username = 'admin';
> ```
> Generate hash baru di: [bcrypt-generator.com](https://bcrypt-generator.com/) (rounds: 10)

---

## 🖨️ Pengaturan Printer Thermal

Aplikasi dirancang untuk printer thermal **80mm** (standar struk toko).

Untuk printer **58mm**, ubah 2 baris di `print_nota.php`:

```css
/* Cari dan ganti: */
width: 80mm  →  width: 58mm
size: 80mm auto  →  size: 58mm auto
```

Tips cetak via browser:
- Matikan **Headers and footers** di dialog print
- Set **Margins** ke `None`
- Pilih printer thermal yang sesuai

---

## 🗄️ Struktur Database

| Tabel / View          | Fungsi                                                           |
|-----------------------|------------------------------------------------------------------|
| `admin`               | Akun admin dengan password bcrypt                                |
| `layanan`             | Jenis layanan: kode, nama, harga/kg, durasi, status aktif        |
| `transaksi`           | Setiap baris = 1 order pelanggan                                 |
| `pengeluaran`         | Catatan pengeluaran operasional harian                           |
| `v_transaksi_lengkap` | View JOIN transaksi + layanan untuk query lebih mudah            |

---

## 🤝 Kontribusi

Pull request sangat disambut! Untuk perubahan besar, buka **Issue** terlebih dahulu untuk mendiskusikan yang ingin diubah.

1. Fork repositori ini
2. Buat branch fitur: `git checkout -b fitur/nama-fitur`
3. Commit perubahan: `git commit -m 'feat: tambah fitur X'`
4. Push ke branch: `git push origin fitur/nama-fitur`
5. Buat Pull Request

---

## 📄 Lisensi

Proyek ini menggunakan lisensi [MIT](LICENSE).

---

<p align="center">Dibuat dengan ☕ untuk <strong>Permana Laundry</strong></p>
