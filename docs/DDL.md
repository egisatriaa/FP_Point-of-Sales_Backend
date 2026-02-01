#  DDL FINAL

## POS FnB (Kasir & Self Order)

Dokumen ini menjelaskan struktur database hasil finalisasi ERD dalam bentuk **Data Definition Language (DDL) MySQL**, disertai penjelasan desain teknis dan aturan bisnis yang diterapkan.

Struktur ini telah divalidasi melalui diskusi desain, koreksi mentor, serta pertimbangan keamanan dan skalabilitas implementasi.

---

## 1. Tabel `roles`

### Fungsi

Menyimpan data role pengguna untuk mendukung mekanisme **Role-Based Access Control (RBAC)**.

### Atribut

| Kolom     | Tipe Data   | Constraint         | Keterangan                      |
| --------- | ----------- | ------------------ | ------------------------------- |
| role_id   | BIGINT      | PK, AUTO_INCREMENT | Primary key role                |
| role_name | VARCHAR(50) | NOT NULL, UNIQUE   | Nama role (admin, cashier, dll) |

### Catatan Desain

* Role dipisahkan dari tabel `users` untuk menghindari hardcoded role.
* Memudahkan pengembangan role baru di masa depan.
* Bersifat master data dan jarang berubah.

---

## 2. Tabel `users`

### Fungsi

Menyimpan data pengguna sistem (Admin & Kasir) sekaligus mendukung autentikasi, otorisasi, dan audit aktivitas.

### Atribut

| Kolom         | Tipe Data    | Constraint              | Keterangan             |
| ------------- | ------------ | ----------------------- | ---------------------- |
| user_id       | BIGINT       | PK, AUTO_INCREMENT      | Primary key user       |
| name          | VARCHAR(100) | NOT NULL                | Nama pengguna          |
| email         | VARCHAR(150) | NOT NULL, UNIQUE        | Email login            |
| password      | VARCHAR(255) | NOT NULL                | Password ter-enkripsi  |
| profile_img   | VARCHAR(255) | NULL                    | Path / URL foto profil |
| role_id       | BIGINT       | NOT NULL, FK            | Relasi ke roles        |
| is_active     | BOOLEAN      | NOT NULL, DEFAULT TRUE  | Status aktif user      |
| is_deleted    | BOOLEAN      | NOT NULL, DEFAULT FALSE | Soft delete flag       |
| last_login_at | DATETIME     | NULL                    | Waktu login terakhir   |
| created_at    | DATETIME     | NOT NULL                | Waktu pembuatan data   |
| updated_at    | DATETIME     | NOT NULL                | Waktu update terakhir  |

### Index

* role_id
* is_active
* is_deleted

### Aturan Bisnis

* User tidak pernah dihapus secara fisik (no hard delete).
* Login valid hanya jika `is_active = true` dan `is_deleted = false`.
* User yang sudah memiliki transaksi tidak boleh dihapus dari database.

---

## 3. Tabel `categories`

### Fungsi

Mengelompokkan produk FnB agar menu lebih terstruktur dan mudah difilter.

### Atribut

| Kolom         | Tipe Data    | Constraint         | Keterangan           |
| ------------- | ------------ | ------------------ | -------------------- |
| category_id   | BIGINT       | PK, AUTO_INCREMENT | Primary key kategori |
| category_name | VARCHAR(100) | NOT NULL, UNIQUE   | Nama kategori        |
| created_at    | DATETIME     | NOT NULL           | Waktu pembuatan      |
| updated_at    | DATETIME     | NOT NULL           | Waktu update         |

### Aturan Desain

* Category adalah master data.
* Tidak memiliki status aktif/nonaktif di level database.
* Category tidak boleh dihapus jika masih direferensikan oleh produk.

---

## 4. Tabel `products`

### Fungsi

Menyimpan data menu FnB yang dijual.

### Atribut

| Kolom        | Tipe Data     | Constraint          | Keterangan           |
| ------------ | ------------- | ------------------- | -------------------- |
| product_id   | BIGINT        | PK, AUTO_INCREMENT  | Primary key produk   |
| category_id  | BIGINT        | NOT NULL, FK        | Relasi ke categories |
| sku          | VARCHAR(50)   | NOT NULL, UNIQUE    | Kode unik produk     |
| product_name | VARCHAR(150)  | NOT NULL            | Nama produk          |
| description  | TEXT          | NULL                | Deskripsi produk     |
| price        | DECIMAL(12,2) | NOT NULL            | Harga produk         |
| stock        | INT           | NOT NULL, DEFAULT 0 | Stok saat ini        |
| created_at   | DATETIME      | NOT NULL            | Waktu pembuatan      |
| updated_at   | DATETIME      | NOT NULL            | Waktu update         |

### Index

* category_id
* stock

### Aturan Bisnis

* Satu produk hanya memiliki satu kategori.
* Status ketersediaan diturunkan dari nilai stok.
* Stok hanya berubah saat transaksi berstatus `completed`.

---

## 5. Tabel `carts`

### Fungsi

Menyimpan data sementara (pre-transaction) sebelum transaksi diselesaikan.

### Atribut

| Kolom      | Tipe Data | Constraint         | Keterangan        |
| ---------- | --------- | ------------------ | ----------------- |
| cart_id    | BIGINT    | PK, AUTO_INCREMENT | Primary key cart  |
| user_id    | BIGINT    | NOT NULL, FK       | Pemilik cart      |
| product_id | BIGINT    | NOT NULL, FK       | Produk dalam cart |
| quantity   | INT       | NOT NULL           | Jumlah item       |
| created_at | DATETIME  | NOT NULL           | Waktu dibuat      |
| updated_at | DATETIME  | NOT NULL           | Waktu update      |

### Index

* user_id
* product_id

### Aturan Bisnis

* Cart tidak mengurangi stok.
* Semua validasi cart dilakukan di backend (stok & harga real-time).
* Tidak ada relasi FK langsung ke tabel transactions.

---

## 6. Tabel `transactions`

### Fungsi

Menyimpan data transaksi penjualan yang bersifat final dan immutable.

### Atribut

| Kolom            | Tipe Data     | Constraint         | Keterangan                     |
| ---------------- | ------------- | ------------------ | ------------------------------ |
| transaction_id   | BIGINT        | PK, AUTO_INCREMENT | Primary key transaksi          |
| user_id          | BIGINT        | NOT NULL, FK       | Kasir / user transaksi         |
| transaction_code | VARCHAR(50)   | NOT NULL, UNIQUE   | Kode transaksi                 |
| transaction_date | DATETIME      | NOT NULL           | Tanggal transaksi              |
| total_amount     | DECIMAL(12,2) | NOT NULL           | Total belanja                  |
| payment_amount   | DECIMAL(12,2) | NOT NULL           | Nominal pembayaran             |
| change_amount    | DECIMAL(12,2) | NOT NULL           | Kembalian                      |
| status           | ENUM          | NOT NULL           | completed / cancelled / failed |
| created_at       | DATETIME      | NOT NULL           | Waktu pembuatan                |

### Index

* user_id
* transaction_date
* status

### Aturan Bisnis

* Stok dikurangi hanya jika status `completed`.
* Transaksi bersifat immutable untuk kebutuhan audit.

---

## 7. Tabel `transaction_details`

### Fungsi

Menyimpan detail item yang dibeli dalam satu transaksi.

### Atribut

| Kolom                 | Tipe Data     | Constraint         | Keterangan             |
| --------------------- | ------------- | ------------------ | ---------------------- |
| transaction_detail_id | BIGINT        | PK, AUTO_INCREMENT | Primary key detail     |
| transaction_id        | BIGINT        | NOT NULL, FK       | Relasi ke transactions |
| product_id            | BIGINT        | NOT NULL, FK       | Produk yang dibeli     |
| quantity              | INT           | NOT NULL           | Jumlah item            |
| price_at_transaction  | DECIMAL(12,2) | NOT NULL           | Snapshot harga         |
| subtotal              | DECIMAL(12,2) | NOT NULL           | quantity x price       |

### Index

* transaction_id
* product_id

### Aturan Desain

* Harga disimpan sebagai snapshot untuk menjaga konsistensi laporan historis.
* Produk tidak boleh dihapus jika masih memiliki histori transaksi.

---

## Relasi Antar Tabel

* roles 1..* users
* users 1..* carts
* products 1..* carts
* users 1..* transactions
* transactions 1..* transaction_details
* products 1..* transaction_details
* categories 1..* products

Catatan:
Relasi antara `carts` dan `transactions` bersifat proses di service layer, bukan relasi foreign key di database.

---

## Asumsi Desain (Locked)

* Satu produk memiliki satu kategori.
* Satu produk memiliki satu harga.
* Stok disimpan di tabel products.
* Cart disimpan sebagai pre-transaction state.
