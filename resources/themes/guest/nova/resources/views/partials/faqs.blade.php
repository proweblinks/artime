@php
    $faqs = Home::getFaqs();
@endphp

<section class="relative section-padding overflow-hidden bg-page">
    <div class="relative z-10 container px-4 mx-auto">
        <div class="md:max-w-4xl mx-auto">
            <p class="mb-7 text-sm text-center font-semibold uppercase tracking-px" style="color: var(--accent-dark);">
                {{ __("Have any questions?") }}
            </p>
            <h2 class="mb-16 text-3xl md:text-5xl text-center font-bold" style="color: var(--text-primary); font-family: 'General Sans', sans-serif;">
                {{ __("Frequently Asked Questions") }}
            </h2>
            <div class="mb-11 flex flex-wrap -m-1"
                 x-data="{ open: null }"
            >
                @foreach($faqs as $faq)
                    <div class="w-full p-1">
                        <a
                            href="#"
                            x-on:click.prevent="open === {{ $faq->id }} ? open = null : open = {{ $faq->id }}"
                        >
                            <div :class="{ 'border-[#0891b2]': open === {{ $faq->id }} }"
                                class="py-7 px-8 glass-card-sm border-2 transition duration-300"
                                :style="open === {{ $faq->id }} ? 'border-color: var(--accent-dark);' : 'border-color: var(--glass-border);'"
                            >
                                <div class="flex flex-wrap justify-between -m-2">
                                    <div class="flex-1 p-2">
                                        <h3 class="text-lg font-semibold leading-normal" style="color: var(--text-primary);">
                                            {{ $faq->title }}
                                        </h3>
                                        <div
                                            x-ref="container_{{ $faq->id }}"
                                            :style="open === {{ $faq->id }} ? 'height: ' + $refs['container_{{ $faq->id }}'].scrollHeight + 'px' : ''"
                                            class="overflow-hidden h-0 duration-500"
                                        >
                                            <p class="mt-4 font-medium" style="color: var(--text-secondary);">
                                                {!! $faq->content !!}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="w-auto p-2">
                                        <div :class="{ 'hidden': open === {{ $faq->id }} }">
                                            <svg class="relative top-1" width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M14.25 6.75L9 12L3.75 6.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted);"></path></svg>
                                        </div>
                                        <div :class="{ 'hidden': open !== {{ $faq->id }} }" class="hidden">
                                            <svg class="relative top-1" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M4.16732 12.5L10.0007 6.66667L15.834 12.5" stroke="#0891b2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
            <p class="text-center font-medium" style="color: var(--text-secondary);">
                <span>{{ __("Still have any questions?") }}</span>
                <a class="font-semibold hover:opacity-80 transition" style="color: var(--accent-dark);" href="{{ url('contact') }}">{{ __("Contact us") }}</a>
            </p>
        </div>
    </div>
</section>