{{-- Prepínač denného a nočného režimu v hornej lište; voľba sa pamätá v prehliadači (rovnako ako vo Filamente). --}}
<div class="admin-theme-toggle" role="group" aria-label="{{ __('admin.appearance_mode.label') }}" x-data>
    <button type="button" x-on:click="$dispatch('theme-changed', 'light')" x-bind:aria-pressed="($store.theme !== 'dark').toString()"
            aria-label="{{ __('admin.appearance_mode.light') }}" x-tooltip="{ content: @js(__('admin.appearance_mode.light')), theme: $store.theme }">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
    </button>
    <button type="button" x-on:click="$dispatch('theme-changed', 'dark')" x-bind:aria-pressed="($store.theme === 'dark').toString()"
            aria-label="{{ __('admin.appearance_mode.dark') }}" x-tooltip="{ content: @js(__('admin.appearance_mode.dark')), theme: $store.theme }">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/></svg>
    </button>
</div>
