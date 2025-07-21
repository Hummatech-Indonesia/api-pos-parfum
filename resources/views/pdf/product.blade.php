<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Data Product</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #888;
            padding: 5px;
            text-align: left;
        }
    </style>
</head>
<body>
    <h2>Data Product</h2>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID</th>
                <th>Name</th>
                <th>Detail Sum Stock</th>
                <th>Category</th>
                <th>Created By</th>
                <th>Description</th>
                <th>Sum Purchase</th>
                <th>Density</th>
                <th>Unit Code</th>
                <th>Price</th>
                <th>Variant Name</th>
                <th>Product Code</th>
                <th>Transaction Detail Count</th>
                <th>Stock</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $no => $product)
                <tr>
                    <td>{{ $no + 1 }}</td>
                    <td>{{ $product->id }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->details_sum_stock ?? 'N/A' }}</td>
                    <td>{{ $product->category->name ?? 'N/A' }}</td>
                    <td>{{ $product->created_by ?? 'N/A' }}</td>
                    <td>{{ $product->description ?? 'N/A' }}</td>
                    <td>{{ $product->sum_purchase ?? 'N/A' }}</td>
                    <td>{{ $product->density ?? 'N/A' }}</td>
                    <td>{{ $product->product_detail[0]->unit_code ?? 'N/A' }}</td>
                    <td>{{ $product->product_detail[0]->price ?? 'N/A' }}</td>
                    <td>{{ $product->product_detail[0]->variant_name ?? 'N/A' }}</td>
                    <td>{{ $product->product_detail[0]->product_code ?? 'N/A' }}</td>
                    <td>{{ $product->product_detail[0]->transaction_details_count ?? 'N/A' }}</td>
                    <td>{{ $product->product_detail[0]->stock ?? '0' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>