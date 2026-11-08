{{-- Formulár hodnotenia: $action, $guest (meno a e-mail), $formId, voliteľne $productId. --}}
<form method="POST" action="{{ $action }}" class="space-y-4 rounded-2xl border border-zinc-200 p-5" id="{{ $formId }}">
    @csrf
    @isset($productId)<input type="hidden" name="product_id" value="{{ $productId }}">@endisset
    <input type="hidden" name="form_token" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) time()) }}">
    <div class="hidden" aria-hidden="true"><label>Web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

    <fieldset>
        <legend class="label">{{ __('reviews::shop.your_rating') }}</legend>
        <div class="mt-1 flex flex-row-reverse justify-end gap-1 text-2xl">
            @for ($i = 5; $i >= 1; $i--)
                <input type="radio" name="rating" value="{{ $i }}" id="{{ $formId }}-r{{ $i }}" class="peer sr-only" @checked((int) old('rating') === $i) required>
                <label for="{{ $formId }}-r{{ $i }}" class="cursor-pointer text-zinc-300 peer-checked:text-amber-500 hover:text-amber-400 peer-hover:text-amber-400" title="{{ __('reviews::shop.stars', ['rating' => $i]) }}">★</label>
            @endfor
        </div>
        @error('rating') <p class="field-error">{{ $message }}</p> @enderror
    </fieldset>

    @if ($guest)
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="{{ $formId }}-name">{{ __('reviews::shop.name') }}</label><input id="{{ $formId }}-name" name="author_name" value="{{ old('author_name') }}" class="input mt-1.5" required maxlength="80" autocomplete="name"></div>
            <div><label class="label" for="{{ $formId }}-email">{{ __('reviews::shop.email') }}</label><input id="{{ $formId }}-email" type="email" name="email" value="{{ old('email') }}" class="input mt-1.5" required autocomplete="email"></div>
        </div>
    @endif
    <div><label class="label" for="{{ $formId }}-title">{{ __('reviews::shop.title') }}</label><input id="{{ $formId }}-title" name="title" value="{{ old('title') }}" class="input mt-1.5" maxlength="120"></div>
    <div><label class="label" for="{{ $formId }}-body">{{ __('reviews::shop.body') }}</label><textarea id="{{ $formId }}-body" name="body" rows="4" class="input mt-1.5" maxlength="3000">{{ old('body') }}</textarea></div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label" for="{{ $formId }}-pros">{{ __('reviews::shop.pros') }}</label><textarea id="{{ $formId }}-pros" name="pros" rows="2" class="input mt-1.5" maxlength="1000">{{ old('pros') }}</textarea></div>
        <div><label class="label" for="{{ $formId }}-cons">{{ __('reviews::shop.cons') }}</label><textarea id="{{ $formId }}-cons" name="cons" rows="2" class="input mt-1.5" maxlength="1000">{{ old('cons') }}</textarea></div>
    </div>
    <button type="submit" class="btn btn-primary">{{ __('reviews::shop.submit') }}</button>
</form>
