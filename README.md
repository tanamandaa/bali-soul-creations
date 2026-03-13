# Bali Soul Creations - Handmade Balinese Craft
**Developed by: Ni Kadek Intan Amanda Putri**
**NIM: 230030308**

Bali Soul Creations adalah aplikasi web e-commerce berbasis PHP yang dirancang untuk mengelola katalog produk kerajinan tangan khas Bali. Proyek ini dibangun untuk memenuhi kriteria sertifikasi kompetensi Junior Web Developer.

## 🌟 Fitur Utama

### Sisi Pelanggan (Customer)
- **Katalog Dinamis**: Menampilkan produk berdasarkan kategori dan sub-kategori.
- **Sistem Keranjang**: Kelola item belanja (tambah, kurangi qty, hapus).
- **Checkout & Pesanan**: Proses pemesanan terintegrasi dengan upload bukti pembayaran.
- **Riwayat Pesanan**: Lacak status pesanan (Awaiting Verification, Shipped, dll).

### Sisi Administrator (Admin)
- **Dashboard**: Ringkasan total pendapatan, pesanan, dan jumlah produk.
- **Manajemen Produk (CRUD)**: Tambah, edit, lihat, dan hapus produk beserta upload foto.
- **Manajemen Kategori**: Kelola struktur kategori pohon (Parent & Sub-category).
- **Manajemen Pesanan**: Verifikasi bukti pembayaran dan update status pengiriman.

## 🛠️ Teknologi yang Digunakan
- **Bahasa Pemrograman**: PHP 8.x (Native dengan OOP & Namespace)
- **Database**: MySQL (PDO Connection)
- **Desain UI**: Tailwind CSS (via CDN) & Google Fonts
- **Standard**: PSR (Coding Guidelines) & PHPDoc (Documentation)

## 📂 Struktur Proyek
- `/app` : Berisi logika inti (Config, Controllers, Models).
- `/uploads` : Tempat penyimpanan file fisik (Produk & Bukti Bayar).
- `index.php` : Antarmuka utama untuk Customer.
- `admin.php` : Antarmuka Panel Administrator.
- `balisoulcreations.sql` : File export database.

## 🚀 Cara Instalasi

1. **Persiapan Database**:
   - Buka **phpMyAdmin**.
   - Buat database baru dengan nama `balisoulcreations`.
   - Import file `balisoulcreations.sql` yang tersedia di root folder.

2. **Konfigurasi Server**:
   - Pastikan folder proyek berada di dalam directory server (misal: `C:/xampp/htdocs/balisoulcreations`).
   - Sesuaikan kredensial database pada file `app/Config/Database.php` atau di bagian awal `index.php` dan `admin.php` jika diperlukan (default: localhost, root, no password).

3. **Akses Aplikasi**:
   - Akses Customer: `http://localhost/balisoulcreations/index.php`
   - Akses Admin: `http://localhost/balisoulcreations/admin.php`

## 🔑 Akun Akses (Testing)
- **Admin**:
  - Username: `admin`
  - Password: `admin123`
- **Customer**:
  - Silakan gunakan fitur **Register** pada halaman utama untuk membuat akun baru.

## 📝 Dokumentasi Kode
Seluruh fungsi dan method dalam sistem ini telah didokumentasikan menggunakan standar **PHPDoc** untuk memudahkan pengembangan dan pemeliharaan kode.