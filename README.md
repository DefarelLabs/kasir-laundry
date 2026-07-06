# 🫧 Permana Laundry — Sistem Kasir Web

> Aplikasi manajemen kasir berbasis **PHP Native + MySQL** untuk usaha laundry skala kecil-menengah. Dibangun tanpa framework — ringan, mudah dikustomisasi, dan siap dijalankan di XAMPP.

---

## ✨ Fitur Utama

### 🧾 Kasir (Transaksi Baru)
- Input nama pelanggan, jumlah cucian, dan jenis layanan
- **Dua tipe hitungan layanan**: ⚖️ **Kilo** (berat desimal, cth: 3.5 kg) dan 🔢 **Satuan** (jumlah bulat, cth: 5 pcs) — kolom input otomatis menyesuaikan (step & validasi) sesuai tipe layanan yang dipilih
- Kalkulasi harga **real-time** (Jumlah × Harga/Kilo atau Harga/Satuan)
- Nomor nota otomatis: format `PL-YYYYMMDD-001`
- Tanggal selesai dihitung otomatis dari durasi layanan
- Cetak nota **1 lembar** (pelanggan) atau **2 lembar** (pelanggan + arsip), label nota otomatis menampilkan "kg" atau "pcs" sesuai tipe layanan

### 📊 Dashboard Admin
- Filter transaksi per tanggal — bisa mundur ke hari sebelumnya
- Statistik harian: jumlah order, pendapatan, **Total Berat (kg)** dan **Total Satuan (pcs) dipisah** (tidak lagi digabung jadi satu angka)
- Ringkasan status: Pending / Selesai / Diambil
- Kolom "Berat/Pcs" pada tabel daftar order menampilkan satuan yang sesuai per transaksi

### 📋 Data Transaksi
- Filter per **bulan** atau **tanggal tertentu**
- Pencarian nama pelanggan / nomor nota
- Update status langsung dari tabel (dropdown inline)
- **✏️ Edit transaksi** — ubah nama pelanggan, layanan, jumlah, dan catatan langsung lewat modal popup (harga & total otomatis dihitung ulang)
- **🗑️ Hapus transaksi** — dengan dialog konfirmasi sebelum data dihapus permanen
- Statistik **Total Berat** dan **Total Satuan** ditampilkan terpisah sesuai periode filter
- Cetak ulang nota dari halaman ini

### ⚙️ Kelola Layanan
- Tambah, edit, dan nonaktifkan jenis layanan
- Atur nama, harga per kilo/satuan, **tipe hitungan (Kilo/Satuan)**, dan durasi estimasi pengerjaan
- Hapus layanan jika belum memiliki riwayat transaksi

### 💸 Pengeluaran
- Catat pengeluaran operasional (parfum, plastik, detergen, dll.)
- Filter per bulan atau per tanggal
- Ringkasan total pengeluaran per periode

### 📈 Laporan
- Preset cepat: Hari Ini, 1 Minggu, 2 Minggu, 1 Bulan, atau Custom
- **Ringkasan keuangan dua lapis:**
  - **Pendapatan Kotor** — dijumlah dari *semua* transaksi (status Pending, Selesai, maupun Diambil)
  - **Pendapatan Bersih** — hanya dihitung dari transaksi berstatus **Diambil** (dianggap sudah lunas), dikurangi Total Pengeluaran
- Rekap per hari dan per jenis layanan, dengan **Berat (kg)** dan **Satuan (pcs)** ditampilkan terpisah
- Top 5 pelanggan terbanyak order
- **Export CSV** untuk dianalisis di Excel (BOM UTF-8, siap dibuka langsung), sudah menyertakan kolom Jumlah, Satuan, dan Pendapatan (Status Diambil)
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
├── index.php                   ← Halaman kasir (input transaksi baru, kilo/satuan)
├── print_nota.php              ← Halaman cetak nota thermal (1 / 2 lembar)
├── setup.php                   ← Setup awal password admin (hapus setelah dipakai!)
├── database.sql                ← SQL untuk membuat database & data awal
│
├── assets/
│   ├── css/
│   │   └── style.css           ← Semua stylesheet global admin panel (termasuk modal)
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
    ├── dashboard.php           ← Dashboard dengan filter tanggal & statistik kg/pcs
    ├── transaksi.php           ← Data transaksi + filter + edit + hapus + update status
    ├── layanan.php             ← CRUD jenis layanan, harga, & tipe hitungan
    ├── pengeluaran.php         ← Catat & kelola pengeluaran operasional
    └── laporan.php             ← Laporan periode, Pendapatan Kotor/Bersih, export CSV
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

> `database.sql` sudah mencakup kolom `tipe_hitungan` (di tabel `layanan` & `transaksi`) serta `berat_kg` dan `berat_pcs` yang terpisah di tabel `transaksi`.

### Langkah 3 — Sesuaikan konfigurasi

Buka `includes/config.php` dan sesuaikan jika perlu:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // username MySQL XAMPP
define('DB_PASS', '');       // password MySQL (default: kosong)
define('DB_NAME', 'db_kasir_laundry');
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

## ⚖️ Tipe Hitungan Layanan: Kilo vs Satuan

Setiap layanan (di menu **Kelola Layanan**) memiliki salah satu dari dua tipe hitungan:

| Tipe       | Contoh Layanan            | Format Input     | Kolom Penyimpanan di `transaksi` |
|------------|----------------------------|------------------|-----------------------------------|
| ⚖️ Kilo    | Cuci Reguler, Express, Kilat | Desimal (cth: 3.5) | `berat_kg`                        |
| 🔢 Satuan  | Cuci Sepatu, Setrika Kemeja | Bulat (cth: 5)      | `berat_pcs`                       |

Kedua kolom (`berat_kg` dan `berat_pcs`) disimpan **terpisah secara fisik** di tabel `transaksi` — transaksi Kilo akan mengisi `berat_pcs = 0`, dan sebaliknya transaksi Satuan akan mengisi `berat_kg = 0`. Dengan begitu, semua penjumlahan statistik (Dashboard, Data Transaksi, Laporan) tidak akan pernah tercampur antara kg dan pcs.

---

## 💵 Logika Perhitungan Keuangan (Laporan)

| Metrik                | Cakupan Status Transaksi                          | Keterangan                                      |
|------------------------|----------------------------------------------------|--------------------------------------------------|
| **Pendapatan Kotor**   | Pending + Selesai + Diambil (semua transaksi)      | Menggambarkan seluruh order yang masuk pada periode tersebut, terlepas dari status pembayaran |
| **Pendapatan Bersih**  | Hanya **Diambil**                                  | Diasumsikan hanya transaksi berstatus Diambil yang sudah benar-benar lunas |
| **Laba Bersih**        | Pendapatan Bersih − Total Pengeluaran              | Angka laba riil yang bisa dijadikan acuan operasional |

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

| Tabel / View          | Fungsi                                                                                   |
|------------------------|-------------------------------------------------------------------------------------------|
| `admin`                | Akun admin dengan password bcrypt                                                         |
| `layanan`               | Jenis layanan: kode, nama, harga, **tipe_hitungan** (kilo/satuan), durasi, status aktif  |
| `transaksi`             | Setiap baris = 1 order pelanggan, dengan **berat_kg**, **berat_pcs**, dan **tipe_hitungan** (snapshot dari layanan saat transaksi dibuat) |
| `pengeluaran`           | Catatan pengeluaran operasional harian                                                    |
| `v_transaksi_lengkap`   | View JOIN transaksi + layanan, membawa `layanan_id`, `tipe_hitungan`, `berat_kg`, `berat_pcs` untuk query lebih mudah |

### Kolom penting di tabel `layanan`
```sql
tipe_hitungan ENUM('kilo','satuan') NOT NULL DEFAULT 'kilo'
```

### Kolom penting di tabel `transaksi`
```sql
berat_kg       DECIMAL(5,2)  NOT NULL DEFAULT 0   -- diisi jika tipe_hitungan = 'kilo'
berat_pcs      INT           NOT NULL DEFAULT 0   -- diisi jika tipe_hitungan = 'satuan'
tipe_hitungan  ENUM('kilo','satuan') NOT NULL DEFAULT 'kilo'
```

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
