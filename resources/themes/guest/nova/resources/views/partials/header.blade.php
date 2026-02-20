<section x-data="{ mobileNavOpen: false, scrolled: false }"
        x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 20 })"
        class="fixed top-0 left-0 right-0 z-60">
    <div :class="scrolled ? 'glass-header scrolled' : 'glass-header'">
        <div class="container mx-auto">
            <div class="flex items-center justify-between px-4 py-4">
                <div class="w-auto">
                    <a href="{{ url("") }}">
                        <img class="h-9" src="{{ url( get_option("website_logo_brand_dark", asset('public/img/logo-brand-dark.png')) ) }}" alt="">
                    </a>
                </div>
                <div class="w-auto">
                    <div class="flex items-center justify-between">
                        <div class="w-auto hidden lg:block">
                            <ul class="flex items-center mr-10">
                                <li class="mr-8">
                                    <a href="{{ url('') }}"
                                       class="font-medium transition duration-200 {{ request()->is('/') ? 'text-accent font-semibold' : 'hover:text-[#0891b2]' }}"
                                       style="{{ request()->is('/') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}">
                                        {{ __("Home") }}
                                    </a>
                                </li>
                                <li class="mr-8">
                                    <a href="{{ url('') }}#features"
                                       class="font-medium transition duration-200 hover:text-[#0891b2]"
                                       style="color: var(--text-secondary);">
                                        {{ __("Features") }}
                                    </a>
                                </li>
                                <li class="mr-8">
                                    <a href="{{ url('pricing') }}"
                                       class="font-medium transition duration-200 {{ request()->is('pricing*') ? 'font-semibold' : 'hover:text-[#0891b2]' }}"
                                       style="{{ request()->is('pricing*') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}">
                                        {{ __("Pricing") }}
                                    </a>
                                </li>
                                <li class="mr-8">
                                    <a href="{{ url('faqs') }}"
                                       class="font-medium transition duration-200 {{ request()->is('faqs*') ? 'font-semibold' : 'hover:text-[#0891b2]' }}"
                                       style="{{ request()->is('faqs*') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}">
                                        {{ __("FAQs") }}
                                    </a>
                                </li>
                                <li class="mr-8">
                                    <a href="{{ url('blogs') }}"
                                       class="font-medium transition duration-200 {{ request()->is('blogs*') ? 'font-semibold' : 'hover:text-[#0891b2]' }}"
                                       style="{{ request()->is('blogs*') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}">
                                        {{ __("Blog") }}
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ url('contact') }}"
                                       class="font-medium transition duration-200 {{ request()->is('contact*') ? 'font-semibold' : 'hover:text-[#0891b2]' }}"
                                       style="{{ request()->is('contact*') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}">
                                        {{ __("Contact") }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="w-auto">
                            <div class="flex flex-wrap items-center gap-2">
                                {{-- Language Dropdown --}}
                                <div class="dropdown dropdown-hover dropdown-center">
                                    <div tabindex="0" class="flex items-center gap-1 px-3 min-h-[2rem] h-[2rem] cursor-pointer">
                                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color: var(--text-secondary);">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/>
                                        </svg>
                                        <svg class="size-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color: var(--text-muted);">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                    @php
                                        $languages = Language::getLanguages();
                                        $currentLang = app()->getLocale();
                                    @endphp

                                    @if($languages->isNotEmpty())
                                    <ul tabindex="0" class="dropdown-content z-[999] menu p-3 shadow rounded-xl w-40 glass-card-sm" style="background: rgba(255,255,255,0.9);">
                                        @foreach($languages as $language)
                                            <li>
                                                <a
                                                    href="{{ url('lang/' . $language->code) }}"
                                                    class="flex items-center gap-2 {{ $currentLang == $language->code ? 'font-semibold' : 'font-medium' }}"
                                                    style="{{ $currentLang == $language->code ? 'color: var(--accent-dark); background: rgba(3,252,244,0.1);' : '' }}"
                                                >
                                                    @if($language->icon)
                                                        <span class="size-4 text-center d-block -mt-1"><i class="{{ $language->icon }}"></i></span>
                                                    @endif
                                                    <span class="truncate">{{ $language->name }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                    @endif
                                </div>

                                @if(Auth::check())
                                    <div class="w-auto hidden lg:block">
                                        <a href="{{ url('app/dashboard') }}" class="btn-accent text-sm py-2.5 px-5">
                                            {{ __('Dashboard') }}
                                        </a>
                                    </div>
                                @else
                                    <div class="w-auto hidden lg:block">
                                        <a href="{{ url('auth/login') }}" class="font-medium transition duration-200 hover:text-[#0891b2] py-2.5 px-4" style="color: var(--text-secondary);">
                                            {{ __("Sign In") }}
                                        </a>
                                    </div>
                                    @if(get_option("auth_signup_page_status", 1))
                                    <div class="w-auto hidden lg:block">
                                        <a href="{{ url('auth/signup') }}" class="btn-accent text-sm py-2.5 px-5">
                                            {{ __("Sign Up") }}
                                        </a>
                                    </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="w-auto lg:hidden ml-3">
                            <button x-on:click="mobileNavOpen = !mobileNavOpen" class="flex items-center justify-center w-10 h-10 rounded-xl" style="background: var(--accent-gradient);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M21 18H3M21 12H3M21 6H3" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile navigation --}}
    <div :class="{'block': mobileNavOpen, 'hidden': !mobileNavOpen}" class="hidden fixed top-0 left-0 bottom-0 w-4/6 sm:max-w-xs z-50">
        <div x-on:click="mobileNavOpen = !mobileNavOpen" class="fixed inset-0 bg-gray-800 opacity-80"></div>
        <nav class="relative z-10 px-9 pt-8 h-full overflow-y-auto" style="background: var(--bg-page);">
            <div class="flex flex-wrap justify-between h-full">
                <div class="w-full">
                    <div class="flex items-center justify-between -m-2">
                        <div class="w-auto p-2">
                            <a class="inline-block" href="{{ url("") }}">
                                <img class="h-9" src="{{ url( get_option("website_logo_brand_dark", asset('public/img/logo-brand-dark.png')) ) }}" alt="">
                            </a>
                        </div>
                        <div class="w-auto p-2">
                            <button x-on:click="mobileNavOpen = !mobileNavOpen">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M6 18L18 6M6 6L18 18" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col justify-center py-16 w-full">
                    <ul>
                        <li class="mb-10">
                            <a class="font-medium transition duration-200 {{ request()->is('/') ? '' : 'hover:text-[#0891b2]' }}"
                               style="{{ request()->is('/') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}"
                               href="{{ url('') }}">
                                {{ __("Home") }}
                            </a>
                        </li>
                        <li class="mb-10">
                            <a class="font-medium transition duration-200 hover:text-[#0891b2]"
                               style="color: var(--text-secondary);"
                               href="{{ url('') }}#features">
                                {{ __("Features") }}
                            </a>
                        </li>
                        <li class="mb-10">
                            <a class="font-medium transition duration-200 {{ request()->is('pricing*') ? '' : 'hover:text-[#0891b2]' }}"
                               style="{{ request()->is('pricing*') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}"
                               href="{{ url('pricing') }}">
                                {{ __("Pricing") }}
                            </a>
                        </li>
                        <li class="mb-10">
                            <a class="font-medium transition duration-200 {{ request()->is('faqs*') ? '' : 'hover:text-[#0891b2]' }}"
                               style="{{ request()->is('faqs*') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}"
                               href="{{ url('faqs') }}">
                                {{ __("FAQs") }}
                            </a>
                        </li>
                        <li class="mb-10">
                            <a class="font-medium transition duration-200 {{ request()->is('blogs*') ? '' : 'hover:text-[#0891b2]' }}"
                               style="{{ request()->is('blogs*') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}"
                               href="{{ url('blogs') }}">
                                {{ __("Blog") }}
                            </a>
                        </li>
                        <li class="mb-10">
                            <a class="font-medium transition duration-200 {{ request()->is('contact*') ? '' : 'hover:text-[#0891b2]' }}"
                               style="{{ request()->is('contact*') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}"
                               href="{{ url('contact') }}">
                                {{ __("Contact") }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="flex flex-col justify-end w-full pb-8">
                    <div class="flex flex-wrap gap-3">
                        @if(Auth::check())
                            <div class="w-full">
                                <a href="{{ url('app/dashboard') }}"
                                   class="btn-accent block text-center py-3 px-5 w-full">
                                    {{ __('Dashboard') }}
                                </a>
                            </div>
                        @else
                            <div class="w-full">
                                <a href="{{ url('auth/login') }}"
                                   class="block text-center py-3 px-5 w-full font-medium rounded-xl border transition"
                                   style="color: var(--text-secondary); border-color: var(--glass-border);">
                                    {{ __("Sign In") }}
                                </a>
                            </div>
                            @if(get_option("auth_signup_page_status", 1))
                                <div class="w-full">
                                    <a href="{{ url('auth/signup') }}"
                                       class="btn-accent block text-center py-3 px-5 w-full">
                                        {{ __("Sign Up") }}
                                    </a>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </nav>
    </div>
</section>