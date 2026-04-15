# Inventaris SMKN 1 Sumenep

Sistem Manajemen Inventaris ini dikembangkan menggunakan Laravel untuk membantu sekolah dalam mengelola data barang, peminjaman, stok opname, dan laporan inventaris secara efisien.

## 📌 Fitur Utama

  - **Manajemen Barang**: Tambah, edit, dan hapus data barang di berbagai ruangan.
  - **QR Code**: Setiap barang memiliki QR Code unik untuk pemindaian cepat (menggunakan *Simple QrCode*).
  - **Peminjaman Barang**: Kelola peminjaman dan perpanjangan durasi peminjaman barang.
  - **Stok Opname**: Memeriksa ketersediaan dan kondisi barang secara berkala.
  - **Ekspor Laporan**: Mendukung pencetakan dokumen dalam format PDF dan Excel.
  - **Pengaturan Fleksibel**: Admin dapat mengubah aturan peminjaman dan pengembalian langsung dari UI tanpa mengedit kode.
  - **Log Aktivitas**: Merekam setiap aksi pengguna untuk keamanan dan audit.
  - **Hak Akses Role-Based**: Hanya admin yang dapat menambahkan dan mengelola pengguna.

-----

## ⚙️ Prasyarat Sistem

Sebelum menginstal aplikasi ini, pastikan komputer Anda telah memenuhi persyaratan versi berikut:

  - **PHP**: Versi **8.2** atau lebih baru.
  - **Composer**: Terinstal untuk mengelola pustaka PHP.
  - **MySQL / MariaDB**: Server basis data lokal (Bisa menggunakan XAMPP, Laragon, atau instalasi Native).

*(Catatan: Aplikasi ini berjalan penuh pada sisi server menggunakan Blade & Livewire, sehingga tidak mewajibkan instalasi Node.js/NPM).*

-----

## 📖 Cara Menjalankan Proyek (Panduan Pemula)

Ikuti langkah-langkah berikut secara berurutan. Panduan ini dirancang agar mudah diikuti oleh siapa saja.

### Langkah 1: Unduh Proyek

Buka terminal atau Command Prompt, lalu jalankan dua perintah berikut untuk mengunduh kode dan masuk ke folder proyek:
```bash
git clone https://github.com/albinurrosif/inventaris-smkn-1-sumenep.git
cd inventaris-smkn-1-sumenep
```

### Langkah 2: Install Dependency

Unduh semua pustaka backend (termasuk Laravel, Livewire, dll) dengan menjalankan perintah:
```bash
composer install
```

### Langkah 3: Siapkan File Konfigurasi Lingkungan (.env)

Aplikasi membutuhkan file konfigurasi untuk menghubungkan database dan mengatur kunci keamanan.

1.  Salin template konfigurasi bawaan:
```bash
    cp .env.example .env
```

2.  Buat kunci keamanan aplikasi:
```bash
    php artisan key:generate
```

### Langkah 4: Buat Database di MySQL

Aplikasi membutuhkan wadah database kosong sebelum diisi data.

1.  Pastikan service MySQL sudah berjalan.
2.  Buka database manager pilihan Anda (contoh: akses **http://localhost/phpmyadmin** di browser, gunakan DBeaver, atau lewat terminal MySQL).
3.  Buat database baru dan beri nama: **inventaris\_smk**

### Langkah 5: Sambungkan Aplikasi ke Database

Buka file **.env** menggunakan teks editor (seperti Notepad atau VS Code). Cari bagian konfigurasi database dan sesuaikan dengan pengaturan MySQL di komputer Anda:
```bash
DB\_CONNECTION=mysql
DB\_HOST=127.0.0.1
DB\_PORT=3306
DB\_DATABASE=inventaris\_smk
DB\_USERNAME=root         \<-- Sesuaikan dengan username Anda (default XAMPP adalah root)
DB\_PASSWORD=             \<-- Isi password MySQL Anda (default XAMPP dibiarkan kosong)
```

### Langkah 6: Jalankan Migrasi Data

Buat seluruh tabel yang dibutuhkan beserta data awal (seperti akun admin) menggunakan perintah:
```bash
php artisan migrate --seed
```

### Langkah 7: Jalankan Aplikasi

Nyalakan server pengembangan lokal:
```bash
php artisan serve
```

Buka browser Anda dan akses alamat: **[http://127.0.0.1:8000](https://www.google.com/search?q=http://127.0.0.1:8000)**

-----

## 🔑 Akun Default (Login)

Proyek ini sudah dilengkapi dengan *Database Seeder* untuk memudahkan tahap pengujian. Anda tidak perlu melakukan registrasi. Silakan login menggunakan salah satu akun demo berikut:

| Role Akses | Email Login | Password | Username (Opsional) |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@smkn1sumenep.sch.id` | `password` | `admin` |
| **Operator 1** | `operator@smkn1sumenep.sch.id` | `password` | `operator1` |
| **Operator 2** | `operator2@smkn1sumenep.sch.id` | `password` | `operator2` |
| **Guru 1** | `guru@smkn1sumenep.sch.id` | `password` | `guru1` |
| **Guru 2** | `guru2@smkn1sumenep.sch.id` | `password` | `guru2` |
