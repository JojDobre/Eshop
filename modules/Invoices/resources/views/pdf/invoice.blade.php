@php
    $money = fn (int $amount) => \App\Core\Support\Price::format($amount, $invoice->currency, $invoice->locale);
    $date = fn ($value) => $value ? $value->format($invoice->locale === 'en' ? 'j M Y' : 'j. n. Y') : '—';
    $rate = fn (float $value) => rtrim(rtrim(number_format($value, 2, ',', ''), '0'), ',').' %';
    $s = $invoice->supplier;
    $c = $invoice->customer;
@endphp
<!DOCTYPE html>
<html lang="{{ $invoice->locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->type->getLabel() }} {{ $invoice->number }}</title>
    <style>
        @page { margin: 32px 36px; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { font-size: 9.5px; color: #18181b; line-height: 1.45; }
        h1 { font-size: 18px; margin: 0; font-weight: bold; }
        .muted { color: #71717a; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { vertical-align: top; }
        .box { border: 1px solid #e4e4e7; border-radius: 6px; padding: 10px 12px; }
        .label { font-size: 8px; text-transform: uppercase; letter-spacing: .04em; color: #71717a; margin-bottom: 3px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 18px; }
        table.items th { text-align: left; font-size: 8px; text-transform: uppercase; color: #71717a; border-bottom: 1px solid #d4d4d8; padding: 6px 4px; }
        table.items td { border-bottom: 1px solid #f4f4f5; padding: 6px 4px; }
        .r { text-align: right; }
        .total { font-size: 14px; font-weight: bold; }
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; font-size: 8px; color: #a1a1aa; text-align: center; }
    </style>
</head>
<body>
    <table class="grid">
        <tr>
            <td style="width: 60%">
                <h1>{{ $invoice->type->getLabel() }}</h1>
                <div class="muted" style="font-size: 12px">{{ __('invoices::invoices.number') }} {{ $invoice->number }}</div>
                @if ($invoice->type === \Modules\Invoices\InvoiceType::Proforma)
                    <div class="muted" style="margin-top: 4px">{{ __('invoices::invoices.proforma_note') }}</div>
                @endif
                @if ($invoice->relatedInvoice)
                    <div class="muted" style="margin-top: 4px">{{ __('invoices::invoices.credit_note_for', ['number' => $invoice->relatedInvoice->number]) }}</div>
                @endif
            </td>
            <td style="width: 40%" class="r">
                <table class="grid">
                    <tr><td class="muted">{{ __('invoices::invoices.issued_at') }}</td><td class="r">{{ $date($invoice->issued_at) }}</td></tr>
                    @if ($invoice->supplied_at)
                        <tr><td class="muted">{{ __('invoices::invoices.supplied_at') }}</td><td class="r">{{ $date($invoice->supplied_at) }}</td></tr>
                    @endif
                    <tr><td class="muted">{{ __('invoices::invoices.due_at') }}</td><td class="r">{{ $date($invoice->due_at) }}</td></tr>
                    <tr><td class="muted">{{ __('invoices::invoices.order') }}</td><td class="r">{{ $c['order_number'] ?? '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="grid" style="margin-top: 18px">
        <tr>
            <td style="width: 50%; padding-right: 8px">
                <div class="box">
                    <div class="label">{{ __('invoices::invoices.supplier') }}</div>
                    <strong>{{ $s['name'] }}</strong><br>
                    @if ($s['street']){{ $s['street'] }}<br>@endif
                    @if ($s['city']){{ trim(($s['zip'] ?? '').' '.$s['city']) }}<br>@endif
                    @if ($s['country']){{ __('countries.'.$s['country']) }}<br>@endif
                    @if ($s['company_id'])<br>{{ __('invoices::invoices.company_id') }}: {{ $s['company_id'] }}@endif
                    @if ($s['tax_id'])<br>{{ __('invoices::invoices.tax_id') }}: {{ $s['tax_id'] }}@endif
                    @if ($s['vat_id'])<br>{{ __('invoices::invoices.vat_id') }}: {{ $s['vat_id'] }}@endif
                    @unless ($invoice->vat_payer)<br>{{ __('invoices::invoices.not_vat_payer') }}@endunless
                    @if ($s['registration'])<br><span class="muted">{{ $s['registration'] }}</span>@endif
                </div>
            </td>
            <td style="width: 50%; padding-left: 8px">
                <div class="box">
                    <div class="label">{{ __('invoices::invoices.customer') }}</div>
                    <strong>{{ $c['company'] ?: $c['name'] }}</strong><br>
                    @if ($c['company']){{ $c['name'] }}<br>@endif
                    {{ $c['street'] }}<br>
                    {{ trim($c['zip'].' '.$c['city']) }}<br>
                    {{ __('countries.'.$c['country']) }}
                    @if ($c['company_id'])<br><br>{{ __('invoices::invoices.company_id') }}: {{ $c['company_id'] }}@endif
                    @if ($c['tax_id'])<br>{{ __('invoices::invoices.tax_id') }}: {{ $c['tax_id'] }}@endif
                    @if ($c['vat_id'])<br>{{ __('invoices::invoices.vat_id') }}: {{ $c['vat_id'] }}@endif
                </div>
            </td>
        </tr>
    </table>

    <table class="grid" style="margin-top: 12px">
        <tr>
            <td style="width: 70%">
                <span class="muted">{{ __('invoices::invoices.payment_method') }}:</span> {{ $invoice->payment_method }}
                @if ($s['iban'])&nbsp;&nbsp;<span class="muted">IBAN:</span> {{ $s['iban'] }}@endif
                @if ($s['swift'])&nbsp;&nbsp;<span class="muted">SWIFT:</span> {{ $s['swift'] }}@endif
                &nbsp;&nbsp;<span class="muted">{{ __('invoices::invoices.variable_symbol') }}:</span> {{ $invoice->variable_symbol }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>{{ __('invoices::invoices.item') }}</th>
                <th class="r">{{ __('invoices::invoices.quantity') }}</th>
                <th class="r">{{ __('invoices::invoices.unit_price') }}</th>
                @if ($invoice->vat_payer)<th class="r">{{ __('invoices::invoices.vat_rate') }}</th>@endif
                <th class="r">{{ __('invoices::invoices.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr>
                    <td>{{ $line['name'] }}</td>
                    <td class="r">{{ $line['quantity'] }}</td>
                    <td class="r">{{ $money($line['unit_price']) }}</td>
                    @if ($invoice->vat_payer)<td class="r">{{ $rate((float) $line['tax_rate']) }}</td>@endif
                    <td class="r">{{ $money($line['total']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="grid" style="margin-top: 16px">
        <tr>
            <td style="width: 55%">
                @if ($qr)
                    <div class="label">{{ __('invoices::invoices.pay_by_qr') }}</div>
                    <img src="{{ $qr }}" alt="QR" style="width: 110px; height: 110px">
                @endif
            </td>
            <td style="width: 45%">
                @if ($invoice->vat_payer && $invoice->vat_breakdown)
                    <table class="grid" style="font-size: 8.5px">
                        <tr class="muted">
                            <td>{{ __('invoices::invoices.vat_rate') }}</td>
                            <td class="r">{{ __('invoices::invoices.tax_base') }}</td>
                            <td class="r">{{ __('invoices::invoices.vat') }}</td>
                            <td class="r">{{ __('invoices::invoices.total') }}</td>
                        </tr>
                        @foreach ($invoice->vat_breakdown as $row)
                            <tr>
                                <td>{{ $rate((float) $row['rate']) }}</td>
                                <td class="r">{{ $money($row['net']) }}</td>
                                <td class="r">{{ $money($row['vat']) }}</td>
                                <td class="r">{{ $money($row['gross']) }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
                <table class="grid" style="margin-top: 10px; border-top: 1px solid #d4d4d8">
                    <tr>
                        <td style="padding-top: 8px">{{ $invoice->type === \Modules\Invoices\InvoiceType::CreditNote ? __('invoices::invoices.to_refund') : ($invoice->order?->isPaid() && $invoice->type === \Modules\Invoices\InvoiceType::Invoice ? __('invoices::invoices.paid') : __('invoices::invoices.to_pay')) }}</td>
                        <td class="r total" style="padding-top: 8px">{{ $money($invoice->type === \Modules\Invoices\InvoiceType::Invoice && $invoice->order?->isPaid() ? $invoice->total : abs($invoice->total)) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ $s['name'] }}@if ($s['email']) · {{ $s['email'] }}@endif @if ($s['phone']) · {{ $s['phone'] }}@endif
        @if ($invoice->note)<br>{!! nl2br(e($invoice->note)) !!}@endif
    </div>
</body>
</html>
