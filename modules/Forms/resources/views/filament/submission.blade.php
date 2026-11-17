{{-- Detail správy z formulára: odosielateľ, kontakty, ostatné polia a dlhý text v čitateľnom rámčeku. --}}
@php
    /** @var \Modules\Forms\Models\FormSubmission $submission */
    $fields = collect($submission->fields());
    $name = $submission->senderName();
    $emails = $fields->where('type', 'email')->where('value', '!=', '');
    $phones = $fields->where('type', 'tel')->where('value', '!=', '');
    $long = $fields->where('type', 'textarea');
    // Meno je v hlavičke, kontakty v riadku pod ním – zvyšné krátke polia idú do mriežky.
    $short = $fields->reject(fn (array $field) => in_array($field['type'], ['email', 'tel', 'textarea'], true)
        || ($name !== null && $field['value'] === $name && $field['type'] === 'text'));
    $initial = mb_strtoupper(mb_substr($name ?? $submission->email ?? '?', 0, 1));
@endphp

<div class="space-y-5">
    <div class="flex items-start gap-4">
        <div class="grid size-12 shrink-0 place-items-center rounded-full bg-primary-500/15 text-lg font-semibold text-primary-600 dark:text-primary-400" aria-hidden="true">{{ $initial }}</div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-base font-semibold text-gray-950 dark:text-white">{{ $name ?? $submission->email ?? __('forms::admin.anonymous') }}</p>
            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                @foreach ($emails as $field)
                    <a href="mailto:{{ $field['value'] }}" class="inline-flex items-center gap-1.5 text-primary-600 hover:underline dark:text-primary-400">
                        <x-filament::icon icon="heroicon-m-envelope" class="size-4" />{{ $field['value'] }}
                    </a>
                @endforeach
                @foreach ($phones as $field)
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $field['value']) }}" class="inline-flex items-center gap-1.5 text-primary-600 hover:underline dark:text-primary-400">
                        <x-filament::icon icon="heroicon-m-phone" class="size-4" />{{ $field['value'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @if ($short->isNotEmpty())
        <dl class="grid gap-x-6 gap-y-3 rounded-xl border border-gray-200 p-4 text-sm sm:grid-cols-2 dark:border-white/10">
            @foreach ($short as $field)
                <div class="min-w-0">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $field['label'] }}</dt>
                    <dd class="mt-0.5 break-words text-gray-950 dark:text-white">{{ $field['value'] !== '' ? $field['value'] : '—' }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    @foreach ($long as $field)
        <section>
            <h3 class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $field['label'] }}</h3>
            @if ($field['value'] !== '')
                <div class="whitespace-pre-line break-words rounded-xl bg-gray-50 p-4 text-sm leading-relaxed text-gray-950 dark:bg-white/5 dark:text-white">{{ $field['value'] }}</div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">—</p>
            @endif
        </section>
    @endforeach
</div>
