@extends('layouts.app')

@section('title', __('Narrative Structures'))

@section('content')
<div class="bg-gray-900 min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-white">{{ __('Narrative Structures') }}</h1>
                <p class="text-gray-400 mt-1">{{ __('Hollywood-level storytelling configuration for script generation') }}</p>
            </div>
            <a href="{{ route('admin.video-wizard.narrative.export') }}"
               class="bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition">
                <i class="fas fa-download mr-2"></i>{{ __('Export JSON') }}
            </a>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="bg-green-500/20 border border-green-500 text-green-400 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                        <span class="text-2xl">📐</span>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">{{ __('Story Arcs') }}</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['total_arcs'] }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                        <span class="text-2xl">📺</span>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">{{ __('Presets') }}</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['total_presets'] }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                        <span class="text-2xl">📈</span>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">{{ __('Tension Curves') }}</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['total_curves'] }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-pink-500/20 rounded-xl flex items-center justify-center">
                        <span class="text-2xl">🎭</span>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">{{ __('Emotional Journeys') }}</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['total_journeys'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Default Settings Form --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white">{{ __('Default Settings') }}</h2>
                <p class="text-gray-400 text-sm">{{ __('Configure default selections for new projects') }}</p>
            </div>
            <form action="{{ route('admin.video-wizard.narrative.update-settings') }}" method="POST" class="p-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('Default Preset') }}</label>
                        <select name="default_preset" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                            <option value="">{{ __('None') }}</option>
                            @foreach($narrativePresets as $key => $preset)
                                <option value="{{ $key }}" {{ ($settings['default_preset'] ?? '') === $key ? 'selected' : '' }}>
                                    {{ $preset['icon'] ?? '' }} {{ $preset['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('Default Story Arc') }}</label>
                        <select name="default_arc" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                            <option value="">{{ __('None') }}</option>
                            @foreach($storyArcs as $key => $arc)
                                <option value="{{ $key }}" {{ ($settings['default_arc'] ?? '') === $key ? 'selected' : '' }}>
                                    {{ $arc['icon'] ?? '' }} {{ $arc['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('Default Tension Curve') }}</label>
                        <select name="default_curve" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                            <option value="">{{ __('None') }}</option>
                            @foreach($tensionCurves as $key => $curve)
                                <option value="{{ $key }}" {{ ($settings['default_curve'] ?? '') === $key ? 'selected' : '' }}>
                                    {{ $curve['icon'] ?? '' }} {{ $curve['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('Default Emotional Journey') }}</label>
                        <select name="default_journey" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                            <option value="">{{ __('None') }}</option>
                            @foreach($emotionalJourneys as $key => $journey)
                                <option value="{{ $key }}" {{ ($settings['default_journey'] ?? '') === $key ? 'selected' : '' }}>
                                    {{ $journey['icon'] ?? '' }} {{ $journey['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex items-center gap-4">
                    <label class="flex items-center gap-2 text-gray-300">
                        <input type="checkbox" name="show_advanced_by_default" value="1"
                               {{ ($settings['show_advanced_by_default'] ?? false) ? 'checked' : '' }}
                               class="w-4 h-4 rounded bg-gray-700 border-gray-600 text-purple-500">
                        {{ __('Show advanced options by default in wizard') }}
                    </label>
                </div>
                <div class="mt-6">
                    <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                        {{ __('Save Settings') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Narrative Presets --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">📺</span> {{ __('Narrative Presets') }}
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Platform-optimized storytelling formulas') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($narrativePresets as $key => $preset)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-purple-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ $preset['icon'] ?? '📺' }}</span>
                                <span class="font-medium text-white">{{ $preset['name'] }}</span>
                            </div>
                            <button onclick="toggleItem('preset', '{{ $key }}')"
                                    class="toggle-btn" data-type="preset" data-key="{{ $key }}"
                                    title="{{ __('Toggle visibility') }}">
                                @if(in_array($key, $settings['disabled_presets'] ?? []))
                                    <i class="fas fa-eye-slash text-gray-500"></i>
                                @else
                                    <i class="fas fa-eye text-green-500"></i>
                                @endif
                            </button>
                        </div>
                        <p class="text-gray-400 text-sm mb-2">{{ $preset['description'] ?? '' }}</p>
                        @if(!empty($preset['tips']))
                            <p class="text-purple-400 text-xs italic">{{ $preset['tips'] }}</p>
                        @endif
                        <div class="mt-3 flex flex-wrap gap-2 text-xs">
                            @if(!empty($preset['defaultArc']))
                                <span class="bg-gray-600 px-2 py-1 rounded text-gray-300">{{ $storyArcs[$preset['defaultArc']]['name'] ?? $preset['defaultArc'] }}</span>
                            @endif
                            @if(!empty($preset['defaultTension']))
                                <span class="bg-gray-600 px-2 py-1 rounded text-gray-300">{{ $tensionCurves[$preset['defaultTension']]['name'] ?? $preset['defaultTension'] }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Story Arcs --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">📐</span> {{ __('Story Arcs') }}
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Narrative structure frameworks') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($storyArcs as $key => $arc)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-blue-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ $arc['icon'] ?? '📐' }}</span>
                                <span class="font-medium text-white">{{ $arc['name'] }}</span>
                            </div>
                            <button onclick="toggleItem('arc', '{{ $key }}')"
                                    class="toggle-btn" data-type="arc" data-key="{{ $key }}"
                                    title="{{ __('Toggle visibility') }}">
                                @if(in_array($key, $settings['disabled_arcs'] ?? []))
                                    <i class="fas fa-eye-slash text-gray-500"></i>
                                @else
                                    <i class="fas fa-eye text-green-500"></i>
                                @endif
                            </button>
                        </div>
                        <p class="text-gray-400 text-sm mb-2">{{ $arc['description'] ?? '' }}</p>
                        @if(!empty($arc['beats']))
                            <div class="text-xs text-blue-400 truncate">
                                {{ implode(' → ', array_map(fn($b) => ucwords(str_replace('_', ' ', $b)), array_slice($arc['beats'], 0, 4))) }}...
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tension Curves --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">📈</span> {{ __('Tension Curves') }}
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Pacing dynamics for engagement') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($tensionCurves as $key => $curve)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-green-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ $curve['icon'] ?? '📈' }}</span>
                                <span class="font-medium text-white">{{ $curve['name'] }}</span>
                            </div>
                            <button onclick="toggleItem('curve', '{{ $key }}')"
                                    class="toggle-btn" data-type="curve" data-key="{{ $key }}"
                                    title="{{ __('Toggle visibility') }}">
                                @if(in_array($key, $settings['disabled_curves'] ?? []))
                                    <i class="fas fa-eye-slash text-gray-500"></i>
                                @else
                                    <i class="fas fa-eye text-green-500"></i>
                                @endif
                            </button>
                        </div>
                        <p class="text-gray-400 text-sm mb-2">{{ $curve['description'] ?? '' }}</p>
                        @if(!empty($curve['curve']))
                            <div class="h-8 flex items-end gap-0.5">
                                @foreach($curve['curve'] as $value)
                                    <div class="flex-1 bg-green-500/60 rounded-t" style="height: {{ $value }}%"></div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Emotional Journeys --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">🎭</span> {{ __('Emotional Journeys') }}
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Viewer feeling arcs') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($emotionalJourneys as $key => $journey)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-pink-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ $journey['icon'] ?? '🎭' }}</span>
                                <span class="font-medium text-white">{{ $journey['name'] }}</span>
                            </div>
                            <button onclick="toggleItem('journey', '{{ $key }}')"
                                    class="toggle-btn" data-type="journey" data-key="{{ $key }}"
                                    title="{{ __('Toggle visibility') }}">
                                @if(in_array($key, $settings['disabled_journeys'] ?? []))
                                    <i class="fas fa-eye-slash text-gray-500"></i>
                                @else
                                    <i class="fas fa-eye text-green-500"></i>
                                @endif
                            </button>
                        </div>
                        <p class="text-gray-400 text-sm mb-2">{{ $journey['description'] ?? '' }}</p>
                        @if(!empty($journey['emotionArc']))
                            <div class="text-xs text-pink-400 truncate">
                                {{ implode(' → ', array_map(fn($e) => ucfirst($e), $journey['emotionArc'])) }}
                            </div>
                        @endif
                        @if(!empty($journey['endFeeling']))
                            <div class="mt-2 text-xs text-gray-500">
                                {{ __('End feeling') }}: <span class="text-pink-300">{{ $journey['endFeeling'] }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
function toggleItem(type, key) {
    fetch('{{ route('admin.video-wizard.narrative.toggle') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ type, key })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btn = document.querySelector(`[data-type="${type}"][data-key="${key}"]`);
            const icon = btn.querySelector('i');
            if (data.enabled) {
                icon.className = 'fas fa-eye text-green-500';
            } else {
                icon.className = 'fas fa-eye-slash text-gray-500';
            }
        }
    });
}
</script>
@endsection
