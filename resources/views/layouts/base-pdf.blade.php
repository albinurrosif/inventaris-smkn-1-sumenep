<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Laporan PDF')</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            /* Ukuran font diperkecil */
            margin: 15px;
            /* Margin diperkecil */
        }

        h3,
        h5 {
            text-align: center;
            margin: 0;
            padding: 3px 0;
            /* Padding diperkecil */
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 10px;
            /* Margin bawah diperkecil */
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 3px;
            /* Padding diperkecil */
            text-align: left;
            font-size: 8px;
            /* Ukuran font dalam sel diperkecil */
            white-space: normal;
            /* Biarkan teks wrap jika perlu */
        }

        .text-center {
            text-align: center;
        }

        .mt-4 {
            margin-top: 15px;
            /* Margin atas diperkecil */
        }
    </style>
</head>

<body>
    @yield('content')
</body>

</html>
