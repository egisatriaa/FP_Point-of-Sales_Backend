<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h2 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 6px;
            border-bottom: 1px dashed #ccc;
        }

        .total {
            font-weight: bold;
        }
    </style>
</head>

<body>

    <h2>STRUK PEMBAYARAN</h2>

    <p>
        Kode Transaksi: {{ $receipt['transaction_code'] }}<br>
        Tanggal: {{ $receipt['transaction_date'] }}<br>
        Kasir: {{ $receipt['cashier'] }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receipt['items'] as $item)
            <tr>
                <td>{{ $item['product_name'] }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ number_format($item['price']) }}</td>
                <td>{{ number_format($item['subtotal']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total">
        Total: {{ number_format($receipt['total_amount']) }} <br>
        Bayar: {{ number_format($receipt['payment_amount']) }} <br>
        Kembalian: {{ number_format($receipt['change_amount']) }}
    </p>

    <p style="text-align:center;">Terima kasih 🙏</p>

</body>

</html>