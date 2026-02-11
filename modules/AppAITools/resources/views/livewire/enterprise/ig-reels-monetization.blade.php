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
                    <i class="fa-light fa-money-bill-trend-up" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Reels Monetization Analyzer</h2>
                    <p>Analyze and optimize Reels for maximum revenue</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">Instagram Profile</label>
                <input type="text" wire:model="profile" class="aith-input"
                       placeholder="@username">
                @error('profile') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Average Views (optional)</label>
                <input type="text" wire:model="avgViews" class="aith-input"
                       placeholder="e.g. 10K, 50K, 100K">
                @error('avgViews') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Follower Count (optional)</label>
                <input type="text" wire:model="followerCount" class="aith-input"
                       placeholder="e.g. 50K, 500K, 1M">
                @error('followerCount') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            @include('appaitools::livewire.enterprise._youtube-connect', ['youtubeField' => 'youtubeChannel'])
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-money-bill-trend-up"></i> Analyze Monetization
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
                <div class="aith-e-loading-title">Analyzing Reels monetization...</div>
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
                <span class="aith-e-result-title">Reels Monetization Analysis</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-ig-reels-monetization', 'Reels-Monetization-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-ig-reels-monetization">

            {{-- Score --}}
            @php $score = $result['reels_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Reels Monetization</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent monetization potential - maximize your Reels revenue
                        @elseif($score >= 50) Good earning potential - optimization can boost payouts significantly
                        @else Below average earnings - focus on growing views and engagement first
                        @endif
                    </div>
                </div>
            </div>

            {{-- Earnings Estimate --}}
            @if(isset($result['earnings_estimate']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-money-bill-trend-up"></i> Earnings Estimate</div>
                <div class="aith-e-grid-3">
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Daily</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['earnings_estimate']['daily'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Weekly</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['earnings_estimate']['weekly'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Monthly</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['earnings_estimate']['monthly'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Monetization Paths --}}
            @if(!empty($result['monetization_paths']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-route"></i> Monetization Paths</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Source</th><th>Est. Monthly</th><th>Eligibility</th><th>Action</th></tr></thead>
                        <tbody>
                        @foreach($result['monetization_paths'] as $path)
                        <tr>
                            <td style="font-weight:600;color:#86efac;">{{ $path['source'] ?? '' }}</td>
                            <td style="font-weight:600;color:#22c55e;">{{ $path['estimated_monthly'] ?? '-' }}</td>
                            <td>
                                @php $elig = strtolower($path['eligibility'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $elig === 'eligible' ? 'aith-e-tag-high' : ($elig === 'partial' || $elig === 'near' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $path['eligibility'] ?? '-' }}</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $path['action'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Content Optimization --}}
            @if(!empty($result['content_optimization']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-sliders"></i> Content Optimization</div>
                @foreach($result['content_optimization'] as $opt)
                <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div style="flex-shrink:0;">
                        @php $impact = strtolower($opt['impact'] ?? ''); @endphp
                        <span class="aith-e-tag {{ $impact === 'high' ? 'aith-e-tag-high' : ($impact === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $opt['impact'] ?? '' }}</span>
                    </div>
                    <div style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $opt['tip'] ?? $opt['suggestion'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Benchmark Comparison --}}
            @if(!empty($result['benchmark_comparison']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-scale-balanced"></i> Benchmark Comparison</div>
                <div class="aith-e-grid-2">
                    @foreach($result['benchmark_comparison'] as $bench)
                    <div class="aith-e-section-card" style="margin-bottom:0;">
                        <div style="font-weight:600;color:#fff;font-size:0.875rem;margin-bottom:0.375rem;">{{ $bench['metric'] ?? $bench['label'] ?? '' }}</div>
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <div>
                                <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Your Value</span>
                                <div style="font-size:0.875rem;color:#86efac;font-weight:600;">{{ $bench['your_value'] ?? '-' }}</div>
                            </div>
                            <div>
                                <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Benchmark</span>
                                <div style="font-size:0.875rem;color:rgba(255,255,255,0.5);">{{ $bench['benchmark'] ?? $bench['average'] ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Growth Milestones --}}
            @if(!empty($result['growth_milestones']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-flag-checkered"></i> Growth Milestones</div>
                @foreach($result['growth_milestones'] as $milestone)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:0.625rem 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div>
                        <span style="font-weight:600;color:#86efac;font-size:0.875rem;">{{ $milestone['followers'] ?? '' }}</span>
                        @if(isset($milestone['estimated_monthly']))
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-top:0.125rem;">Est. {{ $milestone['estimated_monthly'] }}/mo</div>
                        @endif
                    </div>
                    @if(isset($milestone['unlock']))
                    <span style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $milestone['unlock'] }}</span>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.ig-reels-monetization.next_steps', []);
                $allTools = config('appaitools.enterprise_tools', []);
            @endphp
            @if(!empty($nextSteps))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-arrow-right"></i> What's Next?</div>
                <div class="aith-e-grid-2">
                    @foreach($nextSteps as $ns)
                    @php $nsTool = $allTools[$ns['tool']] ?? null; @endphp
                    @if($nsTool)
                    <a href="{{ route($nsTool['route']) }}" class="aith-e-section-card" style="margin-bottom:0;text-decoration:none;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.borderColor='rgba(139,92,246,0.3)'" onmouseout="this.style.borderColor=''">
                        <div style="font-weight:600;color:#c4b5fd;font-size:0.875rem;margin-bottom:0.25rem;">{{ $nsTool['name'] }}</div>
                        <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);">{{ $ns['reason'] }}</div>
                    </a>
                    @endif
                    @endforeach
                </div>
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
