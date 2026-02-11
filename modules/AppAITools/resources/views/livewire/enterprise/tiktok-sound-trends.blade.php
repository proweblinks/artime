<div>
    @include('appaitools::livewire.enterprise._enterprise-tool-base')

    <div class="aith-tool">
        <div class="aith-nav">
            <a href="{{ route('app.ai-tools.enterprise-suite') }}" class="aith-nav-btn">
                <i class="fa-light fa-arrow-left"></i> Enterprise Suite
            </a>
        </div>

        <div class="aith-card">
            <div class="aith-e-tool-header">
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#ec4899,#e11d48);">
                    <i class="fa-light fa-music" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Sound Trend Analyzer</h2>
                    <p>Identify trending sounds and audio before they peak</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">Niche</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. dance, comedy, beauty">
                @error('niche')
                <span class="aith-e-field-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Content Style (optional)</label>
                <input type="text" wire:model="contentStyle" class="aith-input"
                       placeholder="e.g. transitions, storytelling, comedy">
                @error('contentStyle')
                <span class="aith-e-field-error">{{ $message }}</span>
                @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-music"></i> Analyze Sound Trends
                </span>
                <span wire:loading wire:target="analyze">
                    <i class="fa-light fa-spinner-third fa-spin"></i> Analyzing...
                </span>
                <span style="margin-left:0.5rem;opacity:0.6;font-size:0.8rem;">3 credits</span>
            </button>
            @endif

            @if($isLoading)
            {{-- Loading Steps --}}
            <div class="aith-e-loading" x-data="{ step: 0 }" x-init="
                let steps = {{ count($loadingSteps) }};
                let interval = setInterval(() => { if(step < steps - 1) step++; }, 2500);
                $wire.on('loadingComplete', () => clearInterval(interval));
            ">
                <div class="aith-e-loading-title">Scanning sound trends...</div>
                <div class="aith-e-loading-steps">
                    @foreach($loadingSteps as $i => $step)
                    <div class="aith-e-loading-step"
                         :class="{ 'active': step === {{ $i }}, 'done': step > {{ $i }} }">
                        <span class="step-icon">
                            <template x-if="step > {{ $i }}"><i class="fa-solid fa-check"></i></template>
                            <template x-if="step <= {{ $i }}">{{ $i + 1 }}</template>
                        </span>
                        <span class="step-label">{{ $step }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="aith-e-progress-bar">
                    <div class="aith-e-progress-fill" :style="'width:' + ((step + 1) / {{ count($loadingSteps) }} * 100) + '%'"></div>
                </div>
            </div>
            @endif

            @if($result && !$isLoading)
            {{-- Results --}}
            <div class="aith-e-result-header">
                <span class="aith-e-result-title">Sound Trend Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-tiktok-sound-trends', 'Sound-Trend-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-tiktok-sound-trends">
            {{-- Score --}}
            @php $score = $result['sound_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Sound Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent sound strategy - riding trending audio waves
                        @elseif($score >= 50) Good sound selection with trending opportunities
                        @else Needs better sound strategy to boost content reach
                        @endif
                    </div>
                </div>
            </div>

            {{-- Trending Sounds Table --}}
            @if(!empty($result['trending_sounds']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-fire"></i> Trending Sounds</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Sound</th><th>Artist</th><th>Usage</th><th>Growth</th><th>Status</th><th>Best For</th></tr></thead>
                        <tbody>
                        @foreach($result['trending_sounds'] as $sound)
                        <tr>
                            <td style="font-weight:600;color:#ec4899;">{{ $sound['name'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $sound['artist'] ?? '-' }}</td>
                            <td>{{ $sound['usage_count'] ?? '-' }}</td>
                            <td>
                                @if(isset($sound['growth_rate']))
                                <span style="color:#22c55e;font-weight:600;">
                                    <i class="fa-solid fa-arrow-trend-up" style="font-size:0.65rem;"></i> {{ $sound['growth_rate'] }}
                                </span>
                                @else
                                -
                                @endif
                            </td>
                            <td>
                                @if(isset($sound['peak_status']))
                                @php
                                    $ps = strtolower($sound['peak_status']);
                                    $psClass = str_contains($ps, 'rising') || str_contains($ps, 'early') ? 'aith-e-tag-high' :
                                              (str_contains($ps, 'peak') ? 'aith-e-tag-medium' : 'aith-e-tag-low');
                                @endphp
                                <span class="aith-e-tag {{ $psClass }}">{{ $sound['peak_status'] }}</span>
                                @else
                                -
                                @endif
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $sound['best_for'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Emerging Sounds --}}
            @if(!empty($result['emerging_sounds']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-seedling"></i> Emerging Sounds</div>
                <div class="aith-e-grid-2">
                @foreach($result['emerging_sounds'] as $sound)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.875rem;">{{ $sound['name'] ?? '' }}</span>
                        @if(isset($sound['predicted_peak']))
                        <span class="aith-e-tag aith-e-tag-high">Peak: {{ $sound['predicted_peak'] }}</span>
                        @endif
                    </div>
                    @if(isset($sound['current_usage']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.25rem;">
                        <strong style="color:rgba(255,255,255,0.6);">Current usage:</strong> {{ $sound['current_usage'] }}
                    </div>
                    @endif
                    @if(isset($sound['why_trending']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);">{{ $sound['why_trending'] }}</div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Evergreen Sounds --}}
            @if(!empty($result['evergreen_sounds']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-infinity"></i> Evergreen Sounds</div>
                @foreach($result['evergreen_sounds'] as $sound)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div>
                        <span style="font-weight:600;color:#ec4899;font-size:0.875rem;">{{ $sound['name'] ?? '' }}</span>
                        @if(isset($sound['category']))
                        <span style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-left:0.5rem;">{{ $sound['category'] }}</span>
                        @endif
                    </div>
                    @if(isset($sound['best_use_case']))
                    <span style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $sound['best_use_case'] }}</span>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Sound Strategy --}}
            @if(isset($result['sound_strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chess"></i> Sound Strategy</div>
                <div class="aith-e-grid-2">
                    @if(isset($result['sound_strategy']['original_vs_trending']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Original vs Trending</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['sound_strategy']['original_vs_trending'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['sound_strategy']['timing']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Timing</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['sound_strategy']['timing'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['sound_strategy']['niche_fit']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Niche Fit</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['sound_strategy']['niche_fit'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Tips --}}
            @if(!empty($result['tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Sound Tips</div>
                <ul class="aith-e-list">
                    @foreach($result['tips'] as $tip)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            </div>{{-- end pdf-content --}}
            @endif

            {{-- Error --}}
            @if(session('error'))
            <div class="aith-e-error">{{ session('error') }}</div>
            @endif
        </div>

        {{-- History --}}
        @include('appaitools::livewire.enterprise._enterprise-history')
    </div>
</div>
