<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $transaction->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #2b2f33;
            background-color: #ffffff;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .page {
            max-width: 800px;
            margin: 0 auto;
            padding: 48px 56px 40px;
        }

        table { border-collapse: collapse; }
        table td { vertical-align: top; }

        /* ── Header ── */
        .header-top {
            width: 100%;
            border-bottom: 3px solid #1a1a1a;
        }

        .header-top td { vertical-align: middle; }

        .brand-name {
            font-size: 21px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #1a1a1a;
        }

        .brand-tag {
            font-size: 11.5px;
            font-weight: 600;
            color: #8a9199;
            margin-top: 4px;
            line-height: 1.5;
        }

        .invoice-word {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 3px;
            color: #e21f28;
            text-transform: uppercase;
            text-align: right;
        }

        /* ── Meta Bar (No, Date, Payment, Status) ── */
        .meta-bar {
            background: #f7f7f7;
            border-radius: 8px;
            padding: 18px 24px;
            margin: 24px 0 32px;
        }

        .meta-bar table {
            width: 100%;
        }

        .meta-bar td {
            padding: 6px 0;
            width: 50%;
        }

        .meta-label {
            display: block;
            color: #8a9199;
            font-weight: 600;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .meta-value {
            display: block;
            font-weight: 700;
            color: #1a1a1a;
            font-size: 13.5px;
        }

        .align-right { text-align: right; }

        /* ── Status badge ── */
        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .status-paid,
        .status-success  { background: #e3f5ec; color: #0f7a4b; }
        .status-pending   { background: #fef3e2; color: #b9740b; }
        .status-unpaid,
        .status-failed    { background: #fdeaea; color: #c0362c; }
        .status-default   { background: #eef0f2; color: #52585e; }

        /* ── Info Split (Ditagihkan Kepada / Diterbitkan Oleh) ── */
        .info-split {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-split td {
            width: 50%;
        }

        .info-title {
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 8px;
            color: #8a9199;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .info-value {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 14px;
        }

        /* ── Table Items ── */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }

        table.items thead tr {
            background: #1a1a1a;
        }

        table.items thead th {
            text-align: left;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #ffffff;
            padding: 11px 16px;
        }

        table.items thead th:first-child { border-radius: 6px 0 0 6px; }
        table.items thead th:last-child  { border-radius: 0 6px 6px 0; }

        table.items tbody td {
            padding: 14px 16px;
            font-size: 13px;
            color: #2b2f33;
            border-bottom: 1px solid #eceef0;
            vertical-align: middle;
        }

        table.items tbody tr:nth-child(even) {
            background: #fafbfb;
        }

        table.items tbody tr:last-child td {
            border-bottom: none;
        }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }

        /* ── Totals ── */
        .totals-wrap {
            width: 50%;
            margin-left: 50%;
        }

        .totals-wrap table {
            width: 100%;
        }

        .total-row td {
            padding: 8px 0;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
        }

        .total-row .amount {
            color: #2b2f33;
            font-weight: 700;
            text-align: right;
        }

        .total-row.final td {
            font-weight: 800;
            font-size: 14px;
            color: #ffffff;
            padding: 14px 16px;
        }

        .total-row.final {
            background: #e21f28;
            rounded: 8px;
            border-radius: 8px;
        }

        .total-row.final .label {
            color: #fbd7d9;
        }

        .total-row.final .amount {
            color: #ffffff;
            text-align: right;
        }

        /* ── Bottom note ── */
        .thank-you {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eceef0;
            color: #8a9199;
            font-size: 11.5px;
        }

        .thank-you strong {
            display: block;
            color: #1a1a1a;
            font-size: 14px;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <div class="page">

        {{-- Header --}}
        <table class="header-top">
            <tr>
                <td style="width: 60%;">
                    <div class="brand-name">{{ $gymName }}</div>
                    <div class="brand-tag">{!! nl2br(e($gymAddress)) !!}</div>
                </td>
                <td style="width: 40%;">
                    <div class="invoice-word">Invoice</div>
                </td>
            </tr>
        </table>

        {{-- Meta Bar --}}
        @php
            $statusKey = strtolower($transaction->status);
            $statusClass = match(true) {
                str_contains($statusKey, 'success') || (str_contains($statusKey, 'paid') && !str_contains($statusKey, 'unpaid')) => 'status-success',
                str_contains($statusKey, 'pending') => 'status-pending',
                str_contains($statusKey, 'unpaid') || str_contains($statusKey, 'fail') => 'status-unpaid',
                default => 'status-default',
            };
        @endphp
        <div class="meta-bar">
            <table>
                <tr>
                    <td>
                        <span class="meta-label">Invoice Date</span>
                        <span class="meta-value">{{ $transaction->created_at->format('d M Y') }}</span>
                    </td>
                    <td class="align-right">
                        <span class="meta-label">Status</span>
                        <span class="status-badge {{ $statusClass }}">{{ ucfirst($transaction->status) }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="meta-label">Payment Method</span>
                        <span class="meta-value">{{ strtoupper($transaction->payment_method ?? '-') }}</span>
                    </td>
                    <td class="align-right">
                        <span class="meta-label">No. Invoice</span>
                        <span class="meta-value">#{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Info Split --}}
        <table class="info-split">
            <tr>
                <td>
                    <div class="info-title">Ditagihkan Kepada</div>
                    <div class="info-value">{{ $transaction->member->name ?? $transaction->customer_name ?? 'Member' }}</div>
                </td>
                <td class="align-right">
                    <div class="info-title">Diterbitkan Oleh</div>
                    <div class="info-value">{{ $gymName }}</div>
                </td>
            </tr>
        </table>

        {{-- Items Table --}}
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 45%;">Deskripsi</th>
                    <th class="text-right" style="width: 25%;">Harga</th>
                    <th class="text-center" style="width: 10%;">Qty</th>
                    <th class="text-right" style="width: 20%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->products as $tp)
                <tr>
                    <td>
                        <strong style="color: #1a1a1a;">{{ $tp->product?->name ?? '-' }}</strong>
                        @if($tp->product?->category?->name)
                            <div style="font-size:11px; color:#8a9199; margin-top:3px;">{{ $tp->product->category->name }}</div>
                        @endif
                    </td>
                    <td class="text-right">Rp {{ number_format($tp->sell_price / max($tp->quantity, 1), 0, ',', '.') }}</td>
                    <td class="text-center">{{ $tp->quantity }}</td>
                    <td class="text-right" style="font-weight: 700; color: #1a1a1a;">Rp {{ number_format($tp->sell_price, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals-wrap">
            <table>
                <tr class="total-row">
                    <td class="label">Subtotal</td>
                    <td class="amount">Rp {{ number_format($totalPrice, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td class="label">Biaya Layanan</td>
                    <td class="amount">Rp {{ number_format($fee, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row final">
                    <td class="label">Total Bayar</td>
                    <td class="amount">Rp {{ number_format($totalPay, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        {{-- Thank you note --}}
        <div class="thank-you">
            <strong>Terima kasih atas kepercayaan Anda!</strong>
            Invoice ini dibuat secara otomatis oleh sistem {{ $gymName }}.
        </div>

    </div>
</body>
</html>