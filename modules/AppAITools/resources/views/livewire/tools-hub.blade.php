<div>
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-base-content">{{ __('AI Creator Tools') }}</h1>
        <p class="text-base-content/60 mt-1">{{ __('Supercharge your content with AI-powered optimization, analysis, and generation tools.') }}</p>
    </div>

    {{-- Tools Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($tools as $key => $tool)
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

    {{-- Recent Activity --}}
    @if(count($recentActivity) > 0)
        <div class="mt-10">
            <h2 class="text-lg font-semibold text-base-content mb-4">{{ __('Recent Activity') }}</h2>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('Tool') }}</th>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Platform') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentActivity as $activity)
                            <tr class="hover">
                                <td>
                                    <span class="badge badge-sm badge-ghost">{{ str_replace('_', ' ', ucfirst($activity['tool'])) }}</span>
                                </td>
                                <td class="max-w-xs truncate">{{ $activity['title'] ?? '-' }}</td>
                                <td>
                                    @php $platforms = config('appaitools.platforms'); @endphp
                                    @if(isset($platforms[$activity['platform']]))
                                        <i class="{{ $platforms[$activity['platform']]['icon'] }} mr-1"></i>
                                        {{ $platforms[$activity['platform']]['name'] }}
                                    @else
                                        {{ $activity['platform'] }}
                                    @endif
                                </td>
                                <td class="text-base-content/60">{{ \Carbon\Carbon::createFromTimestamp($activity['created'])->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
