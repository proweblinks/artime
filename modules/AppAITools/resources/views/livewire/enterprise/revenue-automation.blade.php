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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#f97316,#ef4444);">
                    <i class="fa-light fa-gears" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Revenue Automation Pipeline</h2>
                    <p>Build automated revenue systems for your channel</p>
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
            <button wire:click="analyze" wire:loading.attr="disabled" wire:target="analyze" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-gears"></i> Build Pipeline
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
                <div class="aith-e-loading-title">Building revenue pipeline...</div>
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
                <span class="aith-e-result-title">Revenue Automation Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-revenue-automation', 'Revenue-Automation-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-revenue-automation">

            {{-- Revenue Summary Cards --}}
            @if(isset($result['total_automated_revenue']))
            <div class="aith-e-grid-3" style="margin-bottom:1rem;">
                <div class="aith-e-summary-card aith-e-summary-card-orange">
                    <div class="aith-e-summary-label">Current Manual</div>
                    <div class="aith-e-summary-value" style="color:#fdba74;">{{ $result['channel_analysis']['current_automation_level'] ?? 'Low' }}</div>
                    <div class="aith-e-summary-sub">Automation level</div>
                </div>
                <div class="aith-e-summary-card aith-e-summary-card-green">
                    <div class="aith-e-summary-label">After Automation</div>
                    <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['total_automated_revenue'] }}</div>
                    <div class="aith-e-summary-sub">Monthly potential</div>
                </div>
                <div class="aith-e-summary-card aith-e-summary-card-blue">
                    <div class="aith-e-summary-label">Time Saved</div>
                    <div class="aith-e-summary-value" style="color:#93c5fd;">{{ count($result['automation_workflows'] ?? []) }}</div>
                    <div class="aith-e-summary-sub">Automated workflows</div>
                </div>
            </div>
            @endif

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
                        @elseif($score >= 50) Good automation opportunities available
                        @else Limited automation options - consider simplifying your workflow
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
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ is_array($val) ? implode(', ', $val) : $val }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Revenue Streams --}}
            @if(!empty($result['revenue_streams']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-money-bill-trend-up"></i> Revenue Streams</div>
                @foreach($result['revenue_streams'] as $stream)
                <div style="padding:0.75rem;border-radius:0.5rem;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $stream['stream'] ?? $stream['name'] ?? '' }}</span>
                        @php $al = strtolower($stream['automation_level'] ?? ''); @endphp
                        <span class="aith-e-tag {{ $al === 'full' ? 'aith-e-tag-high' : ($al === 'partial' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $stream['automation_level'] ?? '' }}</span>
                    </div>
                    <div style="display:flex;gap:1rem;font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.5rem;">
                        <span><strong style="color:rgba(255,255,255,0.6);">Monthly Potential:</strong> {{ $stream['monthly_potential'] ?? '-' }}</span>
                        <span><strong style="color:rgba(255,255,255,0.6);">Setup Time:</strong> {{ $stream['setup_time'] ?? '-' }}</span>
                    </div>
                    @if(!empty($stream['tools_needed']))
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-top:0.375rem;">
                        @foreach((is_array($stream['tools_needed']) ? $stream['tools_needed'] : [$stream['tools_needed']]) as $tool)
                        <span class="aith-e-tag" style="background:rgba(139,92,246,0.15);color:#c4b5fd;">{{ $tool }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Tool Stack --}}
            @if(!empty($result['tool_stack']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-toolbox"></i> Tool Stack</div>
                <div class="aith-e-grid-2">
                @foreach($result['tool_stack'] as $tool)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.875rem;">{{ $tool['tool'] ?? '' }}</span>
                        @if(isset($tool['cost']))
                        <span style="color:#22c55e;font-weight:600;font-size:0.8rem;">{{ $tool['cost'] }}</span>
                        @endif
                    </div>
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.375rem;">{{ $tool['purpose'] ?? '' }}</div>
                    @if(isset($tool['category']))
                    <span class="aith-e-tag" style="background:rgba(139,92,246,0.15);color:#c4b5fd;">{{ $tool['category'] }}</span>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Automation Workflows --}}
            @if(!empty($result['automation_workflows']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-diagram-project"></i> Automation Workflows</div>
                @foreach($result['automation_workflows'] as $workflow)
                <div style="padding:0.75rem;border-radius:0.5rem;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $workflow['workflow'] ?? $workflow['name'] ?? '' }}</span>
                        @if(isset($workflow['revenue_impact']))
                        <span class="aith-e-pill aith-e-pill-green" style="padding:0.2rem 0.5rem;font-size:0.7rem;">{{ $workflow['revenue_impact'] }}</span>
                        @endif
                    </div>
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.375rem;">
                        <strong style="color:rgba(255,255,255,0.6);">Trigger:</strong> {{ $workflow['trigger'] ?? '-' }}
                    </div>
                    @if(!empty($workflow['actions']))
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.375rem;">
                        <strong style="color:rgba(255,255,255,0.6);">Actions:</strong>
                        <ul style="list-style:none;padding:0;margin:0.25rem 0 0;">
                            @foreach($workflow['actions'] as $action)
                            <li style="padding:0.125rem 0;padding-left:0.75rem;position:relative;">
                                <span style="position:absolute;left:0;color:#7c3aed;">&#8226;</span> {{ $action }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Implementation Timeline --}}
            @if(!empty($result['implementation_timeline']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-timeline"></i> Implementation Timeline</div>
                @foreach($result['implementation_timeline'] as $phaseIdx => $phase)
                <div style="display:flex;gap:0.75rem;padding:0.75rem;border-radius:0.5rem;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);margin-bottom:0.75rem;">
                    <span class="aith-e-step-badge">{{ $phaseIdx + 1 }}</span>
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.375rem;">
                            <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $phase['phase'] ?? '' }}</span>
                            <span style="font-size:0.75rem;color:rgba(255,255,255,0.4);">{{ $phase['duration'] ?? '' }}</span>
                        </div>
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.375rem;">
                            <strong style="color:rgba(255,255,255,0.6);">Milestone:</strong> {{ $phase['milestone'] ?? '-' }}
                        </div>
                        @if(!empty($phase['tasks']))
                        <ul style="list-style:none;padding:0;margin:0.25rem 0 0;">
                            @foreach($phase['tasks'] as $task)
                            <li style="font-size:0.75rem;color:rgba(255,255,255,0.4);padding:0.125rem 0;padding-left:0.75rem;position:relative;">
                                <span style="position:absolute;left:0;color:#7c3aed;">&#8226;</span> {{ $task }}
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
                @endforeach
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
