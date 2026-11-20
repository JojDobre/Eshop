{{-- Najpredávanejšie produkty za 30 dní s pruhom podielu. --}}
<x-filament-widgets::widget>
    <x-filament::section :heading="__('orders::admin.dashboard.top_products')" :description="__('orders::admin.dashboard.last_days', ['days' => 30])">
        @php($rows = $this->rows())
        @if ($rows === [])
            <p class="admin-widget-empty">{{ __('orders::admin.dashboard.no_sales') }}</p>
        @else
            <ol class="admin-rank">
                @foreach ($rows as $index => $row)
                    <li>
                        <span class="admin-rank-pos">{{ $index + 1 }}</span>
                        <div class="admin-rank-main">
                            <div class="admin-rank-line">
                                @if ($row['url'])
                                    <a href="{{ $row['url'] }}" class="admin-rank-name">{{ $row['name'] }}</a>
                                @else
                                    <span class="admin-rank-name">{{ $row['name'] }}</span>
                                @endif
                                <span class="admin-rank-value">{{ __('orders::admin.dashboard.pieces', ['count' => $row['quantity']]) }}</span>
                            </div>
                            <span class="admin-rank-bar"><span style="width: {{ max(4, round($row['share'] * 100)) }}%"></span></span>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
