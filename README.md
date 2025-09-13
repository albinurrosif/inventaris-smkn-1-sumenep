# Inventaris SMKN 1 Sumenep

Sistem Manajemen Inventaris ini dikembangkan menggunakan Laravel untuk membantu sekolah dalam mengelola data barang, peminjaman, stok opname, dan laporan inventaris secara efisien.

## 📌 Fitur Utama

-   **Manajemen Barang**: Tambah, edit, dan hapus data barang di berbagai ruangan.
-   **QR Code**: Setiap barang memiliki QR Code unik untuk pemindaian cepat.
-   **Peminjaman Barang**: Kelola peminjaman dan perpanjangan durasi peminjaman barang.
-   **Stok Opname**: Memeriksa ketersediaan dan kondisi barang secara berkala.
-   **Pelaporan Barang Rusak/Hilang**: Melaporkan barang yang rusak atau hilang berdasarkan stok opname.
-   **Pengaturan Fleksibel**: Admin dapat mengubah aturan peminjaman dan pengembalian langsung dari UI tanpa mengedit kode.
-   **Log Aktivitas**: Merekam setiap aksi pengguna untuk keamanan dan audit.
-   **Hak Akses Role-Based**: Hanya admin yang dapat menambahkan dan mengelola pengguna.

## 🛠 Teknologi yang Digunakan

-   **Laravel** – Framework PHP untuk backend.
-   **Blade** – Template engine bawaan Laravel.
-   **MySQL** – Basis data untuk menyimpan informasi inventaris.

## 📖 Cara Menjalankan Proyek

1. Clone repository:
   git clone https://github.com/albinurrosif/inventaris-smkn-1-sumenep.git
   cd inventaris-smkn-1-sumenep

2. Install dependency Laravel:
   composer install

3. Copy file konfigurasi:
   cp .env.example .env

4. Generate application key:
   php artisan key:generate

5. Atur database di .env, lalu jalankan migration:
   php artisan migrate --seed

6. Jalankan aplikasi:
   php artisan serve

