@if (! empty($block['categories']) && $block['categories']->isNotEmpty())
    <section id="{{ $blockId }}" class="container-shop py-14">
        @if (! empty($block['title']))
            <h2 class="text-xl font-semibold tracking-tight">{{ $block['title'] }}</h2>
        @endif
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3">
            @foreach ($block['categories'] as $blockCategory)
                <a href="{{ $blockCategory->url() }}" class="group relative flex aspect-[4/3] items-end overflow-hidden rounded-xl bg-zinc-100 p-5">
                    @if ($image = $blockCategory->imageUrl())
                        <img src="{{ $image }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                        <span class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></span>
                    @endif
                    <span @class(['relative text-lg font-semibold', 'text-white' => $blockCategory->image, 'text-zinc-900' => ! $blockCategory->image])>{{ $blockCategory->name }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
