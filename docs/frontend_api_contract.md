# POS Backend API Contract for Frontend Integration

Dokumen ini berisi spesifikasi API Backend untuk kebutuhan integrasi aplikasi Frontend Kasir (Next.js).

---

## 🔐 1. AUTHENTICATION

### **Login**

- **Method**: `POST`
- **URL**: `/api/login`
- **Role**: All (Admin, Cashier)
- **Deskripsi**: Mendapatkan access token (Bearer Token) untuk otentikasi.

**Request Body:**

```json
{
    "email": "cashier@example.com",
    "password": "password"
}
```

**Response Success (200):**

```json
{
    "success": true,
    "message": "Login success",
    "data": {
        "user": {
            "id": 5,
            "email": "cashier@example.com",
            "role": "cashier"
        },
        "token": "13|xgHJK..."
    }
}
```

### **Authorization Header**

Setiap request ke endpoint yang diproteksi (kecuali Login) **WAJIB** menyertakan header:
`Authorization: Bearer <token>`

---

## 🛒 2. CART MANAGEMENT (Cashier Only)

### **Get Active Cart**

- **Method**: `GET`
- **URL**: `/api/cart`
- **Role**: Cashier
- **Deskripsi**: Mengambil data keranjang belanja yang sedang aktif (belum dibayar). Jika kosong, return struktur kosong.

**Response Success (200):**

```json
{
    "success": true,
    "data": {
        "cart_id": 12,
        "status": "active",
        "total_quantity": 5,
        "total_amount": 75000,
        "items": [
            {
                "cart_item_id": 101,
                "product_id": 1,
                "product_name": "Kopi Susu",
                "price_snapshot": 15000, // Harga saat masuk cart
                "quantity": 2,
                "subtotal": 30000
            },
            {
                "cart_item_id": 102,
                "product_id": 2,
                "product_name": "Roti Bakar",
                "price_snapshot": 15000,
                "quantity": 3,
                "subtotal": 45000
            }
        ]
    }
}
```

### **Add to Cart**

- **Method**: `POST`
- **URL**: `/api/cart`
- **Deskripsi**: Menambah item ke cart. Jika produk sudah ada, quantity akan **di-increment**.
- **Catatan FE**: Response endpoint ini **SAMA PERSIS** dengan `GET /cart`. Jadi FE bisa langsung update state tanpa call GET lagi.

**Request Body:**

```json
{
    "product_id": 1,
    "quantity": 1
}
```

**Response Success (200) - Full Cart Object:**

```json
{
  "success": true,
  "message": "Product added to cart",
  "data": {
    // ... struktur sama dengan GET /cart di atas
    "total_quantity": 6,
    "total_amount": 90000,
    "items": [...]
  }
}
```

### **Remove Item**

- **Method**: `DELETE`
- **URL**: `/api/cart/{cart_item_id}`
- **Parameter**: `cart_item_id` (bukan product_id!)
- **Deskripsi**: Menghapus satu jenis item dari cart.

### **Clear Cart**

- **Method**: `DELETE`
- **URL**: `/api/cart`
- **Deskripsi**: Mengosongkan seluruh isi cart aktif.

---

## 💳 3. CHECKOUT (Cashier Only)

### **Process Checkout**

- **Method**: `POST`
- **URL**: `/api/checkout`
- **Role**: Cashier
- **Deskripsi**: Finalisasi transaksi dari Active Cart.
- **Business Rule Penting**:
    1.  Checkout diproses berdasarkan isi Cart di server. **FE tidak perlu kirim list items**.
    2.  Stock barang divalidasi ulang saat checkout.
    3.  Amount (Harga) menggunakan snapshot yang ada di cart.
    4.  Jika sukses, status cart berubah jadi `completed` (cart aktif jadi kosong).

**Request Body:**

```json
{
    "payment_method": "cash", // cash, qris, debit, etc.
    "paid_amount": 100000 // Jumlah uang yang diserahkan pelanggan
}
```

**Response Success (200) - Receipt Style:**

```json
{
    "success": true,
    "message": "Checkout berhasil",
    "data": {
        "transaction_id": 105,
        "cart_id": 12,
        "transaction_code": "TRX-2026-XYZ",
        "payment_method": "cash",
        "total_amount": 75000, // (Number)
        "paid_amount": 100000, // (Number)
        "change_amount": 25000, // (Number) Kembalian
        "created_at": "2026-02-06 14:30:00",
        "items": [
            {
                "product_id": 1,
                "product_name": "Kopi Susu",
                "price": 15000,
                "quantity": 5,
                "subtotal": 75000
            }
        ]
    }
}
```

**Error Cases (400/409):**

- **Cart Kosong**: `{ "message": "Cart kosong. Tidak ada item..." }` (400)
- **Uang Kurang**: `{ "message": "Uang pembayaran kurang..." }` (400)
- **Stock Habis**: `{ "message": "Stock produk 'Kopi Susu' tidak mencukupi..." }` (409)

---

## 📜 4. TRANSACTION HISTORY

### **Get My Transactions**

- **Method**: `GET`
- **URL**: `/api/transactions/my`
- **Query Params**:
    - `page`: Halaman (default 1)
    - `per_page`: Item per halaman (default 10)
    - `search`: Filter by Kode Transaksi
    - `date_from`: Filter tanggal mulai (YYYY-MM-DD)
    - `date_to`: Filter tanggal akhir (YYYY-MM-DD)

**Response Success (200):**

```json
{
    "success": true,
    "data": [
        {
            "transaction_id": 105,
            "transaction_code": "TRX-2026-XYZ",
            "transaction_date": "2026-02-06 14:30:00",
            "total_amount": 75000,
            "payment_amount": 100000,
            "change_amount": 25000,
            "payment_method": "cash",
            "status": "completed"
        }
        // ... list transactions
    ],
    "meta": { "current_page": 1, "last_page": 5, "total": 50 }
}
```

### **Get Transaction Detail**

- **Method**: `GET`
- **URL**: `/api/transactions/my/{id}`

**Response Success (200):**

```json
{
    "success": true,
    "data": {
        "transaction_code": "TRX-2026-XYZ",
        "status": "completed",
        "total_amount": 75000,
        "payment_method": "cash",
        // Info detail item snapshot saat transaksi terjadi
        "items": [
            {
                "product_name": "Kopi Susu",
                "quantity": 5,
                "price": 15000,
                "subtotal": 75000
            }
        ]
    }
}
```

---

## 🛍️ 5. PRODUCTS (Public/Shared)

### **Get Products**

- **Method**: `GET`
- **URL**: `/api/products` (atau `/api/cashier/products` tergantung implementasi list produk kasir)
- **Deskripsi**: List produk untuk ditampilkan di katalog kasir.

---

## 🔄 POS FLOW UNTUK FRONTEND

Berikut adalah alur standar yang diharapkan diimplementasikan di FE:

1.  **Init/Login**:
    - Cashier Login -> Simpan Token.
2.  **Dashboard/Kasir**:
    - Call `GET /api/products` -> Render Grid Produk.
    - Call `GET /api/cart` -> Render Sidebar Cart (Load state awal).
3.  **Add Item**:
    - User klik produk -> Call `POST /api/cart`.
    - Terima response full cart -> Update state cart lokal (tanpa perlu fetch ulang).
4.  **Checkout**:
    - User klik "Bayar" -> Muncul Modal Input Uang.
    - User Input Uang (`paid_amount`) & Pilih Metode (`payment_method`).
    - Call `POST /api/checkout`.
5.  **Post-Checkout**:
    - Sukses -> Tampilkan Modal Sukses / Struk (gunakan data dari response checkout).
    - State Cart lokal di-reset jadi kosong (karena di backend cart aktif sudah selesai).
    - User bisa "Print Receipt" atau "New Order".
    - Jika "New Order" -> Start dari langkah 2 (Cart backend otomatis siap untuk sesi baru).

---

## ⚠️ BUSINESS RULES & CATATAN PENTING

1.  **Backend Authority**: Cart adalah **Single Source of Truth**. Jangan menghitung total harga di frontend untuk dikirim ke backend. Frontend hanya menampilkan apa yang dikembalikan backend.
2.  **Data Type**: Semua field uang (`price`, `total_amount`, `subtotal`) dikirim sebagai **Number** (Float/Int), bukan String "10000".
3.  **Stock Validation**: Stock dicheck dua kali: saat `Add to Cart` dan saat `Checkout`. Bersiaplah menerima error 409 saat checkout jika barang tiba-tiba habis (race condition).
4.  **Cart Items vs Transaction Items**: `cart_item_id` berbeda dengan `product_id`. Gunakan `cart_item_id` untuk menghapus item dari keranjang.
5.  **Empty Cart**: Checkout dengan cart kosong akan ditolak (400).
6.  **Payment Validation**: `paid_amount` harus >= `total_amount`.

---

_Dokumen ini dibuat otomatis oleh AI Agent berdasarkan implementasi kode terkini (2026)._
