import Alpine from 'alpinejs';

/**
 * Plávajúce upozornenia obchodu. Nové pridá udalosť: window.dispatchEvent(new CustomEvent('shop-toast', { detail: { type, message, link, linkLabel } })).
 */
Alpine.data('shopToasts', (initial = []) => ({
    toasts: [],
    nextId: 1,

    init() {
        initial.forEach((toast) => this.push(toast));
    },

    push(toast) {
        const item = { id: this.nextId++, visible: false, type: 'success', link: null, linkLabel: '', ...toast };
        this.toasts.push(item);
        this.$nextTick(() => {
            this.toasts.find((t) => t.id === item.id).visible = true;
        });
        // Najviac tri upozornenia naraz.
        this.toasts.filter((t) => t.visible).slice(0, -3).forEach((t) => this.dismiss(t));
        setTimeout(() => this.dismiss(item), item.type === 'error' ? 7000 : 4500);
    },

    dismiss(toast) {
        const item = this.toasts.find((t) => t.id === toast.id);
        if (!item) return;
        item.visible = false;
        setTimeout(() => (this.toasts = this.toasts.filter((t) => t.id !== toast.id)), 250);
    },
}));

const toast = (detail) => window.dispatchEvent(new CustomEvent('shop-toast', { detail }));

/**
 * Pridanie do košíka bez obnovenia stránky: formuláre s atribútom data-ajax-cart sa odošlú na pozadí,
 * tlačidlo krátko ukáže potvrdenie a počet kusov v hlavičke sa aktualizuje. Pri chybe spojenia sa
 * formulár odošle bežným spôsobom.
 */
document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-ajax-cart') || !window.fetch) {
        return;
    }

    event.preventDefault();
    const button = event.submitter || form.querySelector('[type="submit"]');

    if (button?.classList.contains('is-loading')) {
        return;
    }

    button?.classList.remove('is-added');
    button?.classList.add('is-loading');
    button?.setAttribute('aria-busy', 'true');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form, event.submitter),
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const errors = data.errors ? Object.values(data.errors).flat() : [];
            toast({ type: 'error', message: errors[0] || data.message || document.querySelector('[data-cart-error]')?.dataset.cartError });

            return;
        }

        document.querySelectorAll('[data-cart-count]').forEach((badge) => {
            badge.textContent = data.count;
            // Skrytý počet (prázdny košík) sa zobrazí; data-cart-count môže určiť triedu zobrazenia, napr. „flex“.
            if (badge.classList.contains('hidden') && badge.dataset.cartCount) {
                badge.classList.add(badge.dataset.cartCount);
            }
            badge.classList.remove('hidden', 'cart-bump');
            void badge.offsetWidth; // reštart animácie
            badge.classList.add('cart-bump');
        });

        button?.classList.add('is-added');
        setTimeout(() => button?.classList.remove('is-added'), 1600);
        toast({ type: 'success', message: data.message, link: data.cart_url, linkLabel: data.cart_label });
    } catch (error) {
        form.removeAttribute('data-ajax-cart');
        form.submit();
    } finally {
        button?.classList.remove('is-loading');
        button?.removeAttribute('aria-busy');
    }
});

window.Alpine = Alpine;
Alpine.start();
