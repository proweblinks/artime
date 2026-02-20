@php
    $blogs = Home::getRecentBlogs(null, 2);
@endphp

<section class="section-padding bg-page">
    <div class="container px-4 mx-auto">
        <div class="flex flex-wrap -m-8">
            <div class="w-full md:w-5/12 p-8">
                <div class="flex flex-col justify-between h-full">
                    <div class="mb-8">
                        <h2 class="mb-5 text-3xl md:text-4xl font-bold leading-tight" style="color: var(--text-primary); font-family: 'General Sans', sans-serif;">
                            {{ __("Our Latest News and Articles") }}
                        </h2>
                        <p class="leading-relaxed" style="color: var(--text-secondary);">
                            {{ __("Read the latest stories, in-depth tutorials, expert interviews, and product updates designed to help you grow your business, master new skills, and stay ahead in the digital world.") }}
                        </p>
                    </div>
                    <a class="inline-flex items-center leading-normal hover:opacity-80 transition" style="color: var(--accent-dark);" href="{{ url("blogs") }}">
                        <span class="mr-2 font-semibold">{{ __("See all articles") }}</span>
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M10.5 3.75L15.75 9M15.75 9L10.5 14.25M15.75 9L2.25 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </a>
                </div>
            </div>
            <div class="w-full md:flex-1 p-8">
                <div class="flex flex-wrap -m-3">
                    @foreach($blogs as $blog)
                        <div class="w-full md:w-1/2 p-3">
                            <div class="max-w-sm mx-auto glass-card overflow-hidden">
                                <div class="overflow-hidden">
                                    <img class="h-56 w-full transform hover:scale-105 transition ease-in-out duration-1000 object-cover"
                                         src="{{ !empty($blog->thumbnail) ? Media::url($blog->thumbnail) : theme_public_asset('images/blog/blog-wide.png') }}"
                                         alt="{{ $blog->title }}">
                                </div>
                                <div class="p-5">
                                    <p class="mb-4 font-sans max-w-max px-3 py-1.5 text-sm font-semibold uppercase rounded-md" style="background: rgba(3,252,244,0.1); color: var(--accent-dark);">
                                        {{ $blog->category->name ?? __("Blog") }}
                                    </p>
                                    <a class="mb-2 inline-block hover:opacity-80 transition"
                                       href="{{ url('blogs/'.$blog->slug) }}">
                                        <h3 class="text-xl font-bold leading-normal" style="color: var(--text-primary);">
                                            {{ $blog->title }}
                                        </h3>
                                    </a>
                                    <p class="leading-relaxed" style="color: var(--text-secondary);">
                                        {{ $blog->desc }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>