{{-- Nezaplatené objednávky s platbou prevodom: koľko dní čakajú a či odišla pripomienka. --}}
<x-filament-widgets::widget>
    @php($data = $this->data())
    <x-filament::section :heading="__('orders::admin.dashboard.unpaid')" :description="trans_choice('orders::admin.dashboard.unpaid_count', $data['count'], ['count' => $data['count']])">
        <x-slot name="afterHeader">
            <x-filament::link :href="$this->settingsUrl()" size="sm" color="gray" icon="heroicon-m-cog-6-tooth">{{ __('orders::admin.dashboard.unpaid_settings') }}</x-filament::link>
        </x-slot>
        @if ($data['rows'] === [])
            <p class="admin-widget-empty">{{ __('orders::admin.dashboard.no_unpaid') }}</p>
        @else
            <ul class="admin-list">
                @foreach ($data['rows'] as $row)
                    <li>
                        <a href="{{ $row['url'] }}" class="admin-list-item">
                            <span class="admin-list-main">
                                <span class="admin-list-title">{{ $row['number'] }} · {{ $row['customer'] }}</span>
                                <span class="admin-list-sub">
                                    {{ trans_choice('orders::admin.dashboard.days_ago', $row['days'], ['count' => $row['days']]) }}
                                    @if ($row['reminded'])
                                        · {{ __('orders::admin.unpaid.reminded') }}
                                    @endif
                                </span>
                            </span>
                            <span class="admin-list-value">{{ $row['total'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
