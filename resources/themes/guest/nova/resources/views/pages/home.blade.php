{{-- ============================================================
     SECTION 1: HERO — "Create Anything with AI"
     ============================================================ --}}
<section class="relative overflow-hidden pt-36 pb-20 md:pt-44 md:pb-28">
    <div class="container px-4 mx-auto relative z-10">
        <div class="max-w-4xl mx-auto text-center">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 glass-pill mb-8">
                <span class="w-2 h-2 rounded-full" style="background: var(--accent);"></span>
                <span>{{ __("AI-First Creative Platform") }}</span>
            </div>

            {{-- Headline --}}
            <h1 class="text-4xl sm:text-5xl md:text-7xl font-bold leading-tight mb-6" style="color: var(--text-primary); font-family: 'General Sans', sans-serif;">
                {{ __("Create Anything") }}
                <span class="gradient-text">{{ __("with AI") }}</span>
            </h1>

            {{-- Subheadline --}}
            <p class="text-lg md:text-xl max-w-2xl mx-auto mb-10 leading-relaxed" style="color: var(--text-secondary);">
                {{ __("Generate videos, images, marketing campaigns, and social content — all from a single prompt. The complete AI creative platform for creators and businesses.") }}
            </p>

            {{-- CTA Buttons --}}
            <div class="flex flex-wrap justify-center gap-4 mb-14">
                <a href="{{ url('auth/signup') }}" class="btn-accent text-base py-3.5 px-8">
                    <i class="fa-light fa-sparkles"></i>
                    {{ __("Start Creating Free") }}
                </a>
                <a href="{{ url('') }}#how-it-works" class="btn-ghost text-base py-3.5 px-8">
                    <i class="fa-light fa-circle-play"></i>
                    {{ __("See How It Works") }}
                </a>
            </div>

            {{-- Capability Pills --}}
            <div class="flex flex-wrap justify-center gap-3 mb-16">
                <span class="glass-pill"><i class="fa-light fa-video"></i> {{ __("AI Video") }}</span>
                <span class="glass-pill"><i class="fa-light fa-image"></i> {{ __("AI Images") }}</span>
                <span class="glass-pill"><i class="fa-light fa-palette"></i> {{ __("Content Studio") }}</span>
                <span class="glass-pill"><i class="fa-light fa-share-nodes"></i> {{ __("Social Publishing") }}</span>
                <span class="glass-pill"><i class="fa-light fa-microphone"></i> {{ __("Voice & Music") }}</span>
            </div>
        </div>

        {{-- App Mockup — Dark browser window showing the ARTime dashboard --}}
        <div class="relative max-w-5xl mx-auto mt-4">
            <div class="rounded-2xl overflow-hidden" style="background: #1a1a2e; box-shadow: 0 25px 60px rgba(0,0,0,0.15), 0 0 0 1px rgba(255,255,255,0.05);">
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
                                {{-- Scene tabs --}}
                                <div class="flex gap-2 mb-3">
                                    <span class="text-xs px-2.5 py-1 rounded-lg font-medium" style="background: rgba(3,252,244,0.15); color: #03fcf4;">{{ __("Scene 1") }}</span>
                                    <span class="text-xs px-2.5 py-1 rounded-lg font-medium" style="background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.4);">{{ __("Scene 2") }}</span>
                                    <span class="text-xs px-2.5 py-1 rounded-lg font-medium" style="background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.4);">{{ __("Scene 3") }}</span>
                                </div>
                                {{-- Shot grid --}}
                                <div class="grid grid-cols-3 gap-2 mb-4">
                                    <div class="rounded-lg overflow-hidden" style="aspect-ratio: 16/9; background: linear-gradient(135deg, #0d9488, #0891b2);">
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="fa-light fa-user text-sm" style="color: rgba(255,255,255,0.5);"></i>
                                        </div>
                                    </div>
                                    <div class="rounded-lg overflow-hidden" style="aspect-ratio: 16/9; background: linear-gradient(135deg, #1a1a2e, #0891b2);">
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="fa-light fa-city text-sm" style="color: rgba(255,255,255,0.5);"></i>
                                        </div>
                                    </div>
                                    <div class="rounded-lg overflow-hidden" style="aspect-ratio: 16/9; background: linear-gradient(135deg, #0284c7, #03fcf4);">
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="fa-light fa-sparkles text-sm" style="color: rgba(255,255,255,0.5);"></i>
                                        </div>
                                    </div>
                                </div>
                                {{-- Timeline bar --}}
                                <div class="mb-3">
                                    <div class="h-2 rounded-full w-full" style="background: rgba(255,255,255,0.06);">
                                        <div class="h-2 rounded-full" style="width: 65%; background: var(--accent-gradient);"></div>
                                    </div>
                                </div>
                                {{-- Pipeline steps --}}
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs px-2 py-0.5 rounded-full" style="background: rgba(3,252,244,0.15); color: #03fcf4;">
                                        <i class="fa-light fa-check mr-1"></i>{{ __("Concept") }}
                                    </span>
                                    <span class="text-xs px-2 py-0.5 rounded-full" style="background: rgba(3,252,244,0.15); color: #03fcf4;">
                                        <i class="fa-light fa-check mr-1"></i>{{ __("Script") }}
                                    </span>
                                    <span class="text-xs px-2 py-0.5 rounded-full" style="background: rgba(3,252,244,0.3); color: #03fcf4; border: 1px solid rgba(3,252,244,0.3);">
                                        <i class="fa-light fa-spinner-third fa-spin mr-1"></i>{{ __("Storyboard") }}
                                    </span>
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
