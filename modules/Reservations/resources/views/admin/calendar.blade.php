@php($t = fn ($key) => __('reservations::admin.'.$key))
<x-filament-panels::page>
    <div class="flex flex-wrap items-center gap-2">
        <x-filament::button color="gray" size="sm" wire:click="move(-1)" icon="heroicon-m-chevron-left" :label-sr-only="true">{{ $t('previous') }}</x-filament::button>
        <x-filament::button color="gray" size="sm" wire:click="today">{{ $t('today') }}</x-filament::button>
        <x-filament::button color="gray" size="sm" wire:click="move(1)" icon="heroicon-m-chevron-right" :label-sr-only="true">{{ $t('next') }}</x-filament::button>
        <h2 class="text-lg font-semibold" style="margin-inline: .5rem">{{ $label }}</h2>
        <div style="margin-inline-start: auto" class="flex gap-2">
            <select wire:model.live="resource" class="fi-select-input rounded-lg text-sm" aria-label="{{ $t('resource') }}">
                <option value="">{{ $t('all_resources') }}</option>
                @foreach ($resources as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            <x-filament::tabs>
                @foreach (['day', 'week', 'month'] as $view)
                    <x-filament::tabs.item :active="$mode === $view" wire:click="$set('mode', '{{ $view }}')">{{ $t('views.'.$view) }}</x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat({{ $mode === 'day' ? 1 : 7 }}, minmax(0, 1fr)); gap: .5rem">
        @foreach ($days as $day)
            @php($items = $byDay[$day->toDateString()])
            <div @class(['rounded-lg border p-2 dark:border-white/10', 'opacity-50' => $mode === 'month' && $day->month !== $month]) style="min-height: {{ $mode === 'month' ? '6rem' : '12rem' }}; {{ $day->isToday() ? 'border-color: rgb(var(--primary-500, 59 130 246))' : '' }}">
                <div class="mb-1 text-xs font-medium text-gray-500">{{ $t('days.'.$day->dayOfWeekIso) }} {{ $day->format('j. n.') }}</div>
                @forelse ($items as $booking)
                    <a href="{{ \Modules\Reservations\Filament\Resources\Bookings\BookingResource::getUrl('index', ['tableSearch' => $booking->number]) }}" class="mb-1 block rounded px-1.5 py-1 text-xs" style="background: {{ $booking->status === 'pending' ? 'rgba(234,179,8,.15)' : 'rgba(34,197,94,.15)' }}">
                        <strong>{{ $booking->product_id ? '' : $booking->starts_at->format('H:i') }}</strong>
                        {{ $booking->title() }}
                        <span class="block text-gray-500">{{ $booking->customer_name }}@if ($booking->resource) · {{ $booking->resource->name }}@endif</span>
                    </a>
                @empty
                    @if ($mode !== 'month')<p class="text-xs text-gray-400">{{ $t('no_bookings') }}</p>@endif
                @endforelse
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
