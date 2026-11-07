<x-filament-panels::page>
    @if (! $this->isConfigured())
        <x-filament::section>
            <p style="font-size: 0.875rem;">{{ __('translator::admin.bulk.not_configured') }}</p>
        </x-filament::section>
    @endif

    <x-filament::section>
        {{ $this->form }}

        @if ($estimate)
            <p style="margin-top: 1rem; font-size: 0.875rem;">
                {{ __('translator::admin.bulk.estimate_result', ['records' => $estimate['records'], 'characters' => number_format($estimate['characters'], 0, ',', ' ')]) }}
                @if (($remaining = $this->remaining()) !== null)
                    {{ __('translator::admin.bulk.remaining', ['remaining' => number_format($remaining, 0, ',', ' ')]) }}
                @endif
            </p>
        @endif
    </x-filament::section>

    {{-- Kým je stránka otvorená, preklad beží po dávkach každé 3 sekundy. --}}
    <div @if ($this->pendingCount() > 0) wire:poll.3s="processBatch" @endif>
    <x-filament::section :heading="__('translator::admin.bulk.progress')">
        <p style="font-size: 0.875rem;">
            @if ($this->pendingCount() > 0)
                {{ __('translator::admin.bulk.pending', ['count' => $this->pendingCount()]) }}
            @else
                {{ __('translator::admin.bulk.idle') }}
            @endif
        </p>
        @if ($error = $this->lastError())
            <p style="margin-top: 0.5rem; font-size: 0.75rem; color: rgb(220 38 38);">{{ $error }}</p>
        @endif
    </x-filament::section>
    </div>
</x-filament-panels::page>
