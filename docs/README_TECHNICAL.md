# Technical Reference: Point of Sales (POS) V2

## 1. Ringkasan Teknis Sistem
Sistem POS ini dibangun dengan arsitektur **Client-Server** web-based yang responsif.
*   **Backend**: Menggunakan framework **Laravel 12 (PHP)** sebagai REST API provider yang menangani logika bisnis, olah data, dan keamanan.
*   **Database**: **MySQL** sebagai Relational Database Management System (RDBMS) untuk penyimpanan data transaksional dan master data.
*   **Frontend**: Aplikasi web yang mengonsumsi API backend, dirancang untuk mendukung interaktivitas tinggi pada modul kasir (POS) dan pengelolaan data admin.
*   **Integrasi**: Sistem menggunakan JSON sebagai format pertukaran data standar antara client dan server.

---

## 2. Target User & Role-Based Access
Sistem menerapkan **Role-Based Access Control (RBAC)** ketat dengan dua peran internal utama dan satu akses publik:

### A. Admin (Full Access / Super User)
*   Mengelola seluruh Master Data (Produk, Kategori, User).
*   Memiliki akses penuh *Read-Only* ke seluruh data transaksi history.
*   Akses ke modul analitik dan pelaporan.
*   *Restricted*: Admin **tidak** berperan sebagai eksekutor transaksi harian (pemisahan duty).

### B. Kasir (Operational / Transactional)
*   Role khusus operasional dengan akses terbatas.
*   Hanya memiliki izin *Create* untuk Transaksi Penjualan.
*   Hanya memiliki izin *Read* terbatas (Katalog Produk & History Transaksi sendiri).
*   *Restricted*: Tidak bisa mengubah Master Data atau menghapus history transaksi.

### C. Publik / Pelanggan (Guest / Unauthenticated)
*   Akses anonim (tanpa login) untuk melihat katalog produk.
*   Fitur "Traceability" untuk melihat struk digital menggunakan **Unique Transaction Code**.

---

## 3. Breakdown Fitur Berdasarkan Role

| Fitur | Admin | Kasir | Publik |
| :--- | :---: | :---: | :---: |
| **Authentication** | Login, Logout, Profile Update | Login, Logout, Profile Update | - |
| **Product Management** | CRUD (Produk & Kategori) | Read-Only (Katalog) | Read-Only (Katalog) |
| **Inventory** | Adjust Stock (Restock/Correction) | Auto-Deduct (via Transaksi) | - |
| **Sales Transaction** | View All History | Create Transaction (POS) | - |
| **Receipt / Struk** | View & Reprint All | View & Print (Current/History) | View by Code |
| **Reporting** | Dashboard & Analytics | - | - |
| **User Management** | CRUD Users (Kasir) | - | - |

---

## 4. Alur Transaksi Secara Teknis (End-to-End)

1.  **Initialization (Pre-Transaction)**
    *   Sistem memuat data produk aktif (`stock > 0`) ke state lokal frontend/cache kasir.
    *   Validasi token autentikasi kasir aktif.

2.  **Cart Management (State Transaction)**
    *   Kasir menambahkan item ke **Cart** (Tabel: `carts`).
    *   **Backend Validation**: Setiap penambahan item memicu validasi `product_id` dan ketersediaan `current_stock`.
    *   Cart bersifat *temporary* (pre-transaction state) dan terikat pada `user_id` kasir.

3.  **Checkout & Execution**
    *   Kasir melakukan request `checkout`.
    *   **Atomic Locking**: Sistem memvalidasi ulang total harga dan stok akhir sebelum commit.
    *   **Data Migration**: Data dipindahkan dari `carts` ke `transaction_details`.
    *   **Header Creation**: Record baru dibuat di tabel `transactions` dengan status `completed`.
    *   **Unique Identifier**: Sistem men-generate `transaction_code` unik (Format: `TRX-{RANDOM}`).

4.  **Inventory Deduction (Post-Processing)**
    *   Trigger sistem otomatis mengurangi stok di tabel `products` berdasarkan quantity final di `transaction_details`.
    *   Data `carts` milik user terkait dibersihkan (Soft/Hard delete).

---

## 5. Manajemen Data & Konsistensi Stok

*   **Single Source of Truth**: Tabel `products` adalah satu-satunya referensi stok yang valid.
*   **Race Condition Handling**: Validasi stok dilakukan dua kali (saat *add to cart* dan saat final *checkout*) untuk mencegah *overselling* saat ada transaksi simultan.
*   **Immutable History**: Data harga produk di `transaction_details` (`price_at_transaction`) disimpan sebagai snapshot statis. Perubahan harga master data di masa depan **tidak akan mengubah** nilai historis laporan penjualan.
*   **Soft Deletes**: Data `users` dan `transactions` tidak pernah dihapus secara fisik (hard delete) untuk menjaga integritas relasi data dan keperluan audit.

---

## 6. Mekanisme Autentikasi & Otorisasi
*   **Token-Based Auth**: Menggunakan **Laravel Sanctum** (atau mekanisme sejenis) untuk manajemen sesi stateless via API Token.
*   **Middleware Guard**:
    *   Endpoint Admin dilindungi middleware `auth:sanctum` + `role:admin`.
    *   Endpoint Kasir dilindungi middleware `auth:sanctum` + `role:cashier`.
    *   Public Endpoint (Katalog/Struk) terbuka tanpa middleware auth.

---

## 7. Output Sistem

1.  **Receipt (Struk)**
    *   Digital View (HTML/JSON) untuk render cepat di frontend.
    *   PDF Generation untuk dokumen formal/downloadable.
    *   Mencakup: Detail Toko, Detail Produk, Total, Bayar, Kembalian, dan Kode Transaksi.

2.  **Laporan Penjualan**
    *   Agregasi data penjualan per periode (Harian, Mingguan, Bulanan).
    *   Kalkulasi total revenue dan volume produk terjual.

---

## 8. Asumsi Teknis & Batasan Sistem
*   **Offline capability**: TIDAK DIDUKUNG. Koneksi internet wajib stabil untuk validasi stok real-time.
*   **Payment Gateway**: Belum terintegrasi. Pembayaran diasumsikan Tunai (*Cash*) atau manual transfer (dicatat manual sebagai 'completed').
*   **Concurrency**: Sistem dioptimalkan untuk UMKM dengan volume transaksi moderat.
*   **Stock Reservation**: Tidak ada fitur "Booked Stock". Stok hanya berkurang saat status transaksi `completed`.

---

> **CATATAN PENTING UNTUK DEVELOPER:**
>
> Dokumen detail tambahan tersedia sebagai referensi validasi dan implementasi:
> 1.  **Software Requirements Specification (SRS)**: Berisi use case detail, diagram UML, dan wireframe.
> 2.  **Data Definition Language (DDL) Notes**: Berisi struktur tabel final, relasi database, dan aturan integritas data.
>
> Harap merujuk pada dokumen tersebut untuk detail spesifikasi field database dan aturan bisnis granular.
