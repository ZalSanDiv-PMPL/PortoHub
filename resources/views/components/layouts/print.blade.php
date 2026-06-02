<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'CV - PortoHub' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Tailwind -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* CSS KHUSUS CETAK KERTAS A4 */
        body {
            background-color: #f3f4f6; /* Gray-100 on screen */
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            color: #111827; /* Gray-900 */
        }
        .page-container {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 10mm auto;
            border-radius: 5px;
            background: white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        @media print {
            @page {
                size: A4;
                margin: 0; /* Remove browser margins */
            }
            body {
                background: white; /* No background in print */
            }
            .page-container {
                margin: 0;
                padding: 15mm 20mm;
                border: initial;
                border-radius: initial;
                width: initial;
                min-height: initial;
                box-shadow: initial;
                background: initial;
                page-break-after: always;
            }
            /* Sembunyikan elemen UI jika tidak sengaja terbawa */
            .no-print {
                display: none !important;
            }
            /* Paksa link tampil bersih */
            a {
                text-decoration: none;
                color: inherit;
            }
        }
    </style>
</head>
<body class="antialiased">
    <div class="page-container">
        {{ $slot }}
    </div>
</body>
</html>
