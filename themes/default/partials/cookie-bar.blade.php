{{-- Lišta súhlasu s cookies. Odmietnutie je rovnako dostupné ako prijatie. --}}
@if (isset($cookieConsent) && $cookieConsent->isActive())
    @php($categories = $cookieConsent->categories())
    <div x-data="{
            open: @js(! $cookieConsent->hasDecided()),
            details: false,
            choice: { analytics: @js($cookieConsent->allows('analytics')), marketing: @js($cookieConsent->allows('marketing')) },
            save(values) {
                const previous = JSON.stringify(this.choice);
                this.choice = values;
                document.cookie = '{{ \Modules\Storefront\Cookies\CookieConsent::COOKIE }}=' + encodeURIComponent(JSON.stringify(values)) + '; max-age=15552000; path=/; SameSite=Lax';
                this.open = false;
                // Po udelení súhlasu sa stránka načíta znova, aby sa povolené kódy spustili.
                if (JSON.stringify(values) !== previous && (values.analytics || values.marketing)) window.location.reload();
            },
         }"
         x-on:open-cookie-settings.window="open = true; details = true"
         x-show="open" x-cloak
         class="fixed inset-x-0 bottom-0 z-50 p-4 sm:p-6" role="dialog" aria-labelledby="cookie-title" aria-live="polite">
        <div class="mx-auto max-w-3xl rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl">
            <h2 id="cookie-title" class="font-semibold">{{ __('theme::shop.cookies_title') }}</h2>
            <p class="mt-2 text-sm text-zinc-600">
                {{ __('theme::shop.cookies_text') }}
                @if ($privacyUrl = app(\Modules\Storefront\Pages\LegalPages::class)->url(\Modules\Storefront\Pages\LegalPages::PRIVACY))
                    <a href="{{ $privacyUrl }}" class="underline underline-offset-2">{{ __('theme::shop.cookies_more') }}</a>
                @endif
            </p>

            <div x-show="details" x-cloak class="mt-4 space-y-3 text-sm">
                <label class="flex items-start gap-3">
                    <input type="checkbox" checked disabled class="mt-0.5 size-4 rounded border-zinc-300">
                    <span><strong class="font-medium">{{ __('theme::shop.cookies_necessary') }}</strong><br><span class="text-zinc-500">{{ __('theme::shop.cookies_necessary_help') }}</span></span>
                </label>
                @if (in_array('analytics', $categories, true))
                    <label class="flex items-start gap-3">
                        <input type="checkbox" x-model="choice.analytics" class="mt-0.5 size-4 rounded border-zinc-300 accent-[var(--brand-primary)]">
                        <span><strong class="font-medium">{{ __('theme::shop.cookies_analytics') }}</strong><br><span class="text-zinc-500">{{ __('theme::shop.cookies_analytics_help') }}</span></span>
                    </label>
                @endif
                @if (in_array('marketing', $categories, true))
                    <label class="flex items-start gap-3">
                        <input type="checkbox" x-model="choice.marketing" class="mt-0.5 size-4 rounded border-zinc-300 accent-[var(--brand-primary)]">
                        <span><strong class="font-medium">{{ __('theme::shop.cookies_marketing') }}</strong><br><span class="text-zinc-500">{{ __('theme::shop.cookies_marketing_help') }}</span></span>
                    </label>
                @endif
            </div>

            <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="btn btn-secondary" x-show="! details" @click="details = true">{{ __('theme::shop.cookies_settings') }}</button>
                <button type="button" class="btn btn-secondary" x-show="details" x-cloak @click="save({ analytics: choice.analytics, marketing: choice.marketing })">{{ __('theme::shop.cookies_save') }}</button>
                <button type="button" class="btn btn-secondary" @click="save({ analytics: false, marketing: false })">{{ __('theme::shop.cookies_reject') }}</button>
                <button type="button" class="btn btn-primary" @click="save({ analytics: true, marketing: true })">{{ __('theme::shop.cookies_accept') }}</button>
            </div>
        </div>
    </div>
@endif
