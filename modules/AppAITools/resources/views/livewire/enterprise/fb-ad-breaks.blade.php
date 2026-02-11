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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#f59e0b,#ea580c);">
                    <i class="fa-light fa-rectangle-ad" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Ad Break Optimizer</h2>
                    <p>Optimize in-stream ad placement and earnings</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">Facebook Page URL</label>
                <input type="text" wire:model="pageUrl" class="aith-input"
                       placeholder="https://facebook.com/yourpage">
                @error('pageUrl') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Content Type (optional)</label>
                <input type="text" wire:model="contentType" class="aith-input"
                       placeholder="e.g. tutorials, vlogs, entertainment, news">
                @error('contentType') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Average Video Length (optional)</label>
                <input type="text" wire:model="avgVideoLength" class="aith-input"
                       placeholder="e.g. 5 min, 10 min, 15 min">
                @error('avgVideoLength') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-rectangle-ad"></i> Analyze Ad Breaks
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
                <div class="aith-e-loading-title">Analyzing ad break potential...</div>
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
                <span class="aith-e-result-title">Ad Break Analysis</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-fb-ad-breaks', 'Ad-Break-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-fb-ad-breaks">

            {{-- Score --}}
            @php $score = $result['ad_break_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Ad Break Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent ad break optimization - maximize your in-stream revenue
                        @elseif($score >= 50) Good ad break potential - optimization can boost earnings significantly
                        @else Significant ad break optimization needed - focus on eligibility and placement
                        @endif
                    </div>
                </div>
            </div>

            {{-- Eligibility Status --}}
            @if(isset($result['eligibility_status']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-shield-check"></i> Eligibility Status</div>
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
                    @php $eligible = strtolower($result['eligibility_status']['status'] ?? ''); @endphp
                    <span style="font-size:0.85rem;padding:0.25rem 0.75rem;border-radius:9999px;font-weight:600;{{ $eligible === 'eligible' ? 'background:rgba(34,197,94,0.15);color:#86efac;' : 'background:rgba(239,68,68,0.15);color:#fca5a5;' }}">
                        <i class="fa-solid {{ $eligible === 'eligible' ? 'fa-circle-check' : 'fa-circle-xmark' }}" style="margin-right:0.25rem;"></i>
                        {{ $result['eligibility_status']['status'] ?? 'Unknown' }}
                    </span>
                    @if(isset($result['eligibility_status']['followers_needed']))
                    <span style="font-size:0.8rem;color:rgba(255,255,255,0.5);">Followers needed: <strong style="color:#fbbf24;">{{ $result['eligibility_status']['followers_needed'] }}</strong></span>
                    @endif
                    @if(isset($result['eligibility_status']['watch_hours_needed']))
                    <span style="font-size:0.8rem;color:rgba(255,255,255,0.5);">Watch hours needed: <strong style="color:#fbbf24;">{{ $result['eligibility_status']['watch_hours_needed'] }}</strong></span>
                    @endif
                </div>
                @if(!empty($result['eligibility_status']['requirements_met']))
                <div style="margin-bottom:0.5rem;">
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.25rem;font-weight:600;">Requirements Met</div>
                    @foreach($result['eligibility_status']['requirements_met'] as $req)
                    <div style="display:flex;align-items:center;gap:0.5rem;padding:0.25rem 0;font-size:0.85rem;color:#86efac;">
                        <i class="fa-solid fa-check" style="font-size:0.7rem;"></i> {{ $req }}
                    </div>
                    @endforeach
                </div>
                @endif
                @if(!empty($result['eligibility_status']['requirements_missing']))
                <div>
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.25rem;font-weight:600;">Requirements Missing</div>
                    @foreach($result['eligibility_status']['requirements_missing'] as $req)
                    <div style="display:flex;align-items:center;gap:0.5rem;padding:0.25rem 0;font-size:0.85rem;color:#fca5a5;">
                        <i class="fa-solid fa-xmark" style="font-size:0.7rem;"></i> {{ $req }}
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

            {{-- Placement Timing --}}
            @if(!empty($result['placement_timing']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-clock"></i> Placement Timing</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Position</th><th>Optimal Timestamp</th><th>Retention Impact</th><th>Revenue Impact</th><th>Recommendation</th></tr></thead>
                        <tbody>
                        @foreach($result['placement_timing'] as $placement)
                        <tr>
                            <td style="font-weight:600;color:#fbbf24;">{{ $placement['position'] ?? '' }}</td>
                            <td style="font-size:0.85rem;color:#fff;">{{ $placement['optimal_timestamp'] ?? '-' }}</td>
                            <td>
                                @php $retention = strtolower($placement['retention_impact'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $retention === 'low' ? 'aith-e-tag-high' : ($retention === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $placement['retention_impact'] ?? '-' }}</span>
                            </td>
                            <td>
                                @php $revenue = strtolower($placement['revenue_impact'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $revenue === 'high' ? 'aith-e-tag-high' : ($revenue === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $placement['revenue_impact'] ?? '-' }}</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $placement['recommendation'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Revenue Estimates --}}
            @if(isset($result['revenue_estimates']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-money-bill-trend-up"></i> Revenue Estimates</div>
                <div class="aith-e-grid-3">
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Per 1,000 Views</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['revenue_estimates']['per_1000_views'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Daily</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['revenue_estimates']['daily'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Weekly</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['revenue_estimates']['weekly'] ?? '-' }}</div>
                    </div>
                </div>
                <div class="aith-e-grid-3" style="margin-top:0.5rem;">
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Monthly</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;">{{ $result['revenue_estimates']['monthly'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Yearly</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['revenue_estimates']['yearly'] ?? '-' }}</div>
                    </div>
                    @if(isset($result['revenue_estimates']['compared_to_youtube']))
                    <div class="aith-e-summary-card aith-e-summary-card-orange">
                        <div class="aith-e-summary-label">vs YouTube</div>
                        <div class="aith-e-summary-value" style="color:#fdba74;font-size:0.8rem;">{{ $result['revenue_estimates']['compared_to_youtube'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Content Format Analysis --}}
            @if(!empty($result['content_format_analysis']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-video"></i> Content Format Analysis</div>
                <div class="aith-e-grid-2">
                @foreach($result['content_format_analysis'] as $format)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#fbbf24;font-size:0.9rem;">{{ $format['format'] ?? '' }}</span>
                        @if(isset($format['ad_break_friendly']))
                        @php $friendly = strtolower($format['ad_break_friendly'] ?? ''); @endphp
                        <span style="font-size:0.7rem;padding:0.125rem 0.375rem;border-radius:0.25rem;{{ $friendly === 'yes' || $friendly === 'high' ? 'background:rgba(34,197,94,0.1);color:#86efac;' : ($friendly === 'moderate' || $friendly === 'medium' ? 'background:rgba(245,158,11,0.1);color:#fbbf24;' : 'background:rgba(239,68,68,0.1);color:#fca5a5;') }}">{{ $format['ad_break_friendly'] }}</span>
                        @endif
                    </div>
                    @if(isset($format['optimal_length']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);margin-bottom:0.25rem;">
                        <strong style="color:rgba(255,255,255,0.7);">Optimal Length:</strong> {{ $format['optimal_length'] }}
                    </div>
                    @endif
                    @if(isset($format['placement_count']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.25rem;">
                        <strong style="color:rgba(255,255,255,0.6);">Placements:</strong> {{ $format['placement_count'] }}
                    </div>
                    @endif
                    @if(!empty($format['tips']))
                    <div style="margin-top:0.375rem;">
                        @foreach($format['tips'] as $tip)
                        <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);padding:0.125rem 0;">
                            <i class="fa-solid fa-circle" style="font-size:0.25rem;margin-right:0.375rem;vertical-align:middle;"></i>{{ $tip }}
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Optimization Tips --}}
            @if(!empty($result['optimization_tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Optimization Tips</div>
                @foreach($result['optimization_tips'] as $tip)
                <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div style="flex:1;font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $tip['tip'] ?? (is_string($tip) ? $tip : '') }}</div>
                    @if(isset($tip['impact']))
                    <div style="flex-shrink:0;">
                        @php $impact = strtolower($tip['impact'] ?? ''); @endphp
                        <span class="aith-e-tag {{ $impact === 'high' ? 'aith-e-tag-high' : ($impact === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $tip['impact'] }}</span>
                    </div>
                    @endif
                    @if(isset($tip['effort']))
                    <div style="flex-shrink:0;">
                        @php $effort = strtolower($tip['effort'] ?? ''); @endphp
                        <span class="aith-e-tag {{ $effort === 'low' ? 'aith-e-tag-high' : ($effort === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $tip['effort'] }} effort</span>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.fb-ad-breaks.next_steps', []);
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
