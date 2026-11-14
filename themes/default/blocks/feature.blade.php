<div id="{{ $blockId }}" class="block-section container-shop py-6">
    <div class="block-feature relative flex gap-4 rounded-2xl border border-zinc-200 bg-white p-6 text-zinc-900">
        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
            @include('theme::partials.block-icon', ['icon' => $block['icon'] ?? 'star', 'class' => 'size-6'])
        </span>
        <div>
            <h3 class="font-semibold">
                @if (! empty($block['url']))<a href="{{ $block['url'] }}" class="after:absolute after:inset-0">{{ $block['title'] ?? '' }}</a>@else{{ $block['title'] ?? '' }}@endif
            </h3>
            @if (! empty($block['text']))
                <p class="mt-1 text-sm text-zinc-600">{{ $block['text'] }}</p>
            @endif
        </div>
    </div>
</div>
