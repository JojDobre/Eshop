{{-- Doplnkové voľby k produktu (darčekové balenie, gravírovanie…) s príplatkom. --}}
@php($productAddons = $product->availableAddons())
@if ($productAddons->isNotEmpty())
    <fieldset class="space-y-3">
        <legend class="text-sm font-medium">{{ __('theme::shop.addons') }}</legend>
        @foreach ($productAddons as $addon)
            <div x-data="{ on: @js((bool) old('addons.'.$addon->id)) }" class="rounded-xl border border-zinc-200 p-3">
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="addons[{{ $addon->id }}]" value="1" x-model="on" class="mt-0.5 size-4 rounded border-zinc-300 accent-[var(--brand-primary)]">
                    <span class="flex-1">
                        <span class="font-medium">{{ $addon->name }}</span>
                        @if ($addon->priceIn() > 0)
                            <span class="text-zinc-500">+{{ format_price($addon->priceIn()) }}</span>
                        @endif
                        @if ($addon->description)
                            <span class="block text-xs text-zinc-500">{{ $addon->description }}</span>
                        @endif
                    </span>
                </label>
                @if ($addon->needs_text)
                    <div x-show="on" x-cloak class="mt-2 pl-7">
                        <label for="addon-text-{{ $addon->id }}" class="sr-only">{{ $addon->text_label ?: __('theme::shop.addon_text') }}</label>
                        <input id="addon-text-{{ $addon->id }}" type="text" name="addon_texts[{{ $addon->id }}]" maxlength="{{ $addon->text_max_length }}"
                               value="{{ old('addon_texts.'.$addon->id) }}" placeholder="{{ $addon->text_label ?: __('theme::shop.addon_text') }}" class="input">
                    </div>
                @endif
            </div>
        @endforeach
    </fieldset>
@endif
