@if (! empty($block['form']))
    <section id="{{ $blockId }}" class="block-section container-shop max-w-3xl py-8">
        @if (! empty($block['title']))
            <h2 class="block-title text-2xl font-semibold tracking-tight">{{ $block['title'] }}</h2>
        @endif
        @if (! empty($block['text']))
            <p class="mt-2 text-zinc-600">{{ $block['text'] }}</p>
        @endif
        <div class="{{ ! empty($block['title']) || ! empty($block['text']) ? 'mt-6' : '' }} rounded-xl border border-zinc-200 bg-white p-5 sm:p-8">
            @include('theme::partials.contact-form', ['form' => $block['form']])
        </div>
    </section>
@endif
