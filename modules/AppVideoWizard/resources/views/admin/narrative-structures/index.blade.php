@extends('layouts.app')

@section('title', __('Narrative Structures'))

@section('css')
{{-- Tailwind CSS for admin panel styling --}}
<script src="https://cdn.tailwindcss.com"></script>
{{-- Alpine.js for tab functionality --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
    [x-cloak] { display: none !important; }
</style>
@endsection

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

        {{-- Tier Tabs --}}
        <div x-data="{ activeTab: 'narrative' }" class="mb-8">
            <div class="flex space-x-2 mb-6 border-b border-gray-700">
                <button @click="activeTab = 'narrative'"
                        :class="activeTab === 'narrative' ? 'border-purple-500 text-purple-400' : 'border-transparent text-gray-400 hover:text-white'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition">
                    <i class="fas fa-book mr-2"></i>{{ __('Narrative Structure') }}
                </button>
                <button @click="activeTab = 'cinematography'"
                        :class="activeTab === 'cinematography' ? 'border-cyan-500 text-cyan-400' : 'border-transparent text-gray-400 hover:text-white'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition">
                    <i class="fas fa-video mr-2"></i>{{ __('Cinematography') }}
                </button>
                <button @click="activeTab = 'engagement'"
                        :class="activeTab === 'engagement' ? 'border-orange-500 text-orange-400' : 'border-transparent text-gray-400 hover:text-white'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition">
                    <i class="fas fa-bolt mr-2"></i>{{ __('Engagement') }}
                </button>
                <button @click="activeTab = 'advanced'"
                        :class="activeTab === 'advanced' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-gray-400 hover:text-white'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition">
                    <i class="fas fa-magic mr-2"></i>{{ __('Advanced') }}
                </button>
            </div>

            {{-- TIER 1: Narrative Structure Stats --}}
            <div x-show="activeTab === 'narrative'" x-cloak>
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
            </div>

            {{-- TIER 2: Cinematography Stats --}}
            <div x-show="activeTab === 'cinematography'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-cyan-500/20 rounded-xl flex items-center justify-center">
                                <span class="text-2xl">🎬</span>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">{{ __('Shot Types') }}</p>
                                <p class="text-2xl font-bold text-white">{{ $stats['total_shot_types'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-yellow-500/20 rounded-xl flex items-center justify-center">
                                <span class="text-2xl">💡</span>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">{{ __('Lighting Styles') }}</p>
                                <p class="text-2xl font-bold text-white">{{ $stats['total_lighting_styles'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-rose-500/20 rounded-xl flex items-center justify-center">
                                <span class="text-2xl">🎨</span>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">{{ __('Color Grades') }}</p>
                                <p class="text-2xl font-bold text-white">{{ $stats['total_color_grades'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-indigo-500/20 rounded-xl flex items-center justify-center">
                                <span class="text-2xl">📐</span>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">{{ __('Compositions') }}</p>
                                <p class="text-2xl font-bold text-white">{{ $stats['total_compositions'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TIER 2: Engagement Stats --}}
            <div x-show="activeTab === 'engagement'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-orange-500/20 rounded-xl flex items-center justify-center">
                                <span class="text-2xl">🪝</span>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">{{ __('Retention Hooks') }}</p>
                                <p class="text-2xl font-bold text-white">{{ $stats['total_retention_hooks'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-teal-500/20 rounded-xl flex items-center justify-center">
                                <span class="text-2xl">🎵</span>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">{{ __('Scene Beats') }}</p>
                                <p class="text-2xl font-bold text-white">{{ count($sceneBeats ?? []) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-violet-500/20 rounded-xl flex items-center justify-center">
                                <span class="text-2xl">✨</span>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">{{ __('Transitions') }}</p>
                                <p class="text-2xl font-bold text-white">{{ count($transitions ?? []) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TIER 3: Advanced Stats --}}
            <div x-show="activeTab === 'advanced'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
                    <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                                <span class="text-2xl">🎨</span>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">{{ __('Visual Styles') }}</p>
                                <p class="text-2xl font-bold text-white">{{ $stats['total_visual_styles'] ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-fuchsia-500/20 rounded-xl flex items-center justify-center">
                                <span class="text-2xl">🎵</span>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">{{ __('Music Moods') }}</p>
                                <p class="text-2xl font-bold text-white">{{ $stats['total_music_moods'] ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-sky-500/20 rounded-xl flex items-center justify-center">
                                <span class="text-2xl">⏱️</span>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">{{ __('Pacing Profiles') }}</p>
                                <p class="text-2xl font-bold text-white">{{ $stats['total_pacing_profiles'] ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-amber-500/20 rounded-xl flex items-center justify-center">
                                <span class="text-2xl">📋</span>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">{{ __('Genre Templates') }}</p>
                                <p class="text-2xl font-bold text-white">{{ $stats['total_genre_templates'] ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-lime-500/20 rounded-xl flex items-center justify-center">
                                <span class="text-2xl">🎭</span>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">{{ __('Visual Themes') }}</p>
                                <p class="text-2xl font-bold text-white">{{ $stats['total_visual_themes'] ?? 0 }}</p>
                            </div>
                        </div>
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
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
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

        {{-- ======================= TIER 2: CINEMATOGRAPHY ======================= --}}

        {{-- Shot Types --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">🎬</span> {{ __('Shot Types') }}
                    <span class="text-xs bg-cyan-500/20 text-cyan-400 px-2 py-1 rounded-full ml-2">{{ __('Cinematography') }}</span>
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Camera framing options for visual descriptions') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($shotTypes ?? [] as $key => $shot)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-cyan-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <span class="font-medium text-white">{{ $shot['name'] }}</span>
                                <span class="text-cyan-400 text-xs ml-1">({{ $shot['abbrev'] ?? '' }})</span>
                            </div>
                            <button onclick="toggleItem('shot_type', '{{ $key }}')"
                                    class="toggle-btn" data-type="shot_type" data-key="{{ $key }}"
                                    title="{{ __('Toggle visibility') }}">
                                @if(in_array($key, $settings['disabled_shot_types'] ?? []))
                                    <i class="fas fa-eye-slash text-gray-500"></i>
                                @else
                                    <i class="fas fa-eye text-green-500"></i>
                                @endif
                            </button>
                        </div>
                        <p class="text-gray-400 text-xs mb-2">{{ $shot['description'] ?? '' }}</p>
                        @if(!empty($shot['bestFor']))
                            <div class="flex flex-wrap gap-1">
                                @foreach(array_slice($shot['bestFor'], 0, 2) as $use)
                                    <span class="bg-gray-600 px-2 py-0.5 rounded text-xs text-gray-300">{{ $use }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Lighting Styles --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">💡</span> {{ __('Lighting Styles') }}
                    <span class="text-xs bg-yellow-500/20 text-yellow-400 px-2 py-1 rounded-full ml-2">{{ __('Cinematography') }}</span>
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Lighting atmosphere for scene visuals') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($lightingStyles ?? [] as $key => $lighting)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-yellow-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <span class="font-medium text-white">{{ $lighting['name'] }}</span>
                            <button onclick="toggleItem('lighting', '{{ $key }}')"
                                    class="toggle-btn" data-type="lighting" data-key="{{ $key }}"
                                    title="{{ __('Toggle visibility') }}">
                                @if(in_array($key, $settings['disabled_lightings'] ?? []))
                                    <i class="fas fa-eye-slash text-gray-500"></i>
                                @else
                                    <i class="fas fa-eye text-green-500"></i>
                                @endif
                            </button>
                        </div>
                        <p class="text-gray-400 text-xs mb-2">{{ $lighting['description'] ?? '' }}</p>
                        @if(!empty($lighting['mood']))
                            <span class="bg-yellow-500/20 text-yellow-400 px-2 py-0.5 rounded text-xs">{{ $lighting['mood'] }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Color Grades --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">🎨</span> {{ __('Color Grades') }}
                    <span class="text-xs bg-rose-500/20 text-rose-400 px-2 py-1 rounded-full ml-2">{{ __('Cinematography') }}</span>
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Color grading styles for visual mood') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($colorGrades ?? [] as $key => $grade)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-rose-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <span class="font-medium text-white">{{ $grade['name'] }}</span>
                            <button onclick="toggleItem('color_grade', '{{ $key }}')"
                                    class="toggle-btn" data-type="color_grade" data-key="{{ $key }}"
                                    title="{{ __('Toggle visibility') }}">
                                @if(in_array($key, $settings['disabled_color_grades'] ?? []))
                                    <i class="fas fa-eye-slash text-gray-500"></i>
                                @else
                                    <i class="fas fa-eye text-green-500"></i>
                                @endif
                            </button>
                        </div>
                        <p class="text-gray-400 text-xs">{{ $grade['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Compositions --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">📐</span> {{ __('Compositions') }}
                    <span class="text-xs bg-indigo-500/20 text-indigo-400 px-2 py-1 rounded-full ml-2">{{ __('Cinematography') }}</span>
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Framing and composition techniques') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($compositions ?? [] as $key => $comp)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-indigo-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <span class="font-medium text-white">{{ $comp['name'] }}</span>
                            <button onclick="toggleItem('composition', '{{ $key }}')"
                                    class="toggle-btn" data-type="composition" data-key="{{ $key }}"
                                    title="{{ __('Toggle visibility') }}">
                                @if(in_array($key, $settings['disabled_compositions'] ?? []))
                                    <i class="fas fa-eye-slash text-gray-500"></i>
                                @else
                                    <i class="fas fa-eye text-green-500"></i>
                                @endif
                            </button>
                        </div>
                        <p class="text-gray-400 text-xs">{{ $comp['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ======================= TIER 2: ENGAGEMENT ======================= --}}

        {{-- Retention Hooks --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">🪝</span> {{ __('Retention Hooks') }}
                    <span class="text-xs bg-orange-500/20 text-orange-400 px-2 py-1 rounded-full ml-2">{{ __('Engagement') }}</span>
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Engagement elements to maintain viewer attention') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($retentionHooks ?? [] as $key => $hook)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-orange-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <span class="font-medium text-white">{{ $hook['name'] }}</span>
                                @if(!empty($hook['insertAfter']))
                                    <span class="text-orange-400 text-xs ml-2">@{{ $hook['insertAfter'] }}s</span>
                                @endif
                            </div>
                            <button onclick="toggleItem('retention_hook', '{{ $key }}')"
                                    class="toggle-btn" data-type="retention_hook" data-key="{{ $key }}"
                                    title="{{ __('Toggle visibility') }}">
                                @if(in_array($key, $settings['disabled_retention_hooks'] ?? []))
                                    <i class="fas fa-eye-slash text-gray-500"></i>
                                @else
                                    <i class="fas fa-eye text-green-500"></i>
                                @endif
                            </button>
                        </div>
                        @if(!empty($hook['templates']))
                            <div class="space-y-1">
                                @foreach(array_slice($hook['templates'], 0, 2) as $template)
                                    <p class="text-gray-400 text-xs italic">"{{ $template }}"</p>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Scene Beats --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">🎵</span> {{ __('Scene Beats') }}
                    <span class="text-xs bg-teal-500/20 text-teal-400 px-2 py-1 rounded-full ml-2">{{ __('Engagement') }}</span>
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Micro-structure within each scene for better pacing') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($sceneBeats ?? [] as $key => $beat)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-white">{{ $beat['name'] }}</span>
                            <span class="bg-teal-500/20 text-teal-400 px-2 py-0.5 rounded text-sm font-bold">{{ $beat['percentage'] }}%</span>
                        </div>
                        <p class="text-gray-400 text-sm mb-2">{{ $beat['description'] ?? '' }}</p>
                        <p class="text-teal-400 text-xs">{{ $beat['purpose'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Transitions --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">✨</span> {{ __('Transitions') }}
                    <span class="text-xs bg-violet-500/20 text-violet-400 px-2 py-1 rounded-full ml-2">{{ __('Engagement') }}</span>
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Scene transition effects') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($transitions ?? [] as $key => $transition)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-violet-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <span class="font-medium text-white">{{ $transition['name'] }}</span>
                            <button onclick="toggleItem('transition', '{{ $key }}')"
                                    class="toggle-btn" data-type="transition" data-key="{{ $key }}"
                                    title="{{ __('Toggle visibility') }}">
                                @if(in_array($key, $settings['disabled_transitions'] ?? []))
                                    <i class="fas fa-eye-slash text-gray-500"></i>
                                @else
                                    <i class="fas fa-eye text-green-500"></i>
                                @endif
                            </button>
                        </div>
                        <p class="text-gray-400 text-xs mb-2">{{ $transition['description'] ?? '' }}</p>
                        @if(!empty($transition['duration']))
                            <span class="bg-violet-500/20 text-violet-400 px-2 py-0.5 rounded text-xs">{{ $transition['duration'] }}ms</span>
                        @endif
                        @if(!empty($transition['bestFor']))
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach(array_slice($transition['bestFor'], 0, 2) as $use)
                                    <span class="bg-gray-600 px-2 py-0.5 rounded text-xs text-gray-300">{{ $use }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ======================= TIER 3: ADVANCED ======================= --}}

        {{-- Visual Styles --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">🎨</span> {{ __('Visual Styles') }}
                    <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-1 rounded-full ml-2">{{ __('Advanced') }}</span>
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Consistent visual style presets for image generation') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($visualStyles ?? [] as $key => $style)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-emerald-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ $style['icon'] ?? '🎨' }}</span>
                                <span class="font-medium text-white">{{ $style['name'] }}</span>
                            </div>
                        </div>
                        <p class="text-gray-400 text-xs mb-2">{{ $style['description'] ?? '' }}</p>
                        @if(!empty($style['bestFor']))
                            <div class="flex flex-wrap gap-1">
                                @foreach(array_slice($style['bestFor'], 0, 2) as $use)
                                    <span class="bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded text-xs">{{ $use }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Music Moods --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">🎵</span> {{ __('Music Moods') }}
                    <span class="text-xs bg-fuchsia-500/20 text-fuchsia-400 px-2 py-1 rounded-full ml-2">{{ __('Advanced') }}</span>
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Soundtrack mood suggestions for scenes') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($musicMoods ?? [] as $key => $mood)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-fuchsia-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ $mood['icon'] ?? '🎵' }}</span>
                                <span class="font-medium text-white">{{ $mood['name'] }}</span>
                            </div>
                        </div>
                        <p class="text-gray-400 text-xs mb-2">{{ $mood['description'] ?? '' }}</p>
                        <div class="flex flex-wrap gap-1 mb-2">
                            <span class="bg-fuchsia-500/20 text-fuchsia-400 px-2 py-0.5 rounded text-xs">{{ $mood['tempo'] ?? 'moderate' }}</span>
                            <span class="bg-gray-600 px-2 py-0.5 rounded text-xs text-gray-300">{{ $mood['energy'] ?? 'medium' }}</span>
                        </div>
                        @if(!empty($mood['instruments']))
                            <div class="text-xs text-gray-500 truncate">
                                {{ implode(', ', array_slice($mood['instruments'], 0, 3)) }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Pacing Profiles --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">⏱️</span> {{ __('Pacing Profiles') }}
                    <span class="text-xs bg-sky-500/20 text-sky-400 px-2 py-1 rounded-full ml-2">{{ __('Advanced') }}</span>
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Words-per-minute and scene duration optimization') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($pacingProfiles ?? [] as $key => $profile)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-sky-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ $profile['icon'] ?? '⏱️' }}</span>
                                <span class="font-medium text-white">{{ $profile['name'] }}</span>
                            </div>
                        </div>
                        <p class="text-gray-400 text-xs mb-2">{{ $profile['description'] ?? '' }}</p>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="bg-sky-500/20 text-sky-400 px-2 py-0.5 rounded text-sm font-bold">{{ $profile['wpm'] ?? 0 }} WPM</span>
                        </div>
                        @if(!empty($profile['sceneDuration']))
                            <div class="text-xs text-gray-500">
                                {{ $profile['sceneDuration']['min'] ?? 0 }}s - {{ $profile['sceneDuration']['max'] ?? 0 }}s per scene
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Genre Templates --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">📋</span> {{ __('Genre Templates') }}
                    <span class="text-xs bg-amber-500/20 text-amber-400 px-2 py-1 rounded-full ml-2">{{ __('Advanced') }}</span>
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Pre-configured settings for specific video genres') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($genreTemplates ?? [] as $key => $template)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-amber-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ $template['icon'] ?? '📋' }}</span>
                                <span class="font-medium text-white">{{ $template['name'] }}</span>
                            </div>
                        </div>
                        <p class="text-gray-400 text-xs mb-3">{{ $template['description'] ?? '' }}</p>
                        @if(!empty($template['tips']))
                            <div class="bg-amber-500/10 border border-amber-500/30 rounded p-2 mb-2">
                                <p class="text-amber-400 text-xs"><i class="fas fa-lightbulb mr-1"></i>{{ $template['tips'] }}</p>
                            </div>
                        @endif
                        @if(!empty($template['defaults']))
                            <div class="flex flex-wrap gap-1">
                                @if(!empty($template['defaults']['visualStyle']))
                                    <span class="bg-gray-600 px-2 py-0.5 rounded text-xs text-gray-300">{{ $template['defaults']['visualStyle'] }}</span>
                                @endif
                                @if(!empty($template['defaults']['musicMood']))
                                    <span class="bg-gray-600 px-2 py-0.5 rounded text-xs text-gray-300">{{ $template['defaults']['musicMood'] }}</span>
                                @endif
                                @if(!empty($template['defaults']['pacingProfile']))
                                    <span class="bg-gray-600 px-2 py-0.5 rounded text-xs text-gray-300">{{ $template['defaults']['pacingProfile'] }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Visual Themes --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <span class="text-xl">🎭</span> {{ __('Visual Themes') }}
                    <span class="text-xs bg-lime-500/20 text-lime-400 px-2 py-1 rounded-full ml-2">{{ __('Advanced') }}</span>
                </h2>
                <p class="text-gray-400 text-sm">{{ __('Cohesive visual theme configurations for consistency') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($visualThemes ?? [] as $key => $theme)
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 hover:border-lime-500/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <span class="font-medium text-white">{{ $theme['name'] }}</span>
                        </div>
                        <p class="text-gray-400 text-xs mb-2">{{ $theme['mood'] ?? '' }} mood</p>
                        @if(!empty($theme['colors']))
                            <div class="flex gap-1 mb-2">
                                @foreach($theme['colors'] as $color)
                                    <div class="w-6 h-6 rounded border border-gray-500" style="background-color: {{ $color }};" title="{{ $color }}"></div>
                                @endforeach
                            </div>
                        @endif
                        <div class="flex flex-wrap gap-1">
                            @if(!empty($theme['lighting']))
                                <span class="bg-gray-600 px-2 py-0.5 rounded text-xs text-gray-300">{{ $theme['lighting'] }}</span>
                            @endif
                            @if(!empty($theme['colorGrade']))
                                <span class="bg-gray-600 px-2 py-0.5 rounded text-xs text-gray-300">{{ $theme['colorGrade'] }}</span>
                            @endif
                        </div>
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
