{{-- Prihlásenie na newsletter (päta obchodu). Súhlas sa potvrdzuje e-mailom. --}}
<form method="POST" action="{{ route('newsletter.subscribe') }}" class="space-y-2">
    @csrf
    <p class="font-medium">{{ __('marketing::shop.newsletter_title') }}</p>
    <div class="hidden" aria-hidden="true"><label>Web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <div class="flex gap-2">
        <label for="newsletter-email" class="sr-only">{{ __('marketing::shop.email') }}</label>
        <input id="newsletter-email" type="email" name="email" required placeholder="{{ __('marketing::shop.email') }}" class="input py-1.5" autocomplete="email">
        <button type="submit" class="btn btn-primary py-1.5">{{ __('marketing::shop.newsletter_submit') }}</button>
    </div>
    <label class="flex items-start gap-2 text-xs text-zinc-600">
        <input type="checkbox" name="consent" value="1" required class="mt-0.5 size-3.5 accent-[var(--brand-primary)]">
        <span>{{ __('marketing::shop.newsletter_consent') }}</span>
    </label>
</form>
