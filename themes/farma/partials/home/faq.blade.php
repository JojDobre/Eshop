{{-- Časté otázky (odpovede môžu obsahovať formátovaný text). --}}
@if ($faqItems->isNotEmpty())
    @php($contactUrl = app(\Modules\Storefront\Pages\LegalPages::class)->url(\Modules\Storefront\Pages\LegalPages::CONTACT) ?? ($shop->contact_email ? 'mailto:'.$shop->contact_email : null))
    <section class="{{ $layout === 'full' ? 'container-shop' : $wrap }} farma-section grid gap-6 py-14 sm:py-20 {{ $layout === 'narrow' ? '' : 'lg:grid-cols-[1fr_1.5fr] lg:gap-16' }}" aria-labelledby="home-faq">
        <div @class(['lg:order-2' => $reverse && $layout !== 'narrow'])>
            <h2 id="home-faq" class="farma-h2">{{ __('theme::farma.faq_title') }}</h2>
            <p class="mt-3 text-muted">{{ __('theme::farma.faq_text') }}</p>
            @if ($contactUrl)
                <a href="{{ $contactUrl }}" class="btn btn-primary mt-6">{{ __('theme::shop.contact') }}</a>
            @endif
        </div>
        <div class="divide-y divide-[var(--farma-line)] border-y border-line">
            @foreach ($faqItems as $index => $faqItem)
                <details class="group py-5" @if ($index === 0) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-extrabold transition-colors hover:text-accent">
                        {{ $faqItem['q'] }}
                        <span class="text-2xl leading-none text-accent transition group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <div class="farma-rich mt-2 text-zinc-600">{{ safe_html($faqItem['a']) }}</div>
                </details>
            @endforeach
        </div>
    </section>
@endif
