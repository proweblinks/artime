<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  dir="{{ Language::getCurrent('dir') }}">
<head>
    <title>
        @hasSection('pagetitle')
            @yield('pagetitle')
        @else
            {{ get_option("website_title", config('site.title')) }}
        @endif
    </title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="keywords" content="{{ get_option("website_keyword", config('site.keywords')) }}">
    <meta name="description" content="{{ get_option("website_description", config('site.description')) }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ url( get_option("website_favicon", asset('public/img/favicon.png')) ) }}">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.cdnfonts.com/css/general-sans?styles=135312,135310,135313,135303" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ theme_public_asset('css/flags/flag-icon.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ theme_public_asset('css/fontawesome/css/all.min.css') }}">
    {!! theme_vite('guest/nova') !!}
    {!! Script::globals() !!}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js" defer></script>

    {{-- Frosted Glass Morphism Design System --}}
    <style>
        :root {
            --bg-page: #f0f4f8;
            --accent: #03fcf4;
            --accent-dark: #0891b2;
            --accent-gradient: linear-gradient(135deg, #03fcf4, #0891b2);
            --text-primary: #1a1a2e;
            --text-secondary: #5a6178;
            --text-muted: #94a0b8;
            --glass-bg: rgba(255,255,255,0.55);
            --glass-border: rgba(255,255,255,0.35);
            --glass-shadow: 0 8px 32px rgba(0,0,0,0.06);
        }

        /* Page background */
        body.glass-page {
            background: var(--bg-page);
            color: var(--text-primary);
        }

        /* Animated mesh gradient blobs */
        .glass-bg-blobs {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .glass-bg-blobs .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.10;
            will-change: transform;
        }
        .glass-bg-blobs .blob-1 {
            width: 600px; height: 600px;
            background: #03fcf4;
            top: -10%; left: -5%;
            animation: blobFloat1 20s ease-in-out infinite;
        }
        .glass-bg-blobs .blob-2 {
            width: 500px; height: 500px;
            background: #0d9488;
            top: 40%; right: -10%;
            animation: blobFloat2 25s ease-in-out infinite;
        }
        .glass-bg-blobs .blob-3 {
            width: 450px; height: 450px;
            background: #0284c7;
            bottom: -5%; left: 30%;
            animation: blobFloat3 22s ease-in-out infinite;
        }
        @keyframes blobFloat1 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(80px, 60px) scale(1.1); }
            66% { transform: translate(-40px, 100px) scale(0.95); }
        }
        @keyframes blobFloat2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(-60px, -80px) scale(1.05); }
            66% { transform: translate(50px, -40px) scale(0.9); }
        }
        @keyframes blobFloat3 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(70px, -50px) scale(1.08); }
            66% { transform: translate(-80px, 30px) scale(0.92); }
        }

        /* Glass card */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--glass-shadow);
        }
        .glass-card-sm {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            box-shadow: var(--glass-shadow);
        }
        .glass-card:hover {
            box-shadow: 0 12px 40px rgba(0,0,0,0.08);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }

        /* Glass header */
        .glass-header {
            background: rgba(240,244,248,0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            transition: all 0.3s ease;
        }
        .glass-header.scrolled {
            background: rgba(255,255,255,0.85);
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }

        /* Buttons */
        .btn-accent {
            background: var(--accent-gradient);
            color: #fff;
            font-weight: 600;
            padding: 0.75rem 1.75rem;
            border-radius: 0.75rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .btn-accent:hover {
            box-shadow: 0 0 24px rgba(3,252,244,0.3);
            transform: translateY(-1px);
        }
        .btn-ghost {
            background: transparent;
            color: var(--text-secondary);
            font-weight: 600;
            padding: 0.75rem 1.75rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .btn-ghost:hover {
            background: rgba(255,255,255,0.6);
            border-color: var(--accent-dark);
            color: var(--accent-dark);
        }

        /* Glass pill badges */
        .glass-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.875rem;
            background: rgba(255,255,255,0.6);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 999px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-secondary);
        }
        .glass-pill i, .glass-pill svg {
            color: var(--accent-dark);
        }

        /* Accent text */
        .text-accent { color: var(--accent-dark); }
        .bg-accent { background: var(--accent); }

        /* Section spacing */
        .section-padding {
            padding: 5rem 0;
        }
        @media (min-width: 768px) {
            .section-padding { padding: 7rem 0; }
        }

        /* Gradient text */
        .gradient-text {
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Floating hero cards */
        .hero-float, .hero-float-delay, .hero-float-delay-2 {
            box-shadow: 0 8px 40px rgba(0,0,0,0.10), 0 0 0 1px rgba(0,0,0,0.04);
        }
        .hero-float {
            animation: heroFloat 6s ease-in-out infinite;
        }
        .hero-float-delay {
            animation: heroFloat 6s ease-in-out infinite 2s;
        }
        .hero-float-delay-2 {
            animation: heroFloat 6s ease-in-out infinite 4s;
        }
        @keyframes heroFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        /* Section backgrounds */
        .bg-page { background: var(--bg-page); }

        /* Feature icon containers */
        .feature-icon {
            width: 3rem; height: 3rem;
            display: flex; align-items: center; justify-content: center;
            border-radius: 0.75rem;
            background: rgba(3,252,244,0.1);
            color: var(--accent-dark);
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        /* Step connector line */
        .step-connector {
            position: absolute;
            top: 2rem;
            left: 100%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--accent), transparent);
            opacity: 0.3;
        }

        /* Responsive grid utilities (missing from compiled Tailwind) */
        @media (min-width: 640px) {
            .sm\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 768px) {
            .md\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .md\:pt-44 { padding-top: 11rem; }
            .md\:pb-28 { padding-bottom: 7rem; }
            .md\:p-8 { padding: 2rem; }
            .md\:p-16 { padding: 4rem; }
            .md\:gap-14 { gap: 3.5rem; }
        }
        @media (min-width: 1024px) {
            .lg\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .lg\:order-1 { order: 1; }
            .lg\:order-2 { order: 2; }
            .lg\:ml-auto { margin-left: auto; }
        }
    </style>
</head>
<body class="antialiased font-body glass-page sm:overflow-x-hidden">

    {{-- Animated background blobs --}}
    <div class="glass-bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    @if( request()->segment(1) != "auth")
        @include('partials.header')
    @endif

    <div class="relative z-10">
        @yield('content')
    </div>

    @if( request()->segment(1) != "auth")
        @include('partials.footer')
    @endif

    <div class="fixed bottom-0 z-50 w-full cookie-policy-bar">
        <div class="p-10 md:px-20 lg:px-36 glass-card" style="border-radius: 0;">
            <div class="container mx-auto">
                <div class="flex flex-wrap items-center -mx-4">
                    <div class="w-full md:w-1/2 px-4 mb-8 md:mb-0">
                        <h3 class="mb-4 text-lg md:text-xl font-semibold" style="color: var(--text-primary);">{{ __("Cookie Policy") }}</h3>
                        <p class="mb-2 font-medium" style="color: var(--text-secondary);">{{ __("We use third-party cookies in order to personalise your experience") }}</p>
                        <a class="flex items-center font-medium text-accent hover:opacity-80" href="{{ url("privacy-policy") }}">
                            <span class="mr-2">{{ __("Read our cookie policy") }}</span>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M15.71 12.71C15.801 12.6149 15.8724 12.5028 15.92 12.38C16.02 12.1365 16.02 11.8635 15.92 11.62C15.8724 11.4972 15.801 11.3851 15.71 11.29L12.71 8.29C12.5217 8.1017 12.2663 7.99591 12 7.99591C11.7337 7.99591 11.4783 8.1017 11.29 8.29C11.1017 8.4783 10.9959 8.7337 10.9959 9C10.9959 9.2663 11.1017 9.5217 11.29 9.71L12.59 11L9 11C8.73479 11 8.48043 11.1054 8.2929 11.2929C8.10536 11.4804 8 11.7348 8 12C8 12.2652 8.10536 12.5196 8.2929 12.7071C8.48043 12.8946 8.73479 13 9 13L12.59 13L11.29 14.29C11.1963 14.383 11.1219 14.4936 11.0711 14.6154C11.0203 14.7373 10.9942 14.868 10.9942 15C10.9942 15.132 11.0203 15.2627 11.0711 15.3846C11.1219 15.5064 11.1963 15.617 11.29 15.71C11.383 15.8037 11.4936 15.8781 11.6154 15.9289C11.7373 15.9797 11.868 16.0058 12 16.0058C12.132 16.0058 12.2627 15.9797 12.3846 15.9289C12.5064 15.8781 12.617 15.8037 12.71 15.71L15.71 12.71ZM22 12C22 10.0222 21.4135 8.08879 20.3147 6.4443C19.2159 4.79981 17.6541 3.51808 15.8268 2.7612C13.9996 2.00433 11.9889 1.80629 10.0491 2.19215C8.10929 2.578 6.32746 3.53041 4.92894 4.92893C3.53041 6.32746 2.578 8.10929 2.19215 10.0491C1.8063 11.9889 2.00433 13.9996 2.76121 15.8268C3.51809 17.6541 4.79981 19.2159 6.4443 20.3147C8.08879 21.4135 10.0222 22 12 22C14.6522 22 17.1957 20.9464 19.0711 19.0711C19.9997 18.1425 20.7363 17.0401 21.2388 15.8268C21.7413 14.6136 22 13.3132 22 12ZM4 12C4 10.4177 4.4692 8.87103 5.34825 7.55544C6.2273 6.23985 7.47673 5.21446 8.93854 4.60896C10.4003 4.00346 12.0089 3.84504 13.5607 4.15372C15.1126 4.4624 16.538 5.22433 17.6569 6.34315C18.7757 7.46197 19.5376 8.88743 19.8463 10.4393C20.155 11.9911 19.9965 13.5997 19.391 15.0615C18.7855 16.5233 17.7602 17.7727 16.4446 18.6518C15.129 19.5308 13.5823 20 12 20C9.87827 20 7.84344 19.1571 6.34315 17.6569C4.84286 16.1566 4 14.1217 4 12Z" fill="currentColor"></path></svg>
                        </a>
                    </div>
                    <div class="w-full md:w-1/2 px-4">
                        <div class="flex flex-wrap justify-end">
                            <div class="w-full md:w-auto py-1 md:py-0 md:mr-4"><a class="inline-block py-3 px-5 w-full leading-5 font-medium text-center border border-gray-200 rounded-xl shadow-sm btn-decline hover:bg-white/60 transition" href="javascript:void(0);" style="color: var(--text-primary);">{{ __("Decline") }}</a></div>
                            <div class="w-full md:w-auto py-1 md:py-0"><a class="btn-accent inline-block py-3 px-5 w-full leading-5 text-center btn-accept" href="javascript:void(0);">{{ __("Allow") }}</a></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="{{ theme_public_asset('js/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('js/main.js') }}"></script>
</body>
</html>