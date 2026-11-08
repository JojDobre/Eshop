<ul style="display: grid; gap: .5rem; font-size: .875rem;">
    @foreach ($results as $result)
        <li style="display: flex; gap: .5rem; align-items: baseline;">
            <x-filament::badge :color="['error' => 'danger', 'warning' => 'warning', 'ok' => 'success'][$result['level']]">
                {{ __('admin.theme_check.levels.'.$result['level']) }}
            </x-filament::badge>
            <span>{{ $result['message'] }}</span>
        </li>
    @endforeach
</ul>
