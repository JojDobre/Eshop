@extends('theme::layouts.app')

@section('title', __('returns::returns.lookup_title'))

@section('content')
    <div class="container-shop max-w-xl py-10">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('returns::returns.lookup_title') }}</h1>
        <p class="mt-3 text-zinc-600">{{ __('returns::returns.lookup_intro') }}</p>
        <form method="POST" action="{{ route('returns.find') }}" class="mt-8 space-y-4">
            @csrf
            <div>
                <label for="number" class="label">{{ __('returns::returns.lookup_number') }}</label>
                <input id="number" name="number" value="{{ old('number') }}" required maxlength="32" class="input mt-1.5" @error('number') aria-invalid="true" @enderror>
                @error('number') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="label">{{ __('returns::returns.lookup_email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="input mt-1.5" autocomplete="email">
            </div>
            <button type="submit" class="btn btn-primary">{{ __('returns::returns.lookup_submit') }}</button>
        </form>
        <p class="mt-10 text-sm text-zinc-500">{{ __('returns::returns.adr') }}</p>
    </div>
@endsection
