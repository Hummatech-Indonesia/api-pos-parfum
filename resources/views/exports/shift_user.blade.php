<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Export Shift User</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #888;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <h2>Laporan Transaksi</h2>
    <p>Tanggal: {{ now()->format('d-m-Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Waktu</th>
                <th>Tanggal</th>
                <th>Uang Keluar</th>
                <th>Uang Masuk</th>
            </tr>
        </thead>
        <tbody>
            {{-- {{ dd($shiftUsers) }} --}}
            @foreach ($shiftUsers as $shift)
                <tr>
                    <td>{{ $shift->user?->name ?? "data tidak ada" }}</td>
                    <td>{{ \Carbon\Carbon::parse($shift->date)->format('H:i:s') ?? "data tidak ada" }}</td>
                    <td>{{ \Carbon\Carbon::parse($shift->date)->format('d-m-Y') ?? "data tidak ada"}}</td>
                    <td>{{ $shift->start_price ?? "data tidak ada"}}</td>
                    <td>{{ $shift->end_price ?? "data tidak ada"}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
