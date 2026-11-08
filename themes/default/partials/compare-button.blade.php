{{-- Pridanie do porovnávača (alebo odkaz, ak už je pridaný). --}}
@if (in_array($product->id, $compareIds ?? [], true))
    <a href="{{ route('compare.show') }}" class="mt-4 inline-flex items-center gap-2 text-sm text-zinc-700 underline underline-offset-2 hover:text-zinc-900">{{ __('catalog::shop.compare_in_list') }}</a>
@else
    <form method="POST" action="{{ route('compare.add') }}" class="mt-4">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <button type="submit" class="inline-flex items-center gap-2 text-sm text-zinc-700 underline underline-offset-2 hover:text-zinc-900">{{ __('catalog::shop.compare_add') }}</button>
    </form>
@endif
