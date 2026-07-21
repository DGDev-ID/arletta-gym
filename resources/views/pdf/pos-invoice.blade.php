<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Invoice #{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #1a1a1a;
            background: #fff;
            padding: 40px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 32px;
        }
        .header-left {
            display: table-cell;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            vertical-align: top;
            text-align: right;
        }
        .gym-name {
            font-size: 22px;
            font-weight: bold;
            color: #111;
        }
        .gym-address {
            font-size: 12px;
            color: #555;
            margin-top: 4px;
        }
        .invoice-title {
            font-size: 26px;
            font-weight: bold;
            color: #333;
            letter-spacing: 1px;
        }
        .invoice-meta {
            font-size: 12px;
            color: #555;
            margin-top: 4px;
        }
        .divider {
            border: none;
            border-top: 2px solid #e5e7eb;
            margin: 20px 0;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 28px;
        }
        .info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 13px;
            font-weight: 600;
            color: #111;
            margin-bottom: 12px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-success { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-failed  { background: #fee2e2; color: #991b1b; }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        table.items thead tr {
            background: #f3f4f6;
        }
        table.items th {
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }
        table.items td {
            padding: 10px 12px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
            color: #1a1a1a;
        }
        table.items td.right, table.items th.right {
            text-align: right;
        }
        table.items td.center, table.items th.center {
            text-align: center;
        }
        .totals {
            margin-left: auto;
            width: 320px;
        }
        .totals-row {
            display: table;
            width: 100%;
            padding: 5px 0;
        }
        .totals-label {
            display: table-cell;
            font-size: 12px;
            color: #6b7280;
        }
        .totals-value {
            display: table-cell;
            text-align: right;
            font-size: 13px;
            font-weight: 600;
        }
        .totals-divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 6px 0;
        }
        .totals-grand {
            display: table;
            width: 100%;
            padding: 8px 0;
        }
        .totals-grand-label {
            display: table-cell;
            font-size: 14px;
            font-weight: bold;
            color: #111;
        }
        .totals-grand-value {
            display: table-cell;
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            color: #111;
        }
        .footer {
            margin-top: 48px;
            border-top: 1px solid #e5e7eb;
            padding-top: 16px;
            font-size: 11px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="gym-name">{{ $gymName }}</div>
            <div class="gym-address">{{ $gymAddress }}</div>
        </div>
        <div class="header-right">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-meta">#{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}</div>
            <div class="invoice-meta">{{ $transaction->created_at->format('d M Y, H:i') }}</div>
        </div>
    </div>

    <hr class="divider" />

    {{-- Transaction Info --}}
    <div class="info-grid">
        <div class="info-col">
            <div class="info-label">Gym</div>
            <div class="info-value">{{ $gymName }}</div>

            <div class="info-label">Alamat</div>
            <div class="info-value">{{ $gymAddress }}</div>

            <div class="info-label">Kasir</div>
            <div class="info-value">{{ $kasirName }}</div>
        </div>
        <div class="info-col" style="text-align: right;">
            <div class="info-label">Metode Pembayaran</div>
            <div class="info-value">{{ strtoupper($transaction->payment_method ?? '-') }}</div>

            <div class="info-label">Status</div>
            <div class="info-value">
                @if($transaction->status === 'success')
                    <span class="status-badge status-success">Paid</span>
                @elseif($transaction->status === 'pending')
                    <span class="status-badge status-pending">Pending</span>
                @else
                    <span class="status-badge status-failed">Failed</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Item Details Table --}}
    <table class="items">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Produk</th>
                <th class="right">Harga Satuan</th>
                <th class="right">Qty</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaction->products as $idx => $tp)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>
                    {{ $tp->product?->name ?? '-' }}
                    @if($tp->product?->category?->name)
                        <br><span style="font-size:11px; color:#9ca3af;">{{ $tp->product->category->name }}</span>
                    @endif
                </td>
                <td class="right">Rp {{ number_format($tp->sell_price / max($tp->quantity, 1), 0, ',', '.') }}</td>
                <td class="right">{{ $tp->quantity }}</td>
                <td class="right">Rp {{ number_format($tp->sell_price, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @if($transaction->products->isEmpty())
            <tr>
                <td colspan="5" style="text-align:center; color:#9ca3af;">Tidak ada item</td>
            </tr>
            @endif
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        <div class="totals-row">
            <span class="totals-label">Harga Dasar</span>
            <span class="totals-value">Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
        </div>
        <div class="totals-row">
            <span class="totals-label">Biaya Layanan</span>
            <span class="totals-value">Rp {{ number_format($fee, 0, ',', '.') }}</span>
        </div>
        <hr class="totals-divider" />
        <div class="totals-grand">
            <span class="totals-grand-label">Total Pembayaran</span>
            <span class="totals-grand-value">Rp {{ number_format($totalPay, 0, ',', '.') }}</span>
        </div>
        @if($cashPaid !== null)
        <hr class="totals-divider" />
        <div class="totals-row">
            <span class="totals-label">Uang Dibayar</span>
            <span class="totals-value">Rp {{ number_format($cashPaid, 0, ',', '.') }}</span>
        </div>
        <div class="totals-row">
            <span class="totals-label" style="color: #059669; font-weight: 600;">Kembalian</span>
            <span class="totals-value" style="color: #059669;">Rp {{ number_format($cashChange, 0, ',', '.') }}</span>
        </div>
        @endif
    </div>

    <div class="footer">
        Dokumen ini digenerate secara otomatis &mdash; {{ $gymName }} &mdash; {{ now()->format('d M Y H:i') }}
    </div>

</body>
</html>