<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 30px 35px; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #14181f;
            line-height: 1.4;
        }

        .header { width: 100%; margin-bottom: 25px; }
        .header td { vertical-align: top; }

        .doc-title { font-size: 16pt; font-weight: bold; }
        .doc-meta { font-size: 9pt; color: #5f6b7a; margin-top: 4px; }

        .parties { width: 100%; margin-bottom: 20px; }
        .parties td { width: 50%; vertical-align: top; padding-right: 15px; }

        .party-label {
            font-size: 7pt;
            letter-spacing: 0.5px;
            color: #8b95a3;
            margin-bottom: 4px;
        }

        .party-name { font-weight: bold; }

        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 18px; }

        table.lines th {
            text-align: left;
            font-size: 7pt;
            letter-spacing: 0.5px;
            color: #5f6b7a;
            border-bottom: 1px solid #c9cfd6;
            padding: 6px 4px;
        }

        table.lines td {
            padding: 6px 4px;
            border-bottom: 1px solid #e3e6ea;
        }

        .num { text-align: right; }

        table.totals { width: 45%; margin-left: 55%; border-collapse: collapse; }
        table.totals td { padding: 4px 0; }
        table.totals td.num { text-align: right; }

        .grand {
            font-weight: bold;
            font-size: 11pt;
            border-top: 1px solid #c9cfd6;
            padding-top: 6px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 7pt;
            color: #8b95a3;
            border-top: 1px solid #e3e6ea;
            padding-top: 6px;
        }

        .draft-notice {
            background: #fffaeb;
            border: 1px solid #b54708;
            color: #b54708;
            padding: 8px;
            margin-bottom: 15px;
            font-size: 8pt;
        }
    </style>
</head>
<body>

@if ($invoice->number === null)
    <div class="draft-notice">
        ΠΡΟΣΧΕΔΙΟ — δεν αποτελεί φορολογικό παραστατικό.
    </div>
@endif

<table class="header">
    <tr>
        <td>
            <div class="party-name">{{ $company?->name }}</div>
            <div class="doc-meta">
                ΑΦΜ {{ $company?->vat_number }}@if ($company?->tax_office) · ΔΟΥ {{ $company->tax_office }}@endif<br>
                {{ $company?->address }}@if ($company?->city), {{ $company->city }}@endif
                {{ $company?->postal_code }}
            </div>
        </td>
        <td style="text-align: right;">
            <div class="doc-title">{{ $typeLabel }}</div>
            <div class="doc-meta">
                @if ($invoice->number)
                    {{ $series?->code }} — {{ $invoice->number }}<br>
                @endif
                {{ $invoice->issue_date?->format('d/m/Y') }}
            </div>
        </td>
    </tr>
</table>

<table class="parties">
    <tr>
        <td>
            <div class="party-label">ΠΡΟΣ</div>
            <div class="party-name">{{ $invoice->customer_name }}</div>
            <div class="doc-meta">
                @if ($invoice->customer_vat_number)
                    ΑΦΜ {{ $invoice->customer_vat_number }}@if ($invoice->customer_tax_office) · ΔΟΥ {{ $invoice->customer_tax_office }}@endif<br>
                @endif
                {{ $invoice->customer_address }}@if ($invoice->customer_city), {{ $invoice->customer_city }}@endif
                {{ $invoice->customer_postal_code }}
            </div>
        </td>
        <td>
            @if ($invoice->mydata_mark)
                <div class="party-label">ΜΑΡΚ</div>
                <div>{{ $invoice->mydata_mark }}</div>
            @endif
        </td>
    </tr>
</table>

<table class="lines">
    <thead>
        <tr>
            <th style="width: 4%;">#</th>
            <th style="width: 44%;">ΠΕΡΙΓΡΑΦΗ</th>
            <th class="num" style="width: 14%;">ΠΟΣΟΤΗΤΑ</th>
            <th class="num" style="width: 14%;">ΤΙΜΗ</th>
            <th class="num" style="width: 8%;">ΦΠΑ</th>
            <th class="num" style="width: 16%;">ΣΥΝΟΛΟ</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invoice->lines as $line)
            <tr>
                <td>{{ $line->position }}</td>
                <td>{{ $line->description }}</td>
                <td class="num">{{ rtrim(rtrim($line->quantity, '0'), '.') }} {{ $line->unit }}</td>
                <td class="num">{{ $format($line->unit_price_cents) }}</td>
                <td class="num">{{ $line->vat_rate }}%</td>
                <td class="num">{{ $format($line->total_cents) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr>
        <td>Καθαρή αξία</td>
        <td class="num">{{ $format($invoice->net_amount_cents) }}</td>
    </tr>
    <tr>
        <td>ΦΠΑ</td>
        <td class="num">{{ $format($invoice->vat_amount_cents) }}</td>
    </tr>
    <tr>
        <td class="grand">Σύνολο</td>
        <td class="num grand">{{ $format($invoice->total_cents) }}</td>
    </tr>
</table>

<div class="footer">
    Εκδόθηκε από {{ $company?->name }} · ΑΦΜ {{ $company?->vat_number }}
</div>

</body>
</html>
