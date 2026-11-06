@props(['name', 'label', 'options' => [], 'value' => null])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="block text-sm font-medium text-zinc-800">{{ $label }}</label>
    <select id="{{ $name }}" name="{{ $name }}"
        class="mt-1.5 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-xs outline-none focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10">
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
