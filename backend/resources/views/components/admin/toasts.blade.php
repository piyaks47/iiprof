{{-- Listens for Livewire `$this->dispatch('notify', message: ..., type: 'success'|'error')` and session('status'). --}}
<div x-data="{
        toasts: [],
        add(detail) {
            const toast = { id: Date.now() + Math.random(), type: detail.type || 'success', message: detail.message };
            this.toasts.push(toast);
            setTimeout(() => this.remove(toast.id), 4500);
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id) },
    }"
    x-init="@if (session('status')) add({ message: @js(session('status')) }) @endif"
    x-on:notify.window="add($event.detail)"
    class="pointer-events-none fixed inset-x-0 bottom-0 z-[80] flex flex-col items-center gap-2 p-4 sm:items-end sm:p-6"
    aria-live="polite">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="opacity-0 translate-x-6"
            class="pointer-events-auto relative flex w-full max-w-sm animate-toast-in items-start gap-3 overflow-hidden rounded-2xl bg-brand-950 px-4 py-3.5 text-sm text-white shadow-2xl shadow-brand-950/30 ring-1 ring-white/10">
            <span x-show="toast.type !== 'error'" class="flex size-6 shrink-0 items-center justify-center rounded-full bg-gold-400/15 text-gold-300"><x-admin.icon name="check" class="size-5" /></span>
            <span x-show="toast.type === 'error'" class="flex size-6 shrink-0 items-center justify-center rounded-full bg-rose-400/15 text-rose-300"><x-admin.icon name="alert" class="size-5" /></span>
            <p class="flex-1 pt-0.5 leading-snug" x-text="toast.message"></p>
            <button type="button" class="text-slate-400 transition hover:rotate-90 hover:text-white" x-on:click="remove(toast.id)" aria-label="Dismiss">
                <x-admin.icon name="close" class="size-4" />
            </button>
            <span class="absolute inset-x-0 bottom-0 h-0.5 origin-left bg-gradient-to-r from-gold-300 to-gold-500" x-init="$el.animate([{ transform: 'scaleX(1)' }, { transform: 'scaleX(0)' }], { duration: 4500, easing: 'linear', fill: 'forwards' })"></span>
        </div>
    </template>
</div>
