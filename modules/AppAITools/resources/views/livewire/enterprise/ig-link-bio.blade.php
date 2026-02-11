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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#f43f5e,#ec4899);">
                    <i class="fa-light fa-link" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Link-in-Bio Optimizer</h2>
                    <p>Optimize your link tree for maximum conversions</p>
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
                <label class="aith-label">Current Links (optional)</label>
                <textarea wire:model="currentLinks" class="aith-input" rows="3"
                          placeholder="Paste your current link-in-bio links, one per line..."></textarea>
                @error('currentLinks') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-link"></i> Optimize Bio
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
                <div class="aith-e-loading-title">Optimizing link-in-bio...</div>
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
                <span class="aith-e-result-title">Link-in-Bio Optimization Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-ig-link-bio', 'Link-in-Bio-Optimization')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-ig-link-bio">

            {{-- Score --}}
            @php $score = $result['bio_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Bio Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent bio optimization
                        @elseif($score >= 50) Good bio with room for improvement
                        @else Significant bio optimization needed
                        @endif
                    </div>
                </div>
            </div>

            {{-- Bio Analysis --}}
            @if(isset($result['bio_analysis']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-clipboard-check"></i> Bio Analysis</div>
                @if(isset($result['bio_analysis']['current_assessment']))
                <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-bottom:0.75rem;padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;">
                    {{ $result['bio_analysis']['current_assessment'] }}
                </div>
                @endif
                @if(!empty($result['bio_analysis']['improvements']))
                <div style="margin-bottom:0.75rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;">Improvements</span>
                    <ul class="aith-e-list">
                        @foreach($result['bio_analysis']['improvements'] as $improvement)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $improvement }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <div class="aith-e-grid-2">
                    @if(isset($result['bio_analysis']['keyword_usage']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Keyword Usage</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['bio_analysis']['keyword_usage'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['bio_analysis']['cta_effectiveness']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">CTA Effectiveness</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['bio_analysis']['cta_effectiveness'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Link Structure --}}
            @if(!empty($result['link_structure']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-list-ol"></i> Link Structure</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Position</th><th>Link Type</th><th>Label</th><th>Purpose</th><th>Expected CTR</th></tr></thead>
                        <tbody>
                        @foreach($result['link_structure'] as $link)
                        <tr>
                            <td style="font-weight:600;color:#f43f5e;">{{ $link['position'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $link['link_type'] ?? '-' }}</td>
                            <td style="font-weight:600;color:#fff;">{{ $link['label'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $link['purpose'] ?? '-' }}</td>
                            <td style="font-weight:600;color:#22c55e;">{{ $link['expected_ctr'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Conversion Tips --}}
            @if(!empty($result['conversion_tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-bullseye-arrow"></i> Conversion Tips</div>
                @foreach($result['conversion_tips'] as $tip)
                <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div style="flex:1;">
                        <div style="font-size:0.85rem;color:#fff;">{{ $tip['tip'] ?? ($tip['text'] ?? '') }}</div>
                    </div>
                    @if(isset($tip['impact']))
                    @php $impact = strtolower($tip['impact']); @endphp
                    <span class="aith-e-tag {{ $impact === 'high' ? 'aith-e-tag-high' : ($impact === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $tip['impact'] }}</span>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Funnel Design --}}
            @if(isset($result['funnel_design']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-filter"></i> Funnel Design</div>
                @if(!empty($result['funnel_design']['awareness_links']))
                <div style="margin-bottom:0.75rem;">
                    <span style="font-size:0.75rem;color:rgba(255,255,255,0.4);font-weight:600;text-transform:uppercase;display:block;margin-bottom:0.375rem;">Awareness Links</span>
                    <ul class="aith-e-list">
                        @foreach($result['funnel_design']['awareness_links'] as $link)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $link }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if(!empty($result['funnel_design']['consideration_links']))
                <div style="margin-bottom:0.75rem;">
                    <span style="font-size:0.75rem;color:rgba(255,255,255,0.4);font-weight:600;text-transform:uppercase;display:block;margin-bottom:0.375rem;">Consideration Links</span>
                    <ul class="aith-e-list">
                        @foreach($result['funnel_design']['consideration_links'] as $link)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $link }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if(!empty($result['funnel_design']['conversion_links']))
                <div>
                    <span style="font-size:0.75rem;color:rgba(255,255,255,0.4);font-weight:600;text-transform:uppercase;display:block;margin-bottom:0.375rem;">Conversion Links</span>
                    <ul class="aith-e-list">
                        @foreach($result['funnel_design']['conversion_links'] as $link)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $link }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            @endif

            {{-- A/B Test Ideas --}}
            @if(!empty($result['ab_test_ideas']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-flask"></i> A/B Test Ideas</div>
                <div class="aith-e-grid-2">
                @foreach($result['ab_test_ideas'] as $test)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="font-weight:600;color:#fff;font-size:0.875rem;margin-bottom:0.5rem;">{{ $test['element'] ?? '' }}</div>
                    <div style="display:flex;gap:0.5rem;margin-bottom:0.375rem;">
                        <span style="font-size:0.75rem;padding:0.125rem 0.375rem;border-radius:0.25rem;background:rgba(244,63,94,0.1);color:#f43f5e;">A: {{ $test['variant_a'] ?? '' }}</span>
                        <span style="font-size:0.75rem;padding:0.125rem 0.375rem;border-radius:0.25rem;background:rgba(236,72,153,0.1);color:#ec4899;">B: {{ $test['variant_b'] ?? '' }}</span>
                    </div>
                    @if(isset($test['hypothesis']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">
                        <strong style="color:rgba(255,255,255,0.6);">Hypothesis:</strong> {{ $test['hypothesis'] }}
                    </div>
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

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.ig-link-bio.next_steps', []);
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
