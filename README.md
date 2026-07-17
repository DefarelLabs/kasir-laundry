# 🫧 Permana Laundry — Sistem Kasir Web

> Aplikasi manajemen kasir berbasis **PHP Native + MySQL** untuk usaha laundry skala kecil-menengah. Dibangun tanpa framework — ringan, mudah dikustomisasi, dan siap dijalankan di XAMPP.

---

## ✨ Fitur Utama

### 🧾 Kasir (Transaksi Baru)
- **Halaman kasir kini dilindungi login** — hanya admin yang sudah login yang bisa membuka `index.php` (redirect otomatis ke `admin/login.php` jika belum login)
- **Keranjang multi-layanan**: 1 nota kini bisa berisi lebih dari satu jenis layanan sekaligus (misal Cuci Reguler + Cuci Sepatu dalam 1 nota), ditambahkan satu per satu ke keranjang sebelum disimpan
- Input nama pelanggan, jumlah cucian, dan jenis layanan
- **Dua tipe hitungan layanan**: ⚖️ **Kilo** (berat desimal, cth: 3.5 kg) dan 🔢 **Satuan** (jumlah bulat, cth: 5 pcs) — kolom input otomatis menyesuaikan (step & validasi) sesuai tipe layanan yang dipilih
- Kalkulasi harga **real-time** (Jumlah × Harga/Kilo atau Harga/Satuan), dijumlah otomatis dari seluruh layanan di keranjang
- Nomor nota otomatis: format `PL-YYYYMMDD-001`
- Tanggal selesai dihitung otomatis dari durasi layanan **terlama** di antara layanan yang dipilih dalam 1 nota
- **💵 Deposit / Uang Muka (DP)**: input opsional saat transaksi dibuat — kolom "Sisa Bayar" terkalkulasi otomatis secara *real-time* (Total − Deposit) begitu kasir mengetik nominal DP
- Cetak nota **1 lembar** (pelanggan) atau **2 lembar** (pelanggan + arsip), menampilkan seluruh layanan dalam nota tersebut berurutan ke bawah beserta total keseluruhan, **Deposit**, dan **Sisa Tagihan** (jika ada DP)

### 📊 Dashboard Admin
- Filter transaksi per tanggal — bisa mundur ke hari sebelumnya
- Statistik harian: jumlah order, pendapatan, **Total Berat (kg)** dan **Total Satuan (pcs) dipisah** (tidak lagi digabung jadi satu angka)
- **⚠️ Kartu "Sisa Piutang"**: total tagihan yang belum lunas pada hari yang difilter, beserta total deposit yang sudah masuk
- Ringkasan status: Pending / Selesai / Diambil
- Kolom "Layanan" dan "Berat/Pcs" pada tabel daftar order menampilkan **seluruh layanan dalam 1 nota** (bisa lebih dari satu baris per transaksi)
- Kolom **"Status Bayar"** pada tabel daftar order — badge ✅ Lunas atau nominal sisa tagihan per nota

### 📋 Data Transaksi
- Filter per **bulan** atau **tanggal tertentu**, plus filter **Status Bayar** (Lunas / Belum Lunas)
- Pencarian nama pelanggan / nomor nota
- Update status langsung dari tabel (dropdown inline)
- **✅ Status "Diambil" = otomatis lunas** — begitu status transaksi diubah menjadi **Diambil**, sisa tagihan otomatis di-nolkan (dianggap pelanggan sudah membayar penuh saat mengambil cucian); kalau status diubah kembali ke Pending/Selesai, sisa tagihan dihitung ulang dari `total_harga − deposit`
- **✏️ Edit transaksi** — ubah Nama Pelanggan, Catatan, daftar layanan/berat, **dan Deposit** langsung lewat modal popup (sisa bayar otomatis dihitung ulang saat total atau deposit berubah)
- **🗑️ Hapus transaksi** — dengan dialog konfirmasi sebelum data dihapus permanen (menghapus header otomatis menghapus seluruh detail layanannya)
- Kolom **Deposit** dan **Status Bayar** (✅ Lunas / sisa tagihan) pada tabel, plus kartu **Total Piutang** di atas tabel
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
- **Ringkasan keuangan (5 kartu):**
  - **Pendapatan Kotor** — dijumlah dari *semua* transaksi (status Pending, Selesai, maupun Diambil)
  - **Deposit Diterima** — total seluruh uang muka yang sudah masuk pada periode tersebut
  - **Piutang Belum Lunas** — total sisa tagihan dari transaksi yang belum berstatus Diambil
  - **Total Pengeluaran** — akumulasi pengeluaran operasional periode tersebut
  - **Laba Bersih** — *Uang Diterima* (transaksi **Diambil** dihitung penuh dari `total_harga` + transaksi lain dihitung dari **deposit**-nya saja, supaya tidak dobel-hitung) dikurangi Total Pengeluaran
- **🧾 Daftar Piutang Belum Lunas** — tabel rinci nota yang masih punya sisa tagihan pada periode tersebut, lengkap dengan total, deposit, dan sisa tagihan per nota
- Rekap per hari dan per jenis layanan, dengan **Berat (kg)** dan **Satuan (pcs)** ditampilkan terpisah
- Top 5 pelanggan terbanyak order
- **Export CSV** untuk dianalisis di Excel (BOM UTF-8, siap dibuka langsung) — 1 baris CSV mewakili 1 layanan (bukan 1 nota), kini menyertakan kolom **Deposit** dan **Sisa Bayar** per baris, plus baris ringkasan Deposit/Piutang; dilengkapi validasi JSON supaya export tidak gagal diam-diam akibat karakter tidak biasa di catatan/nama
- Tampilan print-friendly yang sudah disesuaikan ke ukuran kertas **A4** (font, grid, dan tabel otomatis mengecil saat dicetak/disimpan sebagai PDF agar tidak "kegedean" atau terpotong)

---

## 🛠️ Tech Stack

| Layer      | Teknologi                                                    |
|------------|--------------------------------------------------------------|
| Backend    | PHP 8+ (PDO, Prepared Statements)                            |
| Database   | MySQL 5.7+ / MariaDB (via XAMPP)                             |
| Frontend   | HTML5, CSS3 (Flexbox/Grid), Vanilla JavaScript (ES5 compat.) |
| Fonts      | [Plus Jakarta Sans](https://fonts.google.com/specimen/Plus+Jakarta+Sans), [Source Code Pro](https://fonts.google.com/specimen/Source+Code+Pro) (Google Fonts) |
| Print      | CSS `@media print` — nota kertas thermal 80mm, laporan disesuaikan ke A4 |
| Keamanan   | bcrypt password hash, PDO prepared statements, session login (juga melindungi halaman Kasir) |

---

## 📁 Struktur Folder

```
kasir-laundry/
│
├── index.php                   ← Halaman kasir (dilindungi login, keranjang multi-layanan)
├── print_nota.php              ← Halaman cetak nota thermal (1 / 2 lembar, multi-layanan per nota)
├── setup.php                   ← Setup awal password admin (hapus setelah dipakai!)
├── database.sql                ← SQL install BARU dari nol (skema Header & Detail)
│
├── assets/
│   ├── css/
│   │   └── style.css           ← Semua stylesheet global admin panel (termasuk modal)
│   ├── js/
│   │   └── script.js           ← Semua JavaScript global (sidebar, CSV export)
│   └── logo/
│       └── logo.png            ← Foto Icon Website
│
├── includes/
│   ├── config.php              ← DB connection, session, helper functions
│   ├── admin_header.php        ← Layout: sidebar + topbar (load CSS & JS eksternal)
│   └── admin_footer.php        ← Layout: penutup tag HTML
│
└── admin/
    ├── login.php               ← Halaman login admin (juga dipakai untuk login ke Kasir)
    ├── logout.php               ← Proses logout (destroy session)
    ├── dashboard.php            ← Dashboard dengan filter tanggal & statistik kg/pcs
    ├── transaksi.php            ← Data transaksi + filter + edit header + hapus + update status
    ├── layanan.php              ← CRUD jenis layanan, harga, & tipe hitungan
    ├── pengeluaran.php          ← Catat & kelola pengeluaran operasional
    └── laporan.php              ← Laporan periode, Pendapatan Kotor/Bersih, export CSV
```

---

## 🚀 Cara Instalasi

### Prasyarat
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8+)
- Browser modern (Chrome, Firefox, Edge)

### Langkah 1 — Salin folder ke XAMPP

```bash
# Salin folder proyek ke direktori htdocs XAMPP
C:\xampp\htdocs\kasir-laundry\
```

### Langkah 2 — Buat database

1. Jalankan XAMPP, aktifkan **Apache** dan **MySQL**
2. Buka `http://localhost/phpmyadmin`
3. **Instalasi baru (database masih kosong)**: klik tab **Import** → pilih file `database.sql` → klik **Go**

> `database.sql` sudah mencakup skema **Header & Detail**: tabel `transaksi` (header nota) dan `transaksi_detail` (1 baris per layanan dalam nota, termasuk `tipe_hitungan`, `jumlah`, `harga_per_unit`, `subtotal`).

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
http://localhost/kasir-laundry/setup.php
```

> ⚠️ **Penting:** Hapus `setup.php` dari server setelah langkah ini selesai!

### Langkah 5 — Akses aplikasi

| Halaman              | URL                                                   |
|----------------------|-------------------------------------------------------|
| Kasir (input order)  | `http://localhost/kasir-laundry/` *(perlu login admin)* |
| Login Admin          | `http://localhost/kasir-laundry/admin/login.php`    |
| Dashboard Admin      | `http://localhost/kasir-laundry/admin/dashboard.php`|

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

| Tipe       | Contoh Layanan            | Format Input     | Kolom Penyimpanan di `transaksi_detail` |
|------------|----------------------------|------------------|-------------------------------------------|
| ⚖️ Kilo    | Cuci Reguler, Express, Kilat | Desimal (cth: 3.5) | `jumlah` (dengan `tipe_hitungan`='kilo')  |
| 🔢 Satuan  | Cuci Sepatu, Setrika Kemeja | Bulat (cth: 5)      | `jumlah` (dengan `tipe_hitungan`='satuan') |

Setiap layanan yang dipilih dalam 1 nota tersimpan sebagai 1 baris di `transaksi_detail`, lengkap dengan `tipe_hitungan`-nya sendiri. Dengan begitu, 1 nota bisa berisi campuran layanan Kilo dan Satuan sekaligus, dan semua penjumlahan statistik (Dashboard, Data Transaksi, Laporan) tetap dipisah per tipe tanpa tercampur.

---

## 💳 Deposit / Uang Muka (DP) & Piutang

Setiap transaksi bisa diberi **deposit** (uang muka) saat dibuat di halaman Kasir, atau diubah kemudian lewat menu Edit di **Data Transaksi**. Sistem menyimpan 2 kolom di tabel `transaksi`:

- `deposit` — nominal uang muka yang sudah dibayar pelanggan
- `sisa_bayar` — otomatis dihitung sebagai `total_harga − deposit`

**Aturan status "Diambil" = otomatis lunas:**

| Perubahan Status                          | Efek pada `sisa_bayar`                              |
|--------------------------------------------|------------------------------------------------------|
| → **Diambil**                              | Otomatis di-set **0** (dianggap pelanggan sudah melunasi sisa tagihan saat mengambil cucian) |
| → Pending / Selesai (termasuk dari Diambil ke status lain) | Dihitung ulang dari `total_harga − deposit`          |

Dengan aturan ini, kasir **tidak perlu** mengubah nominal deposit secara manual agar suatu nota tercatat lunas — cukup ubah status transaksinya menjadi **Diambil**, dan **Total Piutang** di Dashboard, Data Transaksi, maupun Laporan akan otomatis berkurang.

Nota cetak (`print_nota.php`) juga menampilkan baris **"Deposit / Dibayar"** dan **"Sisa Tagihan"** (atau **"LUNAS"**) di bawah Total, tapi hanya muncul jika nota tersebut memang punya deposit.

---

## 💵 Logika Perhitungan Keuangan (Laporan)

| Metrik                     | Cakupan Status Transaksi                                                                 | Keterangan                                                                 |
|------------------------------|--------------------------------------------------------------------------------------------|------------------------------------------------------------------------------|
| **Pendapatan Kotor**         | Pending + Selesai + Diambil (semua transaksi)                                              | Menggambarkan seluruh order yang masuk pada periode tersebut, terlepas dari status pembayaran |
| **Deposit Diterima**         | Semua transaksi, dijumlah dari kolom `deposit`                                              | Total uang muka yang benar-benar sudah masuk pada periode tersebut          |
| **Piutang Belum Lunas**      | Semua transaksi **selain Diambil**, dijumlah dari kolom `sisa_bayar`                        | Sisa tagihan yang masih harus ditagih ke pelanggan                          |
| **Uang Diterima** (dasar Laba Bersih) | **Diambil** → dihitung penuh dari `total_harga`. **Selain Diambil** → dihitung hanya dari `deposit` | Mencegah deposit dihitung dobel: begitu status jadi Diambil, deposit "melebur" jadi bagian pelunasan penuh, bukan ditambahkan lagi ke total_harga |
| **Laba Bersih**              | Uang Diterima − Total Pengeluaran                                                           | Angka laba riil (uang yang sudah benar-benar di tangan) yang bisa dijadikan acuan operasional |

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
| `transaksi`             | **Header** nota: nama pelanggan, total harga (dijumlah dari semua layanan di nota), **deposit**, **sisa_bayar**, tanggal, status, catatan |
| `transaksi_detail`      | **Detail** nota: 1 baris per layanan yang dipilih dalam 1 nota (nama layanan, tipe_hitungan, jumlah, harga_per_unit, subtotal — snapshot dari layanan saat transaksi dibuat) |
| `pengeluaran`           | Catatan pengeluaran operasional harian                                                    |
| `v_transaksi_lengkap`   | View: header `transaksi` + ringkasan jumlah item & daftar nama layanan (`GROUP_CONCAT`) per nota |

### Kolom penting di tabel `layanan`
```sql
tipe_hitungan ENUM('kilo','satuan') NOT NULL DEFAULT 'kilo'
```

### Kolom penting di tabel `transaksi` (Header)
```sql
total_harga      DECIMAL(12,0) NOT NULL   -- total gabungan seluruh layanan di nota ini
deposit          DECIMAL(12,0) NOT NULL DEFAULT 0   -- uang muka / DP yang sudah dibayar
sisa_bayar       DECIMAL(12,0) NOT NULL DEFAULT 0   -- total_harga - deposit; otomatis jadi 0 saat status = 'diambil'
tanggal_selesai  DATETIME      NOT NULL   -- dihitung dari durasi layanan TERLAMA di nota
```

### Kolom penting di tabel `transaksi_detail` (Detail)
```sql
transaksi_id    INT            NOT NULL   -- FK ke transaksi.id, ON DELETE CASCADE
layanan_id      INT            NOT NULL   -- FK ke layanan.id
tipe_hitungan   ENUM('kilo','satuan') NOT NULL DEFAULT 'kilo'
jumlah          DECIMAL(8,2)   NOT NULL   -- berat (kg) atau jumlah (pcs), sesuai tipe_hitungan
subtotal        DECIMAL(12,0)  NOT NULL   -- jumlah × harga_per_unit, untuk layanan ini saja
```

> 🔁 **Migrasi dari versi lama:** jika sebelumnya tabel `transaksi` Anda masih memiliki kolom `layanan_id`, `berat_kg`, `berat_pcs`, `tipe_hitungan`, `harga_per_kg` langsung (1 transaksi = 1 layanan), gunakan `migration_multi_layanan.sql` untuk memindahkan data lama ke `transaksi_detail` sebelum kolom-kolom tersebut dihapus dari `transaksi`.

> 💳 **Migrasi untuk menambahkan fitur Deposit:** jika database Anda sudah ada datanya dan belum punya kolom `deposit`/`sisa_bayar`, jalankan query berikut di tab SQL phpMyAdmin (tidak perlu install ulang dari nol):
> ```sql
> ALTER TABLE `transaksi`
>   ADD COLUMN `deposit`    DECIMAL(12,0) NOT NULL DEFAULT 0 AFTER `total_harga`,
>   ADD COLUMN `sisa_bayar` DECIMAL(12,0) NOT NULL DEFAULT 0 AFTER `deposit`;
> ```

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