{{--
    Výber obrázkov z knižnice: mriežka po stránkach (24 obrázkov), hľadanie podľa názvu a vybrané obrázky
    hore nad mriežkou. Stav poľa je zoznam ID súborov. Pri poli pre jeden obrázok klik nahradí výber.
--}}
@php
    $items = $getItems();
    $multiple = $isMultiple();
@endphp
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle(@js($getStatePath())),
            items: @js($items),
            multiple: @js($multiple),
            search: '',
            page: 1,
            perPage: 24,
            get selected() { return Array.isArray(this.state) ? this.state.map(Number) : [] },
            get filtered() {
                const q = this.search.trim().toLowerCase();
                return q === '' ? this.items : this.items.filter((item) => item.name.toLowerCase().includes(q));
            },
            get pages() { return Math.max(1, Math.ceil(this.filtered.length / this.perPage)) },
            get visible() { return this.filtered.slice((this.page - 1) * this.perPage, this.page * this.perPage) },
            get chosen() { return this.items.filter((item) => this.selected.includes(item.id)) },
            isSelected(id) { return this.selected.includes(id) },
            toggle(id) {
                if (! this.multiple) { this.state = this.isSelected(id) ? [] : [id]; return }
                this.state = this.isSelected(id) ? this.selected.filter((value) => value !== id) : [...this.selected, id];
            },
        }"
        x-init="$watch('search', () => page = 1); $watch('page', () => $el.scrollIntoView({ block: 'start', behavior: 'smooth' }))"
        class="scroll-mt-4 space-y-4"
    >
        <div class="flex flex-wrap items-center gap-3">
            <x-filament::input.wrapper class="min-w-0 flex-1">
                <x-filament::input type="search" x-model.debounce.200ms="search" placeholder="{{ __('admin.media.search') }}" />
            </x-filament::input.wrapper>
            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="@js(__('admin.media.found')).replace(':count', filtered.length)"></p>
        </div>

        {{-- Vybrané obrázky: vidno ich hneď, netreba posúvať nadol. --}}
        <div x-show="chosen.length" x-cloak class="flex flex-wrap items-center gap-2 rounded-xl bg-primary-50 p-2 dark:bg-white/5">
            <span class="px-1 text-sm font-medium text-primary-700 dark:text-primary-400">{{ __('admin.media.selected') }}</span>
            <template x-for="item in chosen" :key="'chosen-' + item.id">
                <button type="button" @click="toggle(item.id)" class="group relative size-12 overflow-hidden rounded-lg ring-2 ring-primary-500" :title="item.name">
                    <img :src="item.url" alt="" class="size-full object-cover">
                    <span class="absolute inset-0 hidden place-items-center bg-black/50 text-white group-hover:grid" aria-hidden="true">×</span>
                </button>
            </template>
        </div>

        <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            <template x-for="item in visible" :key="item.id">
                <button type="button" @click="toggle(item.id)" class="group relative text-left" :aria-pressed="isSelected(item.id).toString()">
                    <span class="block aspect-square overflow-hidden rounded-lg bg-gray-100 ring-offset-2 transition dark:bg-white/5"
                          :class="isSelected(item.id) ? 'ring-2 ring-primary-500' : 'group-hover:opacity-80'">
                        <img :src="item.url" alt="" loading="lazy" class="size-full object-cover">
                    </span>
                    <span x-show="isSelected(item.id)" class="absolute right-1.5 top-1.5 grid size-6 place-items-center rounded-full bg-primary-600 text-xs font-bold text-white shadow">✓</span>
                    <span class="mt-1 block truncate text-xs text-gray-600 dark:text-gray-300" x-text="item.name"></span>
                </button>
            </template>
        </div>

        <p x-show="filtered.length === 0" x-cloak class="py-6 text-center text-sm text-gray-500">{{ __('admin.media.empty') }}</p>

        <div x-show="pages > 1" x-cloak class="flex items-center justify-center gap-3">
            <x-filament::button color="gray" size="sm" x-on:click="page = Math.max(1, page - 1)" x-bind:disabled="page === 1">{{ __('admin.media.previous') }}</x-filament::button>
            <span class="text-sm text-gray-600 dark:text-gray-300" x-text="page + ' / ' + pages"></span>
            <x-filament::button color="gray" size="sm" x-on:click="page = Math.min(pages, page + 1)" x-bind:disabled="page === pages">{{ __('admin.media.next') }}</x-filament::button>
        </div>
    </div>
</x-dynamic-component>
