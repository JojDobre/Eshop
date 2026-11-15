{{-- Ako to funguje: fotka a tri kroky. --}}
@php($stepsImage = $themeOptions->imageUrl('steps_image'))
<section class="{{ $wrap }} py-10" aria-labelledby="home-steps">
    <div @class(['leaf-r grid items-center gap-6 bg-soft2 p-6 sm:p-10', 'lg:grid-cols-2' => $stepsImage])>
        @if ($stepsImage)
            <img {{ responsive_img($stepsImage, '(min-width: 1024px) 45vw, 90vw') }} alt="" @class(['leaf-sm aspect-[4/3] w-full object-cover', 'lg:order-2' => $reverse]) loading="lazy">
        @endif
        <div>
            <h2 id="home-steps" class="lp-h2">{{ $opt('steps_title') }}</h2>
            <ol class="mt-6 space-y-5">
                @foreach (range(1, 3) as $i)
                    <li class="flex gap-4">
                        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-ac font-head text-xl text-onac" aria-hidden="true">{{ $i }}</span>
                        <span><b class="block text-lg">{{ $opt('step_'.$i.'_title') }}</b><span class="text-muted">{{ $opt('step_'.$i.'_text') }}</span></span>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
