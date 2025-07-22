<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Laporan Transaksi</h2>
    <p>Tanggal: {{ now()->format('d-m-Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>Nama Kasir</th>
                <th>Nama Pembeli</th>
                <th>Jumlah Yang Dibeli</th>
                <th>Total Harga</th>
                <th>Tanggal Pembelian</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $trx)
                <tr>
                    <td>{{ $trx->cashier_id ?? 'kasir' }}</td>
                    <td>{{ $trx->user_name}}</td>
                    <td>{{ $trx->transaction_details_count }}</td>
                    <td>{{ number_format($trx->amount_price) }}</td>
                    <td>{{ \Carbon\Carbon::parse($trx->payment_time)->format('d-m-Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
