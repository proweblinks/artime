{{-- ============================================================
     SECTION 1: HERO — "Create Anything with AI" + Hyperspeed BG
     ============================================================ --}}
<section class="relative overflow-hidden" style="background: #000;">
    {{-- Hyperspeed Three.js canvas container (desktop) --}}
    <div id="hyperspeed-container" style="position:absolute;inset:0;z-index:0;overflow:hidden;"></div>

    {{-- Mobile CSS-only animated light streaks fallback --}}
    <div class="hero-mobile-bg">
        <div class="hero-streak-1"></div>
        <div class="hero-streak-2"></div>
        <div class="hero-streak-3"></div>
        <div class="hero-glow"></div>
    </div>

    {{-- Dark-to-light gradient fade at bottom --}}
    <div style="position:absolute;bottom:0;left:0;right:0;height:180px;z-index:1;background:linear-gradient(to bottom, transparent 0%, #f0f4f8 100%);pointer-events:none;"></div>

    <div class="container px-4 mx-auto relative z-10 pt-36 pb-24 md:pt-44 md:pb-32">
        <div class="max-w-4xl mx-auto text-center">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 mb-8" style="padding:0.375rem 0.875rem;background:rgba(255,255,255,0.08);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.12);border-radius:999px;font-size:0.8125rem;font-weight:500;color:rgba(255,255,255,0.7);">
                <span class="w-2 h-2 rounded-full" style="background: var(--accent);"></span>
                <span>{{ __("AI-First Creative Platform") }}</span>
            </div>

            {{-- Headline --}}
            <h1 class="text-4xl sm:text-5xl md:text-7xl font-bold leading-tight mb-6" style="color: #fff; font-family: 'General Sans', sans-serif;">
                {{ __("Create Anything") }}
                <span class="gradient-text">{{ __("with AI") }}</span>
            </h1>

            {{-- Subheadline --}}
            <p class="text-lg md:text-xl max-w-2xl mx-auto mb-10 leading-relaxed" style="color: rgba(255,255,255,0.65);">
                {{ __("Generate videos, images, marketing campaigns, and social content — all from a single prompt. The complete AI creative platform for creators and businesses.") }}
            </p>

            {{-- CTA Buttons --}}
            <div class="flex flex-wrap justify-center gap-4 mb-14">
                <a href="{{ url('auth/signup') }}" class="btn-accent text-base py-3.5 px-8">
                    <i class="fa-light fa-sparkles"></i>
                    {{ __("Start Creating Free") }}
                </a>
                <a href="{{ url('') }}#how-it-works" class="text-base py-3.5 px-8 inline-flex items-center gap-2 font-semibold rounded-xl transition-all duration-300" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);color:rgba(255,255,255,0.85);backdrop-filter:blur(8px);">
                    <i class="fa-light fa-circle-play"></i>
                    {{ __("See How It Works") }}
                </a>
            </div>

            {{-- Capability Pills --}}
            <div class="flex flex-wrap justify-center gap-3 mb-16">
                @foreach(['fa-video' => 'AI Video', 'fa-image' => 'AI Images', 'fa-palette' => 'Content Studio', 'fa-share-nodes' => 'Social Publishing', 'fa-microphone' => 'Voice & Music'] as $icon => $label)
                <span style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.875rem;background:rgba(255,255,255,0.06);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.1);border-radius:999px;font-size:0.8125rem;font-weight:500;color:rgba(255,255,255,0.6);">
                    <i class="fa-light {{ $icon }}" style="color:#03fcf4;"></i> {{ __($label) }}
                </span>
                @endforeach
            </div>
        </div>

        {{-- App Mockup — Dark browser window showing the ARTime dashboard --}}
        <div class="relative max-w-5xl mx-auto mt-4">
            <div class="rounded-2xl overflow-hidden" style="background: rgba(26,26,46,0.85); backdrop-filter:blur(16px); box-shadow: 0 25px 60px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.08);">
                {{-- Browser chrome --}}
                <div class="flex items-center gap-2 px-5 py-3" style="background: rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.06);">
                    <div class="w-3 h-3 rounded-full" style="background: #ff5f57;"></div>
                    <div class="w-3 h-3 rounded-full" style="background: #febc2e;"></div>
                    <div class="w-3 h-3 rounded-full" style="background: #28c840;"></div>
                    <div class="flex-1 mx-4">
                        <div class="max-w-sm mx-auto h-6 rounded-lg flex items-center justify-center px-3" style="background: rgba(255,255,255,0.06);">
                            <span class="text-xs" style="color: rgba(255,255,255,0.3);">artime.ai/app/video-wizard/studio</span>
                        </div>
                    </div>
                </div>
                {{-- App body --}}
                <div class="flex" style="min-height: 320px;">
                    {{-- Sidebar --}}
                    <div class="hidden md:block w-14 flex-shrink-0 py-4 px-2 space-y-4" style="background: rgba(255,255,255,0.03); border-right: 1px solid rgba(255,255,255,0.06);">
                        <div class="w-10 h-10 rounded-xl mx-auto flex items-center justify-center" style="background: var(--accent-gradient);">
                            <i class="fa-light fa-sparkles text-white text-sm"></i>
                        </div>
                        <div class="w-10 h-10 rounded-xl mx-auto flex items-center justify-center" style="background: rgba(255,255,255,0.06);">
                            <i class="fa-light fa-clapperboard-play text-sm" style="color: rgba(255,255,255,0.4);"></i>
                        </div>
                        <div class="w-10 h-10 rounded-xl mx-auto flex items-center justify-center" style="background: rgba(255,255,255,0.06);">
                            <i class="fa-light fa-palette text-sm" style="color: rgba(255,255,255,0.4);"></i>
                        </div>
                        <div class="w-10 h-10 rounded-xl mx-auto flex items-center justify-center" style="background: rgba(255,255,255,0.06);">
                            <i class="fa-light fa-share-nodes text-sm" style="color: rgba(255,255,255,0.4);"></i>
                        </div>
                        <div class="w-10 h-10 rounded-xl mx-auto flex items-center justify-center" style="background: rgba(255,255,255,0.06);">
                            <i class="fa-light fa-chart-mixed text-sm" style="color: rgba(255,255,255,0.4);"></i>
                        </div>
                    </div>
                    {{-- Main content --}}
                    <div class="flex-1 p-5 md:p-6">
                        {{-- Title bar --}}
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-semibold" style="color: rgba(255,255,255,0.9);">{{ __("Video Wizard") }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium" style="background: rgba(3,252,244,0.15); color: #03fcf4;">{{ __("Premium") }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-7 px-3 rounded-lg flex items-center text-xs font-medium" style="background: var(--accent-gradient); color: white;">
                                    <i class="fa-light fa-sparkles mr-1"></i> {{ __("Generate") }}
                                </div>
                            </div>
                        </div>
                        {{-- Content area --}}
                        <div class="flex flex-wrap gap-4">
                            {{-- Phone preview mockup --}}
                            <div class="w-32 md:w-40 flex-shrink-0">
                                <div class="rounded-xl overflow-hidden" style="background: linear-gradient(180deg, #0891b2 0%, #1a1a2e 50%, #03fcf4 100%); aspect-ratio: 9/16;">
                                    <div class="flex items-center justify-center h-full">
                                        <div class="text-center">
                                            <div class="w-10 h-10 rounded-full mx-auto flex items-center justify-center mb-2" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(8px);">
                                                <i class="fa-solid fa-play text-white text-xs ml-0.5"></i>
                                            </div>
                                            <span class="text-xs" style="color: rgba(255,255,255,0.5);">0:24</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- Storyboard panels --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex gap-2 mb-3">
                                    <span class="text-xs px-2.5 py-1 rounded-lg font-medium" style="background: rgba(3,252,244,0.15); color: #03fcf4;">{{ __("Scene 1") }}</span>
                                    <span class="text-xs px-2.5 py-1 rounded-lg font-medium" style="background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.4);">{{ __("Scene 2") }}</span>
                                    <span class="text-xs px-2.5 py-1 rounded-lg font-medium" style="background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.4);">{{ __("Scene 3") }}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-2 mb-4">
                                    <div class="rounded-lg overflow-hidden" style="aspect-ratio: 16/9; background: linear-gradient(135deg, #0d9488, #0891b2);"><div class="w-full h-full flex items-center justify-center"><i class="fa-light fa-user text-sm" style="color: rgba(255,255,255,0.5);"></i></div></div>
                                    <div class="rounded-lg overflow-hidden" style="aspect-ratio: 16/9; background: linear-gradient(135deg, #1a1a2e, #0891b2);"><div class="w-full h-full flex items-center justify-center"><i class="fa-light fa-city text-sm" style="color: rgba(255,255,255,0.5);"></i></div></div>
                                    <div class="rounded-lg overflow-hidden" style="aspect-ratio: 16/9; background: linear-gradient(135deg, #0284c7, #03fcf4);"><div class="w-full h-full flex items-center justify-center"><i class="fa-light fa-sparkles text-sm" style="color: rgba(255,255,255,0.5);"></i></div></div>
                                </div>
                                <div class="mb-3"><div class="h-2 rounded-full w-full" style="background: rgba(255,255,255,0.06);"><div class="h-2 rounded-full" style="width: 65%; background: var(--accent-gradient);"></div></div></div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs px-2 py-0.5 rounded-full" style="background: rgba(3,252,244,0.15); color: #03fcf4;"><i class="fa-light fa-check mr-1"></i>{{ __("Concept") }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full" style="background: rgba(3,252,244,0.15); color: #03fcf4;"><i class="fa-light fa-check mr-1"></i>{{ __("Script") }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full" style="background: rgba(3,252,244,0.3); color: #03fcf4; border: 1px solid rgba(3,252,244,0.3);"><i class="fa-light fa-spinner-third fa-spin mr-1"></i>{{ __("Storyboard") }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full" style="background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.3);">{{ __("Animation") }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full" style="background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.3);">{{ __("Export") }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     SECTION 2: POWERED BY — Trust Strip
     ============================================================ --}}
<section class="py-10">
    <div class="container px-4 mx-auto">
        <div class="glass-card-sm py-6 px-8 text-center">
            <p class="text-sm font-medium mb-5" style="color: var(--text-muted); letter-spacing: 0.05em;">
                {{ __("POWERED BY WORLD-CLASS AI") }}
            </p>
            <div class="flex flex-wrap justify-center items-center gap-8 md:gap-14 opacity-50">
                <span class="text-lg font-semibold" style="color: var(--text-secondary);">OpenAI</span>
                <span class="text-lg font-semibold" style="color: var(--text-secondary);">Google</span>
                <span class="text-lg font-semibold" style="color: var(--text-secondary);">Anthropic</span>
                <span class="text-lg font-semibold" style="color: var(--text-secondary);">FAL.AI</span>
                <span class="text-lg font-semibold" style="color: var(--text-secondary);">MiniMax</span>
                <span class="text-lg font-semibold" style="color: var(--text-secondary);">DeepSeek</span>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     SECTION 3: PRODUCT SHOWCASE — "One Platform, Infinite Creativity"
     ============================================================ --}}
<section class="section-padding" id="features">
    <div class="container px-4 mx-auto">
        <div class="max-w-2xl mx-auto text-center mb-16">
            <h2 class="text-3xl md:text-5xl font-bold mb-5" style="color: var(--text-primary); font-family: 'General Sans', sans-serif;">
                {{ __("One Platform, Infinite Creativity") }}
            </h2>
            <p class="text-lg leading-relaxed" style="color: var(--text-secondary);">
                {{ __("Everything you need to create, publish, and grow — powered by the most advanced AI models available.") }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Video Wizard --}}
            <div class="glass-card p-8 text-center transition-all duration-300">
                <div class="feature-icon mx-auto mb-6">
                    <i class="fa-light fa-clapperboard-play"></i>
                </div>
                <h3 class="text-xl font-bold mb-3" style="color: var(--text-primary);">{{ __("Video Wizard") }}</h3>
                <p class="leading-relaxed mb-5" style="color: var(--text-secondary);">
                    {{ __("Turn any idea into a cinematic video with AI-generated scripts, storyboards, voiceover, and animation.") }}
                </p>
                <a href="{{ url('auth/signup') }}" class="text-sm font-semibold inline-flex items-center gap-1" style="color: var(--accent-dark);">
                    {{ __("Try it free") }} <i class="fa-light fa-arrow-right text-xs"></i>
                </a>
            </div>

            {{-- Content Studio --}}
            <div class="glass-card p-8 text-center transition-all duration-300">
                <div class="feature-icon mx-auto mb-6">
                    <i class="fa-light fa-palette"></i>
                </div>
                <h3 class="text-xl font-bold mb-3" style="color: var(--text-primary);">{{ __("Content Studio") }}</h3>
                <p class="leading-relaxed mb-5" style="color: var(--text-secondary);">
                    {{ __("Extract your brand DNA from your website, then generate campaigns, creatives, and ads — all on-brand.") }}
                </p>
                <a href="{{ url('auth/signup') }}" class="text-sm font-semibold inline-flex items-center gap-1" style="color: var(--accent-dark);">
                    {{ __("Try it free") }} <i class="fa-light fa-arrow-right text-xs"></i>
                </a>
            </div>

            {{-- Social Publishing --}}
            <div class="glass-card p-8 text-center transition-all duration-300">
                <div class="feature-icon mx-auto mb-6">
                    <i class="fa-light fa-share-nodes"></i>
                </div>
                <h3 class="text-xl font-bold mb-3" style="color: var(--text-primary);">{{ __("Social Publishing") }}</h3>
                <p class="leading-relaxed mb-5" style="color: var(--text-secondary);">
                    {{ __("Schedule and publish to every platform. AI writes your captions, picks the best times, and tracks performance.") }}
                </p>
                <a href="{{ url('auth/signup') }}" class="text-sm font-semibold inline-flex items-center gap-1" style="color: var(--accent-dark);">
                    {{ __("Try it free") }} <i class="fa-light fa-arrow-right text-xs"></i>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     SECTION 4: VIDEO WIZARD DEEP-DIVE
     ============================================================ --}}
<section class="section-padding">
    <div class="container px-4 mx-auto">
        <div class="flex flex-wrap items-center -mx-4">
            <div class="w-full lg:w-1/2 px-4 mb-12 lg:mb-0">
                <div class="max-w-lg">
                    <span class="text-sm font-semibold uppercase tracking-wider mb-4 inline-block" style="color: var(--accent-dark);">
                        {{ __("Video Wizard") }}
                    </span>
                    <h2 class="text-3xl md:text-4xl font-bold mb-6 leading-tight" style="color: var(--text-primary); font-family: 'General Sans', sans-serif;">
                        {{ __("From Script to Screen, Powered by AI") }}
                    </h2>
                    <p class="text-lg leading-relaxed mb-8" style="color: var(--text-secondary);">
                        {{ __("Describe your vision. ARTime writes the script, generates every image, animates scenes with lip-synced characters, adds voiceover and music, and exports a ready-to-publish video. No editing skills needed.") }}
                    </p>
                    <div class="flex flex-wrap gap-2 mb-8">
                        <span class="glass-pill text-xs"><i class="fa-light fa-pen-nib"></i> {{ __("AI Script Writing") }}</span>
                        <span class="glass-pill text-xs"><i class="fa-light fa-image"></i> {{ __("Image Generation") }}</span>
                        <span class="glass-pill text-xs"><i class="fa-light fa-lips"></i> {{ __("Lip-Sync Animation") }}</span>
                        <span class="glass-pill text-xs"><i class="fa-light fa-microphone"></i> {{ __("AI Voiceover") }}</span>
                        <span class="glass-pill text-xs"><i class="fa-light fa-download"></i> {{ __("One-Click Export") }}</span>
                    </div>
                    <a href="{{ url('auth/signup') }}" class="btn-accent">
                        {{ __("Try Video Wizard") }}
                        <i class="fa-light fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="w-full lg:w-1/2 px-4">
                {{-- Glass mockup of video wizard pipeline --}}
                <div class="glass-card p-6 md:p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-3 h-3 rounded-full" style="background: #ff6b6b;"></div>
                        <div class="w-3 h-3 rounded-full" style="background: #ffd93d;"></div>
                        <div class="w-3 h-3 rounded-full" style="background: #6bcb77;"></div>
                    </div>
                    {{-- Pipeline steps mockup --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold" style="background: var(--accent-gradient);">1</div>
                            <div class="flex-1 h-3 rounded-full" style="background: var(--accent); opacity: 0.2;"><div class="h-3 rounded-full w-full" style="background: var(--accent-gradient);"></div></div>
                            <span class="text-xs font-medium" style="color: var(--text-muted);">{{ __("Concept") }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold" style="background: var(--accent-gradient);">2</div>
                            <div class="flex-1 h-3 rounded-full" style="background: var(--accent); opacity: 0.2;"><div class="h-3 rounded-full w-4/5" style="background: var(--accent-gradient);"></div></div>
                            <span class="text-xs font-medium" style="color: var(--text-muted);">{{ __("Script") }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold" style="background: var(--accent-gradient);">3</div>
                            <div class="flex-1 h-3 rounded-full" style="background: var(--accent); opacity: 0.2;"><div class="h-3 rounded-full w-3/5" style="background: var(--accent-gradient);"></div></div>
                            <span class="text-xs font-medium" style="color: var(--text-muted);">{{ __("Storyboard") }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold opacity-40" style="background: var(--accent-gradient);">4</div>
                            <div class="flex-1 h-3 rounded-full" style="background: var(--accent); opacity: 0.1;"></div>
                            <span class="text-xs font-medium" style="color: var(--text-muted);">{{ __("Animation") }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold opacity-30" style="background: var(--accent-gradient);">5</div>
                            <div class="flex-1 h-3 rounded-full" style="background: var(--accent); opacity: 0.1;"></div>
                            <span class="text-xs font-medium" style="color: var(--text-muted);">{{ __("Export") }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     SECTION 5: CONTENT STUDIO DEEP-DIVE
     ============================================================ --}}
<section class="section-padding">
    <div class="container px-4 mx-auto">
        <div class="flex flex-wrap items-center -mx-4">
            <div class="w-full lg:w-1/2 px-4 mb-12 lg:mb-0 order-2 lg:order-1">
                {{-- Glass mockup of content studio --}}
                <div class="glass-card p-6 md:p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-3 h-3 rounded-full" style="background: #ff6b6b;"></div>
                        <div class="w-3 h-3 rounded-full" style="background: #ffd93d;"></div>
                        <div class="w-3 h-3 rounded-full" style="background: #6bcb77;"></div>
                    </div>
                    {{-- Brand DNA mockup --}}
                    <div class="mb-6">
                        <div class="text-xs font-semibold mb-3" style="color: var(--text-muted);">{{ __("BRAND DNA") }}</div>
                        <div class="flex gap-2 mb-4">
                            <div class="w-8 h-8 rounded-lg" style="background: #1a1a2e;"></div>
                            <div class="w-8 h-8 rounded-lg" style="background: #0891b2;"></div>
                            <div class="w-8 h-8 rounded-lg" style="background: #03fcf4;"></div>
                            <div class="w-8 h-8 rounded-lg" style="background: #f0f4f8;"></div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium" style="background: rgba(3,252,244,0.1); color: var(--accent-dark);">{{ __("Professional") }}</span>
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium" style="background: rgba(3,252,244,0.1); color: var(--accent-dark);">{{ __("Innovative") }}</span>
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium" style="background: rgba(3,252,244,0.1); color: var(--accent-dark);">{{ __("Modern") }}</span>
                        </div>
                    </div>
                    {{-- Creatives grid mockup --}}
                    <div class="grid grid-cols-3 gap-2">
                        <div class="rounded-lg h-20" style="background: linear-gradient(135deg, #1a1a2e, #0891b2);"></div>
                        <div class="rounded-lg h-20" style="background: linear-gradient(135deg, #0891b2, #03fcf4);"></div>
                        <div class="rounded-lg h-20" style="background: linear-gradient(135deg, #e0f2fe, #ccfbf1);"></div>
                    </div>
                </div>
            </div>
            <div class="w-full lg:w-1/2 px-4 order-1 lg:order-2 mb-12 lg:mb-0">
                <div class="max-w-lg lg:ml-auto">
                    <span class="text-sm font-semibold uppercase tracking-wider mb-4 inline-block" style="color: var(--accent-dark);">
                        {{ __("Content Studio") }}
                    </span>
                    <h2 class="text-3xl md:text-4xl font-bold mb-6 leading-tight" style="color: var(--text-primary); font-family: 'General Sans', sans-serif;">
                        {{ __("Your Brand, Amplified by AI") }}
                    </h2>
                    <p class="text-lg leading-relaxed mb-8" style="color: var(--text-secondary);">
                        {{ __("Paste your website URL. ARTime extracts your colors, fonts, tone, and aesthetic — your Brand DNA. Then generates campaign ideas, ad creatives, and product photoshoots that look like your brand, every time.") }}
                    </p>
                    <div class="flex flex-wrap gap-2 mb-8">
                        <span class="glass-pill text-xs"><i class="fa-light fa-dna"></i> {{ __("Brand DNA Extraction") }}</span>
                        <span class="glass-pill text-xs"><i class="fa-light fa-lightbulb"></i> {{ __("Campaign Generator") }}</span>
                        <span class="glass-pill text-xs"><i class="fa-light fa-wand-magic-sparkles"></i> {{ __("AI Creatives") }}</span>
                        <span class="glass-pill text-xs"><i class="fa-light fa-font"></i> {{ __("Text Overlays") }}</span>
                        <span class="glass-pill text-xs"><i class="fa-light fa-camera"></i> {{ __("Product Photoshoot") }}</span>
                    </div>
                    <a href="{{ url('auth/signup') }}" class="btn-accent">
                        {{ __("Try Content Studio") }}
                        <i class="fa-light fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     SECTION 6: AI CAPABILITIES GRID — "Built on the Best AI"
     ============================================================ --}}
<section class="section-padding">
    <div class="container px-4 mx-auto">
        <div class="max-w-2xl mx-auto text-center mb-16">
            <h2 class="text-3xl md:text-5xl font-bold mb-5" style="color: var(--text-primary); font-family: 'General Sans', sans-serif;">
                {{ __("Built on the Best AI") }}
            </h2>
            <p class="text-lg leading-relaxed" style="color: var(--text-secondary);">
                {{ __("Access the most powerful AI models through one platform. We integrate the best so you can focus on creating.") }}
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            {{-- Card 1 --}}
            <div class="glass-card p-6 transition-all duration-300">
                <div class="feature-icon mb-4">
                    <i class="fa-light fa-message-bot"></i>
                </div>
                <h3 class="font-bold mb-2" style="color: var(--text-primary);">{{ __("AI Text Generation") }}</h3>
                <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">{{ __("GPT-4o, Claude, Gemini, Grok, DeepSeek — choose the best model for every task.") }}</p>
            </div>
            {{-- Card 2 --}}
            <div class="glass-card p-6 transition-all duration-300">
                <div class="feature-icon mb-4">
                    <i class="fa-light fa-image-landscape"></i>
                </div>
                <h3 class="font-bold mb-2" style="color: var(--text-primary);">{{ __("AI Image Generation") }}</h3>
                <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">{{ __("Flux Pro, HiDream, Gemini Vision — photorealistic images and art from text prompts.") }}</p>
            </div>
            {{-- Card 3 --}}
            <div class="glass-card p-6 transition-all duration-300">
                <div class="feature-icon mb-4">
                    <i class="fa-light fa-film"></i>
                </div>
                <h3 class="font-bold mb-2" style="color: var(--text-primary);">{{ __("AI Video Generation") }}</h3>
                <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">{{ __("MiniMax, InfiniteTalk with lip-sync, Seedance — bring still images to life.") }}</p>
            </div>
            {{-- Card 4 --}}
            <div class="glass-card p-6 transition-all duration-300">
                <div class="feature-icon mb-4">
                    <i class="fa-light fa-waveform-lines"></i>
                </div>
                <h3 class="font-bold mb-2" style="color: var(--text-primary);">{{ __("AI Voice & Music") }}</h3>
                <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">{{ __("Kokoro TTS, Qwen TTS, OpenAI TTS — natural voices and custom soundtracks.") }}</p>
            </div>
            {{-- Card 5 --}}
            <div class="glass-card p-6 transition-all duration-300">
                <div class="feature-icon mb-4">
                    <i class="fa-light fa-calendar-clock"></i>
                </div>
                <h3 class="font-bold mb-2" style="color: var(--text-primary);">{{ __("Smart Publishing") }}</h3>
                <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">{{ __("Schedule, bulk post, RSS auto-publish — your content goes live on every platform automatically.") }}</p>
            </div>
            {{-- Card 6 --}}
            <div class="glass-card p-6 transition-all duration-300">
                <div class="feature-icon mb-4">
                    <i class="fa-light fa-users"></i>
                </div>
                <h3 class="font-bold mb-2" style="color: var(--text-primary);">{{ __("Team Collaboration") }}</h3>
                <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">{{ __("Multi-user workspaces, roles, and approval workflows for seamless teamwork.") }}</p>
            </div>
            {{-- Card 7 --}}
            <div class="glass-card p-6 transition-all duration-300">
                <div class="feature-icon mb-4">
                    <i class="fa-light fa-chart-mixed"></i>
                </div>
                <h3 class="font-bold mb-2" style="color: var(--text-primary);">{{ __("Analytics & Insights") }}</h3>
                <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">{{ __("Track performance across all channels with real-time dashboards and reports.") }}</p>
            </div>
            {{-- Card 8 --}}
            <div class="glass-card p-6 transition-all duration-300">
                <div class="feature-icon mb-4">
                    <i class="fa-light fa-globe"></i>
                </div>
                <h3 class="font-bold mb-2" style="color: var(--text-primary);">{{ __("Multi-Language") }}</h3>
                <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">{{ __("Create content in 50+ languages. AI detects and generates in your audience's language.") }}</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     SECTION 7: HOW IT WORKS — "Three Steps to Your First Creation"
     ============================================================ --}}
<section class="section-padding" id="how-it-works">
    <div class="container px-4 mx-auto">
        <div class="max-w-2xl mx-auto text-center mb-16">
            <h2 class="text-3xl md:text-5xl font-bold mb-5" style="color: var(--text-primary); font-family: 'General Sans', sans-serif;">
                {{ __("Three Steps to Your First Creation") }}
            </h2>
            <p class="text-lg leading-relaxed" style="color: var(--text-secondary);">
                {{ __("No learning curve. No editing skills. Just describe what you want and let AI do the rest.") }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Step 1 --}}
            <div class="glass-card p-8 text-center relative">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-6 text-2xl font-bold text-white" style="background: var(--accent-gradient);">
                    1
                </div>
                <h3 class="text-xl font-bold mb-3" style="color: var(--text-primary);">{{ __("Describe") }}</h3>
                <p class="leading-relaxed" style="color: var(--text-secondary);">
                    {{ __("Tell ARTime what you want. A video ad, a social campaign, a product photoshoot — just describe it in your own words.") }}
                </p>
                <div class="step-connector hidden md:block"></div>
            </div>
            {{-- Step 2 --}}
            <div class="glass-card p-8 text-center relative">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-6 text-2xl font-bold text-white" style="background: var(--accent-gradient);">
                    2
                </div>
                <h3 class="text-xl font-bold mb-3" style="color: var(--text-primary);">{{ __("Generate") }}</h3>
                <p class="leading-relaxed" style="color: var(--text-secondary);">
                    {{ __("AI creates everything: script, images, video, voiceover, music, text overlays. Review and refine in real-time.") }}
                </p>
                <div class="step-connector hidden md:block"></div>
            </div>
            {{-- Step 3 --}}
            <div class="glass-card p-8 text-center">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-6 text-2xl font-bold text-white" style="background: var(--accent-gradient);">
                    3
                </div>
                <h3 class="text-xl font-bold mb-3" style="color: var(--text-primary);">{{ __("Publish") }}</h3>
                <p class="leading-relaxed" style="color: var(--text-secondary);">
                    {{ __("Export your creation or publish directly to your social channels. One click, every platform.") }}
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     SECTION 8: SOCIAL PUBLISHING — "Automate Your Social Presence"
     ============================================================ --}}
<section class="section-padding">
    <div class="container px-4 mx-auto">
        <div class="flex flex-wrap items-center -mx-4">
            <div class="w-full lg:w-1/2 px-4 mb-12 lg:mb-0">
                {{-- Platform icons mockup --}}
                <div class="glass-card p-8">
                    <div class="grid grid-cols-4 gap-4">
                        <div class="glass-card-sm p-4 text-center">
                            <i class="fab fa-facebook text-2xl" style="color: #1877F2;"></i>
                        </div>
                        <div class="glass-card-sm p-4 text-center">
                            <i class="fab fa-instagram text-2xl" style="color: #E4405F;"></i>
                        </div>
                        <div class="glass-card-sm p-4 text-center">
                            <i class="fab fa-x-twitter text-2xl" style="color: #1a1a2e;"></i>
                        </div>
                        <div class="glass-card-sm p-4 text-center">
                            <i class="fab fa-tiktok text-2xl" style="color: #1a1a2e;"></i>
                        </div>
                        <div class="glass-card-sm p-4 text-center">
                            <i class="fab fa-youtube text-2xl" style="color: #FF0000;"></i>
                        </div>
                        <div class="glass-card-sm p-4 text-center">
                            <i class="fab fa-linkedin text-2xl" style="color: #0A66C2;"></i>
                        </div>
                        <div class="glass-card-sm p-4 text-center">
                            <i class="fab fa-pinterest text-2xl" style="color: #E60023;"></i>
                        </div>
                        <div class="glass-card-sm p-4 text-center">
                            <i class="fab fa-telegram text-2xl" style="color: #0088CC;"></i>
                        </div>
                    </div>
                    <div class="mt-6 text-center">
                        <p class="text-sm font-medium" style="color: var(--text-muted);">{{ __("8+ platforms supported") }}</p>
                    </div>
                </div>
            </div>
            <div class="w-full lg:w-1/2 px-4">
                <div class="max-w-lg lg:ml-auto">
                    <span class="text-sm font-semibold uppercase tracking-wider mb-4 inline-block" style="color: var(--accent-dark);">
                        {{ __("Social Publishing") }}
                    </span>
                    <h2 class="text-3xl md:text-4xl font-bold mb-6 leading-tight" style="color: var(--text-primary); font-family: 'General Sans', sans-serif;">
                        {{ __("Automate Your Social Presence") }}
                    </h2>
                    <p class="text-lg leading-relaxed mb-8" style="color: var(--text-secondary);">
                        {{ __("Connect all your social accounts in seconds. Schedule posts, auto-publish from RSS feeds, and let AI generate your content calendar — so your brand is always active, even when you're not.") }}
                    </p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-3">
                            <i class="fa-light fa-check-circle" style="color: var(--accent-dark);"></i>
                            <span style="color: var(--text-secondary);">{{ __("Unified Calendar — see all channels at a glance") }}</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fa-light fa-check-circle" style="color: var(--accent-dark);"></i>
                            <span style="color: var(--text-secondary);">{{ __("Bulk Scheduling — publish dozens of posts at once") }}</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fa-light fa-check-circle" style="color: var(--accent-dark);"></i>
                            <span style="color: var(--text-secondary);">{{ __("RSS Auto-Post — fresh content from any feed, hands-free") }}</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fa-light fa-check-circle" style="color: var(--accent-dark);"></i>
                            <span style="color: var(--text-secondary);">{{ __("AI Captions — generated text tailored to each platform") }}</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fa-light fa-check-circle" style="color: var(--accent-dark);"></i>
                            <span style="color: var(--text-secondary);">{{ __("Best-Time Publishing — post when your audience is most active") }}</span>
                        </li>
                    </ul>
                    <a href="{{ url('auth/signup') }}" class="btn-accent">
                        {{ __("Start Publishing") }}
                        <i class="fa-light fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     SECTION 9: PRICING (existing partial)
     ============================================================ --}}
@include("partials.pricing")

{{-- ============================================================
     SECTION 10: TESTIMONIALS — "Loved by Creators"
     ============================================================ --}}
<section class="section-padding">
    <div class="container px-4 mx-auto">
        <div class="max-w-2xl mx-auto text-center mb-16">
            <h2 class="text-3xl md:text-5xl font-bold mb-5" style="color: var(--text-primary); font-family: 'General Sans', sans-serif;">
                {{ __("Loved by Creators") }}
            </h2>
            <p class="text-lg leading-relaxed" style="color: var(--text-secondary);">
                {{ __("See what creators and businesses are saying about ARTime.") }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Testimonial 1 --}}
            <div class="glass-card p-8">
                <div class="flex gap-1 mb-5">
                    @for ($i = 0; $i < 5; $i++)
                        <svg width="18" height="18" viewBox="0 0 19 18" fill="none"><path d="M9.30769 0L12.1838 5.82662L18.6154 6.76111L13.9615 11.2977L15.0598 17.7032L9.30769 14.6801L3.55554 17.7032L4.65385 11.2977L0 6.76111L6.43162 5.82662L9.30769 0Z" fill="#F59E0B"></path></svg>
                    @endfor
                </div>
                <h3 class="text-lg font-bold mb-4" style="color: var(--text-primary);">
                    {{ __("'The Video Wizard is mind-blowing!'") }}
                </h3>
                <p class="mb-6 leading-relaxed" style="color: var(--text-secondary);">
                    {{ __("I described a product launch video and ARTime created the script, generated all the visuals, added voiceover, and exported a professional video in under 10 minutes. This would have taken my team days.") }}
                </p>
                <p class="font-bold text-sm" style="color: var(--text-primary);">{{ __("Sarah Chen — Creative Director") }}</p>
            </div>

            {{-- Testimonial 2 --}}
            <div class="glass-card p-8">
                <div class="flex gap-1 mb-5">
                    @for ($i = 0; $i < 5; $i++)
                        <svg width="18" height="18" viewBox="0 0 19 18" fill="none"><path d="M9.30769 0L12.1838 5.82662L18.6154 6.76111L13.9615 11.2977L15.0598 17.7032L9.30769 14.6801L3.55554 17.7032L4.65385 11.2977L0 6.76111L6.43162 5.82662L9.30769 0Z" fill="#F59E0B"></path></svg>
                    @endfor
                </div>
                <h3 class="text-lg font-bold mb-4" style="color: var(--text-primary);">
                    {{ __("'Content Studio saved our brand'") }}
                </h3>
                <p class="mb-6 leading-relaxed" style="color: var(--text-secondary);">
                    {{ __("We pasted our website URL and it extracted our entire brand identity. Now every campaign, every creative, every ad looks perfectly on-brand — without hiring a designer.") }}
                </p>
                <p class="font-bold text-sm" style="color: var(--text-primary);">{{ __("Michael Torres — Startup Founder") }}</p>
            </div>

            {{-- Testimonial 3 --}}
            <div class="glass-card p-8">
                <div class="flex gap-1 mb-5">
                    @for ($i = 0; $i < 5; $i++)
                        <svg width="18" height="18" viewBox="0 0 19 18" fill="none"><path d="M9.30769 0L12.1838 5.82662L18.6154 6.76111L13.9615 11.2977L15.0598 17.7032L9.30769 14.6801L3.55554 17.7032L4.65385 11.2977L0 6.76111L6.43162 5.82662L9.30769 0Z" fill="#F59E0B"></path></svg>
                    @endfor
                </div>
                <h3 class="text-lg font-bold mb-4" style="color: var(--text-primary);">
                    {{ __("'Finally, one tool for everything'") }}
                </h3>
                <p class="mb-6 leading-relaxed" style="color: var(--text-secondary);">
                    {{ __("I used to juggle five different apps for social media, video, and design. ARTime replaced them all. The AI publishing automation alone saves me 15 hours a week.") }}
                </p>
                <p class="font-bold text-sm" style="color: var(--text-primary);">{{ __("Emily Park — Social Media Manager") }}</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     SECTION 11: FAQ (existing partial)
     ============================================================ --}}
@include("partials.faqs")

{{-- ============================================================
     SECTION 12: BLOG (existing partial)
     ============================================================ --}}
@include("partials.home-blog")

{{-- ============================================================
     CTA SECTION — "Ready to Create?"
     ============================================================ --}}
<section class="section-padding">
    <div class="container px-4 mx-auto">
        <div class="glass-card p-12 md:p-16 text-center relative overflow-hidden">
            {{-- Accent glow --}}
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-96 h-96 rounded-full opacity-10" style="background: var(--accent); filter: blur(80px);"></div>

            <div class="relative z-10">
                <h2 class="text-3xl md:text-5xl font-bold mb-5" style="color: var(--text-primary); font-family: 'General Sans', sans-serif;">
                    {{ __("Ready to Create?") }}
                </h2>
                <p class="text-lg mb-10 max-w-lg mx-auto" style="color: var(--text-secondary);">
                    {{ __("Start free. No credit card required. Create your first AI video, campaign, or social post in minutes.") }}
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ url('auth/signup') }}" class="btn-accent text-base py-3.5 px-8">
                        <i class="fa-light fa-sparkles"></i>
                        {{ __("Start Creating Free") }}
                    </a>
                    <a href="{{ url('contact') }}" class="btn-ghost text-base py-3.5 px-8">
                        {{ __("Talk to Us") }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     HYPERSPEED THREE.JS BACKGROUND — vanilla JS (converted from React)
     Only loads on desktop (>768px) for performance
     ============================================================ --}}
<script type="module">
if (window.innerWidth > 768) {
(async function() {
    const THREE = await import('three');
    const { BloomEffect, EffectComposer, EffectPass, RenderPass, SMAAEffect, SMAAPreset } = await import('postprocessing');

    const container = document.getElementById('hyperspeed-container');
    if (!container) return;

    /* ── Helpers ── */
    const random = base => Array.isArray(base) ? Math.random()*(base[1]-base[0])+base[0] : Math.random()*base;
    const pickRandom = arr => Array.isArray(arr) ? arr[Math.floor(Math.random()*arr.length)] : arr;
    function lerp(cur,tgt,spd=0.1,lim=0.001){let c=(tgt-cur)*spd;if(Math.abs(c)<lim)c=tgt-cur;return c;}
    const nsin = v => Math.sin(v)*0.5+0.5;
    function resizeToDisplay(renderer,setSize){const c=renderer.domElement;const w=c.clientWidth,h=c.clientHeight;if(c.width!==w||c.height!==h){setSize(w,h,false);return true;}return false;}

    /* ── Effect Options (ARTime branded) ── */
    const opts = {
        distortion: 'turbulentDistortion',
        length: 400, roadWidth: 10, islandWidth: 2,
        lanesPerRoad: 4, fov: 90, fovSpeedUp: 150, speedUp: 2,
        carLightsFade: 0.4, totalSideLightSticks: 20,
        lightPairsPerRoadWay: 40,
        shoulderLinesWidthPercentage: 0.05,
        brokenLinesWidthPercentage: 0.1,
        brokenLinesLengthPercentage: 0.5,
        lightStickWidth: [0.12, 0.5],
        lightStickHeight: [1.3, 1.7],
        movingAwaySpeed: [60, 80],
        movingCloserSpeed: [-120, -160],
        carLightsLength: [400*0.03, 400*0.2],
        carLightsRadius: [0.05, 0.14],
        carWidthPercentage: [0.3, 0.5],
        carShiftX: [-0.8, 0.8],
        carFloorSeparation: [0, 5],
        colors: {
            roadColor: 0x080808, islandColor: 0x0a0a0a, background: 0x000000,
            shoulderLines: 0x131313, brokenLines: 0x131313,
            leftCars:  [0x03fcf4, 0x0891b2, 0x06b6d4],
            rightCars: [0x03b3c3, 0x0e5ea5, 0x324555],
            sticks: 0x03fcf4
        }
    };

    /* ── Distortion Definitions ── */
    const turbulentUniforms = {
        uFreq: { value: new THREE.Vector4(4,8,8,1) },
        uAmp:  { value: new THREE.Vector4(25,5,10,10) }
    };

    const distortions = {
        turbulentDistortion: {
            uniforms: turbulentUniforms,
            getDistortion: `
                uniform vec4 uFreq;
                uniform vec4 uAmp;
                float nsin(float val){ return sin(val)*0.5+0.5; }
                #define PI 3.14159265358979
                float getDistortionX(float progress){
                    return ( cos(PI*progress*uFreq.r+uTime)*uAmp.r + pow(cos(PI*progress*uFreq.g+uTime*(uFreq.g/uFreq.r)),2.)*uAmp.g );
                }
                float getDistortionY(float progress){
                    return ( -nsin(PI*progress*uFreq.b+uTime)*uAmp.b + -pow(nsin(PI*progress*uFreq.a+uTime/(uFreq.b/uFreq.a)),5.)*uAmp.a );
                }
                vec3 getDistortion(float progress){
                    return vec3( getDistortionX(progress)-getDistortionX(0.0125), getDistortionY(progress)-getDistortionY(0.0125), 0. );
                }
            `,
            getJS: (progress, time) => {
                const uF = turbulentUniforms.uFreq.value, uA = turbulentUniforms.uAmp.value;
                const getX = p => Math.cos(Math.PI*p*uF.x+time)*uA.x + Math.pow(Math.cos(Math.PI*p*uF.y+time*(uF.y/uF.x)),2)*uA.y;
                const getY = p => -nsin(Math.PI*p*uF.z+time)*uA.z - Math.pow(nsin(Math.PI*p*uF.w+time/(uF.z/uF.w)),5)*uA.w;
                let d = new THREE.Vector3(getX(progress)-getX(progress+0.007), getY(progress)-getY(progress+0.007), 0);
                return d.multiply(new THREE.Vector3(-2,-5,0)).add(new THREE.Vector3(0,0,-10));
            }
        }
    };

    opts.distortion = distortions[opts.distortion];

    /* ── GLSL Shader Chunks ── */
    const carLightsFragment = `
#define USE_FOG
${THREE.ShaderChunk['fog_pars_fragment']}
varying vec3 vColor; varying vec2 vUv; uniform vec2 uFade;
void main(){
    vec3 color=vec3(vColor); float alpha=smoothstep(uFade.x,uFade.y,vUv.x);
    gl_FragColor=vec4(color,alpha); if(gl_FragColor.a<0.0001)discard;
    ${THREE.ShaderChunk['fog_fragment']}
}
    `;
    const carLightsVertex = `
#define USE_FOG
${THREE.ShaderChunk['fog_pars_vertex']}
attribute vec3 aOffset; attribute vec3 aMetrics; attribute vec3 aColor;
uniform float uTravelLength; uniform float uTime;
varying vec2 vUv; varying vec3 vColor;
${opts.distortion.getDistortion}
void main(){
    vec3 transformed=position.xyz; float radius=aMetrics.r; float myLength=aMetrics.g; float speed=aMetrics.b;
    transformed.xy*=radius; transformed.z*=myLength;
    transformed.z+=myLength-mod(uTime*speed+aOffset.z,uTravelLength); transformed.xy+=aOffset.xy;
    float progress=abs(transformed.z/uTravelLength); transformed.xyz+=getDistortion(progress);
    vec4 mvPosition=modelViewMatrix*vec4(transformed,1.); gl_Position=projectionMatrix*mvPosition;
    vUv=uv; vColor=aColor;
    ${THREE.ShaderChunk['fog_vertex']}
}
    `;
    const sideSticksVertex = `
#define USE_FOG
${THREE.ShaderChunk['fog_pars_vertex']}
attribute float aOffset; attribute vec3 aColor; attribute vec2 aMetrics;
uniform float uTravelLength; uniform float uTime; varying vec3 vColor;
mat4 rotationY(in float angle){return mat4(cos(angle),0,sin(angle),0, 0,1.0,0,0, -sin(angle),0,cos(angle),0, 0,0,0,1);}
${opts.distortion.getDistortion}
void main(){
    vec3 transformed=position.xyz; float width=aMetrics.x; float height=aMetrics.y;
    transformed.xy*=vec2(width,height); float time=mod(uTime*60.*2.+aOffset,uTravelLength);
    transformed=(rotationY(3.14/2.)*vec4(transformed,1.)).xyz;
    transformed.z+=-uTravelLength+time; float progress=abs(transformed.z/uTravelLength);
    transformed.xyz+=getDistortion(progress); transformed.y+=height/2.; transformed.x+=-width/2.;
    vec4 mvPosition=modelViewMatrix*vec4(transformed,1.); gl_Position=projectionMatrix*mvPosition;
    vColor=aColor;
    ${THREE.ShaderChunk['fog_vertex']}
}
    `;
    const sideSticksFragment = `
#define USE_FOG
${THREE.ShaderChunk['fog_pars_fragment']}
varying vec3 vColor;
void main(){
    gl_FragColor=vec4(vColor,1.);
    ${THREE.ShaderChunk['fog_fragment']}
}
    `;
    const roadMarkings_vars = `
        uniform float uLanes; uniform vec3 uBrokenLinesColor; uniform vec3 uShoulderLinesColor;
        uniform float uShoulderLinesWidthPercentage; uniform float uBrokenLinesWidthPercentage; uniform float uBrokenLinesLengthPercentage;
        highp float random(vec2 co){highp float a=12.9898;highp float b=78.233;highp float c=43758.5453;highp float dt=dot(co.xy,vec2(a,b));highp float sn=mod(dt,3.14);return fract(sin(sn)*c);}
    `;
    const roadMarkings_fragment = `
        uv.y=mod(uv.y+uTime*0.05,1.); float laneWidth=1.0/uLanes;
        float brokenLineWidth=laneWidth*uBrokenLinesWidthPercentage; float laneEmptySpace=1.-uBrokenLinesLengthPercentage;
        float brokenLines=step(1.0-brokenLineWidth,fract(uv.x*2.0))*step(laneEmptySpace,fract(uv.y*10.0));
        float sideLines=step(1.0-brokenLineWidth,fract((uv.x-laneWidth*(uLanes-1.0))*2.0))+step(brokenLineWidth,uv.x);
        brokenLines=mix(brokenLines,sideLines,uv.x);
    `;
    const islandFragment = `
#define USE_FOG
varying vec2 vUv; uniform vec3 uColor; uniform float uTime;
${THREE.ShaderChunk['fog_pars_fragment']}
void main(){
    vec2 uv=vUv; vec3 color=vec3(uColor);
    gl_FragColor=vec4(color,1.);
    ${THREE.ShaderChunk['fog_fragment']}
}
    `;
    const roadFragment = `
#define USE_FOG
varying vec2 vUv; uniform vec3 uColor; uniform float uTime;
${roadMarkings_vars}
${THREE.ShaderChunk['fog_pars_fragment']}
void main(){
    vec2 uv=vUv; vec3 color=vec3(uColor);
    ${roadMarkings_fragment}
    gl_FragColor=vec4(color,1.);
    ${THREE.ShaderChunk['fog_fragment']}
}
    `;
    const roadVertex = `
#define USE_FOG
uniform float uTime;
${THREE.ShaderChunk['fog_pars_vertex']}
uniform float uTravelLength; varying vec2 vUv;
${opts.distortion.getDistortion}
void main(){
    vec3 transformed=position.xyz;
    vec3 distortion=getDistortion((transformed.y+uTravelLength/2.)/uTravelLength);
    transformed.x+=distortion.x; transformed.z+=distortion.y; transformed.y+=-1.*distortion.z;
    vec4 mvPosition=modelViewMatrix*vec4(transformed,1.); gl_Position=projectionMatrix*mvPosition;
    vUv=uv;
    ${THREE.ShaderChunk['fog_vertex']}
}
    `;

    /* ── Road Class ── */
    class Road {
        constructor(webgl, options){ this.webgl=webgl; this.options=options; this.uTime={value:0}; }
        createPlane(side, width, isRoad){
            const o=this.options; let segments=100;
            const geometry=new THREE.PlaneGeometry(isRoad?o.roadWidth:o.islandWidth, o.length, 20, segments);
            let uniforms={uTravelLength:{value:o.length}, uColor:{value:new THREE.Color(isRoad?o.colors.roadColor:o.colors.islandColor)}, uTime:this.uTime};
            if(isRoad) Object.assign(uniforms,{uLanes:{value:o.lanesPerRoad},uBrokenLinesColor:{value:new THREE.Color(o.colors.brokenLines)},uShoulderLinesColor:{value:new THREE.Color(o.colors.shoulderLines)},uShoulderLinesWidthPercentage:{value:o.shoulderLinesWidthPercentage},uBrokenLinesLengthPercentage:{value:o.brokenLinesLengthPercentage},uBrokenLinesWidthPercentage:{value:o.brokenLinesWidthPercentage}});
            const material=new THREE.ShaderMaterial({fragmentShader:isRoad?roadFragment:islandFragment, vertexShader:roadVertex, side:THREE.DoubleSide, uniforms:Object.assign(uniforms,this.webgl.fogUniforms,o.distortion.uniforms)});
            const mesh=new THREE.Mesh(geometry,material); mesh.rotation.x=-Math.PI/2; mesh.position.z=-o.length/2;
            mesh.position.x+=(o.islandWidth/2+o.roadWidth/2)*side; this.webgl.scene.add(mesh); return mesh;
        }
        init(){ this.leftRoadWay=this.createPlane(-1,this.options.roadWidth,true); this.rightRoadWay=this.createPlane(1,this.options.roadWidth,true); this.island=this.createPlane(0,this.options.islandWidth,false); }
        update(time){ this.uTime.value=time; }
    }

    /* ── CarLights Class ── */
    class CarLights {
        constructor(webgl,options,colors,speed,fade){ this.webgl=webgl; this.options=options; this.colors=colors; this.speed=speed; this.fade=fade; }
        init(){
            const o=this.options; let curve=new THREE.LineCurve3(new THREE.Vector3(0,0,0),new THREE.Vector3(0,0,-1));
            let geometry=new THREE.TubeGeometry(curve,40,1,8,false);
            let instanced=new THREE.InstancedBufferGeometry().copy(geometry); instanced.instanceCount=o.lightPairsPerRoadWay*2;
            let laneWidth=o.roadWidth/o.lanesPerRoad; let aOffset=[],aMetrics=[],aColor=[];
            let colors=this.colors; if(Array.isArray(colors)) colors=colors.map(c=>new THREE.Color(c)); else colors=new THREE.Color(colors);
            for(let i=0;i<o.lightPairsPerRoadWay;i++){
                let radius=random(o.carLightsRadius),length=random(o.carLightsLength),speed=random(this.speed);
                let carLane=i%o.lanesPerRoad, laneX=carLane*laneWidth-o.roadWidth/2+laneWidth/2;
                let carWidth=random(o.carWidthPercentage)*laneWidth, carShiftX=random(o.carShiftX)*laneWidth;
                laneX+=carShiftX; let offsetY=random(o.carFloorSeparation)+radius*1.3, offsetZ=-random(o.length);
                aOffset.push(laneX-carWidth/2,offsetY,offsetZ,laneX+carWidth/2,offsetY,offsetZ);
                aMetrics.push(radius,length,speed,radius,length,speed);
                let color=pickRandom(colors); aColor.push(color.r,color.g,color.b,color.r,color.g,color.b);
            }
            instanced.setAttribute('aOffset',new THREE.InstancedBufferAttribute(new Float32Array(aOffset),3,false));
            instanced.setAttribute('aMetrics',new THREE.InstancedBufferAttribute(new Float32Array(aMetrics),3,false));
            instanced.setAttribute('aColor',new THREE.InstancedBufferAttribute(new Float32Array(aColor),3,false));
            let material=new THREE.ShaderMaterial({fragmentShader:carLightsFragment,vertexShader:carLightsVertex,transparent:true,
                uniforms:Object.assign({uTime:{value:0},uTravelLength:{value:o.length},uFade:{value:this.fade}},this.webgl.fogUniforms,o.distortion.uniforms)});
            let mesh=new THREE.Mesh(instanced,material); mesh.frustumCulled=false; this.webgl.scene.add(mesh); this.mesh=mesh;
        }
        update(time){ this.mesh.material.uniforms.uTime.value=time; }
    }

    /* ── LightsSticks Class ── */
    class LightsSticks {
        constructor(webgl,options){ this.webgl=webgl; this.options=options; }
        init(){
            const o=this.options; const geometry=new THREE.PlaneGeometry(1,1);
            let instanced=new THREE.InstancedBufferGeometry().copy(geometry); let total=o.totalSideLightSticks; instanced.instanceCount=total;
            let stickoffset=o.length/(total-1); const aOffset=[],aColor=[],aMetrics=[];
            let colors=o.colors.sticks; if(Array.isArray(colors)) colors=colors.map(c=>new THREE.Color(c)); else colors=new THREE.Color(colors);
            for(let i=0;i<total;i++){
                let w=random(o.lightStickWidth),h=random(o.lightStickHeight);
                aOffset.push((i-1)*stickoffset*2+stickoffset*Math.random());
                let color=pickRandom(colors); aColor.push(color.r,color.g,color.b); aMetrics.push(w,h);
            }
            instanced.setAttribute('aOffset',new THREE.InstancedBufferAttribute(new Float32Array(aOffset),1,false));
            instanced.setAttribute('aColor',new THREE.InstancedBufferAttribute(new Float32Array(aColor),3,false));
            instanced.setAttribute('aMetrics',new THREE.InstancedBufferAttribute(new Float32Array(aMetrics),2,false));
            const material=new THREE.ShaderMaterial({fragmentShader:sideSticksFragment,vertexShader:sideSticksVertex,side:THREE.DoubleSide,
                uniforms:Object.assign({uTravelLength:{value:o.length},uTime:{value:0}},this.webgl.fogUniforms,o.distortion.uniforms)});
            const mesh=new THREE.Mesh(instanced,material); mesh.frustumCulled=false; this.webgl.scene.add(mesh); this.mesh=mesh;
        }
        update(time){ this.mesh.material.uniforms.uTime.value=time; }
    }

    /* ── Main App Class ── */
    const renderer = new THREE.WebGLRenderer({ antialias:false, alpha:true });
    renderer.setSize(container.offsetWidth, container.offsetHeight, false);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    container.appendChild(renderer.domElement);

    const camera = new THREE.PerspectiveCamera(opts.fov, container.offsetWidth/container.offsetHeight, 0.1, 10000);
    camera.position.set(0, 8, -5);

    const scene = new THREE.Scene();
    scene.background = null;
    const fog = new THREE.Fog(opts.colors.background, opts.length*0.2, opts.length*500);
    scene.fog = fog;

    const fogUniforms = { fogColor:{value:fog.color}, fogNear:{value:fog.near}, fogFar:{value:fog.far} };
    const webgl = { scene, fogUniforms };
    const clock = new THREE.Clock();

    const road = new Road(webgl, opts); road.init();
    const leftLights = new CarLights(webgl, opts, opts.colors.leftCars, opts.movingAwaySpeed, new THREE.Vector2(0, 1-opts.carLightsFade));
    leftLights.init(); leftLights.mesh.position.setX(-opts.roadWidth/2 - opts.islandWidth/2);
    const rightLights = new CarLights(webgl, opts, opts.colors.rightCars, opts.movingCloserSpeed, new THREE.Vector2(1, 0+opts.carLightsFade));
    rightLights.init(); rightLights.mesh.position.setX(opts.roadWidth/2 + opts.islandWidth/2);
    const sticks = new LightsSticks(webgl, opts);
    sticks.init(); sticks.mesh.position.setX(-(opts.roadWidth + opts.islandWidth/2));

    /* ── Post-processing ── */
    const composer = new EffectComposer(renderer);
    composer.addPass(new RenderPass(scene, camera));
    const bloomPass = new EffectPass(camera, new BloomEffect({ luminanceThreshold:0.2, luminanceSmoothing:0, resolutionScale:1 }));
    composer.addPass(bloomPass);
    const smaaPass = new EffectPass(camera, new SMAAEffect({ preset:SMAAPreset.MEDIUM, searchImage:SMAAEffect.searchImageDataURL, areaImage:SMAAEffect.areaImageDataURL }));
    smaaPass.renderToScreen = true;
    composer.addPass(smaaPass);

    /* ── Speed-up interaction ── */
    let fovTarget = opts.fov, speedUpTarget = 0, speedUp = 0, timeOffset = 0;
    container.addEventListener('mousedown', ()=>{ fovTarget=opts.fovSpeedUp; speedUpTarget=opts.speedUp; });
    container.addEventListener('mouseup', ()=>{ fovTarget=opts.fov; speedUpTarget=0; });
    container.addEventListener('mouseout', ()=>{ fovTarget=opts.fov; speedUpTarget=0; });
    container.addEventListener('touchstart', ()=>{ fovTarget=opts.fovSpeedUp; speedUpTarget=opts.speedUp; }, {passive:true});
    container.addEventListener('touchend', ()=>{ fovTarget=opts.fov; speedUpTarget=0; }, {passive:true});

    /* ── Resize ── */
    function onResize(){
        const w=container.offsetWidth, h=container.offsetHeight;
        renderer.setSize(w,h); camera.aspect=w/h; camera.updateProjectionMatrix(); composer.setSize(w,h);
    }
    window.addEventListener('resize', onResize);

    /* ── Animation Loop ── */
    function tick(){
        requestAnimationFrame(tick);
        const delta = clock.getDelta();
        let lerpPct = Math.exp(-(-60*Math.log2(1-0.1))*delta);
        speedUp += lerp(speedUp, speedUpTarget, lerpPct, 0.00001);
        timeOffset += speedUp*delta;
        let time = clock.elapsedTime + timeOffset;
        rightLights.update(time); leftLights.update(time); sticks.update(time); road.update(time);
        let fovChange = lerp(camera.fov, fovTarget, lerpPct);
        if(fovChange!==0){ camera.fov+=fovChange*delta*6; camera.updateProjectionMatrix(); }
        if(opts.distortion.getJS){
            const d=opts.distortion.getJS(0.025,time);
            camera.lookAt(new THREE.Vector3(camera.position.x+d.x, camera.position.y+d.y, camera.position.z+d.z));
            camera.updateProjectionMatrix();
        }
        composer.render(delta);
    }
    tick();
})();
}
</script>
