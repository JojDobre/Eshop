{{-- Formulár z modulu Kontaktné formuláre ($form). Chyby sú v samostatnej skupine, aby sa viac formulárov na stránke neovplyvňovalo. --}}
@php
    $bagName = 'form'.$form->id;
    $bag = $errors->getBag($bagName);
    $useOld = $bag->any();
    $sent = session('form_sent_'.$form->id);
    $privacyUrl = app(\Modules\Storefront\Pages\LegalPages::class)->url(\Modules\Storefront\Pages\LegalPages::PRIVACY);
@endphp
<form id="form-{{ $form->id }}" method="POST" action="{{ route('forms.submit', $form) }}" class="contact-form grid gap-4 sm:grid-cols-2" novalidate>
    @csrf
    <input type="hidden" name="form_token" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) time()) }}">
    {{-- Skryté pole proti robotom (človek ho nevidí ani nevyplní). --}}
    <div class="hidden" aria-hidden="true"><label>Web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

    @if ($sent)
        <div class="sm:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900" role="status">{{ $sent }}</div>
    @endif

    @foreach ($form->formFields() as $field)
        @php
            $id = 'form-'.$form->id.'-'.$field['key'];
            $value = $useOld ? old($field['key']) : null;
            $invalid = $bag->has($field['key']);
            $describedBy = $invalid ? 'aria-invalid=true aria-describedby='.$id.'-error' : '';
        @endphp
        <div class="{{ $field['width'] === 'half' ? '' : 'sm:col-span-2' }}">
            @if ($field['type'] === 'checkbox')
                <label class="flex items-start gap-2 text-sm">
                    <input id="{{ $id }}" type="checkbox" name="{{ $field['key'] }}" value="1" class="mt-0.5 size-4 rounded border-zinc-300" @checked($value) @if ($field['required']) required @endif {{ $describedBy }}>
                    <span>{{ $field['label'] }}</span>
                </label>
            @else
                <label for="{{ $id }}" class="label">{{ $field['label'] }}@unless ($field['required']) <span class="font-normal text-zinc-500">({{ __('theme::shop.optional') }})</span>@endunless</label>
                @if ($field['type'] === 'textarea')
                    <textarea id="{{ $id }}" name="{{ $field['key'] }}" rows="5" maxlength="5000" class="input mt-1.5" placeholder="{{ $field['placeholder'] }}" @if ($field['required']) required @endif {{ $describedBy }}>{{ $value }}</textarea>
                @elseif ($field['type'] === 'select')
                    <select id="{{ $id }}" name="{{ $field['key'] }}" class="input mt-1.5" @if ($field['required']) required @endif {{ $describedBy }}>
                        <option value="">{{ __('forms::shop.choose') }}</option>
                        @foreach ($field['options'] as $option)
                            <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                @else
                    <input id="{{ $id }}" type="{{ $field['type'] }}" name="{{ $field['key'] }}" value="{{ $value }}" class="input mt-1.5" placeholder="{{ $field['placeholder'] }}"
                           @if ($field['type'] === 'email') autocomplete="email" @elseif ($field['type'] === 'tel') autocomplete="tel" @endif
                           @if ($field['required']) required @endif {{ $describedBy }}>
                @endif
            @endif
            @if ($invalid)
                <p id="{{ $id }}-error" class="field-error">{{ $bag->first($field['key']) }}</p>
            @endif
        </div>
    @endforeach

    <div class="flex flex-wrap items-center gap-4 sm:col-span-2">
        <button type="submit" class="btn btn-primary">{{ $form->submit_label ?: __('forms::shop.submit') }}</button>
        @if ($privacyUrl)
            <p class="text-xs text-zinc-500">{!! __('forms::shop.privacy', ['url' => e($privacyUrl)]) !!}</p>
        @endif
    </div>
</form>
