<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi Guru {{ $period }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .header { width: 100%; border-bottom: 2px solid #111; margin-bottom: 14px; }
        .header td { vertical-align: middle; padding-bottom: 8px; }
        .logo { width: 56px; height: 56px; }
        h1 { font-size: 15px; margin: 0; }
        h2 { font-size: 12px; margin: 2px 0 0; font-weight: normal; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #555; padding: 4px 6px; }
        table.data th { background: #e5e7eb; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 66px;">
                <img class="logo" src="{{ public_path('images/logo.png') }}" alt="Logo">
            </td>
            <td>
                <h1>{{ $institution }}</h1>
                <h2>Rekap Absensi Guru - {{ $period }}</h2>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th class="center">No</th>
                <th>Nama</th>
                <th>NIP</th>
                <th class="center">Hadir</th>
                <th class="center">Terlambat</th>
                <th class="center">Izin</th>
                <th class="center">Sakit</th>
                <th class="center">Cuti</th>
                <th class="center">Alpa</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['nip'] ?? '-' }}</td>
                    <td class="center">{{ $row['hadir'] }}</td>
                    <td class="center">{{ $row['terlambat'] }}</td>
                    <td class="center">{{ $row['izin'] }}</td>
                    <td class="center">{{ $row['sakit'] }}</td>
                    <td class="center">{{ $row['cuti'] }}</td>
                    <td class="center">{{ $row['alpa'] }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="center">Belum ada data guru aktif.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
