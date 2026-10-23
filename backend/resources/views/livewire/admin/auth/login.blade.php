<div>
    <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-gold-600">Welcome back</p>
    <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight text-brand-950">Sign in</h1>
    <p class="mt-3 text-sm leading-6 text-slate-500">Administrators, editors and members sign in here — you’ll land on your own dashboard.</p>

    <form wire:submit="login" class="mt-10 space-y-5">
        <x-admin.input label="Email" type="email" wire:model="email" autocomplete="username" autofocus required class="[&_input]:h-11" />
        <div x-data="{ show: false }" class="relative">
            <x-admin.input label="Password" ::type="show ? 'text' : 'password'" type="password" wire:model="password" autocomplete="current-password" required class="[&_input]:h-11 [&_input]:pr-16" />
            <button type="button" x-on:click="show = ! show" class="absolute right-3 top-[2.15rem] rounded-md px-1.5 py-1 text-xs font-semibold text-slate-500 hover:text-brand-700" x-text="show ? 'Hide' : 'Show'"></button>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" wire:model="remember" class="rounded border-slate-300 text-brand-800 focus:ring-gold-400">
            Keep me signed in
        </label>

        <x-admin.button type="submit" size="lg" class="w-full" wire:loading.attr="disabled" wire:target="login">
            <span wire:loading.remove wire:target="login" class="inline-flex items-center gap-2">Sign in <span aria-hidden="true">&rarr;</span></span>
            <span wire:loading wire:target="login" class="inline-flex items-center gap-2"><span class="size-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span> Signing in…</span>
        </x-admin.button>
    </form>
</div>
