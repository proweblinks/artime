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
                    <i class="fa-light fa-rectangle-vertical-history" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Story Engagement Planner</h2>
                    <p>Plan interactive stories that drive engagement and sales</p>
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
                <label class="aith-label">Goal (optional)</label>
                <input type="text" wire:model="goal" class="aith-input"
                       placeholder="e.g. drive sales, increase engagement, grow followers">
                @error('goal') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Industry (optional)</label>
                <input type="text" wire:model="industry" class="aith-input"
                       placeholder="e.g. beauty, fitness, tech, food">
                @error('industry') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-rectangle-vertical-history"></i> Plan Stories
                </span>
                <span wire:loading wire:target="analyze">
                    <i class="fa-light fa-spinner-third fa-spin"></i> Planning...
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
                <div class="aith-e-loading-title">Planning story engagement strategy...</div>
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
                <span class="aith-e-result-title">Story Engagement Plan</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-ig-story-planner', 'Story-Engagement-Plan')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Plan
                    </button>
                </div>
            </div>

            <div id="pdf-content-ig-story-planner">

            {{-- Score --}}
            @php $score = $result['engagement_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Engagement Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent engagement potential - your stories will drive strong interaction
                        @elseif($score >= 50) Good engagement foundation - interactive elements will boost results
                        @else Low engagement predicted - follow the plan to increase story interaction
                        @endif
                    </div>
                </div>
            </div>

            {{-- Story Framework --}}
            @if(!empty($result['story_framework']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-calendar-week"></i> Story Framework</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Day</th><th>Theme</th><th>Sticker Type</th><th>CTA</th><th>Goal</th></tr></thead>
                        <tbody>
                        @foreach($result['story_framework'] as $frame)
                        <tr>
                            <td style="font-weight:600;color:#c084fc;">{{ $frame['day'] ?? '' }}</td>
                            <td style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $frame['theme'] ?? '-' }}</td>
                            <td>
                                <span class="aith-e-tag aith-e-tag-medium">{{ $frame['sticker_type'] ?? '-' }}</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $frame['cta'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $frame['goal'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Interactive Elements --}}
            @if(!empty($result['interactive_elements']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-hand-pointer"></i> Interactive Elements</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Type</th><th>Usage</th><th>Expected Engagement</th><th>Best For</th></tr></thead>
                        <tbody>
                        @foreach($result['interactive_elements'] as $elem)
                        <tr>
                            <td style="font-weight:600;color:#c084fc;">{{ $elem['type'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.7);">{{ $elem['usage'] ?? '-' }}</td>
                            <td>
                                @php $eng = strtolower($elem['expected_engagement'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $eng === 'high' ? 'aith-e-tag-high' : ($eng === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $elem['expected_engagement'] ?? '-' }}</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $elem['best_for'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Funnel Strategy --}}
            @if(isset($result['funnel_strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-filter"></i> Funnel Strategy</div>
                <div class="aith-e-grid-3">
                    {{-- Awareness --}}
                    @if(!empty($result['funnel_strategy']['awareness']))
                    <div class="aith-e-section-card" style="margin-bottom:0;border-top:3px solid #a855f7;">
                        <div style="font-weight:600;color:#c084fc;font-size:0.85rem;margin-bottom:0.5rem;">Awareness</div>
                        <ul style="margin:0;padding:0;list-style:none;">
                            @foreach($result['funnel_strategy']['awareness'] as $item)
                            <li style="font-size:0.8rem;color:rgba(255,255,255,0.6);padding:0.25rem 0;">
                                <i class="fa-solid fa-circle" style="font-size:0.25rem;vertical-align:middle;margin-right:0.375rem;"></i>{{ $item }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    {{-- Consideration --}}
                    @if(!empty($result['funnel_strategy']['consideration']))
                    <div class="aith-e-section-card" style="margin-bottom:0;border-top:3px solid #d946ef;">
                        <div style="font-weight:600;color:#e879f9;font-size:0.85rem;margin-bottom:0.5rem;">Consideration</div>
                        <ul style="margin:0;padding:0;list-style:none;">
                            @foreach($result['funnel_strategy']['consideration'] as $item)
                            <li style="font-size:0.8rem;color:rgba(255,255,255,0.6);padding:0.25rem 0;">
                                <i class="fa-solid fa-circle" style="font-size:0.25rem;vertical-align:middle;margin-right:0.375rem;"></i>{{ $item }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    {{-- Conversion --}}
                    @if(!empty($result['funnel_strategy']['conversion']))
                    <div class="aith-e-section-card" style="margin-bottom:0;border-top:3px solid #22c55e;">
                        <div style="font-weight:600;color:#86efac;font-size:0.85rem;margin-bottom:0.5rem;">Conversion</div>
                        <ul style="margin:0;padding:0;list-style:none;">
                            @foreach($result['funnel_strategy']['conversion'] as $item)
                            <li style="font-size:0.8rem;color:rgba(255,255,255,0.6);padding:0.25rem 0;">
                                <i class="fa-solid fa-circle" style="font-size:0.25rem;vertical-align:middle;margin-right:0.375rem;"></i>{{ $item }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Content Calendar --}}
            @if(!empty($result['content_calendar']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-calendar-days"></i> Content Calendar</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Time Slot</th><th>Content Type</th><th>Hook</th></tr></thead>
                        <tbody>
                        @foreach($result['content_calendar'] as $slot)
                        <tr>
                            <td style="font-weight:600;color:#c084fc;">{{ $slot['time_slot'] ?? '' }}</td>
                            <td>
                                <span class="aith-e-tag aith-e-tag-medium">{{ $slot['content_type'] ?? '-' }}</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.7);">{{ $slot['hook'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
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
                $nextSteps = config('appaitools.enterprise_tools.ig-story-planner.next_steps', []);
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
