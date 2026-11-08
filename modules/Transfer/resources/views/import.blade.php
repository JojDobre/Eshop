<x-filament-panels::page>
    @php($import = $this->import)

    @if (! $import)
        <form wire:submit="upload" style="display: grid; gap: 1rem;">
            {{ $this->form }}
            <div><x-filament::button type="submit">{{ __('transfer::admin.import.continue') }}</x-filament::button></div>
        </form>
    @elseif ($import->status === 'mapping')
        <x-filament::section :heading="__('transfer::admin.import.mapping')" :description="__('transfer::admin.import.mapping_help', ['rows' => $import->total_rows])">
            <div style="display: grid; gap: .5rem; grid-template-columns: repeat(auto-fill, minmax(16rem, 1fr));">
                @foreach ($mapping as $column => $field)
                    <label style="display: grid; gap: .25rem;">
                        <span style="font-weight: 600;">{{ $column }}</span>
                        <select wire:model.live="mapping.{{ $column }}" class="fi-select-input" style="border: 1px solid rgba(128,128,128,.4); border-radius: .5rem; padding: .375rem;">
                            <option value="">{{ __('transfer::admin.import.skip') }}</option>
                            @foreach ($this->fields as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section :heading="__('transfer::admin.import.preview')">
            <div style="overflow-x: auto;">
                <table style="width: 100%; font-size: .875rem; border-collapse: collapse;">
                    <thead><tr>
                        <th style="text-align: left; padding: .25rem .5rem;">#</th>
                        @foreach (array_filter($mapping) as $field)<th style="text-align: left; padding: .25rem .5rem;">{{ $this->fields[$field] ?? $field }}</th>@endforeach
                        <th style="text-align: left; padding: .25rem .5rem;">{{ __('transfer::admin.import.check') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($this->preview as $row)
                            <tr style="border-top: 1px solid rgba(128,128,128,.2);">
                                <td style="padding: .25rem .5rem;">{{ $row['row'] }}</td>
                                @foreach (array_filter($mapping) as $field)<td style="padding: .25rem .5rem;">{{ \Illuminate\Support\Str::limit($row['data'][$field] ?? '', 40) }}</td>@endforeach
                                <td style="padding: .25rem .5rem; color: {{ $row['errors'] ? '#dc2626' : '#16a34a' }};">{{ $row['errors'] ? implode(' ', $row['errors']) : __('transfer::admin.import.ok') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <div style="display: flex; gap: .5rem;">
            <x-filament::button wire:click="start">{{ __('transfer::admin.import.start') }}</x-filament::button>
            <x-filament::button color="gray" wire:click="restart">{{ __('transfer::admin.import.cancel') }}</x-filament::button>
        </div>
    @else
        <x-filament::section :heading="__('transfer::admin.import.progress')">
            <div @if ($import->status === 'running') wire:poll.750ms="continueImport" @endif style="display: grid; gap: .5rem;">
                @php($percent = $import->total_rows ? (int) round($import->processed_rows / $import->total_rows * 100) : 100)
                <div style="height: .5rem; background: rgba(128,128,128,.2); border-radius: 9999px; overflow: hidden;"><div style="height: 100%; width: {{ $percent }}%; background: #6366f1;"></div></div>
                <p>{{ __('transfer::admin.import.progress_text', ['processed' => $import->processed_rows, 'total' => $import->total_rows, 'created' => $import->created_count, 'updated' => $import->updated_count]) }}</p>
                @if ($import->errors)
                    <ul style="color: #dc2626; font-size: .875rem; list-style: disc; padding-left: 1.25rem;">
                        @foreach ($import->errors as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                @endif
                @if ($import->status === 'done')
                    <div><x-filament::button color="gray" wire:click="restart">{{ __('transfer::admin.import.new') }}</x-filament::button></div>
                @endif
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
