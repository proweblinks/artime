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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#22c55e,#059669);">
                    <i class="fa-light fa-rocket" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>CPM Booster Strategist</h2>
                    <p>Optimize content for higher-paying advertisers</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">YouTube Channel URL</label>
                <input type="url" wire:model="url" class="aith-input"
                       placeholder="https://youtube.com/@channel">
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Target Niche (optional)</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. finance, tech reviews, health">
            </div>
            <button wire:click="analyze" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <i class="fa-light fa-rocket"></i> Boost CPM Strategy
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
                <div class="aith-e-loading-title">Analyzing CPM opportunities...</div>
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
                <span class="aith-e-result-title">CPM Boost Strategy</span>
                <button wire:click="$set('result', null)" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                    <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                </button>
            </div>

            {{-- Score --}}
            @php $score = $result['cpm_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">CPM Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent CPM potential - premium advertiser niche
                        @elseif($score >= 50) Good CPM range with room for optimization
                        @else Below average CPM - significant boost possible
                        @endif
                    </div>
                </div>
            </div>

            {{-- Current Analysis --}}
            @if(isset($result['current_analysis']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-simple"></i> Current Analysis</div>
                <div class="aith-e-grid-2">
                    @foreach($result['current_analysis'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $val }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- High CPM Keywords --}}
            @if(!empty($result['high_cpm_keywords']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-tags"></i> High CPM Keywords</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Keyword</th><th>Est. CPM</th><th>Search Volume</th><th>Competition</th></tr></thead>
                        <tbody>
                        @foreach($result['high_cpm_keywords'] as $kw)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $kw['keyword'] ?? '' }}</td>
                            <td style="color:#22c55e;font-weight:600;">{{ $kw['estimated_cpm'] ?? '-' }}</td>
                            <td>{{ $kw['search_volume'] ?? '-' }}</td>
                            <td>
                                @php $comp = strtolower($kw['competition'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $comp === 'low' ? 'aith-e-tag-high' : ($comp === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $kw['competition'] ?? '-' }}</span>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Video Ideas --}}
            @if(!empty($result['video_ideas']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-video"></i> High-CPM Video Ideas</div>
                @foreach($result['video_ideas'] as $idea)
                <div class="aith-e-section-card" style="margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $idea['title'] ?? '' }}</span>
                        @if(isset($idea['target_cpm']))
                        <span class="aith-e-tag aith-e-tag-high">{{ $idea['target_cpm'] }} CPM</span>
                        @endif
                    </div>
                    @if(isset($idea['reasoning']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.5rem;">{{ $idea['reasoning'] }}</div>
                    @endif
                    @if(!empty($idea['keywords']))
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($idea['keywords'] as $kw)
                        <span class="aith-e-tag" style="background:rgba(34,197,94,0.15);color:#86efac;">{{ $kw }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Content Calendar --}}
            @if(!empty($result['content_calendar']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-calendar"></i> Content Calendar</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Week</th><th>Topic</th><th>Target CPM</th><th>Format</th></tr></thead>
                        <tbody>
                        @foreach($result['content_calendar'] as $item)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $item['week'] ?? '' }}</td>
                            <td>{{ $item['topic'] ?? '' }}</td>
                            <td style="color:#22c55e;font-weight:600;">{{ $item['target_cpm'] ?? '-' }}</td>
                            <td>{{ $item['format'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Tips --}}
            @if(!empty($result['optimization_tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Optimization Tips</div>
                <ul class="aith-e-list">
                    @foreach($result['optimization_tips'] as $tip)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @endif

            {{-- Error --}}
            @if(session('error'))
            <div class="aith-e-error">{{ session('error') }}</div>
            @endif
        </div>

        {{-- History --}}
        @if(count($history) > 0 && !$result)
        <div class="aith-card" style="margin-top:1rem;">
            <div class="aith-e-section-card-title"><i class="fa-light fa-clock-rotate-left"></i> Recent Analyses</div>
            @foreach($history as $i => $item)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid rgba(255,255,255,0.04);cursor:pointer;"
                 wire:click="loadHistoryItem({{ $i }})">
                <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);">{{ \Illuminate\Support\Str::limit($item['title'], 60) }}</span>
                <span style="font-size:0.75rem;color:rgba(255,255,255,0.25);">{{ $item['time_ago'] }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
