<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense Report</title>
    <style>
        @page { margin: 18mm 14mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #0f2744;
            margin: 0;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #ff9b44;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .logo { max-height: 62px; max-width: 140px; }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f2744;
            margin: 0 0 4px 0;
        }
        .company-meta {
            font-size: 10px;
            color: #4b5c73;
            line-height: 1.45;
            margin: 0;
        }
        .doc-title {
            text-align: right;
        }
        .doc-title h1 {
            margin: 0;
            font-size: 20px;
            color: #0f2744;
            letter-spacing: 0.5px;
        }
        .doc-title p {
            margin: 3px 0 0;
            font-size: 10px;
            color: #6b7c93;
        }
        .filters {
            background: #f4f7fb;
            border: 1px solid #e8eef5;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 14px;
            font-size: 10px;
            color: #4b5c73;
        }
        .filters strong { color: #0f2744; }
        table.expenses {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.expenses th {
            background: #0f2744;
            color: #fff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 7px 6px;
            text-align: left;
        }
        table.expenses td {
            border: 1px solid #e8eef5;
            padding: 6px;
            font-size: 9.5px;
            vertical-align: top;
            word-wrap: break-word;
        }
        table.expenses tr:nth-child(even) td { background: #fafbfd; }
        .right { text-align: right; }
        .advance { background: #fff1f2 !important; }
        .totals {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .totals td {
            padding: 8px 10px;
            font-size: 11px;
        }
        .totals .label {
            background: #0f2744;
            color: #fff;
            font-weight: bold;
            width: 70%;
        }
        .totals .value {
            background: #ff9b44;
            color: #fff;
            font-weight: bold;
            text-align: right;
            width: 30%;
        }
        .words {
            margin-top: 10px;
            font-size: 10px;
            color: #4b5c73;
        }
        .footer {
            margin-top: 28px;
            width: 100%;
            border-collapse: collapse;
        }
        .footer td {
            font-size: 10px;
            color: #4b5c73;
            vertical-align: bottom;
        }
        .sign {
            text-align: right;
        }
        .sign-line {
            margin-top: 36px;
            border-top: 1px solid #c5d0de;
            display: inline-block;
            min-width: 160px;
            padding-top: 6px;
        }
        .empty {
            text-align: center;
            padding: 24px;
            color: #6b7c93;
            border: 1px dashed #e8eef5;
        }
    </style>
</head>
<body>
@php
    $addressLines = $payslip->addressLines();
@endphp

<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 62%;">
                @if(!empty($logoDataUri))
                    <img src="{{ $logoDataUri }}" class="logo" alt="Logo"><br>
                @endif
                <p class="company-name">{{ $payslip->company_name }}</p>
                @foreach($addressLines as $line)
                    <p class="company-meta">{{ $line }}</p>
                @endforeach
                @if($payslip->cin)
                    <p class="company-meta">CIN: {{ $payslip->cin }}</p>
                @endif
                @if($payslip->location)
                    <p class="company-meta">Location: {{ $payslip->location }}</p>
                @endif
            </td>
            <td class="doc-title" style="width: 38%;">
                <h1>EXPENSE REPORT</h1>
                <p>Receipt No: {{ $receiptNo }}</p>
                <p>Generated: {{ now()->format('d M Y, h:i A') }}</p>
            </td>
        </tr>
    </table>
</div>

<div class="filters">
    <strong>Applied filters:</strong> {{ $filterLabel }}
</div>

@if($expenses->isEmpty())
    <div class="empty">No expenses found for the selected filters.</div>
@else
    <table class="expenses">
        <thead>
        <tr>
            <th style="width:10%;">Date</th>
            <th style="width:16%;">Entity</th>
            <th style="width:14%;">Category</th>
            <th style="width:24%;">Description</th>
            <th style="width:12%;">Payment</th>
            <th style="width:10%;">Status</th>
            <th style="width:14%;" class="right">Amount (₹)</th>
        </tr>
        </thead>
        <tbody>
        @foreach($expenses as $expense)
            @php $isAdvance = $expense->isAdvanceDisbursement(); @endphp
            <tr>
                <td class="{{ $isAdvance ? 'advance' : '' }}">{{ optional($expense->expense_date)->format('d-m-Y') }}</td>
                <td class="{{ $isAdvance ? 'advance' : '' }}">{{ $expense->entityName() }}</td>
                <td class="{{ $isAdvance ? 'advance' : '' }}">{{ $expense->category?->name ?? '—' }}</td>
                <td class="{{ $isAdvance ? 'advance' : '' }}">{{ $expense->description ?: '—' }}</td>
                <td class="{{ $isAdvance ? 'advance' : '' }}">
                    {{ $expense->payment_method ?: '—' }}
                    @if($expense->reference_id)
                        <br><span style="color:#6b7c93;">{{ $expense->reference_id }}</span>
                    @endif
                </td>
                <td class="{{ $isAdvance ? 'advance' : '' }}">{{ $expense->status ?: 'Pending' }}</td>
                <td class="right {{ $isAdvance ? 'advance' : '' }}">{{ number_format((float) $expense->amount, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Total ({{ $expenses->count() }} record{{ $expenses->count() === 1 ? '' : 's' }})</td>
            <td class="value">₹ {{ number_format($totalAmount, 2) }}</td>
        </tr>
    </table>

    <p class="words"><strong>Total amount (in words):</strong> {{ $amountInWords }}</p>
@endif

<table class="footer">
    <tr>
        <td>For: {{ $payslip->company_name }}</td>
        <td class="sign">
            <div class="sign-line">{{ $payslip->signatory_name ?: 'Authorised Signatory' }}</div>
        </td>
    </tr>
</table>
</body>
</html>
