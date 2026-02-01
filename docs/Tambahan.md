# Tambahan dan Revisi untuk Dokumen SRS Point of Sales

Berikut adalah daftar poin yang perlu ditambahkan atau direvisi pada dokumen SRS berdasarkan fitur yang sudah tersedia di dalam kode (Codebase).

---

### 1. Revisi pada Bagian "1.3 Batasan Produk"

**Semula:**
> Fitur struk transaksi pada versi awal hanya ditampilkan di layar. Fitur pencetakan atau pengunduhan struk dalam bentuk PDF bersifat opsional dan dikembangkan jika waktu memungkinkan.

**Menjadi (Update):**
> Sistem menyediakan fitur untuk menampilkan struk transaksi di layar serta **mengunduh struk dalam format PDF**. Struk juga dapat diakses melalui tautan publik yang aman.

---

### 2. Tambahan pada Bagian "4. Functional Requirement"

Tambahkan poin-poin berikut ke dalam tabel Kebutuhan Fungsional untuk mencakup fitur PDF, Publik Akses, dan Manajemen Keranjang yang detail.

| ID | Kebutuhan Fungsional | Penjelasan |
| :--- | :--- | :--- |
| **FUNC-REQ-017** | **Sistem dapat mengunduh struk transaksi (PDF)** | Sistem harus menyediakan fitur bagi Admin, Kasir, dan Pelanggan untuk mengunduh struk transaksi dalam format file PDF. |
| **FUNC-REQ-018** | **Sistem menyediakan akses struk publik** | Sistem harus memungkinkan akses ke halaman struk transaksi melalui tautan (URL) unik tanpa memerlukan login (untuk dibagikan ke pelanggan). |
| **FUNC-REQ-019** | **Kasir dapat menghapus item dari keranjang** | Sistem harus memungkinkan Kasir untuk membatalkan/menghapus item produk tertentu yang sudah masuk ke dalam daftar transaksi (cart) sebelum checkout. |
| **FUNC-REQ-020** | **Kasir dapat mengosongkan keranjang** | Sistem harus menyediakan fitur untuk menghapus seluruh daftar belanja (clear cart) sekaligus jika transaksi dibatalkan. |

---

### 3. Revisi pada "4.1 Use Case Diagram" & Deskripsi

Disarankan menambahkan Use Case atau memperbarui deskripsi Use Case yang ada:

*   **Update UC5 (Memproses Transaksi):** Tambahkan alur alternatif (Alternate Flow) dimana Kasir bisa menghapus item dari keranjang sebelum pembayaran.
*   **Update UC6 (Melihat Riwayat Transaksi):** Tambahkan detail bahwa output dari riwayat transaksi mencakup opsi "Unduh PDF".
*   **Tambah Use Case Baru (Opsional):** "Mengakses Struk Publik" (Aktor: Pelanggan/Guest).

---

### 4. Tambahan pada "External Interface Requirements" (3.3 Software Interface)

Tambahkan library PDF generator yang digunakan jika perlu spesifik:
*   **PDF Generation:** Sistem menggunakan library dompdf (atau library sejenis yang terinstall di Laravel) untuk menghasilkan dokumen struk digital.
