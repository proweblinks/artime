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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#a855f7,#ec4899);">
                    <i class="fa-light fa-gem" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Sponsorship Rate Calculator</h2>
                    <p>Calculate your true market value for brand deals</p>
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
            <button wire:click="analyze" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <i class="fa-light fa-gem"></i> Calculate Rates
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
                <div class="aith-e-loading-title">Calculating sponsorship rates...</div>
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
                <span class="aith-e-result-title">Sponsorship Rate Results</span>
                <button wire:click="$set('result', null)" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                    <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                </button>
            </div>

            {{-- Score --}}
            @php $score = $result['sponsorship_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Sponsorship Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Highly attractive to sponsors
                        @elseif($score >= 50) Good sponsorship potential
                        @else Build engagement metrics to attract sponsors
                        @endif
                    </div>
                </div>
            </div>

            {{-- Channel Profile --}}
            @if(isset($result['channel_profile']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-simple"></i> Channel Profile</div>
                <div class="aith-e-grid-2">
                    @foreach($result['channel_profile'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $val }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Rate Tiers --}}
            @if(!empty($result['rate_tiers']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-layer-group"></i> Rate Tiers</div>
                <div class="aith-e-grid-2">
                    @foreach($result['rate_tiers'] as $tier)
                    <div class="aith-e-section-card" style="margin-bottom:0;">
                        <div style="font-weight:600;color:#fff;font-size:0.9rem;margin-bottom:0.5rem;">{{ $tier['name'] ?? $tier['tier'] ?? '' }}</div>
                        <div style="display:flex;gap:0.5rem;margin-bottom:0.375rem;">
                            <span style="font-size:0.8rem;color:rgba(255,255,255,0.5);">Min:</span>
                            <span style="font-size:0.875rem;color:#a855f7;font-weight:600;">{{ $tier['min'] ?? '-' }}</span>
                            <span style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-left:0.5rem;">Max:</span>
                            <span style="font-size:0.875rem;color:#ec4899;font-weight:600;">{{ $tier['max'] ?? '-' }}</span>
                        </div>
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $tier['description'] ?? '' }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Niche CPM Benchmark --}}
            @if(isset($result['niche_cpm_benchmark']))
            <div class="aith-e-section-card" style="text-align:center;">
                <div class="aith-e-section-card-title"><i class="fa-light fa-gauge-high"></i> Niche CPM Benchmark</div>
                <div style="font-size:1.5rem;font-weight:700;color:#a855f7;margin:0.75rem 0;">{{ $result['niche_cpm_benchmark'] }}</div>
            </div>
            @endif

            {{-- Comparable Creators --}}
            @if(!empty($result['comparable_creators']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-users"></i> Comparable Creators</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Name</th><th>Subscribers</th><th>Est. Rate</th></tr></thead>
                        <tbody>
                        @foreach($result['comparable_creators'] as $creator)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $creator['name'] ?? '' }}</td>
                            <td>{{ $creator['subscribers'] ?? '-' }}</td>
                            <td>{{ $creator['estimated_rate'] ?? $creator['est_rate'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Negotiation Tips --}}
            @if(!empty($result['negotiation_tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-handshake"></i> Negotiation Tips</div>
                <ul class="aith-e-list">
                    @foreach($result['negotiation_tips'] as $tip)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Media Kit Suggestions --}}
            @if(!empty($result['media_kit_suggestions']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-file-lines"></i> Media Kit Suggestions</div>
                <ul class="aith-e-list">
                    @foreach($result['media_kit_suggestions'] as $suggestion)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $suggestion }}</li>
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
