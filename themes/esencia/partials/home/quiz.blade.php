{{-- Banner s výzvou (napr. kvíz typu pleti): farebná plocha, nadpis, text, tlačidlo a fotka. --}}
@php
    $quizLink = trim((string) $themeOptions->get('quiz_link'));
    $quizImage = $themeOptions->imageUrl('quiz_image');
@endphp
@if ($quizLink !== '')
    <section class="{{ $wrap }} py-10" aria-labelledby="home-quiz">
        <div @class(['grid items-center overflow-hidden rounded-[2rem] bg-ac2 text-onac2', 'md:grid-cols-[1.2fr_1fr]' => $quizImage])>
            <div @class(['p-8 sm:p-12', 'md:order-2' => $reverse && $quizImage])>
                <h2 id="home-quiz" class="text-4xl leading-[0.95] sm:text-5xl">{{ $opt('quiz_title') }}</h2>
                <p class="mt-4 max-w-md font-medium">{{ $opt('quiz_text') }}</p>
                <a href="{{ $quizLink }}" class="btn btn-ink mt-7">{{ $opt('quiz_button') }} @include('theme::partials.icon', ['name' => 'arrow', 'class' => 'size-4', 'stroke' => 2])</a>
            </div>
            @if ($quizImage)
                <img {{ responsive_img($quizImage, '(min-width: 768px) 40vw, 95vw') }} alt="" class="h-72 w-full object-cover md:h-full" loading="lazy">
            @endif
        </div>
    </section>
@endif
