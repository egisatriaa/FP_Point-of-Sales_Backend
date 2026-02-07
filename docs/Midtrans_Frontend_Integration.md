# Panduan Integrasi Frontend Midtrans (Next.js / React)

Panduan ini menjelaskan cara mengintegrasikan pembayaran Midtrans Snap di frontend aplikasi Kasir.

## 1. Persiapan Environment

Pastikan frontend memiliki **Client Key** Midtrans. Tambahkan di `.env.local`:

```env
NEXT_PUBLIC_MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxxx  # Ganti dengan Client Key Sandbox/Production Anda
NEXT_PUBLIC_MIDTRANS_SNAP_URL=https://app.sandbox.midtrans.com/snap/snap.js # Gunakan URL Production jika live
```

## 2. Install Snap.js

Tambahkan script Snap.js secara global atau di halaman checkout. Contoh di `layout.tsx` atau `_document.tsx`:

```jsx
// Contoh di Next.js (app/layout.tsx atau component)
import Script from "next/script";

export default function RootLayout({ children }) {
    return (
        <html lang="id">
            <body>
                {children}
                <Script
                    src={process.env.NEXT_PUBLIC_MIDTRANS_SNAP_URL}
                    data-client-key={
                        process.env.NEXT_PUBLIC_MIDTRANS_CLIENT_KEY
                    }
                    strategy="lazyOnload"
                />
            </body>
        </html>
    );
}
```

> **Catatan:** Pastikan `data-client-key` terisi dengan benar.

## 3. Flow Checkout

1.  User klik "Bayar" di Cart.
2.  Frontend request ke API Backend: `POST /api/checkout` dengan `payment_method: "midtrans"`.
3.  Backend mengembalikan `snap_token`.
4.  Frontend memanggil `window.snap.pay(snap_token)`.

## 4. Contoh Implementasi Component (CheckoutButton.tsx)

Berikut adalah contoh komponen tombol checkout:

```tsx
"use client";

import { useState } from "react";
import axios from "axios";

declare global {
    interface Window {
        snap: any;
    }
}

export default function CheckoutButton({ cartItems, totalAmount }) {
    const [loading, setLoading] = useState(false);

    const handleCheckout = async () => {
        try {
            setLoading(true);

            // 1. Request Snap Token ke Backend
            const response = await axios.post(
                "/api/checkout",
                {
                    payment_method: "midtrans",
                    paid_amount: totalAmount, // Backend akan memvalidasi ini
                },
                {
                    headers: { Authorization: `Bearer ${token}` }, // Jangan lupa Token Auth
                },
            );

            const { snap_token, transaction_code } = response.data.data;

            // 2. Trigger Snap Popup
            if (window.snap) {
                window.snap.pay(snap_token, {
                    // Callback Sukses
                    onSuccess: function (result) {
                        console.log("Payment Success:", result);
                        alert("Pembayaran Berhasil!");
                        // Redirect ke halaman sukses / struk
                        window.location.href = `/receipt/${transaction_code}`;
                    },
                    // Callback Pending (User tutup popup tanpa bayar atau pilih ATM tapi belum transfer)
                    onPending: function (result) {
                        console.log("Payment Pending:", result);
                        alert("Menunggu Pembayaran...");
                        window.location.href = `/transactions/my`; // Arahkan ke list transaksi
                    },
                    // Callback Error
                    onError: function (result) {
                        console.error("Payment Error:", result);
                        alert("Pembayaran Gagal!");
                    },
                    // Callback Close (User tutup popup)
                    onClose: function () {
                        alert(
                            "Anda menutup popup pembayaran sebelum menyelesaikan transaksi.",
                        );
                    },
                });
            } else {
                alert("Snap JS belum terload. Refresh halaman.");
            }
        } catch (error) {
            console.error("Checkout Error:", error);
            alert(
                error.response?.data?.message ||
                    "Terjadi kesalahan saat checkout",
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <button
            onClick={handleCheckout}
            disabled={loading}
            className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
        >
            {loading ? "Processing..." : "Bayar via Midtrans"}
        </button>
    );
}
```

## 5. Webhook (Backend)

Frontend **TIDAK PERLU** melakukan update status transaksi ke database secara manual setelah sukses.

- Midtrans akan mengirim notifikasi (Webhook) ke Backend `POST /api/webhooks/midtrans`.
- Backend akan otomatis mengupdate status menjadi `completed` (Paid) atau `expired`.
- Frontend hanya perlu menampilkan status terbaru dengan me-refresh data transaksi dari API (`GET /transactions/{id}` atau `GET /transactions/my`).
