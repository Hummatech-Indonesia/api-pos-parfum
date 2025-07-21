<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Data User</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
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
    <h2>Data User</h2>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $no => $user)
                <tr>
                    <td>{{ $no + 1 }}</td>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->roles[0]->name ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>