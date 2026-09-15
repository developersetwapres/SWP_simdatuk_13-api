<!DOCTYPE html>
<html>

<head>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <style type="text/css">
        * {
            box-sizing: border-box;
        }

        html * {
            font-family: Inter, sans-serif !important;
            color: #394346;
        }

        @page {
            margin: 72px 32px;
        }

        body {
            margin: 0;
            font-size: 9px;
            color: #394346;
        }

        header {
            position: fixed;
            top: -42px;
            left: 0;
            right: 0;
        }

        .logo {
            width: 200px;
        }

        .title {
            font-size: 15px;
            font-weight: 700;
            color: #263238;
            text-align: center;
        }

        .sub-title {
            font-size: 10px;
            font-weight: 500;
            color: #7B8589;
            margin-top: 4px;
            text-align: center;
        }

        .report-table {
            width: 100%;
            margin-top: 18px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .report-table th {
            background-color: #394346;
            color: #FFFFFF;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 8px 10px;
            text-align: left;
            border-bottom: 2px solid #DCE1E3;
        }

        .report-table th:last-child {
            text-align: right;
            width: 100px;
        }

        /* Kelompok jabatan */
        .group-row {
            page-break-after: avoid;
        }

        .group-row td {
            background-color: #EEF2F4;
            color: #263238;
            font-size: 9px;
            font-weight: 700;
            padding: 8px 10px;
            border-top: 1px solid #D8E0E3;
            border-bottom: 1px solid #D8E0E3;
        }

        .group-row td:first-child {
            border-left: 3px solid #5B7C8D;
        }

        .group-row td:last-child {
            text-align: right;
            color: #394346;
        }

        /* Baris jabatan */
        .position-row td {
            font-size: 9px;
            font-weight: 400;
            padding: 6px 10px 6px 22px;
            border-bottom: 1px solid #E8ECEE;
            vertical-align: middle;
        }

        .position-row td:first-child {
            color: #4F5B5F;
        }

        .position-row td:last-child {
            text-align: right;
            font-weight: 600;
            color: #394346;
            padding-right: 10px;
        }

        /* Baris biasa / jabatan yang tidak masuk kelompok */
        .normal-row td {
            font-size: 9px;
            font-weight: 400;
            padding: 7px 10px;
            border-bottom: 1px solid #E8ECEE;
        }

        .normal-row td:last-child {
            text-align: right;
            font-weight: 600;
        }

        /* Total akhir */
        .total-row td {
            background-color: #394346;
            color: #FFFFFF;
            font-size: 9px;
            font-weight: 700;
            padding: 9px 10px;
            border-top: 3px solid #FFFFFF;
        }

        .total-row td:last-child {
            text-align: right;
            color: #FFFFFF;
        }

        .total-label {
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .total-number {
            font-size: 10px;
            font-weight: 700;
        }
    </style>
</head>

<body>

    <header>
        <img src="{{ \App\Support\ExportLogo::dataUri() }}" class="logo" />
    </header>

    <div class="title">
        {{ $title }}
    </div>

    <div class="sub-title">
        Per Tanggal : {{ $date }}
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th>Jabatan / Kelompok</th>
                <th>Jumlah</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($data as $value)
                @if ($value['type'] == 3)
                    {{-- Header kelompok --}}
                    <tr class="group-row">
                        <td>{{ $value['title'] }}</td>
                        <td>{{ $value['body'] }}</td>
                    </tr>
                @elseif ($value['type'] == 2)
                    {{-- Jabatan di dalam kelompok --}}
                    <tr class="position-row">
                        <td>{{ $value['title'] }}</td>
                        <td>{{ $value['body'] }}</td>
                    </tr>
                @else
                    {{-- Jabatan biasa --}}
                    <tr class="normal-row">
                        <td>{{ $value['title'] }}</td>
                        <td>{{ $value['body'] }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</body>

</html>
