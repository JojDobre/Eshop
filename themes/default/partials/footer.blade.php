<footer class="border-t border-zinc-100 bg-zinc-50">
    <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 text-sm text-zinc-600 sm:grid-cols-2 sm:px-6">
        <div>
            <p class="font-semibold text-zinc-900">{{ $company->company_name ?: $shop->shop_name }}</p>
            @if ($company->hasAddress())
                <address class="mt-2 not-italic">
                    {{ $company->street }}<br>
                    {{ trim($company->zip.' '.$company->city) }}
                </address>
            @endif
            @if ($company->company_id)
                <p class="mt-2">{{ __('theme::shop.company_id') }}: {{ $company->company_id }}</p>
            @endif
        </div>
        <div class="sm:text-right">
            <p><a href="mailto:{{ $shop->contact_email }}" class="hover:text-zinc-900">{{ $shop->contact_email }}</a></p>
            @if ($shop->contact_phone)
                <p class="mt-1"><a href="tel:{{ preg_replace('/\s+/', '', $shop->contact_phone) }}" class="hover:text-zinc-900">{{ $shop->contact_phone }}</a></p>
            @endif
        </div>
    </div>
    <div class="border-t border-zinc-100 py-4 text-center text-xs text-zinc-400">
        © {{ date('Y') }} {{ $company->company_name ?: $shop->shop_name }}
    </div>
</footer>
