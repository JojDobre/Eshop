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

/**
 * Text vždy celý (napr. názov obchodu v hlavičke): písmo sa zmenšuje, kým sa text nezmestí do jedného riadka;
 * ak ani pri najmenšom písme nestačí, zalomí sa na dva riadky. Žiadne „…“.
 */
const fitText = (element) => {
    element.classList.remove('is-wrapped');
    element.style.fontSize = '';

    const base = parseFloat(getComputedStyle(element).fontSize);
    const minimum = Math.max(12, base * 0.6);
    let size = base;

    while (element.scrollWidth > element.clientWidth + 1 && size > minimum) {
        size -= 0.5;
        element.style.fontSize = `${size}px`;
    }

    if (element.scrollWidth > element.clientWidth + 1) {
        element.classList.add('is-wrapped');
        element.style.fontSize = `${Math.max(minimum, base * 0.75)}px`;
    }
};

const fitAll = () => document.querySelectorAll('[data-fit-text]').forEach(fitText);

if (window.ResizeObserver) {
    const observer = new ResizeObserver(() => requestAnimationFrame(fitAll));
    document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('[data-fit-text]').forEach((element) => observer.observe(element.parentElement ?? element)));
}

document.addEventListener('DOMContentLoaded', fitAll);
window.addEventListener('load', fitAll);

window.Alpine = Alpine;
Alpine.start();
