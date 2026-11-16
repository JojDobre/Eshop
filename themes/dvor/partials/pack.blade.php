{{-- Balenie produktu z typu produktu a množstva (napr. „250 g“, „1 l“, „10 ks“); bez typu sa nič nevypíše. --}}
@php
    $packType = \Modules\Catalog\CatalogFeatures::enabled('product_types') ? $product->productType : null;
    $packAmount = (float) $product->unit_amount;
    $packNumber = fn (float $value) => rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',');
    $packLabel = $packType && $packAmount > 0 ? match ($packType->unit) {
        'kg' => $packAmount < 1 ? $packNumber($packAmount * 1000).' g' : $packNumber($packAmount).' kg',
        'l' => $packAmount < 1 ? $packNumber($packAmount * 1000).' ml' : $packNumber($packAmount).' l',
        'pcs' => __('theme::dvor.pieces', ['count' => $packNumber($packAmount)]),
        default => $packNumber($packAmount).' '.$packType->unit,
    } : null;
@endphp
@if ($packLabel){{ $packLabel }}@endif
