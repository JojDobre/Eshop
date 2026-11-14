{{--
    Responzívne tabuľky: bunkám doplní popisok stĺpca (data-label), aby sa v úzkom priestore dali zobraziť
    ako karty (pozri resources/css/filament/admin/theme.css), a ikonovým akciám v riadku bublinku s názvom.
--}}
<script>
    (() => {
        let scheduled = false;

        const enhance = () => {
            scheduled = false;

            document.querySelectorAll('table.fi-ta-table').forEach((table) => {
                const headers = [...table.querySelectorAll(':scope > thead > tr:last-child > th')];
                const labels = headers.map((th) =>
                    th.matches('.fi-ta-selection-cell, .fi-ta-actions-header-cell') ? '' : th.innerText.trim(),
                );
                const primary = headers.findIndex((th, i) => labels[i] !== '' && ! /image|avatar|icon|color/.test(th.className));

                table.querySelectorAll(':scope > tbody > tr.fi-ta-row').forEach((row) => {
                    [...row.children].forEach((cell, i) => {
                        cell.dataset.label = labels[i] ?? '';
                        cell.classList.toggle('admin-primary-cell', i === primary);
                        cell.classList.toggle('admin-actions-cell', cell.querySelector(':scope > .fi-ta-actions') !== null);
                        const isImageColumn = /image|avatar/.test(headers[i]?.className ?? '');
                        cell.classList.toggle('admin-empty-cell', isImageColumn
                            ? ! cell.querySelector('img')
                            : cell.innerText.trim() === '' && ! cell.querySelector('img, input, button, a, svg, [role=switch]'));
                    });

                    row.querySelectorAll('.fi-ta-actions .fi-link').forEach((action) => {
                        const text = action.querySelector('.fi-link-label')?.innerText.trim();

                        if (text && ! action.hasAttribute('title')) {
                            action.setAttribute('title', text);
                        }
                    });
                });
            });
        };

        const schedule = () => {
            if (! scheduled) {
                scheduled = true;
                requestAnimationFrame(enhance);
            }
        };

        document.addEventListener('DOMContentLoaded', schedule);
        document.addEventListener('livewire:navigated', schedule);
        new MutationObserver(schedule).observe(document.documentElement, { childList: true, subtree: true });
        schedule();
    })();
</script>
