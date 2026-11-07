@extends('theme::layouts.app')

@section('title', __('theme::shop.returns_title'))
@section('noindex', true)

@section('content')
    <div class="container-shop max-w-3xl py-10" x-data="{ type: @js(old('type', $type->value)) }">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('theme::shop.returns_title') }}</h1>
        <p class="mt-3 text-zinc-600">{{ __('theme::shop.returns_intro', ['number' => $order->number]) }}</p>

        <form method="POST" action="{{ route('returns.store', $order->token) }}" class="mt-8 space-y-8" novalidate>
            @csrf

            <fieldset class="grid gap-3 sm:grid-cols-2">
                <legend class="sr-only">{{ __('theme::shop.returns_type') }}</legend>
                @foreach (\Modules\Returns\ReturnType::cases() as $case)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 p-4 has-[:checked]:border-primary has-[:checked]:ring-1 has-[:checked]:ring-primary">
                        <input type="radio" name="type" value="{{ $case->value }}" x-model="type" class="mt-1 accent-[var(--brand-primary)]">
                        <span>
                            <span class="block font-medium">{{ $case->getLabel() }}</span>
                            <span class="mt-0.5 block text-sm text-zinc-500">{{ __('theme::shop.returns_'.$case->value.'_help') }}</span>
                        </span>
                    </label>
                @endforeach
            </fieldset>

            <fieldset>
                <legend class="text-lg font-semibold">{{ __('theme::shop.returns_items') }}</legend>
                <ul class="mt-3 divide-y divide-zinc-100 border-y border-zinc-100">
                    @foreach ($order->items as $item)
                        <li class="flex items-center justify-between gap-4 py-3 text-sm">
                            <label for="qty-{{ $item->id }}" class="flex-1">{{ $item->fullName() }} <span class="text-zinc-500">({{ __('theme::shop.returns_ordered', ['count' => $item->quantity]) }})</span></label>
                            <input id="qty-{{ $item->id }}" type="number" name="quantities[{{ $item->id }}]" min="0" max="{{ $item->quantity }}"
                                   value="{{ old('quantities.'.$item->id, $order->items->count() === 1 ? $item->quantity : 0) }}" class="input w-24">
                        </li>
                    @endforeach
                </ul>
                @error('quantities') <p class="field-error">{{ $message }}</p> @enderror
            </fieldset>

            <div x-show="type === 'complaint'" x-cloak>
                <label for="preferred_resolution" class="label">{{ __('theme::shop.returns_resolution') }}</label>
                <select id="preferred_resolution" name="preferred_resolution" class="input mt-1.5">
                    @foreach (\Modules\Returns\ComplaintResolution::cases() as $resolution)
                        <option value="{{ $resolution->value }}" @selected(old('preferred_resolution') === $resolution->value)>{{ $resolution->getLabel() }}</option>
                    @endforeach
                </select>
                @error('preferred_resolution') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="reason" class="label">
                    <span x-show="type === 'complaint'">{{ __('theme::shop.returns_defect') }}</span>
                    <span x-show="type !== 'complaint'">{{ __('theme::shop.returns_reason') }} <span class="font-normal text-zinc-500">({{ __('theme::shop.optional') }})</span></span>
                </label>
                <textarea id="reason" name="reason" rows="4" maxlength="2000" class="input mt-1.5" @error('reason') aria-invalid="true" @enderror>{{ old('reason') }}</textarea>
                @error('reason') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            @include('theme::partials.field', ['name' => 'refund_account', 'label' => __('theme::shop.returns_refund_account'), 'required' => false, 'help' => __('theme::shop.returns_refund_account_help')])

            <p class="rounded-xl bg-zinc-50 p-4 text-sm text-zinc-600" x-show="type !== 'complaint'">{{ __('theme::shop.returns_withdrawal_info') }}</p>
            <p class="rounded-xl bg-zinc-50 p-4 text-sm text-zinc-600" x-show="type === 'complaint'" x-cloak>{{ __('theme::shop.returns_complaint_info') }}</p>

            <div class="flex flex-col gap-3 sm:flex-row">
                <button type="submit" class="btn btn-primary px-8 py-3">{{ __('theme::shop.returns_submit') }}</button>
                <a href="{{ $order->publicUrl() }}" class="btn btn-secondary px-8 py-3">{{ __('theme::shop.back') }}</a>
            </div>
        </form>
    </div>
@endsection
