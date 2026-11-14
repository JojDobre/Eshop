{{-- Komentáre pod článkom. --}}
@if ($commentsOpen || $post->comments->isNotEmpty())
    <section id="komentare" class="container-shop py-12">
        <div class="mx-auto {{ $postWidth }}">
            <h2 class="text-xl font-semibold tracking-tight">{{ __('theme::blog.comments') }} ({{ $post->comments->count() }})</h2>
            @if (session('comment_status'))
                <p role="status" class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('comment_status') }}</p>
            @endif
            <ul class="mt-6 space-y-6">
                @foreach ($post->comments as $comment)
                    <li class="rounded-xl border border-zinc-100 p-4">
                        <p class="text-sm font-medium">{{ $comment->name }} <span class="font-normal text-zinc-500">· {{ $comment->created_at->format(app()->getLocale() === 'en' ? 'j M Y' : 'j. n. Y') }}</span></p>
                        <p class="mt-2 whitespace-pre-line text-zinc-700">{{ $comment->content }}</p>
                    </li>
                @endforeach
            </ul>

            @if ($commentsOpen)
                <form method="POST" action="{{ route('blog.comment', $post->slug) }}" class="mt-8 space-y-4">
                    @csrf
                    <input type="hidden" name="form_token" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) time()) }}">
                    {{-- Skryté pole proti robotom (človek ho nevidí ani nevyplní). --}}
                    <div class="hidden" aria-hidden="true"><label>Web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @include('theme::partials.field', ['name' => 'name', 'label' => __('theme::blog.comment_name'), 'required' => true])
                        @include('theme::partials.field', ['name' => 'email', 'label' => __('theme::blog.comment_email'), 'type' => 'email', 'required' => true])
                    </div>
                    <div>
                        <label for="comment-content" class="block text-sm font-medium">{{ __('theme::blog.comment_text') }}</label>
                        <textarea id="comment-content" name="content" rows="4" required maxlength="3000" class="input mt-1">{{ old('content') }}</textarea>
                        @error('content') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <p class="text-xs text-zinc-500">{{ __('theme::blog.comment_privacy') }}</p>
                    <button type="submit" class="btn btn-primary">{{ __('theme::blog.comment_submit') }}</button>
                </form>
            @endif
        </div>
    </section>
@endif
