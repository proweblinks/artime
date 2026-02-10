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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#f97316,#dc2626);">
                    <i class="fa-light fa-users-viewfinder" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Audience Monetization Profiler</h2>
                    <p>Deep analysis of your audience's spending behavior</p>
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
                <i class="fa-light fa-users-viewfinder"></i> Profile Audience
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
                <div class="aith-e-loading-title">Profiling audience...</div>
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
                <span class="aith-e-result-title">Audience Profile Results</span>
                <button wire:click="$set('result', null)" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                    <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                </button>
            </div>

            {{-- Score --}}
            @php $score = $result['audience_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Audience Monetization Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) High-value audience with strong spending power
                        @elseif($score >= 50) Moderate spending potential - targeted offers recommended
                        @else Lower spending audience - focus on volume-based strategies
                        @endif
                    </div>
                </div>
            </div>

            {{-- Channel Overview --}}
            @if(isset($result['channel_overview']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-simple"></i> Channel Overview</div>
                <div class="aith-e-grid-2">
                    @foreach($result['channel_overview'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $val }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Demographic Segments --}}
            @if(!empty($result['demographic_segments']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-users"></i> Demographic Segments</div>
                @foreach($result['demographic_segments'] as $seg)
                <div class="aith-e-section-card" style="margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $seg['segment'] ?? '' }}</span>
                        <div style="display:flex;gap:0.5rem;align-items:center;">
                            @if(isset($seg['percentage']))
                            <span class="aith-e-tag" style="background:rgba(249,115,22,0.15);color:#fdba74;">{{ $seg['percentage'] }}</span>
                            @endif
                            @if(isset($seg['spending_power']))
                            @php $sp = strtolower($seg['spending_power']); @endphp
                            <span class="aith-e-tag {{ $sp === 'high' ? 'aith-e-tag-high' : ($sp === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $seg['spending_power'] }}</span>
                            @endif
                        </div>
                    </div>
                    @if(!empty($seg['interests']))
                    <div style="margin-bottom:0.375rem;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Interests</span>
                        <div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-top:0.25rem;">
                            @foreach($seg['interests'] as $interest)
                            <span class="aith-e-tag" style="background:rgba(249,115,22,0.1);color:#fdba74;">{{ $interest }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @if(!empty($seg['purchase_triggers']))
                    <div>
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Purchase Triggers</span>
                        <div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-top:0.25rem;">
                            @foreach($seg['purchase_triggers'] as $trigger)
                            <span class="aith-e-tag" style="background:rgba(239,68,68,0.1);color:#fca5a5;">{{ $trigger }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Spending Analysis --}}
            @if(isset($result['spending_analysis']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-wallet"></i> Spending Analysis</div>
                <div class="aith-e-grid-2">
                    @foreach($result['spending_analysis'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">
                            @if(is_array($val))
                                {{ implode(', ', $val) }}
                            @else
                                @php $valLc = strtolower($val); @endphp
                                @if(in_array($valLc, ['high', 'medium', 'low']))
                                <span class="aith-e-tag {{ $valLc === 'high' ? 'aith-e-tag-high' : ($valLc === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $val }}</span>
                                @else
                                {{ $val }}
                                @endif
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Product Recommendations --}}
            @if(!empty($result['product_recommendations']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-boxes-stacked"></i> Product Recommendations</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Product Type</th><th>Price Range</th><th>Conversion</th><th>Reasoning</th></tr></thead>
                        <tbody>
                        @foreach($result['product_recommendations'] as $prod)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $prod['product_type'] ?? '' }}</td>
                            <td style="color:#f97316;font-weight:600;">{{ $prod['price_range'] ?? '-' }}</td>
                            <td>
                                @php $cp = strtolower($prod['conversion_potential'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $cp === 'high' ? 'aith-e-tag-high' : ($cp === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $prod['conversion_potential'] ?? '-' }}</span>
                            </td>
                            <td>{{ $prod['reasoning'] ?? '' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Monetization Strategies --}}
            @if(!empty($result['monetization_strategies']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Monetization Strategies</div>
                <ul class="aith-e-list">
                    @foreach($result['monetization_strategies'] as $strategy)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $strategy }}</li>
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
        @include('appaitools::livewire.enterprise._enterprise-history')
    </div>
</div>
