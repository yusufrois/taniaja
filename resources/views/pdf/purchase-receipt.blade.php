<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota Pembayaran #{{ $purchase->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #222; }
        .header-table { width: 100%; margin-bottom: 20px; }
        .header-table td { vertical-align: top; }
        h1 { font-size: 18px; margin: 0 0 4px 0; }
        .muted { color: #666; }
        table.detail { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table.detail td { border: 1px solid #999; padding: 6px 8px; }
        table.detail td.label { background: #f0f0f0; width: 180px; font-weight: bold; }
        .total-row td { font-weight: bold; font-size: 14px; }
        .signatures { width: 100%; margin-top: 50px; }
        .signatures td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #333; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <h1>NOTA PEMBAYARAN KE PETANI</h1>
                <div class="muted">No: {{ $purchase->id }}</div>
                <div class="muted">Tanggal: {{ \Carbon\Carbon::parse($purchase->purchase_date)->translatedFormat('d F Y') }}</div>
            </td>
            <td style="text-align:right">
                <strong>{{ $purchase->company?->name ?? '' }}</strong>
            </td>
        </tr>
    </table>

    <table class="detail">
        <tr>
            <td class="label">Nama Petani/Supplier</td>
            <td>{{ $purchase->supplier?->name }}</td>
        </tr>
        <tr>
            <td class="label">Komoditas</td>
            <td>{{ $purchase->crop?->name }} @if($purchase->variety) — {{ $purchase->variety->name }} @endif</td>
        </tr>
        <tr>
            <td class="label">Grade</td>
            <td>{{ $purchase->grade?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah</td>
            <td>{{ number_format($purchase->quantity, 2) }} kg</td>
        </tr>
        <tr>
            <td class="label">Harga per Satuan</td>
            <td>Rp {{ number_format($purchase->unit_price, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td class="label">Total Pembayaran</td>
            <td>Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Status Bayar</td>
            <td>{{ strtoupper($purchase->payment_status) }} (Terbayar: Rp {{ number_format($purchase->total_paid, 0, ',', '.') }} / Sisa: Rp {{ number_format($purchase->remaining, 0, ',', '.') }})</td>
        </tr>
        @if($purchase->notes)
        <tr>
            <td class="label">Catatan</td>
            <td>{{ $purchase->notes }}</td>
        </tr>
        @endif
    </table>

    <table class="signatures">
        <tr>
            <td>Petani/Penjual</td>
            <td>Petugas Kulakan ({{ $purchase->creator?->name ?? '-' }})</td>
        </tr>
    </table>
</body>
</html>
