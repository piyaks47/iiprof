<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#070f20">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ $siteName }} Admin</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $settings = app(\App\Support\SiteSettings::class);
    $public = $settings->forPublic();
    $logo = $public['logo_url'] ?? null;
    $bigLogo = ($public['login_logo_url'] ?? null) ?: $logo;
    $background = $public['login_background_url'] ?? null;
    $short = $settings->get('short_name') ?: mb_substr($siteName, 0, 2);
@endphp
<body class="min-h-full bg-white font-sans text-slate-800 antialiased">
    <div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">
        {{-- Brand panel --}}
        <aside class="relative isolate hidden overflow-hidden bg-brand-950 p-12 text-white lg:flex lg:flex-col">
            @if ($background)
                <img src="{{ $background }}" alt="" aria-hidden="true" class="absolute inset-0 -z-20 size-full object-cover opacity-40" onerror="this.remove()">
                <div aria-hidden="true" class="absolute inset-0 -z-20 bg-gradient-to-br from-brand-950/90 via-brand-950/70 to-brand-900/40"></div>
            @endif
            <div aria-hidden="true" class="lattice absolute inset-0 -z-10 opacity-60 [mask-image:radial-gradient(ellipse_at_30%_40%,black,transparent_75%)]"></div>
            <div aria-hidden="true" class="absolute -right-32 top-1/3 -z-10 size-[30rem] rounded-full bg-gold-500/15 blur-3xl"></div>
            <div aria-hidden="true" class="absolute -bottom-40 left-10 -z-10 size-[26rem] rounded-full bg-brand-500/25 blur-3xl"></div>
            <svg aria-hidden="true" viewBox="0 0 600 600" class="absolute -right-40 top-1/2 -z-10 h-[120%] -translate-y-1/2 animate-[spin_80s_linear_infinite] text-gold-300 opacity-[0.14]">
                @foreach ([280, 230, 180, 130] as $i => $r)
                    <circle cx="300" cy="300" r="{{ $r }}" fill="none" stroke="currentColor" stroke-width="{{ $i === 0 ? 1.2 : 0.6 }}" @if ($i % 2) stroke-dasharray="2 10" @endif />
                @endforeach
            </svg>

            <a href="{{ url('/') }}" class="flex items-center gap-4">
                @if ($bigLogo)
                    <img src="{{ $bigLogo }}" alt="" class="h-20 w-auto max-w-24 shrink-0 object-contain drop-shadow-[0_12px_28px_rgb(0_0_0/0.5)]" onerror="this.nextElementSibling.hidden = false; this.remove()">
                @endif
                <span @if ($bigLogo) hidden @endif class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-gold-300 to-gold-600 text-lg font-bold text-brand-950 shadow-xl">{{ mb_strtoupper(mb_substr($short, 0, 3)) }}</span>
                <span>
                    @if ($tagline = $settings->get('tagline'))
                        <span class="block text-[10px] font-bold uppercase tracking-[0.3em] text-gold-300">{{ $tagline }}</span>
                    @endif
                    <span class="mt-1 block font-display text-xl font-semibold">{{ $siteName }}</span>
                </span>
            </a>

            <div class="mt-auto max-w-md">
                <p class="flex items-center gap-3 text-[11px] font-bold uppercase tracking-[0.3em] text-gold-300"><span class="h-px w-10 bg-gold-400"></span> Admin &amp; member area</p>
                <h2 class="mt-6 font-display text-5xl font-semibold leading-[1.08]">One place for your <em class="bg-gradient-to-r from-gold-200 via-gold-400 to-gold-300 bg-clip-text text-transparent">community</em>.</h2>
                <p class="mt-6 text-base leading-7 text-slate-300">Manage the website, your members and every IIP platform — Hunar, Jeevika, Khazana and more — from one dashboard.</p>
            </div>
            <p class="mt-12 text-xs text-slate-500">&copy; {{ date('Y') }} {{ $siteName }}</p>
        </aside>

        {{-- Form --}}
        <main class="flex flex-col px-6 py-10 sm:px-12">
            <div class="flex items-center justify-between lg:justify-end">
                <a href="{{ url('/') }}" class="flex items-center gap-3 lg:hidden">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="" class="size-10 object-contain" onerror="this.remove()">
                    @endif
                    <span class="font-display text-lg font-semibold text-brand-950">{{ $siteName }}</span>
                </a>
                <a href="{{ url('/') }}" class="group inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.14em] text-slate-400 transition hover:text-brand-700">
                    <span class="transition group-hover:-translate-x-0.5">&larr;</span> Website
                </a>
            </div>
            <div class="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-12 animate-page-in">
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
