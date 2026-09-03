<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Jalan {{ $note->delivery_number }}</title>
    <style>
        /* DomPDF has limited CSS support — table-based layout, no
           flexbox/grid, inline-safe fonts only. Kept deliberately
           simple/printable. */
        body { font-family: sans-serif; font-size: 12px; color: #222; }
        .header-table { width: 100%; margin-bottom: 20px; }
        .header-table td { vertical-align: top; }
        h1 { font-size: 18px; margin: 0 0 4px 0; }
        .muted { color: #666; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table.items th, table.items td { border: 1px solid #999; padding: 6px 8px; text-align: left; }
        table.items th { background: #f0f0f0; }
        .signatures { width: 100%; margin-top: 50px; }
        .signatures td { width: 33%; text-align: center; padding-top: 40px; border-top: 1px solid #333; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <h1>SURAT JALAN</h1>
                <div class="muted">No: {{ $note->delivery_number }}</div>
                <div class="muted">Tanggal: {{ \Carbon\Carbon::parse($note->date)->translatedFormat('d F Y') }}</div>
            </td>
            <td style="text-align:right">
                <strong>{{ $note->company?->name ?? '' }}</strong>
            </td>
        </tr>
    </table>

    <p>
        <strong>Kepada:</strong><br>
        {{ $note->customer?->name }}<br>
        {{ $note->customer?->address ?? '' }}
    </p>

    @if($note->sale)
        <p class="muted">Terkait Invoice: {{ $note->sale->invoice_number }}</p>
    @endif

    <table class="items">
        <thead>
            <tr>
                <th style="width:40px">No</th>
                <th>Deskripsi Barang</th>
                <th style="width:100px">Jumlah</th>
                <th style="width:80px">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($note->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ number_format($item->quantity, 2) }}</td>
                    <td>{{ $item->unit }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($note->notes)
        <p><strong>Catatan:</strong> {{ $note->notes }}</p>
    @endif

    <table class="signatures">
        <tr>
            <td>Pengirim</td>
            <td>Pengemudi</td>
            <td>Penerima</td>
        </tr>
    </table>
</body>
</html>
