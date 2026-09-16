<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; font-size: 14px; color: #14181f; line-height: 1.5;">

    <p>Καλησπέρα,</p>

    @if ($invoice->number)
        <p>
            Σας αποστέλλουμε το παραστατικό
            <strong>{{ $invoice->series?->code }}-{{ $invoice->number }}</strong>
            με ημερομηνία {{ $invoice->issue_date?->format('d/m/Y') }}.
        </p>
    @else
        <p>Σας αποστέλλουμε προσχέδιο παραστατικού.</p>
    @endif

    <p>
        Συνολικό ποσό:
        <strong>{{ number_format($invoice->total_cents / 100, 2, ',', '.') }} €</strong>
    </p>

    <p>Το παραστατικό επισυνάπτεται σε μορφή PDF.</p>

    @if ($invoice->mydata_mark)
        <p style="color: #5f6b7a; font-size: 12px;">
            ΜΑΡΚ: {{ $invoice->mydata_mark }}
        </p>
    @endif

    <p>
        Με εκτίμηση,<br>
        {{ $invoice->company?->name }}
    </p>

</body>
</html>
