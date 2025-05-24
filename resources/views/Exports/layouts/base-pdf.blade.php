<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Laporan PDF')</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 20px;
        }

        h3,
        h5 {
            text-align: center;
            margin: 0;
            padding: 4px 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 15px;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .mt-4 {
            margin-top: 24px;
        }
    </style>
</head>

<body>
    @yield('content')
</body>

</html>
