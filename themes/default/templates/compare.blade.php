@extends('theme::layouts.app')

@section('title', __('catalog::shop.compare_title'))
@section('noindex', true)

@push('head')
    <style>
        @media print {
            header, footer, .compare-tools, .compare-actions { display: none !important; }
            .compare-scroll { overflow: visible !important; }
        }
    </style>
@endpush

@section('content')
    <div class="container-shop py-8" x-data="{ onlyDiff: false, hidden: [], picker: false }">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-3xl font-semibold tracking-tight">{{ __('catalog::shop.compare_title') }}</h1>
            @if ($groups !== [])
                <div class="compare-tools flex flex-wrap items-center gap-3 text-sm">
                    <label class="inline-flex items-center gap-2"><input type="checkbox" x-model="onlyDiff" class="size-4 accent-[var(--brand-primary)]"> {{ __('catalog::shop.compare_only_differences') }}</label>
                    <button type="button" class="btn btn-secondary py-1.5" @click="picker = ! picker" :aria-expanded="picker.toString()">{{ __('catalog::shop.compare_choose') }}</button>
                    @if ($shareUrl)
                        <button type="button" class="btn btn-secondary py-1.5" x-data="{ copied: false }" @click="navigator.clipboard?.writeText(@js($shareUrl)); copied = true; setTimeout(() => copied = false, 2000)">
                            <span x-show="! copied">{{ __('catalog::shop.compare_share') }}</span><span x-show="copied" x-cloak>{{ __('catalog::shop.compare_copied') }}</span>
                        </button>
                    @endif
                    <button type="button" class="btn btn-secondary py-1.5" onclick="window.print()">{{ __('catalog::shop.compare_print') }}</button>
                    @unless ($shared)
                        <form method="POST" action="{{ route('compare.clear') }}">@csrf @method('DELETE')<button type="submit" class="text-zinc-600 underline underline-offset-2 hover:text-zinc-900">{{ __('catalog::shop.compare_clear') }}</button></form>
                    @endunless
                </div>
            @endif
        </div>

        @if ($groups === [])
            <p class="mt-8 text-zinc-600">{{ __('catalog::shop.compare_empty') }}</p>
        @endif

        @foreach ($groups as $index => $group)
            <section class="mt-10" aria-label="{{ $group['type'] ?? __('catalog::shop.compare_title') }}">
                @if (count($groups) > 1)
                    <h2 class="text-xl font-semibold">{{ $group['type'] ?? __('catalog::shop.compare_other') }}</h2>
                @endif

                {{-- Výber vlastností podľa skupín. --}}
                <div x-show="picker" x-cloak class="compare-tools mt-4 grid gap-4 rounded-xl border border-zinc-200 p-4 text-sm sm:grid-cols-3">
                    @foreach (collect($group['rows'])->groupBy('group') as $groupName => $rows)
                        <fieldset>
                            <legend class="font-medium">{{ $groupName }}</legend>
                            @foreach ($rows as $row)
                                <label class="mt-1 flex items-center gap-2">
                                    <input type="checkbox" class="size-4 accent-[var(--brand-primary)]" :checked="! hidden.includes('{{ $row['key'] }}')"
                                           @change="hidden = $event.target.checked ? hidden.filter(k => k !== '{{ $row['key'] }}') : [...hidden, '{{ $row['key'] }}']">
                                    {{ $row['label'] }}
                                </label>
                            @endforeach
                        </fieldset>
                    @endforeach
                </div>

                {{-- Na mobile 2 produkty na šírku obrazovky, ďalšie sa posúvajú; na počítači do 4. --}}
                <div class="compare-scroll mt-4 overflow-x-auto">
                    <table class="w-full min-w-full table-fixed border-collapse text-sm" style="min-width: {{ max(2, $group['products']->count()) * 50 }}%">
                        <thead class="sticky top-16 z-10 bg-white">
                            <tr>
                                <th scope="col" class="w-1/4 min-w-[8rem] p-3 text-left align-bottom text-zinc-500 max-sm:hidden">{{ __('catalog::shop.compare_property') }}</th>
                                @foreach ($group['products'] as $product)
                                    <th scope="col" class="p-3 text-left align-top font-normal">
                                        <a href="{{ $product->url() }}" class="block">
                                            <span class="block aspect-square max-w-[9rem] overflow-hidden rounded-lg bg-zinc-100">@include('theme::partials.product-image', ['url' => $product->mainImageUrl(), 'alt' => $product->name])</span>
                                            <span class="mt-2 block font-medium text-zinc-900 hover:underline">{{ $product->name }}</span>
                                        </a>
                                        <span class="mt-1 block font-semibold">{{ ($price = $product->priceFrom()) !== null ? format_price($product->displayPrice($price)) : __('theme::shop.price_unavailable') }}</span>
                                        <div class="compare-actions mt-2 flex flex-wrap items-center gap-2">
                                            @if (Route::has('cart.add') && $product->isAvailable() && ! $product->hasVariants())
                                                <form method="POST" action="{{ route('cart.add') }}">@csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><button type="submit" class="btn btn-primary py-1 text-xs">{{ __('theme::shop.add_to_cart') }}</button></form>
                                            @else
                                                <a href="{{ $product->url() }}" class="btn btn-secondary py-1 text-xs">{{ __('catalog::shop.compare_detail') }}</a>
                                            @endif
                                            @unless ($shared)
                                                <form method="POST" action="{{ route('compare.remove', $product->id) }}">@csrf @method('DELETE')<button type="submit" class="text-xs text-zinc-500 underline hover:text-zinc-900" aria-label="{{ __('catalog::shop.compare_remove', ['product' => $product->name]) }}">{{ __('catalog::shop.compare_remove_short') }}</button></form>
                                            @endunless
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        @foreach (collect($group['rows'])->groupBy('group') as $groupName => $rows)
                            <tbody>
                                <tr><th colspan="{{ $group['products']->count() + 1 }}" scope="colgroup" class="bg-zinc-50 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $groupName }}</th></tr>
                                @foreach ($rows as $row)
                                    <tr class="border-b border-zinc-100 {{ $row['differs'] ? 'compare-differs' : '' }}"
                                        x-show="! hidden.includes('{{ $row['key'] }}') && (! onlyDiff || {{ $row['differs'] ? 'true' : 'false' }})">
                                        <th scope="row" class="p-3 text-left font-normal text-zinc-500 max-sm:hidden">{{ $row['label'] }}</th>
                                        @foreach ($group['products'] as $product)
                                            @php($isBest = in_array($product->id, $row['best'], true))
                                            <td @class(['p-3 align-top', 'bg-amber-50' => $row['differs'] && ! $isBest, 'bg-emerald-50 font-semibold text-emerald-800' => $isBest])>
                                                <span class="block text-xs text-zinc-500 sm:hidden">{{ $row['label'] }}</span>
                                                {{ $row['values'][$product->id] ?? '—' }}
                                                @if ($isBest)<span class="sr-only">({{ __('catalog::shop.compare_best') }})</span>@endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        @endforeach
                    </table>
                </div>
                <p class="mt-2 text-xs text-zinc-500">
                    <span class="inline-block size-3 rounded-sm bg-amber-50 align-middle ring-1 ring-amber-200"></span> {{ __('catalog::shop.compare_legend_diff') }}
                    <span class="ml-3 inline-block size-3 rounded-sm bg-emerald-50 align-middle ring-1 ring-emerald-200"></span> {{ __('catalog::shop.compare_legend_best') }}
                </p>
            </section>
        @endforeach
    </div>
@endsection
