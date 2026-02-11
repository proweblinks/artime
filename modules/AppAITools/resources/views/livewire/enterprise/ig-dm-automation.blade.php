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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    <i class="fa-light fa-message-bot" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>DM Automation Strategist</h2>
                    <p>Design keyword-triggered DM funnels for lead gen</p>
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
                <label class="aith-label">Product Type (optional)</label>
                <input type="text" wire:model="productType" class="aith-input"
                       placeholder="e.g. course, coaching, digital product, SaaS">
                @error('productType') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Audience Size (optional)</label>
                <input type="text" wire:model="audienceSize" class="aith-input"
                       placeholder="e.g. 5K, 50K, 500K">
                @error('audienceSize') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-message-bot"></i> Build DM Strategy
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
                <div class="aith-e-loading-title">Building DM automation strategy...</div>
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
                <span class="aith-e-result-title">DM Automation Strategy Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-ig-dm-automation', 'DM-Automation-Strategy')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-ig-dm-automation">

            {{-- Score --}}
            @php $score = $result['automation_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Automation Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent automation potential
                        @elseif($score >= 50) Good automation opportunity
                        @else Limited automation potential
                        @endif
                    </div>
                </div>
            </div>

            {{-- Keyword Triggers --}}
            @if(!empty($result['keyword_triggers']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-keyboard"></i> Keyword Triggers</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Keyword</th><th>Response Type</th><th>Message Template</th><th>Conversion Goal</th></tr></thead>
                        <tbody>
                        @foreach($result['keyword_triggers'] as $trigger)
                        <tr>
                            <td style="font-weight:600;color:#8b5cf6;">{{ $trigger['keyword'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $trigger['response_type'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.7);">{{ $trigger['message_template'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $trigger['conversion_goal'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Funnel Blueprints --}}
            @if(!empty($result['funnel_blueprints']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-diagram-project"></i> Funnel Blueprints</div>
                @foreach($result['funnel_blueprints'] as $funnel)
                <div class="aith-e-section-card" style="margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $funnel['name'] ?? '' }}</span>
                        @if(isset($funnel['trigger']))
                        <span class="aith-e-tag aith-e-tag-medium">{{ $funnel['trigger'] }}</span>
                        @endif
                    </div>
                    @if(!empty($funnel['steps']))
                    <div style="overflow-x:auto;margin-bottom:0.5rem;">
                        <table class="aith-e-table">
                            <thead><tr><th>Step</th><th>Action</th><th>Delay</th><th>Message</th></tr></thead>
                            <tbody>
                            @foreach($funnel['steps'] as $fStep)
                            <tr>
                                <td style="font-weight:600;color:#8b5cf6;">{{ $fStep['step'] ?? '' }}</td>
                                <td style="font-size:0.8rem;color:rgba(255,255,255,0.7);">{{ $fStep['action'] ?? '-' }}</td>
                                <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $fStep['delay'] ?? '-' }}</td>
                                <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $fStep['message'] ?? '-' }}</td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                    @if(isset($funnel['expected_conversion']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">
                        <strong style="color:rgba(255,255,255,0.6);">Expected Conversion:</strong>
                        <span style="color:#22c55e;font-weight:600;">{{ $funnel['expected_conversion'] }}</span>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Compliance --}}
            @if(isset($result['compliance']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-shield-check"></i> Compliance</div>
                @if(!empty($result['compliance']['rules']))
                <div style="margin-bottom:0.75rem;">
                    <span style="font-size:0.75rem;color:rgba(255,255,255,0.4);font-weight:600;text-transform:uppercase;display:block;margin-bottom:0.375rem;">Rules</span>
                    <ul class="aith-e-list">
                        @foreach($result['compliance']['rules'] as $rule)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $rule }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if(!empty($result['compliance']['best_practices']))
                <div style="margin-bottom:0.75rem;">
                    <span style="font-size:0.75rem;color:rgba(255,255,255,0.4);font-weight:600;text-transform:uppercase;display:block;margin-bottom:0.375rem;">Best Practices</span>
                    <ul class="aith-e-list">
                        @foreach($result['compliance']['best_practices'] as $practice)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $practice }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if(!empty($result['compliance']['risks']))
                <div>
                    <span style="font-size:0.75rem;color:rgba(255,255,255,0.4);font-weight:600;text-transform:uppercase;display:block;margin-bottom:0.375rem;">Risks</span>
                    <ul class="aith-e-list">
                        @foreach($result['compliance']['risks'] as $risk)
                        <li style="color:#fbbf24;"><span class="bullet"><i class="fa-solid fa-triangle-exclamation" style="font-size:0.35rem;color:#fbbf24;"></i></span> {{ $risk }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            @endif

            {{-- Revenue Estimate --}}
            @if(isset($result['revenue_estimate']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-line"></i> Revenue Estimate</div>
                <div class="aith-e-grid-3">
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Leads per Month</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;">{{ $result['revenue_estimate']['leads_per_month'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Conversion Rate</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['revenue_estimate']['conversion_rate'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Estimated Revenue</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['revenue_estimate']['estimated_revenue'] ?? '-' }}</div>
                    </div>
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

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.ig-dm-automation.next_steps', []);
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
