# Panduan Testing Flow Point of Sales dengan Swagger

Dokumen ini berisi panduan langkah demi langkah untuk melakukan pengujian (testing) aplikasi Point of Sales menggunakan antarmuka Swagger UI.

## 📋 Persiapan Awal
1. Pastikan server berjalan: `php artisan serve`
2. Buka Swagger UI di browser: `http://localhost:8000/api/documentation`
3. Pastikan database memiliki data awal (User Admin, User Kasir, dan beberapa Produk).

---

## 🛒 1. Flow Kasir (Cashier Lifecycle)
**Skenario:** Kasir masuk ke sistem, melayani pelanggan dengan menambahkan barang ke keranjang, melakukan checkout, dan mencetak struk.

### Langkah 1: Login Kasir
*   **Endpoint:** `POST /api/login`
*   **Input (Body):**
    ```json
    {
      "email": "cashier@example.com",
      "password": "password"
    }
    ```
*   **Action:** Klik `Execute`.
*   **Hasil:** Salin (Copy) kode `token` dari response body.

### Langkah 2: Authorize (Otentikasi)
*   Klik tombol **Authorize** (ikon gembok) di bagian atas halaman Swagger.
*   Masukkan value: `Bearer <paste_token_disini>`
*   Klik **Authorize** lalu **Close**.

### Langkah 3: Lihat Katalog Produk
*   **Endpoint:** `GET /api/products`
*   **Action:** Klik `Execute`.
*   **Tujuan:** Melihat daftar produk dan stok yang tersedia. Catat `id` produk yang akan dibeli.

### Langkah 4: Tambah ke Keranjang (Add to Cart)
*   **Endpoint:** `POST /api/cart`
*   **Input (Body):**
    ```json
    {
      "product_id": 1,
      "quantity": 2
    }
    ```
*   **Action:** Klik `Execute`. Ulangi langkah ini untuk produk lain jika perlu.

### Langkah 5: Cek Keranjang Belanja
*   **Endpoint:** `GET /api/cart`
*   **Action:** Klik `Execute`.
*   **Hasil:** Memastikan semua item sudah masuk dan subtotal benar.

### Langkah 6: Checkout (Pembayaran)
*   **Endpoint:** `POST /api/checkout`
*   **Input (Body):**
    ```json
    {
      "payment_amount": 100000
    }
    ```
    *(Pastikan `payment_amount` >= `total_amount` di keranjang)*.
*   **Action:** Klik `Execute`.
*   **Hasil:** Transaksi berhasil. **Catat/Ingat** `transaction_id` dan `transaction_code` dari response.

### Langkah 7: Lihat & Unduh Struk
*   **Lihat Data Struk:**
    *   **Endpoint:** `GET /api/transactions/{id}/receipt`
    *   **Input:** Masukkan `transaction_id` (misal: 1).
*   **Unduh PDF:**
    *   **Endpoint:** `GET /api/transactions/{id}/receipt/pdf`
    *   **Input:** Masukkan `transaction_id`.
    *   **Hasil:** File PDF struk akan terunduh.

---

## 👔 2. Flow Admin (Management & Reporting)
**Skenario:** Admin masuk ke sistem untuk mengelola data produk dan memantau hasil penjualan hari ini.

### Langkah 1: Login Admin
*   **Endpoint:** `POST /api/login`
*   **Input (Body):**
    ```json
    {
      "email": "admin@example.com",
      "password": "password"
    }
    ```
*   **Action:** Klik `Execute`. -> **Salin token baru**.

### Langkah 2: Re-Authorize
*   Jika sebelumnya login sebagai Kasir, silakan refresh halaman atau logout.
*   Klik **Authorize**, dan masukkan token milik Admin: `Bearer <token_admin>`.

### Langkah 3: Tambah Produk Baru
*   **Endpoint:** `POST /api/admin/products`
*   **Input (Body):**
    ```json
    {
      "product_name": "Menu Spesial Baru",
      "price": 25000,
      "stock": 50,
      "image": "(biarkan kosong atau hapus field ini jika testing text-only)"
    }
    ```
*   **Action:** Klik `Execute`.

### Langkah 4: Monitor Transaksi
*   **Endpoint:** `GET /api/admin/transactions`
*   **Action:** Klik `Execute`.
*   **Tujuan:** Melihat daftar semua transaksi yang terjadi (termasuk yang baru saja dilakukan Kasir).

### Langkah 5: Analisa Laporan (Reports)
*   **Ringkasan Penjualan:**
    *   **Endpoint:** `GET /api/admin/reports/summary`
    *   **Input:** `period` = `day` (untuk lihat hari ini).
*   **Produk Terlaris:**
    *   **Endpoint:** `GET /api/admin/reports/top-products`
    *   **Input:** `period` = `month`.
    *   **Hasil:** Melihat produk apa yang paling laku.
