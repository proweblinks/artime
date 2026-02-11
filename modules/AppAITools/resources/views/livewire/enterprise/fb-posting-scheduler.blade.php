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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#eab308,#d97706);">
                    <i class="fa-light fa-calendar-clock" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Posting Time Optimizer</h2>
                    <p>Find the optimal posting schedule for maximum reach</p>
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
                <label class="aith-label">Timezone (optional)</label>
                <input type="text" wire:model="timezone" class="aith-input"
                       placeholder="e.g. EST, PST, GMT+1">
                @error('timezone') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Content Type (optional)</label>
                <input type="text" wire:model="contentType" class="aith-input"
                       placeholder="e.g. videos, images, links, text posts">
                @error('contentType') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-calendar-clock"></i> Optimize Schedule
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
                <div class="aith-e-loading-title">Optimizing posting schedule...</div>
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
                <span class="aith-e-result-title">Posting Schedule Optimization</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-fb-posting-scheduler', 'Posting-Schedule')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-fb-posting-scheduler">

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
                        @else Needs significant timing adjustments for better reach
                        @endif
                    </div>
                </div>
            </div>

            {{-- Best Times --}}
            @if(!empty($result['best_times']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-clock"></i> Best Times to Post</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Day</th><th>Times</th><th>Engagement Level</th><th>Best Format</th></tr></thead>
                        <tbody>
                        @foreach($result['best_times'] as $dayData)
                        <tr>
                            <td style="font-weight:600;color:#eab308;">{{ $dayData['day'] ?? '' }}</td>
                            <td style="color:rgba(255,255,255,0.7);">{{ is_array($dayData['times'] ?? null) ? implode(', ', $dayData['times']) : ($dayData['times'] ?? '-') }}</td>
                            <td>
                                @php $el = strtolower($dayData['engagement_level'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $el === 'high' ? 'aith-e-tag-high' : ($el === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $dayData['engagement_level'] ?? '-' }}</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $dayData['best_format'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Weekly Schedule --}}
            @if(!empty($result['weekly_schedule']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-calendar-week"></i> Weekly Schedule</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Day</th><th>Post Count</th><th>Best Slots</th><th>Content Type</th><th>Format</th></tr></thead>
                        <tbody>
                        @foreach($result['weekly_schedule'] as $item)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $item['day'] ?? '' }}</td>
                            <td style="color:#eab308;font-weight:600;">{{ $item['post_count'] ?? '-' }}</td>
                            <td style="color:rgba(255,255,255,0.7);">{{ is_array($item['best_slots'] ?? null) ? implode(', ', $item['best_slots']) : ($item['best_slots'] ?? '-') }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $item['content_type'] ?? '-' }}</td>
                            <td>
                                @if(isset($item['format']))
                                <span class="aith-e-tag aith-e-tag-medium">{{ $item['format'] }}</span>
                                @else
                                -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Format-Specific Timing --}}
            @if(isset($result['format_specific_timing']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-grid-2"></i> Format-Specific Timing</div>
                <div class="aith-e-grid-2">
                    @if(isset($result['format_specific_timing']['reels']))
                    <div class="aith-e-section-card" style="margin-bottom:0;border-top:3px solid #eab308;">
                        <div style="font-weight:600;color:#fbbf24;font-size:0.85rem;margin-bottom:0.5rem;"><i class="fa-light fa-clapperboard-play" style="margin-right:0.25rem;"></i> Reels</div>
                        @if(!empty($result['format_specific_timing']['reels']['best_times']))
                        <div style="margin-bottom:0.375rem;">
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Best Times</span>
                            <div style="display:flex;flex-wrap:wrap;gap:0.25rem;margin-top:0.125rem;">
                                @foreach((array)$result['format_specific_timing']['reels']['best_times'] as $time)
                                <span style="font-size:0.8rem;color:rgba(255,255,255,0.7);background:rgba(234,179,8,0.1);padding:0.125rem 0.375rem;border-radius:0.25rem;">{{ $time }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if(isset($result['format_specific_timing']['reels']['frequency']))
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">
                            <strong style="color:rgba(255,255,255,0.6);">Frequency:</strong> {{ $result['format_specific_timing']['reels']['frequency'] }}
                        </div>
                        @endif
                    </div>
                    @endif

                    @if(isset($result['format_specific_timing']['posts']))
                    <div class="aith-e-section-card" style="margin-bottom:0;border-top:3px solid #d97706;">
                        <div style="font-weight:600;color:#fbbf24;font-size:0.85rem;margin-bottom:0.5rem;"><i class="fa-light fa-image" style="margin-right:0.25rem;"></i> Posts</div>
                        @if(!empty($result['format_specific_timing']['posts']['best_times']))
                        <div style="margin-bottom:0.375rem;">
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Best Times</span>
                            <div style="display:flex;flex-wrap:wrap;gap:0.25rem;margin-top:0.125rem;">
                                @foreach((array)$result['format_specific_timing']['posts']['best_times'] as $time)
                                <span style="font-size:0.8rem;color:rgba(255,255,255,0.7);background:rgba(217,119,6,0.1);padding:0.125rem 0.375rem;border-radius:0.25rem;">{{ $time }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if(isset($result['format_specific_timing']['posts']['frequency']))
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">
                            <strong style="color:rgba(255,255,255,0.6);">Frequency:</strong> {{ $result['format_specific_timing']['posts']['frequency'] }}
                        </div>
                        @endif
                    </div>
                    @endif

                    @if(isset($result['format_specific_timing']['stories']))
                    <div class="aith-e-section-card" style="margin-bottom:0;border-top:3px solid #f59e0b;">
                        <div style="font-weight:600;color:#fbbf24;font-size:0.85rem;margin-bottom:0.5rem;"><i class="fa-light fa-rectangle-vertical-history" style="margin-right:0.25rem;"></i> Stories</div>
                        @if(!empty($result['format_specific_timing']['stories']['best_times']))
                        <div style="margin-bottom:0.375rem;">
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Best Times</span>
                            <div style="display:flex;flex-wrap:wrap;gap:0.25rem;margin-top:0.125rem;">
                                @foreach((array)$result['format_specific_timing']['stories']['best_times'] as $time)
                                <span style="font-size:0.8rem;color:rgba(255,255,255,0.7);background:rgba(245,158,11,0.1);padding:0.125rem 0.375rem;border-radius:0.25rem;">{{ $time }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if(isset($result['format_specific_timing']['stories']['frequency']))
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">
                            <strong style="color:rgba(255,255,255,0.6);">Frequency:</strong> {{ $result['format_specific_timing']['stories']['frequency'] }}
                        </div>
                        @endif
                    </div>
                    @endif

                    @if(isset($result['format_specific_timing']['lives']))
                    <div class="aith-e-section-card" style="margin-bottom:0;border-top:3px solid #b45309;">
                        <div style="font-weight:600;color:#fbbf24;font-size:0.85rem;margin-bottom:0.5rem;"><i class="fa-light fa-signal-stream" style="margin-right:0.25rem;"></i> Lives</div>
                        @if(!empty($result['format_specific_timing']['lives']['best_times']))
                        <div style="margin-bottom:0.375rem;">
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Best Times</span>
                            <div style="display:flex;flex-wrap:wrap;gap:0.25rem;margin-top:0.125rem;">
                                @foreach((array)$result['format_specific_timing']['lives']['best_times'] as $time)
                                <span style="font-size:0.8rem;color:rgba(255,255,255,0.7);background:rgba(180,83,9,0.1);padding:0.125rem 0.375rem;border-radius:0.25rem;">{{ $time }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if(isset($result['format_specific_timing']['lives']['frequency']))
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">
                            <strong style="color:rgba(255,255,255,0.6);">Frequency:</strong> {{ $result['format_specific_timing']['lives']['frequency'] }}
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Avoid Times --}}
            @if(!empty($result['avoid_times']))
            <div class="aith-e-section-card" style="border:1px solid rgba(239,68,68,0.2);">
                <div class="aith-e-section-card-title" style="color:#fca5a5;"><i class="fa-light fa-triangle-exclamation"></i> Times to Avoid</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Time</th><th>Reason</th><th>Engagement Drop</th></tr></thead>
                        <tbody>
                        @foreach($result['avoid_times'] as $avoid)
                        <tr>
                            <td style="font-weight:600;color:#fca5a5;">{{ $avoid['time'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $avoid['reason'] ?? '-' }}</td>
                            <td style="font-weight:600;color:#ef4444;">{{ $avoid['engagement_drop'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Frequency Recommendation --}}
            @if(isset($result['frequency_recommendation']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-gauge-high"></i> Frequency Recommendation</div>
                <div class="aith-e-grid-2" style="margin-bottom:0.5rem;">
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
                <div class="aith-e-grid-2" style="margin-bottom:0.75rem;">
                    @if(isset($result['frequency_recommendation']['reels_per_week']))
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Reels Per Week</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['frequency_recommendation']['reels_per_week'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['frequency_recommendation']['stories_per_day']))
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Stories Per Day</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;">{{ $result['frequency_recommendation']['stories_per_day'] }}</div>
                    </div>
                    @endif
                </div>
                @if(isset($result['frequency_recommendation']['reasoning']))
                <div style="font-size:0.85rem;color:rgba(255,255,255,0.6);padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;">
                    {{ $result['frequency_recommendation']['reasoning'] }}
                </div>
                @endif
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.fb-posting-scheduler.next_steps', []);
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
