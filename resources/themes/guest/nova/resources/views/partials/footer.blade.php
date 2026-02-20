<section class="relative z-10 mt-10">
    {{-- Gradient accent line at top --}}
    <div class="h-px w-full" style="background: var(--accent-gradient);"></div>

    <div class="glass-card" style="border-radius: 0; border-left: none; border-right: none; border-bottom: none;">
        <div class="container px-4 mx-auto">
            <div class="py-10">
                <div class="flex flex-wrap items-center justify-between -m-4">
                    {{-- Logo --}}
                    <div class="w-auto p-4">
                        <a href="{{ url('/') }}">
                            <img class="h-9" src="{{ url(get_option("website_logo_brand_dark", asset('public/img/logo-brand-dark.png'))) }}" alt="">
                        </a>
                    </div>
                    {{-- Main Menu --}}
                    <ul class="flex flex-wrap -m-4 md:-m-6 p-4">
                        <li class="p-4 md:p-6">
                            <a class="font-medium tracking-tight transition duration-200 {{ request()->is('/') ? '' : '' }}"
                               style="{{ request()->is('/') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}"
                               href="{{ url('') }}">
                                {{ __("Home") }}
                            </a>
                        </li>
                        <li class="p-4 md:p-6">
                            <a class="font-medium tracking-tight transition duration-200"
                               style="color: var(--text-secondary);"
                               href="{{ url('') }}#features">
                                {{ __("Features") }}
                            </a>
                        </li>
                        <li class="p-4 md:p-6">
                            <a class="font-medium tracking-tight transition duration-200"
                               style="{{ request()->is('pricing*') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}"
                               href="{{ url('pricing') }}">
                                {{ __("Pricing") }}
                            </a>
                        </li>
                        <li class="p-4 md:p-6">
                            <a class="font-medium tracking-tight transition duration-200"
                               style="{{ request()->is('faqs*') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}"
                               href="{{ url('faqs') }}">
                                {{ __("FAQs") }}
                            </a>
                        </li>
                        <li class="p-4 md:p-6">
                            <a class="font-medium tracking-tight transition duration-200"
                               style="{{ request()->is('blogs*') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}"
                               href="{{ url('blogs') }}">
                                {{ __("Blog") }}
                            </a>
                        </li>
                        <li class="p-4 md:p-6">
                            <a class="font-medium tracking-tight transition duration-200"
                               style="{{ request()->is('contact*') ? 'color: var(--accent-dark);' : 'color: var(--text-secondary);' }}"
                               href="{{ url('contact') }}">
                                {{ __("Contact") }}
                            </a>
                        </li>
                    </ul>

                    {{-- Social Icons --}}
                    <div class="w-auto p-4">
                        <div class="flex flex-wrap items-center -m-3">
                            @if(get_option("social_page_facebook", ""))
                                <div class="w-auto p-3">
                                    <a class="transition duration-200 hover:opacity-70"
                                       style="color: var(--text-secondary);"
                                       href="{{ get_option('social_page_facebook') }}"
                                       title="Facebook" target="_blank" rel="noopener">
                                        <i class="fab fa-facebook fa-lg"></i>
                                    </a>
                                </div>
                            @endif

                            @if(get_option("social_page_instagram", ""))
                                <div class="w-auto p-3">
                                    <a class="transition duration-200 hover:opacity-70"
                                       style="color: var(--text-secondary);"
                                       href="{{ get_option('social_page_instagram') }}"
                                       title="Instagram" target="_blank" rel="noopener">
                                        <i class="fab fa-instagram fa-lg"></i>
                                    </a>
                                </div>
                            @endif

                            @if(get_option("social_page_tiktok", ""))
                                <div class="w-auto p-3">
                                    <a class="transition duration-200 hover:opacity-70"
                                       style="color: var(--text-secondary);"
                                       href="{{ get_option('social_page_tiktok') }}"
                                       title="TikTok" target="_blank" rel="noopener">
                                        <i class="fab fa-tiktok fa-lg"></i>
                                    </a>
                                </div>
                            @endif

                            @if(get_option("social_page_youtube", ""))
                                <div class="w-auto p-3">
                                    <a class="transition duration-200 hover:opacity-70"
                                       style="color: var(--text-secondary);"
                                       href="{{ get_option('social_page_youtube') }}"
                                       title="YouTube" target="_blank" rel="noopener">
                                        <i class="fab fa-youtube fa-lg"></i>
                                    </a>
                                </div>
                            @endif

                            @if(get_option("social_page_x", ""))
                                <div class="w-auto p-3">
                                    <a class="transition duration-200 hover:opacity-70"
                                       style="color: var(--text-secondary);"
                                       href="{{ get_option('social_page_x') }}"
                                       title="X (Twitter)" target="_blank" rel="noopener">
                                        <i class="fab fa-x-twitter fa-lg"></i>
                                    </a>
                                </div>
                            @endif

                            @if(get_option("social_page_pinterest", ""))
                                <div class="w-auto p-3">
                                    <a class="transition duration-200 hover:opacity-70"
                                       style="color: var(--text-secondary);"
                                       href="{{ get_option('social_page_pinterest') }}"
                                       title="Pinterest" target="_blank" rel="noopener">
                                        <i class="fab fa-pinterest fa-lg"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            {{-- Bottom bar --}}
            <div class="py-5" style="border-top: 1px solid var(--glass-border);">
                <div class="flex flex-wrap justify-between items-center -m-4">
                    <div class="w-auto p-4">
                        <p class="tracking-tight text-sm" style="color: var(--text-muted);">&copy; {{ date('Y') }} {{ get_option("website_title", "ARTime") }}. {{ __("All Rights Reserved") }}</p>
                    </div>
                    <div class="w-auto p-4">
                        <div class="flex flex-wrap gap-6">
                            <a class="tracking-tight text-sm transition duration-200 hover:opacity-70" style="color: var(--text-muted);" href="{{ url('privacy-policy') }}">{{ __("Privacy Policy") }}</a>
                            <a class="tracking-tight text-sm transition duration-200 hover:opacity-70" style="color: var(--text-muted);" href="{{ url('terms-of-service') }}">{{ __("Terms & Conditions") }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>