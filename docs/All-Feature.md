

# 📘 Dokumen Fitur Aplikasi Kasir (POS)

Dokumen ini menjelaskan seluruh fitur yang tersedia pada aplikasi Kasir (Point of Sale), yang terbagi ke dalam tiga peran utama: **Admin**, **Kasir**, dan **Publik/Pelanggan**.

---

## 👨‍💼 Fitur Admin

Admin memiliki akses penuh untuk mengelola sistem, produk, serta memantau performa penjualan.

### 1. Autentikasi

* **Login Admin**
  Admin dapat masuk ke sistem menggunakan kredensial yang valid.
* **Logout**
  Mengakhiri sesi penggunaan secara aman.

### 2. Manajemen Produk

Admin bertanggung jawab atas pengelolaan seluruh katalog produk.

* **Melihat Daftar Produk**
  Menampilkan seluruh produk yang tersimpan di database.
* **Menambahkan Produk Baru**
  Input data produk seperti:

  * Nama Produk
  * SKU
  * Stok
  * Harga
  * Kategori
* **Mengubah Data Produk**
  Mengedit informasi produk yang sudah ada.
* **Menghapus Produk**
  Menghapus produk (hanya diperbolehkan jika produk belum digunakan dalam transaksi).

### 3. Laporan Penjualan

Menyediakan insight bisnis secara real-time.

* **Ringkasan Penjualan**
  Menampilkan total pendapatan dan jumlah transaksi berdasarkan periode:

  * Harian
  * Mingguan
  * Bulanan
  * Tahunan
* **Produk Terlaris**
  Menampilkan produk dengan penjualan tertinggi berdasarkan:

  * Jumlah terjual
  * Total pendapatan
* **Grafik Penjualan** (*on Going*)
  Visualisasi tren penjualan dalam bentuk chart.

### 4. Monitoring Transaksi

* **Daftar Seluruh Transaksi**
  Admin dapat melihat semua transaksi dari seluruh kasir.
* **Detail Transaksi**
  Melihat rincian item, jumlah, total, dan status transaksi.
* **Unduh Ulang Struk**
  Mengunduh atau mencetak ulang struk transaksi dalam format PDF.

---

## 🧾 Fitur Kasir

Kasir berfokus pada proses transaksi penjualan secara langsung.

### 1. Point of Sale (POS)

* **Melihat Produk**
  Menampilkan daftar produk lengkap dengan indikator ketersediaan stok.
* **Manajemen Keranjang (Cart)**:

  * Menambahkan produk ke keranjang
  * Mengubah jumlah produk di keranjang
  * Menghapus produk tertentu dari keranjang
  * Mengosongkan seluruh produk di keranjang
* **Validasi Stok Otomatis**
  Sistem otomatis mencegah penambahan jumlah produk melebihi stok yang tersedia.

### 2. Checkout & Pembayaran

* **Proses Pembayaran**
  Sistem menghitung total belanja, menerima nominal pembayaran, dan menghitung kembalian.
* **Pembuatan Transaksi**

  * Menghasilkan kode transaksi unik
  * Mengurangi stok produk secara otomatis setelah transaksi berhasil

### 3. Riwayat & Struk

* **Riwayat Transaksi Saya**
  Kasir dapat melihat daftar transaksi yang pernah ditangani.
* **Detail Transaksi**
  Menampilkan rincian item yang dijual dalam satu transaksi.
* **Struk Digital**:

  * Menampilkan struk di layar
  * Mengunduh struk dalam format PDF

---

## 🌐 Fitur Publik / Pelanggan

Fitur yang dapat diakses tanpa login atau oleh pengguna umum.

### 1. Katalog Produk

* **Lihat Daftar Produk**
  Menampilkan produk beserta harga tanpa perlu login
  (contoh penggunaan: layar Self-Order Kiosk).

### 2. Akses Struk Mandiri

Pelanggan dapat mengakses struk transaksi secara online menggunakan kode transaksi.

* **Lihat Struk**
  Menampilkan detail struk dengan memasukkan kode transaksi.
* **Unduh Struk PDF**
  Mengunduh struk secara langsung dalam format PDF.
* **Link Struk yang Bisa Dibagikan**
  Menghasilkan URL struk yang dapat dibagikan ke pihak lain.

### 3. Akun Pengguna (belum diimplementasikan)

* **Registrasi**
  Membuat akun baru.
* **Login**
  Masuk ke dashboard pengguna (jika tersedia fitur lanjutan).

---

