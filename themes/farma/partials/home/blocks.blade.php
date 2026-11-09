{{-- Bloky z Obsah → Úvodná stránka a bloky (banner, text, video, formulár, produkty…). --}}
@if (! empty($homeBlocks))
    <div class="farma-section">{!! render_blocks($homeBlocks) !!}</div>
@endif
