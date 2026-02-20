@php
    $pricing = \Pricing::plansWithFeatures();
    $planTypes = \Modules\AdminPlans\Facades\Plan::getTypes();
    $minCol = 3;
@endphp

<section x-data="{ type: {{ array_key_first($planTypes) }} }" class="section-padding overflow-hidden relative z-20 bg-page">
    <div class="container px-4 mx-auto mb-10">
        <h2 class="text-3xl md:text-5xl font-bold mb-6" style="color: var(--text-primary); font-family: 'General Sans', sans-serif;">
            {{ __("Pricing") }}
        </h2>
        <div class="mb-16 flex flex-wrap justify-between -m-4">
            <div class="w-auto p-4">
                <div class="md:max-w-md">
                    <p class="text-lg leading-relaxed" style="color: var(--text-secondary);">
                        {{ __("Choose an affordable plan packed with top features to engage your audience, create loyalty, and boost sales.") }}
                    </p>
                </div>
            </div>
            {{-- Toggle button group --}}
            <div class="w-auto p-4">
                <div class="inline-flex items-center max-w-max gap-2">
                    @foreach($planTypes as $typeKey => $typeLabel)
                        <button
                            type="button"
                            class="px-4 py-1 mx-1 rounded-full font-semibold transition"
                            :class="type == {{ $typeKey }} ? 'text-white' : 'hover:opacity-80'"
                            :style="type == {{ $typeKey }} ? 'background: var(--accent-gradient); color: #fff;' : 'background: rgba(255,255,255,0.6); color: var(--text-secondary);'"
                            x-on:click="type={{ $typeKey }}"
                        >
                            {{ __($typeLabel) }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="glass-card overflow-hidden">
            <div class="flex flex-wrap md:divide-x" style="border-color: var(--glass-border);">
                @foreach($planTypes as $typeKey => $typeLabel)
                    @php
                        $plans = $pricing[$typeKey] ?? [];
                        $planCount = count($plans);
                    @endphp

                    @foreach($plans as $index => $plan)

                        @php
                            $isFreePlan = $plan['free_plan'];
                        @endphp

                        <div class="w-full xs:w-full sm:w-full md:w-full lg:w-1/3 flex-1"
                             x-show="type == {{ $typeKey }}"
                             x-transition
                             style="display: none; z-index: {{ 1000 - $index }}">

                            <div class="relative px-9 pt-8 pb-11 h-full" style="backdrop-filter: blur(46px);">

                                {{-- Ribbon Featured --}}
                                @if(!empty($plan['featured']))
                                    <div class="overflow-hidden absolute right-0 w-40 h-40 top-0">
                                        <div class="absolute top-6 -right-10 rotate-45">
                                            <span class="text-white px-12 py-1 text-xs font-bold shadow-md uppercase" style="background: var(--accent-gradient);">
                                                {{ __('Featured') }}
                                            </span>
                                        </div>
                                    </div>
                                @endif

                                <span class="mb-3 inline-block text-sm font-semibold uppercase tracking-px leading-snug" style="color: var(--accent-dark);">
                                    {{ __($plan['name'] ?? '-') }}
                                </span>
                                <p class="mb-6 font-medium leading-relaxed" style="color: var(--text-secondary);">
                                    {{ __($plan['desc'] ?? '') }}
                                </p>
                                <h3 class="mb-1 text-4xl font-bold leading-tight" style="color: var(--text-primary);">
                                    @if($isFreePlan)
                                        {{ price(0) }}
                                    @else
                                        {{ price($plan['price'] ?? 0) }}
                                    @endif
                                    <span style="color: var(--text-muted);">/{{ strtolower($typeLabel) }}</span>
                                </h3>
                                <p class="mb-8 text-sm font-medium leading-relaxed" style="color: var(--text-muted);">
                                    {{ __("Billed") }} {{ $typeLabel }}
                                </p>

                                @if($isFreePlan)
                                    <a href="{{ route('payment.index', $plan['id_secure']) }}" class="mb-9 py-4 px-9 w-full font-semibold rounded-xl text-center block border transition" style="color: var(--accent-dark); border-color: var(--accent-dark); background: transparent;" onmouseover="this.style.background='rgba(3,252,244,0.1)'" onmouseout="this.style.background='transparent'">
                                        {{ __("Start for Free") }}
                                    </a>
                                @else
                                    <a href="{{ route('payment.index', $plan['id_secure']) }}" class="btn-accent mb-9 py-4 px-9 w-full text-center block justify-center">
                                        {{ __("Choose Plan") }}
                                    </a>
                                @endif
                                <ul>
                                    @foreach($plan['features'] ?? [] as $feature)
                                        <li class="mb-4 flex items-center gap-2">
                                            <i class="fa-regular fa-check {{ $feature['check'] ? '' : '' }}" style="color: {{ $feature['check'] ? 'var(--accent-dark)' : 'var(--text-muted)' }};"></i>
                                            <p class="font-semibold leading-normal" style="color: var(--text-primary);">{{ __($feature['label'] ?? $feature) }}</p>

                                            @if(!empty($feature['subfeature']))
                                                <div x-data="{ open: false, timer: null }" class="relative ml-2">
                                                    <div
                                                        @mouseenter="clearTimeout(timer); open = true"
                                                        @mouseleave="timer = setTimeout(() => open = false, 120)"
                                                        class="w-5 h-5 flex items-center justify-center rounded-full text-xs transition cursor-pointer z-20 relative"
                                                        style="background: rgba(3,252,244,0.15); color: var(--accent-dark);"
                                                    ><i class="fa-light fa-info"></i></div>
                                                    <div
                                                        x-show="open"
                                                        @mouseenter="clearTimeout(timer); open = true"
                                                        @mouseleave="timer = setTimeout(() => open = false, 120)"
                                                        class="absolute left-full top-1/2 ml-3 -translate-y-1/3 z-800 min-w-60 max-h-[400px] overflow-y-auto glass-card-sm p-4"
                                                        style="background: rgba(255,255,255,0.95);"
                                                        x-transition
                                                    >
                                                        @foreach($feature['subfeature'] as $tabGroup)
                                                            <div class="mb-5 last:mb-0">
                                                                <div class="font-semibold text-sm mb-3 text-left" style="color: var(--text-primary);">
                                                                    {{ __($tabGroup['tab_name']) }}
                                                                </div>
                                                                <ul class="text-sm space-y-1 text-left">
                                                                    @foreach($tabGroup['items'] as $sub)
                                                                        <li class="flex items-center gap-1.5 py-1">
                                                                            @if($sub['check'])
                                                                                <span class="w-5 h-5 flex items-center justify-center rounded-full text-xs font-semibold" style="background: rgba(3,252,244,0.15); color: var(--accent-dark);">
                                                                                  <i class="fa-solid fa-check"></i>
                                                                                </span>
                                                                            @else
                                                                                <span class="w-5 h-5 flex items-center justify-center rounded-full bg-error/20 text-xs font-semibold">
                                                                                  <i class="fa-solid fa-xmark"></i>
                                                                                </span>
                                                                            @endif
                                                                            <span style="color: var(--text-secondary);">{{ __($sub['label']) }}</span>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endforeach
                    @for($i = $planCount; $i < $minCol; $i++)
                        <div class="w-full md:w-1/2 lg:w-1/3 flex-1"
                             x-show="type == {{ $typeKey }}"
                             style="display: none;"></div>
                    @endfor
                @endforeach
            </div>
        </div>
    </div>
    <p class="mb-4 text-sm text-center font-medium" style="color: var(--text-muted);">
        {{ __("Trusted by secure payment service") }}
    </p>
    <div class="flex flex-wrap gap-2 justify-center">
        <div class="w-auto">
            <a href="#">
                <img class="h-24" src="{{ theme_public_asset('logos/brands/stripe.svg') }}" alt="Stripe">
            </a>
        </div>
        <div class="w-auto">
            <a href="#">
                <img class="h-24" src="{{ theme_public_asset('logos/brands/amex.svg') }}" alt="Amex">
            </a>
        </div>
        <div class="w-auto">
            <a href="#">
                <img class="h-24" src="{{ theme_public_asset('logos/brands/mastercard.svg') }}" alt="Mastercard">
            </a>
        </div>
        <div class="w-auto">
            <img class="h-24" src="{{ theme_public_asset('logos/brands/paypal.svg') }}" alt="Paypal">
        </div>
        <div class="w-auto">
            <a href="#">
                <img class="h-24" src="{{ theme_public_asset('logos/brands/visa.svg') }}" alt="Visa">
            </a>
        </div>
        <div class="w-auto">
            <a href="#">
                <img class="h-24" src="{{ theme_public_asset('logos/brands/apple-pay.svg') }}" alt="Apple Pay">
            </a>
        </div>
    </div>
</section>