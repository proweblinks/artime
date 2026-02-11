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
                    <i class="fa-light fa-clock" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Posting Time Optimizer</h2>
                    <p>Find optimal posting times based on your audience data</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">TikTok Profile</label>
                <input type="text" wire:model="profile" class="aith-input"
                       placeholder="@username">
                @error('profile')
                <span class="aith-e-field-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Timezone (optional)</label>
                <input type="text" wire:model="timezone" class="aith-input"
                       placeholder="e.g. EST, PST, UTC+8">
                @error('timezone')
                <span class="aith-e-field-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Content Type (optional)</label>
                <input type="text" wire:model="contentType" class="aith-input"
                       placeholder="e.g. comedy, educational">
                @error('contentType')
                <span class="aith-e-field-error">{{ $message }}</span>
                @enderror
            </div>
            @include('appaitools::livewire.enterprise._youtube-connect', ['youtubeField' => 'youtubeChannel'])
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-clock"></i> Optimize Timing
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
                <div class="aith-e-loading-title">Analyzing posting times...</div>
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
                <span class="aith-e-result-title">Posting Time Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-tiktok-posting-time', 'Posting-Time-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-tiktok-posting-time">
            {{-- Score --}}
            @php $score = $result['timing_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Timing Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent posting schedule - maximizing audience reach
                        @elseif($score >= 50) Good timing strategy with optimization potential
                        @else Needs significant timing adjustments for better engagement
                        @endif
                    </div>
                </div>
            </div>

            {{-- Best Times Grid --}}
            @if(!empty($result['best_times']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-calendar-days"></i> Best Times to Post</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:0.5rem;">
                @foreach($result['best_times'] as $dayData)
                <div class="aith-e-section-card" style="margin-bottom:0;text-align:center;">
                    <div style="font-weight:600;color:#f59e0b;font-size:0.875rem;margin-bottom:0.375rem;">{{ $dayData['day'] ?? '' }}</div>
                    @if(!empty($dayData['times']))
                    <div style="display:flex;flex-direction:column;gap:0.25rem;">
                        @foreach((array)$dayData['times'] as $time)
                        <span style="font-size:0.8rem;color:rgba(255,255,255,0.7);background:rgba(245,158,11,0.1);padding:0.125rem 0.375rem;border-radius:0.25rem;">{{ $time }}</span>
                        @endforeach
                    </div>
                    @endif
                    @if(isset($dayData['engagement_level']))
                    @php $el = strtolower($dayData['engagement_level']); @endphp
                    <div style="margin-top:0.375rem;">
                        <span class="aith-e-tag {{ $el === 'high' ? 'aith-e-tag-high' : ($el === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}" style="font-size:0.7rem;">{{ $dayData['engagement_level'] }}</span>
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Weekly Schedule --}}
            @if(!empty($result['weekly_schedule']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-table"></i> Weekly Schedule</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Day</th><th>Posts</th><th>Best Slots</th><th>Content Type</th></tr></thead>
                        <tbody>
                        @foreach($result['weekly_schedule'] as $item)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $item['day'] ?? '' }}</td>
                            <td style="color:#f59e0b;font-weight:600;">{{ $item['post_count'] ?? '-' }}</td>
                            <td>{{ is_array($item['best_slots'] ?? null) ? implode(', ', $item['best_slots']) : ($item['best_slots'] ?? '-') }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $item['content_type'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Peak Hours --}}
            @if(isset($result['peak_hours']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-line"></i> Peak Hours</div>
                <div class="aith-e-grid-2">
                    @if(isset($result['peak_hours']['weekday']))
                    <div class="aith-e-section-card" style="margin-bottom:0;">
                        <div style="font-weight:600;color:#f59e0b;font-size:0.875rem;margin-bottom:0.5rem;">
                            <i class="fa-light fa-briefcase" style="margin-right:0.25rem;"></i> Weekday
                        </div>
                        @if(is_array($result['peak_hours']['weekday']))
                        <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                            @foreach($result['peak_hours']['weekday'] as $hour)
                            <span class="aith-e-pill aith-e-pill-green">{{ $hour }}</span>
                            @endforeach
                        </div>
                        @else
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);">{{ $result['peak_hours']['weekday'] }}</div>
                        @endif
                    </div>
                    @endif
                    @if(isset($result['peak_hours']['weekend']))
                    <div class="aith-e-section-card" style="margin-bottom:0;">
                        <div style="font-weight:600;color:#ea580c;font-size:0.875rem;margin-bottom:0.5rem;">
                            <i class="fa-light fa-couch" style="margin-right:0.25rem;"></i> Weekend
                        </div>
                        @if(is_array($result['peak_hours']['weekend']))
                        <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                            @foreach($result['peak_hours']['weekend'] as $hour)
                            <span class="aith-e-pill aith-e-pill-green">{{ $hour }}</span>
                            @endforeach
                        </div>
                        @else
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);">{{ $result['peak_hours']['weekend'] }}</div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Frequency Recommendation --}}
            @if(isset($result['frequency_recommendation']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-gauge-high"></i> Frequency Recommendation</div>
                <div class="aith-e-grid-2" style="margin-bottom:0.75rem;">
                    @if(isset($result['frequency_recommendation']['posts_per_day']))
                    <div class="aith-e-summary-card aith-e-summary-card-orange">
                        <div class="aith-e-summary-label">Posts Per Day</div>
                        <div class="aith-e-summary-value" style="color:#fdba74;">{{ $result['frequency_recommendation']['posts_per_day'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['frequency_recommendation']['posts_per_week']))
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Posts Per Week</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['frequency_recommendation']['posts_per_week'] }}</div>
                    </div>
                    @endif
                </div>
                @if(isset($result['frequency_recommendation']['reasoning']))
                <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;">
                    {{ $result['frequency_recommendation']['reasoning'] }}
                </div>
                @endif
            </div>
            @endif

            {{-- Tips --}}
            @if(!empty($result['tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Timing Tips</div>
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
