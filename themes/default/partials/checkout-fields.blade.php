{{-- Doplňujúce údaje k objednávke (vlastné polia objednávky z administrácie). --}}
@if (! empty($checkoutFields) && $checkoutFields->isNotEmpty())
    <fieldset class="space-y-4">
        <legend class="text-lg font-semibold">{{ __('theme::shop.additional_details') }}</legend>
        @foreach ($checkoutFields as $field)
            @php
                $name = 'custom_fields['.$field->key.']';
                $key = 'custom_fields.'.$field->key;
                $id = 'custom_field_'.$field->key;
                $type = $field->type;
            @endphp
            <div>
                @if ($type === \Modules\Catalog\CustomFields\CustomFieldType::Boolean)
                    <label class="flex items-center gap-3 text-sm">
                        <input type="hidden" name="{{ $name }}" value="0">
                        <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" @checked(old($key)) class="size-4 rounded border-zinc-300 accent-[var(--brand-primary)]">
                        {{ $field->name }}
                    </label>
                @else
                    <label for="{{ $id }}" class="label">{{ $field->name }}@unless ($field->is_required) <span class="font-normal text-zinc-500">({{ __('theme::shop.optional') }})</span>@endunless</label>
                    @switch($type)
                        @case(\Modules\Catalog\CustomFields\CustomFieldType::Textarea)
                            <textarea id="{{ $id }}" name="{{ $name }}" rows="3" maxlength="2000" class="input mt-1.5">{{ old($key) }}</textarea>
                            @break
                        @case(\Modules\Catalog\CustomFields\CustomFieldType::Select)
                            <select id="{{ $id }}" name="{{ $name }}" class="input mt-1.5">
                                <option value="">—</option>
                                @foreach ($field->optionLabels() as $optionKey => $optionLabel)
                                    <option value="{{ $optionKey }}" @selected(old($key) === $optionKey)>{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                            @break
                        @case(\Modules\Catalog\CustomFields\CustomFieldType::Date)
                            <input id="{{ $id }}" type="date" name="{{ $name }}" value="{{ old($key) }}" class="input mt-1.5">
                            @break
                        @case(\Modules\Catalog\CustomFields\CustomFieldType::Number)
                            <input id="{{ $id }}" type="text" inputmode="decimal" name="{{ $name }}" value="{{ old($key) }}" class="input mt-1.5">
                            @break
                        @default
                            <input id="{{ $id }}" type="text" name="{{ $name }}" value="{{ old($key) }}" maxlength="255" class="input mt-1.5">
                    @endswitch
                @endif
                @if ($field->help)
                    <p class="mt-1 text-xs text-zinc-500">{{ $field->help }}</p>
                @endif
                @error($key) <p class="field-error">{{ $message }}</p> @enderror
            </div>
        @endforeach
    </fieldset>
@endif
