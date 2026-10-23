// Livewire 4 injects its own script (with Alpine.js). Admin UI behaviour lives here.

const store = {
    get(key, fallback = null) {
        try {
            const value = localStorage.getItem(key);
            return value === null ? fallback : JSON.parse(value);
        } catch {
            return fallback;
        }
    },
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch {
            /* private mode */
        }
    },
};

// Rail (collapsed sidebar) is applied before first paint by an inline script in the layout;
// keep it on <html> across wire:navigate page swaps.
const setRail = (on) => {
    document.documentElement.toggleAttribute('data-rail', on);
    store.set('admin.rail', on);
};

/** Fuzzy match: every character of the query in order. Lower score = better. */
function fuzzy(query, text) {
    const q = query.toLowerCase().trim();
    const t = text.toLowerCase();
    if (!q) return 0;
    const direct = t.indexOf(q);
    if (direct !== -1) return direct;
    let pos = -1;
    let gaps = 0;
    for (const ch of q.replace(/\s+/g, '')) {
        const next = t.indexOf(ch, pos + 1);
        if (next === -1) return null;
        gaps += next - pos - 1;
        pos = next;
    }
    return 100 + gaps;
}

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    /** Shell: mobile drawer, rail mode, tooltips for the rail, command palette, shortcuts. */
    Alpine.data('adminShell', (items = []) => ({
        drawer: false,
        rail: document.documentElement.hasAttribute('data-rail'),
        palette: false,
        query: '',
        cursor: 0,
        items,
        tip: { show: false, text: '', top: 0 },

        toggleRail() {
            this.rail = !this.rail;
            setRail(this.rail);
            this.tip.show = false;
        },

        hint(el) {
            if (!this.rail || window.innerWidth < 1024) return;
            const rect = el.getBoundingClientRect();
            this.tip = { show: true, text: el.dataset.tip || el.innerText.trim(), top: rect.top + rect.height / 2 };
        },

        /** Indices of matching items, best first (the list is rendered server-side with icons). */
        get ranked() {
            return this.items
                .map((item, i) => ({ i, score: fuzzy(this.query, `${item.label} ${item.group}`) }))
                .filter((r) => r.score !== null)
                .sort((a, b) => a.score - b.score || a.i - b.i)
                .slice(0, this.query ? 14 : 60)
                .map((r) => r.i);
        },

        pos(i) {
            return this.ranked.indexOf(i);
        },

        openPalette() {
            this.palette = true;
            this.query = '';
            this.cursor = 0;
            this.$nextTick(() => this.$refs.paletteInput?.focus());
        },

        closePalette() {
            this.palette = false;
        },

        move(step) {
            const n = this.ranked.length;
            if (n) this.cursor = (this.cursor + step + n) % n;
            this.$nextTick(() => this.$refs.paletteList?.querySelector('[aria-selected="true"]')?.scrollIntoView({ block: 'nearest' }));
        },

        go(index = this.ranked[this.cursor]) {
            const item = this.items[index];
            if (!item) return;
            this.palette = false;
            if (item.action === 'logout') {
                document.getElementById('logout-form')?.submit();
            } else if (item.external) {
                window.open(item.url, '_blank', 'noopener');
            } else if (window.Livewire?.navigate) {
                window.Livewire.navigate(item.url);
            } else {
                window.location.href = item.url;
            }
        },

        onKey(e) {
            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName) || e.target.isContentEditable;
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                this.palette ? this.closePalette() : this.openPalette();
            } else if (e.key === '/' && !typing && !this.palette) {
                e.preventDefault();
                this.openPalette();
            } else if (e.key === 'Escape') {
                this.palette = false;
                this.drawer = false;
            } else if ((e.metaKey || e.ctrlKey) && e.key === '\\') {
                e.preventDefault();
                this.toggleRail();
            }
        },
    }));

    /** Sidebar scroll: keeps its position across pages, fades where content continues. */
    Alpine.data('sidebarScroll', () => ({
        atTop: true,
        atBottom: true,

        init() {
            const el = this.$el;
            const saved = sessionStorage.getItem('admin.nav.scroll');
            if (saved !== null) {
                el.scrollTop = Number(saved);
            }
            // Make sure the current page is visible.
            const active = el.querySelector('[aria-current="page"]');
            if (active) {
                const a = active.getBoundingClientRect();
                const box = el.getBoundingClientRect();
                if (a.top < box.top + 24 || a.bottom > box.bottom - 24) {
                    active.scrollIntoView({ block: 'center' });
                }
            }
            this.update();
            const save = () => sessionStorage.setItem('admin.nav.scroll', String(el.scrollTop));
            document.addEventListener('livewire:navigating', save, { once: true });
            window.addEventListener('beforeunload', save, { once: true });
            new ResizeObserver(() => this.update()).observe(el);
        },

        update() {
            const el = this.$el;
            this.atTop = el.scrollTop < 4;
            this.atBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 4;
        },
    }));

    /** Collapsible sidebar group, remembered per browser. */
    Alpine.data('navGroup', (id, active = false) => ({
        open: active || !(store.get('admin.nav.closed', []) || []).includes(id),
        toggle() {
            this.open = !this.open;
            const closed = new Set(store.get('admin.nav.closed', []) || []);
            this.open ? closed.delete(id) : closed.add(id);
            store.set('admin.nav.closed', [...closed]);
        },
    }));

    /** Animated number for KPI tiles: <span x-data="countUp(42)" x-text="display"> */
    Alpine.data('countUp', (target = 0, duration = 1100) => ({
        display: '0',
        init() {
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const format = (n) => Math.round(n).toLocaleString();
            if (reduce || target <= 0) {
                this.display = format(target);
                return;
            }
            const start = performance.now();
            const tick = (now) => {
                const t = Math.min(1, (now - start) / duration);
                this.display = format(target * (1 - Math.pow(2, -10 * t)));
                if (t < 1) requestAnimationFrame(tick);
                else this.display = format(target);
            };
            requestAnimationFrame(tick);
        },
    }));

    /**
     * Formatting buttons for Markdown fields (bold, italic, heading, lists, link, quote) — the text stays Markdown,
     * so the website and the mobile app render it the same way. Livewire picks the change up through an input event.
     */
    Alpine.data('mdToolbar', () => ({
        area() {
            return this.$root.querySelector('textarea');
        },
        apply(next, selStart, selEnd) {
            const ta = this.area();
            ta.value = next;
            ta.dispatchEvent(new Event('input', { bubbles: true }));
            ta.focus();
            ta.setSelectionRange(selStart, selEnd);
        },
        wrap(before, after = before, placeholder = 'text') {
            const ta = this.area();
            const { selectionStart: a, selectionEnd: b, value } = ta;
            const picked = value.slice(a, b) || placeholder;
            this.apply(value.slice(0, a) + before + picked + after + value.slice(b), a + before.length, a + before.length + picked.length);
        },
        lines(prefix) {
            const ta = this.area();
            const { selectionStart: a, selectionEnd: b, value } = ta;
            const start = value.lastIndexOf('\n', a - 1) + 1;
            const nl = value.indexOf('\n', b);
            const end = nl === -1 ? value.length : nl;
            let n = 0;
            const changed = (value.slice(start, end) || 'item').split('\n')
                .map((l) => (prefix === '1. ' ? `${++n}. ` : prefix) + l.replace(/^(#{1,6} |[-*] |\d+\. |> )/, ''))
                .join('\n');
            this.apply(value.slice(0, start) + changed + value.slice(end), start, start + changed.length);
        },
        link() {
            const url = window.prompt('Link address (https://… or /page)', 'https://');
            if (url) this.wrap('[', `](${url})`, 'link text');
        },
    }));
});
