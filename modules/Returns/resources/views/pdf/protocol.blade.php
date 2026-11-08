<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #18181b; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: left; padding: 5px 6px; border-bottom: 1px solid #e4e4e7; vertical-align: top; }
        th { width: 32%; color: #52525b; font-weight: normal; }
        .muted { color: #71717a; }
        .sign { margin-top: 48px; width: 100%; }
        .sign td { border: none; padding-top: 40px; text-align: center; }
        .line { border-top: 1px solid #a1a1aa; padding-top: 4px; }
    </style>
</head>
<body>
    <h1>{{ __('returns::admin.protocol_title.'.$request->type->value) }} {{ $request->number }}</h1>
    <p class="muted">{{ $shop['name'] }} · {{ $shop['address'] }} · {{ $shop['email'] }}</p>

    <table>
        <tr><th>{{ __('returns::admin.order') }}</th><td>{{ $request->order->number }} ({{ $request->order->created_at->format('j. n. Y') }})</td></tr>
        <tr><th>{{ __('returns::admin.customer') }}</th><td>{{ $request->order->billing_name }}<br>{{ $request->order->billing_street }}, {{ $request->order->billing_zip }} {{ $request->order->billing_city }}<br>{{ $request->order->email }} · {{ $request->order->phone }}</td></tr>
        <tr><th>{{ __('returns::admin.created_at') }}</th><td>{{ $request->created_at->format('j. n. Y H:i') }}</td></tr>
        <tr><th>{{ __('returns::admin.items') }}</th><td>{!! nl2br(e($request->itemsSummary())) !!}</td></tr>
        <tr><th>{{ __('returns::admin.reason') }}</th><td>{!! nl2br(e($request->reason ?: '—')) !!}</td></tr>
        @if ($request->preferred_resolution)
            <tr><th>{{ __('returns::admin.preferred_resolution') }}</th><td>{{ $request->preferred_resolution->getLabel() }}</td></tr>
        @endif
        <tr><th>{{ __('returns::admin.deadline_at') }}</th><td>{{ $request->deadline_at->format('j. n. Y') }}</td></tr>
        <tr><th>{{ __('returns::admin.status') }}</th><td>{{ $request->status->getLabel() }}</td></tr>
        @if ($request->outcome)
            <tr><th>{{ __('returns::admin.outcome') }}</th><td>{{ \Modules\Returns\ReturnOutcome::from($request->outcome)->getLabel() }}@if ($request->refund_amount) – {{ format_price($request->refund_amount, $request->order->currency) }}@endif</td></tr>
        @endif
        <tr><th>{{ __('returns::admin.resolution') }}</th><td>{!! nl2br(e($request->resolution ?: '—')) !!}</td></tr>
        @if ($request->resolved_at)
            <tr><th>{{ __('returns::admin.resolved_at') }}</th><td>{{ $request->resolved_at->format('j. n. Y') }}</td></tr>
        @endif
    </table>

    <table class="sign">
        <tr>
            <td><div class="line">{{ __('returns::admin.signature_shop') }}</div></td>
            <td><div class="line">{{ __('returns::admin.signature_customer') }}</div></td>
        </tr>
    </table>
    <p class="muted">{{ __('returns::admin.protocol_generated', ['date' => now()->format('j. n. Y H:i')]) }}</p>
</body>
</html>
