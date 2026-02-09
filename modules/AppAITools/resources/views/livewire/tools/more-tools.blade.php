<div>
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('app.ai-tools.index') }}" class="btn btn-ghost btn-sm">
            <i class="fa-light fa-arrow-left"></i>
        </a>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center">
            <i class="fa-light fa-grid-2-plus text-white text-lg"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-base-content">{{ __('More AI Tools') }}</h1>
            <p class="text-sm text-base-content/60">{{ __('Additional AI-powered tools for content creators') }}</p>
        </div>
    </div>

    {{-- Sub-Tools Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($subTools as $key => $tool)
            <a href="{{ route($tool['route']) }}" class="group">
                <div class="card bg-base-200 border border-base-300 hover:border-primary/50 transition-all duration-300 hover:shadow-lg hover:shadow-primary/5 h-full">
                    <div class="card-body">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $tool['color'] }} flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform duration-300">
                                <i class="{{ $tool['icon'] }} text-white text-xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-base-content group-hover:text-primary transition-colors">
                                    {{ __($tool['name']) }}
                                </h3>
                                <p class="text-sm text-base-content/60 mt-1 line-clamp-2">
                                    {{ __($tool['description']) }}
                                </p>
                            </div>
                        </div>
                        @if($tool['credits'] > 0)
                            <div class="mt-4 flex items-center gap-2">
                                <span class="badge badge-sm badge-ghost">
                                    <i class="fa-light fa-coins mr-1"></i>
                                    {{ $tool['credits'] }} {{ __('credits') }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>
