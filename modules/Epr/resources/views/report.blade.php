<x-filament-panels::page>
    {{ $this->form }}

    @if ($report = $this->report)
        <x-filament::section :heading="__('epr::admin.result', ['orders' => $report['orders']])">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-2 pr-4 font-medium">{{ __('epr::admin.material') }}</th>
                            <th class="py-2 pr-4 text-right font-medium">{{ __('epr::admin.products_kg') }}</th>
                            <th class="py-2 pr-4 text-right font-medium">{{ __('epr::admin.shipments_kg') }}</th>
                            <th class="py-2 text-right font-medium">{{ __('epr::admin.total_kg') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($report['materials'] as $material => $values)
                            <tr>
                                <td class="py-2 pr-4">{{ __('epr::admin.materials.'.$material) }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ number_format($values['products'] / 1000, 3, ',', ' ') }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ number_format($values['shipments'] / 1000, 3, ',', ' ') }}</td>
                                <td class="py-2 text-right font-semibold tabular-nums">{{ number_format($values['total'] / 1000, 3, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
