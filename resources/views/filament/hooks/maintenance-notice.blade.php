{{--
    Počas aktualizácie je obchod v režime údržby a ďalšie požiadavky administrácie (napr. obnovovanie počtov
    v menu) dostanú odpoveď 503. Namiesto bieleho okna s chybou sa zobrazí nenápadné oznámenie,
    ktoré po prvej úspešnej požiadavke zmizne.
--}}
<div id="admin-maintenance-notice" class="admin-maintenance-notice" role="status" aria-live="polite" hidden>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/></svg>
    <span>{{ __('maintenance.admin_notice') }}</span>
</div>
<script>
    document.addEventListener('livewire:init', () => {
        const notice = document.getElementById('admin-maintenance-notice')

        window.Livewire.interceptRequest(({ onError, onSuccess }) => {
            onError(({ response, preventDefault }) => {
                if (response.status !== 503) return
                preventDefault()
                notice.hidden = false
            })
            onSuccess(() => { notice.hidden = true })
        })
    })
</script>
