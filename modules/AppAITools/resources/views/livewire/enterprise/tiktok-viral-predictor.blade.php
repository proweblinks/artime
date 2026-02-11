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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#a855f7,#7c3aed);">
                    <i class="fa-light fa-crystal-ball" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Viral Content Predictor</h2>
                    <p>Predict viral potential of your content before posting</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">Content Description</label>
                <textarea wire:model="contentDescription" class="aith-input" rows="3"
                          placeholder="Describe your video concept in detail..."></textarea>
                @error('contentDescription') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Niche (optional)</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. fitness, cooking, comedy">
                @error('niche') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Follower Count (optional)</label>
                <input type="text" wire:model="followerCount" class="aith-input"
                       placeholder="e.g. 10K, 50K, 1M">
                @error('followerCount') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-crystal-ball"></i> Predict Viral Potential
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
                <div class="aith-e-loading-title">Predicting viral potential...</div>
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
                <span class="aith-e-result-title">Viral Prediction Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-tiktok-viral-predictor', 'Viral-Prediction')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-tiktok-viral-predictor">

            {{-- Score --}}
            @php $score = $result['viral_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Viral Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) High viral potential - strong chance of blowing up
                        @elseif($score >= 50) Moderate viral potential - optimize to increase reach
                        @else Low viral potential - significant changes recommended
                        @endif
                    </div>
                </div>
            </div>

            {{-- Prediction Summary --}}
            @if(isset($result['prediction']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-mixed"></i> Prediction Summary</div>
                <div class="aith-e-grid-3">
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Estimated Views</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['prediction']['estimated_views'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Confidence</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;">{{ $result['prediction']['confidence'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Viral Probability</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['prediction']['viral_probability'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Viral Signals --}}
            @if(!empty($result['viral_signals']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-signal-bars"></i> Viral Signals</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Signal</th><th>Score</th><th>Analysis</th></tr></thead>
                        <tbody>
                        @foreach($result['viral_signals'] as $signal)
                        <tr>
                            <td style="font-weight:600;color:#c4b5fd;">{{ $signal['signal'] ?? '' }}</td>
                            <td>
                                @php $ss = intval($signal['score'] ?? 0); @endphp
                                <span class="aith-e-tag {{ $ss >= 80 ? 'aith-e-tag-high' : ($ss >= 50 ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $ss }}/100</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $signal['analysis'] ?? '' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Strengths --}}
            @if(!empty($result['strengths']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-check-circle" style="color:#22c55e;"></i> Strengths</div>
                <ul class="aith-e-list">
                    @foreach($result['strengths'] as $strength)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;color:#22c55e;"></i></span> {{ $strength }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Risks --}}
            @if(!empty($result['risks']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-triangle-exclamation" style="color:#f97316;"></i> Risks</div>
                <ul class="aith-e-list">
                    @foreach($result['risks'] as $risk)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;color:#f97316;"></i></span> {{ $risk }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Optimization Suggestions --}}
            @if(!empty($result['optimization_suggestions']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-wand-magic-sparkles"></i> Optimization Suggestions</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Area</th><th>Current</th><th>Suggested</th><th>Impact</th></tr></thead>
                        <tbody>
                        @foreach($result['optimization_suggestions'] as $suggestion)
                        <tr>
                            <td style="font-weight:600;color:#c4b5fd;">{{ $suggestion['area'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $suggestion['current'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.7);">{{ $suggestion['suggested'] ?? '' }}</td>
                            <td>
                                @php $impact = strtolower($suggestion['impact'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $impact === 'high' ? 'aith-e-tag-high' : ($impact === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $suggestion['impact'] ?? '-' }}</span>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Similar Viral Content --}}
            @if(!empty($result['similar_viral_content']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-fire"></i> Similar Viral Content</div>
                <div class="aith-e-grid-2">
                @foreach($result['similar_viral_content'] as $content)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="font-weight:600;color:#fff;font-size:0.875rem;margin-bottom:0.375rem;">{{ $content['description'] ?? '' }}</div>
                    @if(isset($content['views']))
                    <div style="font-size:0.875rem;color:#a855f7;font-weight:600;margin-bottom:0.375rem;">
                        <i class="fa-light fa-eye" style="font-size:0.75rem;"></i> {{ $content['views'] }}
                    </div>
                    @endif
                    @if(isset($content['why_viral']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $content['why_viral'] }}</div>
                    @endif
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
