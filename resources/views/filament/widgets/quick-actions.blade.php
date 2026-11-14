<x-filament-widgets::widget>
    <div class="admin-quick-actions">
        @foreach ($this->tiles() as $tile)
            <a href="{{ $tile['url'] }}" @class(['admin-quick-tile', 'admin-quick-tile-accent' => $tile['accent'] ?? false])>
                <x-filament::icon :icon="$tile['icon']" class="admin-quick-icon" />
                <span class="admin-quick-label">{{ $tile['label'] }}</span>
                @if (($tile['count'] ?? 0) > 0)
                    <span class="admin-quick-count">{{ $tile['count'] }}</span>
                @endif
            </a>
        @endforeach
    </div>
</x-filament-widgets::widget>
