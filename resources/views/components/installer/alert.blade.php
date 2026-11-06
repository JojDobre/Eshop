@props(['type' => 'error'])

<div role="{{ $type === 'error' ? 'alert' : 'status' }}" @class([
    'rounded-lg border px-4 py-3 text-sm',
    'border-red-200 bg-red-50 text-red-800' => $type === 'error',
    'border-amber-200 bg-amber-50 text-amber-900' => $type === 'warning',
    'border-emerald-200 bg-emerald-50 text-emerald-800' => $type === 'success',
])>{{ $slot }}</div>
