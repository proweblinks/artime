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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#14b8a6,#06b6d4);">
                    <i class="fa-light fa-clone" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Multi-Income Converter</h2>
                    <p>Turn one video into multiple revenue streams</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">YouTube Video URL</label>
                <input type="url" wire:model="url" class="aith-input"
                       placeholder="https://youtube.com/watch?v=...">
            </div>
            <button wire:click="analyze" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <i class="fa-light fa-clone"></i> Convert to Multi-Income
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
                <div class="aith-e-loading-title">Converting to multi-income...</div>
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
                <span class="aith-e-result-title">Multi-Income Analysis Results</span>
                <button wire:click="$set('result', null)" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                    <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                </button>
            </div>

            {{-- Score --}}
            @php $score = $result['multi_income_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Multi-Income Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent multi-income potential
                        @elseif($score >= 50) Good revenue diversification opportunities
                        @else Limited multi-income options - consider different content formats
                        @endif
                    </div>
                </div>
            </div>

            {{-- Video Analysis --}}
            @if(isset($result['video_analysis']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-simple"></i> Video Analysis</div>
                <div class="aith-e-grid-2">
                    @foreach($result['video_analysis'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ is_array($val) ? implode(', ', $val) : $val }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Income Streams --}}
            @if(!empty($result['income_streams']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-money-bill-trend-up"></i> Income Streams</div>
                @foreach($result['income_streams'] as $stream)
                <div style="padding:0.75rem;border-radius:0.5rem;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $stream['platform'] ?? '' }}</span>
                        <span class="aith-e-tag aith-e-tag-medium">{{ $stream['content_type'] ?? '' }}</span>
                        @php $effort = strtolower($stream['effort_level'] ?? ''); @endphp
                        <span class="aith-e-tag {{ $effort === 'low' ? 'aith-e-tag-high' : ($effort === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $stream['effort_level'] ?? '' }}</span>
                    </div>
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.5rem;">{{ $stream['description'] ?? '' }}</div>
                    <div style="display:flex;gap:1rem;font-size:0.75rem;color:rgba(255,255,255,0.4);">
                        <span><strong style="color:rgba(255,255,255,0.6);">Est. Revenue:</strong> {{ $stream['estimated_revenue'] ?? '-' }}</span>
                        <span><strong style="color:rgba(255,255,255,0.6);">Time to Create:</strong> {{ $stream['time_to_create'] ?? '-' }}</span>
                    </div>
                    @if(!empty($stream['content_draft']))
                    <pre style="white-space:pre-wrap;font-size:0.8rem;color:rgba(255,255,255,0.5);margin-top:0.5rem;">{{ $stream['content_draft'] }}</pre>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Repurposing Plan --}}
            @if(!empty($result['repurposing_plan']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-calendar-days"></i> Repurposing Plan</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Day</th><th>Action</th><th>Platform</th><th>Format</th></tr></thead>
                        <tbody>
                        @foreach($result['repurposing_plan'] as $plan)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $plan['day'] ?? '' }}</td>
                            <td>{{ $plan['action'] ?? '-' }}</td>
                            <td>{{ $plan['platform'] ?? '-' }}</td>
                            <td>{{ $plan['format'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Total Potential Revenue --}}
            @if(isset($result['total_potential_revenue']))
            <div class="aith-e-section-card">
                <div style="text-align:center;padding:1rem;">
                    <span style="font-size:1.5rem;font-weight:800;color:#86efac;">{{ $result['total_potential_revenue'] }}</span>
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);margin-top:0.25rem;">Total Potential Revenue</div>
                </div>
            </div>
            @endif

            {{-- Automation Suggestions --}}
            @if(!empty($result['automation_suggestions']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Automation Suggestions</div>
                <ul class="aith-e-list">
                    @foreach($result['automation_suggestions'] as $suggestion)
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
