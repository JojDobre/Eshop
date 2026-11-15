{{-- Bloky z Obsah → Bloky (formulár, video, text, uložené sekcie…). --}}
@if (! empty($homeBlocks))
    <div>{!! render_blocks($homeBlocks) !!}</div>
@endif
