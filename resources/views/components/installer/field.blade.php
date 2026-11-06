@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'required' => true, 'autocomplete' => 'off'])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="block text-sm font-medium text-zinc-800">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $type === 'password' ? '' : old($name, $value) }}"
        autocomplete="{{ $autocomplete }}"
        @if ($required) required @endif
        @if ($help) aria-describedby="{{ $name }}-help" @endif
        @error($name) aria-invalid="true" @enderror
        {{ $attributes->except('class') }}
        class="mt-1.5 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-xs outline-none transition focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10 aria-invalid:border-red-500"
    >
    @if ($help)
        <p id="{{ $name }}-help" class="mt-1 text-xs text-zinc-500">{{ $help }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
