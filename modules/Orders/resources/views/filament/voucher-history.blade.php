<div class="space-y-2 text-sm">
    <p>{{ __('orders::admin.vouchers.balance') }}: <strong>{{ format_price($voucher->balance, $voucher->currency) }}</strong> / {{ format_price($voucher->initial_amount, $voucher->currency) }}</p>
    @forelse ($usages as $usage)
        <div class="flex justify-between border-t border-gray-200 pt-2 dark:border-white/10">
            <span>{{ $usage->created_at?->format('j. n. Y H:i') }} · {{ $usage->amount < 0 ? __('orders::admin.vouchers.refund') : __('orders::admin.vouchers.use') }} @if ($usage->order) ({{ $usage->order->number }}) @endif</span>
            <span>{{ $usage->amount < 0 ? '+' : '−' }}{{ format_price(abs($usage->amount), $voucher->currency) }}</span>
        </div>
    @empty
        <p class="text-gray-500">{{ __('orders::admin.vouchers.no_usage') }}</p>
    @endforelse
</div>
