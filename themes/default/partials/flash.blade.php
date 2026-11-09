{{--
    Plávajúce upozornenia (vpravo dole, na mobile dole na celú šírku). Správy z relácie sa zobrazia po načítaní
    stránky, ďalšie posiela JavaScript udalosťou „shop-toast“ (napr. pridanie do košíka bez obnovenia stránky).
--}}
@php
    $flashToasts = [];
    if ($flashAdded = session('cart_added')) {
        $flashToasts[] = ['type' => 'success', 'message' => __('theme::shop.added_to_cart', ['product' => $flashAdded]), 'link' => Route::has('cart.show') ? route('cart.show') : null, 'linkLabel' => __('theme::shop.go_to_cart')];
    }
    if (session('success')) {
        $flashToasts[] = ['type' => 'success', 'message' => session('success')];
    }
    if (session('error')) {
        $flashToasts[] = ['type' => 'error', 'message' => session('error')];
    }
@endphp
<div class="pointer-events-none fixed inset-x-3 bottom-3 z-[60] flex flex-col items-stretch gap-2 sm:inset-x-auto sm:bottom-6 sm:right-6 sm:w-96"
     x-data="shopToasts(@js($flashToasts))" @shop-toast.window="push($event.detail)" aria-live="polite" data-cart-error="{{ __('theme::shop.cart_error') }}">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="translate-y-2 opacity-0"
             :role="toast.type === 'error' ? 'alert' : 'status'"
             class="shop-toast pointer-events-auto flex items-start gap-3 rounded-2xl border bg-white p-4 text-sm shadow-xl shadow-zinc-900/10"
             :class="toast.type === 'error' ? 'border-red-200' : 'border-zinc-200'">
            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full" :class="toast.type === 'error' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'" aria-hidden="true">
                <svg x-show="toast.type !== 'error'" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" /></svg>
                <svg x-show="toast.type === 'error'" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" d="M12 7v6M12 17h.01" /></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-medium text-zinc-900" x-text="toast.message"></p>
                <a x-show="toast.link" :href="toast.link" class="mt-1 inline-block font-semibold text-primary underline underline-offset-2" x-text="toast.linkLabel"></a>
            </div>
            <button type="button" class="-m-1 rounded-full p-1 text-zinc-400 hover:text-zinc-700" @click="dismiss(toast)" aria-label="{{ __('theme::shop.close') }}">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </div>
    </template>
</div>
