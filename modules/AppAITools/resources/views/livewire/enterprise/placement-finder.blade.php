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
                <div class="aith-e-tool-icon aith-e-icon-purple-violet" style="background:linear-gradient(135deg,#a855f7,#7c3aed);">
                    <i class="fa-light fa-bullseye-pointer" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Placement Finder</h2>
                    <p>Find YouTube channels for Google Ads placements in your niche</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">YouTube Channel URL</label>
                <input type="url" wire:model="url" class="aith-input"
                       placeholder="https://youtube.com/@channel">
                @error('url') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Target Niche (optional)</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. tech reviews, fitness, cooking">
                @error('niche') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" wire:target="analyze" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-magnifying-glass"></i> Find Placements
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
                <div class="aith-e-loading-title">Analyzing placements...</div>
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
                <span class="aith-e-result-title">Placement Analysis Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-placement-finder', 'Placement-Finder-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-placement-finder">

            {{-- Score --}}
            @php $score = $result['placement_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Placement Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent placement potential
                        @elseif($score >= 50) Good placement opportunities available
                        @else Limited placement options - consider broadening criteria
                        @endif
                    </div>
                </div>
            </div>

            {{-- Channel Analysis --}}
            @if(isset($result['channel_analysis']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-simple"></i> Channel Analysis</div>
                <div class="aith-e-grid-2">
                    @foreach($result['channel_analysis'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $val }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Audience Insights --}}
            @if(isset($result['audience_insights']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-pie"></i> Audience Insights</div>
                <div class="aith-e-grid-2">
                    @if(isset($result['audience_insights']['demographics']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Demographics</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">
                            @if(is_array($result['audience_insights']['demographics']))
                                {{ implode(', ', $result['audience_insights']['demographics']) }}
                            @else
                                {{ $result['audience_insights']['demographics'] }}
                            @endif
                        </div>
                    </div>
                    @endif
                    @if(isset($result['audience_insights']['interests']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Interests</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">
                            @if(is_array($result['audience_insights']['interests']))
                                {{ implode(', ', $result['audience_insights']['interests']) }}
                            @else
                                {{ $result['audience_insights']['interests'] }}
                            @endif
                        </div>
                    </div>
                    @endif
                    @if(isset($result['audience_insights']['buying_intent']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Buying Intent</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">
                            @if(is_array($result['audience_insights']['buying_intent']))
                                {{ implode(', ', $result['audience_insights']['buying_intent']) }}
                            @else
                                {{ $result['audience_insights']['buying_intent'] }}
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Similar Channels --}}
            @if(!empty($result['similar_channels']))
            <div class="aith-e-section-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                    <div class="aith-e-section-card-title" style="margin-bottom:0;"><i class="fa-light fa-users"></i> Similar Channels for Placement</div>
                    <button class="aith-e-btn-copy" onclick="enterpriseCopy('{{ collect($result['similar_channels'])->pluck('name')->filter()->implode('\n') }}', 'All channel names copied!')">
                        <i class="fa-light fa-copy"></i> Copy All Channels
                    </button>
                </div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Channel</th><th>Relevance</th><th>Est. CPM</th><th>Overlap</th><th>Recommendation</th></tr></thead>
                        <tbody>
                        @foreach($result['similar_channels'] as $ch)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $ch['name'] ?? '' }}</td>
                            <td>
                                @php $rs = $ch['relevance_score'] ?? 0; @endphp
                                <span class="aith-e-tag {{ $rs >= 80 ? 'aith-e-tag-high' : ($rs >= 50 ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $rs }}/100</span>
                            </td>
                            <td>{{ $ch['estimated_cpm'] ?? '-' }}</td>
                            <td>{{ $ch['audience_overlap'] ?? '-' }}</td>
                            <td>{{ $ch['recommendation'] ?? '' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Targeting Keywords --}}
            @if(!empty($result['targeting_keywords']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-tags"></i> Targeting Keywords</div>
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                    @foreach($result['targeting_keywords'] as $kw)
                    <span class="aith-e-tag" style="background:rgba(139,92,246,0.15);color:#c4b5fd;">{{ $kw }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Budget Recommendation --}}
            @if(isset($result['budget_recommendation']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-sack-dollar"></i> Budget Recommendation</div>
                <div class="aith-e-grid-2">
                    @foreach($result['budget_recommendation'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $val }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Tips --}}
            @if(!empty($result['tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Tips</div>
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
