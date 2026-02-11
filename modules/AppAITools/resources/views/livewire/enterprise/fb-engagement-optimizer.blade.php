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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#a855f7,#d946ef);">
                    <i class="fa-light fa-heart-pulse" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Engagement Rate Optimizer</h2>
                    <p>Optimize content for maximum Facebook engagement</p>
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
                <label class="aith-label">Average Engagement (optional)</label>
                <input type="text" wire:model="avgEngagement" class="aith-input"
                       placeholder="e.g. 3%, 5%, 10%">
                @error('avgEngagement') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Content Type (optional)</label>
                <input type="text" wire:model="contentType" class="aith-input"
                       placeholder="e.g. videos, images, links, text posts">
                @error('contentType') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-heart-pulse"></i> Analyze Engagement
                </span>
                <span wire:loading wire:target="analyze">
                    <i class="fa-light fa-spinner-third fa-spin"></i> Analyzing...
                </span>
                <span style="margin-left:0.5rem;opacity:0.6;font-size:0.8rem;">2 credits</span>
            </button>
            @endif

            @if($isLoading)
            {{-- Loading Steps --}}
            <div class="aith-e-loading" x-data="{ step: 0 }" x-init="
                let steps = {{ count($loadingSteps) }};
                let interval = setInterval(() => { if(step < steps - 1) step++; }, 2500);
                $wire.on('loadingComplete', () => clearInterval(interval));
            ">
                <div class="aith-e-loading-title">Analyzing engagement patterns...</div>
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
                <span class="aith-e-result-title">Engagement Optimization</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-fb-engagement-optimizer', 'Engagement-Optimization')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-fb-engagement-optimizer">

            {{-- Score --}}
            @php $score = $result['engagement_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Engagement Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent engagement rate - your content resonates strongly
                        @elseif($score >= 50) Good engagement with room for optimization
                        @else Below average engagement - significant improvements needed
                        @endif
                    </div>
                </div>
            </div>

            {{-- Current Assessment --}}
            @if(isset($result['current_assessment']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-simple"></i> Current Assessment</div>
                <div class="aith-e-grid-2">
                    @if(isset($result['current_assessment']['avg_engagement']))
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Average Engagement</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;font-size:1.25rem;">{{ $result['current_assessment']['avg_engagement'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['current_assessment']['reaction_breakdown']))
                    <div class="aith-e-section-card" style="margin-bottom:0;">
                        <div style="font-weight:600;color:#fff;font-size:0.85rem;margin-bottom:0.5rem;">Reaction Breakdown</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.375rem;">
                            @if(isset($result['current_assessment']['reaction_breakdown']['likes']))
                            <div>
                                <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Likes</span>
                                <div style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $result['current_assessment']['reaction_breakdown']['likes'] }}</div>
                            </div>
                            @endif
                            @if(isset($result['current_assessment']['reaction_breakdown']['comments']))
                            <div>
                                <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Comments</span>
                                <div style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $result['current_assessment']['reaction_breakdown']['comments'] }}</div>
                            </div>
                            @endif
                            @if(isset($result['current_assessment']['reaction_breakdown']['shares']))
                            <div>
                                <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Shares</span>
                                <div style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $result['current_assessment']['reaction_breakdown']['shares'] }}</div>
                            </div>
                            @endif
                            @if(isset($result['current_assessment']['reaction_breakdown']['saves']))
                            <div>
                                <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Saves</span>
                                <div style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $result['current_assessment']['reaction_breakdown']['saves'] }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                <div class="aith-e-grid-2" style="margin-top:0.5rem;">
                    @if(isset($result['current_assessment']['best_format']))
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Best Format</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['current_assessment']['best_format'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['current_assessment']['worst_format']))
                    <div class="aith-e-summary-card" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.15);">
                        <div class="aith-e-summary-label">Worst Format</div>
                        <div class="aith-e-summary-value" style="color:#fca5a5;">{{ $result['current_assessment']['worst_format'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Algorithm Alignment --}}
            @if(isset($result['algorithm_alignment']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-microchip-ai"></i> Algorithm Alignment</div>
                @if(isset($result['algorithm_alignment']['distribution_score']))
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Distribution Score</span>
                    <span style="font-size:1.1rem;font-weight:700;color:#a855f7;">{{ $result['algorithm_alignment']['distribution_score'] }}</span>
                </div>
                @endif
                @if(!empty($result['algorithm_alignment']['ranking_signals']))
                <div style="margin-bottom:0.5rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;">Ranking Signals</span>
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($result['algorithm_alignment']['ranking_signals'] as $signal)
                        <span class="aith-e-pill aith-e-pill-green">{{ $signal }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(!empty($result['algorithm_alignment']['penalties']))
                <div style="margin-bottom:0.5rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;">Penalties</span>
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($result['algorithm_alignment']['penalties'] as $penalty)
                        <span style="display:inline-flex;align-items:center;padding:0.125rem 0.5rem;border-radius:9999px;font-size:0.75rem;background:rgba(239,68,68,0.1);color:#fca5a5;">{{ $penalty }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(!empty($result['algorithm_alignment']['boost_factors']))
                <div>
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;">Boost Factors</span>
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($result['algorithm_alignment']['boost_factors'] as $factor)
                        <span style="display:inline-flex;align-items:center;padding:0.125rem 0.5rem;border-radius:9999px;font-size:0.75rem;background:rgba(59,130,246,0.1);color:#93c5fd;">{{ $factor }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif

            {{-- Engagement Triggers --}}
            @if(!empty($result['engagement_triggers']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-bolt"></i> Engagement Triggers</div>
                <div class="aith-e-grid-2">
                @foreach($result['engagement_triggers'] as $trigger)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $trigger['title'] ?? '' }}</span>
                        @if(isset($trigger['type']))
                        <span class="aith-e-tag aith-e-tag-medium">{{ $trigger['type'] }}</span>
                        @endif
                    </div>
                    @if(isset($trigger['implementation']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);margin-bottom:0.375rem;">
                        <strong style="color:rgba(255,255,255,0.7);">Implementation:</strong> {{ $trigger['implementation'] }}
                    </div>
                    @endif
                    @if(isset($trigger['expected_lift']))
                    <div style="display:flex;align-items:center;gap:0.375rem;margin-bottom:0.25rem;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Expected Lift</span>
                        <span style="font-size:0.875rem;font-weight:600;color:#22c55e;">{{ $trigger['expected_lift'] }}</span>
                    </div>
                    @endif
                    @if(isset($trigger['example']))
                    <div style="font-size:0.8rem;color:#d8b4fe;">
                        <strong style="color:rgba(255,255,255,0.6);">Example:</strong> {{ $trigger['example'] }}
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Content Optimization --}}
            @if(!empty($result['content_optimization']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-sliders"></i> Content Optimization</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Format</th><th>Current Engagement</th><th>Optimized Engagement</th><th>Changes</th><th>Priority</th></tr></thead>
                        <tbody>
                        @foreach($result['content_optimization'] as $opt)
                        <tr>
                            <td style="font-weight:600;color:#c084fc;">{{ $opt['format'] ?? '' }}</td>
                            <td style="color:rgba(255,255,255,0.5);">{{ $opt['current_engagement'] ?? '-' }}</td>
                            <td style="font-weight:600;color:#22c55e;">{{ $opt['optimized_engagement'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">
                                @if(is_array($opt['changes'] ?? null))
                                <ul style="margin:0;padding:0;list-style:none;">
                                    @foreach($opt['changes'] as $change)
                                    <li style="padding:0.125rem 0;"><i class="fa-solid fa-circle" style="font-size:0.25rem;vertical-align:middle;margin-right:0.25rem;"></i>{{ $change }}</li>
                                    @endforeach
                                </ul>
                                @else
                                {{ $opt['changes'] ?? '-' }}
                                @endif
                            </td>
                            <td>
                                @php $priority = strtolower($opt['priority'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $priority === 'high' ? 'aith-e-tag-high' : ($priority === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $opt['priority'] ?? '-' }}</span>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Action Plan --}}
            @if(!empty($result['action_plan']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-list-check"></i> Action Plan</div>
                @foreach($result['action_plan'] as $week)
                <div class="aith-e-section-card" style="margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="font-weight:600;color:#c084fc;font-size:0.9rem;">{{ $week['week'] ?? '' }}</span>
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            @if(isset($week['focus_format']))
                            <span class="aith-e-tag aith-e-tag-medium">{{ $week['focus_format'] }}</span>
                            @endif
                            @if(isset($week['target_engagement']))
                            <span style="font-size:0.8rem;color:#22c55e;font-weight:600;">Target: {{ $week['target_engagement'] }}</span>
                            @endif
                        </div>
                    </div>
                    @if(!empty($week['actions']))
                    <ul class="aith-e-list">
                        @foreach($week['actions'] as $action)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $action }}</li>
                        @endforeach
                    </ul>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.fb-engagement-optimizer.next_steps', []);
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
