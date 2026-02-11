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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#14b8a6,#059669);">
                    <i class="fa-light fa-recycle" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Content Format Recycler</h2>
                    <p>Repurpose top-performing content across Facebook formats</p>
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
                <label class="aith-label">Content Topic (optional)</label>
                <input type="text" wire:model="contentTopic" class="aith-input"
                       placeholder="e.g. tutorials, product reviews, behind the scenes">
                @error('contentTopic') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Original Format (optional)</label>
                <input type="text" wire:model="originalFormat" class="aith-input"
                       placeholder="e.g. video, post, story, reel">
                @error('originalFormat') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            @include('appaitools::livewire.enterprise._youtube-connect', ['youtubeField' => 'youtubeChannel'])
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-recycle"></i> Analyze Content
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
                <div class="aith-e-loading-title">Analyzing content recycling opportunities...</div>
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
                <span class="aith-e-result-title">Content Recycling Plan</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-fb-content-recycler', 'Content-Recycling')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-fb-content-recycler">

            {{-- Score --}}
            @php $score = $result['recycling_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Recycling Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent recycling potential - maximize your content library value
                        @elseif($score >= 50) Good recycling opportunities - several formats can be repurposed
                        @else Limited recycling potential - focus on creating more recyclable content first
                        @endif
                    </div>
                </div>
            </div>

            {{-- Content Audit --}}
            @if(isset($result['content_audit']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-clipboard-check"></i> Content Audit</div>
                @if(isset($result['content_audit']['total_recyclable']))
                <div class="aith-e-summary-card aith-e-summary-card-green" style="margin-bottom:0.75rem;">
                    <div class="aith-e-summary-label">Total Recyclable Content</div>
                    <div class="aith-e-summary-value" style="color:#86efac;font-size:1.5rem;">{{ $result['content_audit']['total_recyclable'] }}</div>
                </div>
                @endif
                @if(!empty($result['content_audit']['top_performers']))
                <div style="margin-bottom:0.5rem;">
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.25rem;font-weight:600;">Top Performers</div>
                    @foreach($result['content_audit']['top_performers'] as $performer)
                    <div style="font-size:0.8rem;color:#86efac;padding:0.125rem 0;">
                        <i class="fa-light fa-star" style="margin-right:0.375rem;font-size:0.7rem;"></i>{{ $performer }}
                    </div>
                    @endforeach
                </div>
                @endif
                @if(!empty($result['content_audit']['underutilized_formats']))
                <div style="margin-bottom:0.5rem;">
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.25rem;font-weight:600;">Underutilized Formats</div>
                    @foreach($result['content_audit']['underutilized_formats'] as $format)
                    <div style="font-size:0.8rem;color:#fbbf24;padding:0.125rem 0;">
                        <i class="fa-light fa-triangle-exclamation" style="margin-right:0.375rem;font-size:0.7rem;"></i>{{ $format }}
                    </div>
                    @endforeach
                </div>
                @endif
                @if(!empty($result['content_audit']['evergreen_content']))
                <div>
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.25rem;font-weight:600;">Evergreen Content</div>
                    @foreach($result['content_audit']['evergreen_content'] as $content)
                    <div style="font-size:0.8rem;color:#2dd4bf;padding:0.125rem 0;">
                        <i class="fa-light fa-leaf" style="margin-right:0.375rem;font-size:0.7rem;"></i>{{ $content }}
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

            {{-- Format Conversions --}}
            @if(!empty($result['format_conversions']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-arrows-repeat"></i> Format Conversions</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Original Format</th><th>Target Format</th><th>Adaptation</th><th>Expected Reach</th><th>Effort</th></tr></thead>
                        <tbody>
                        @foreach($result['format_conversions'] as $conversion)
                        <tr>
                            <td style="font-weight:600;color:#2dd4bf;">{{ $conversion['original_format'] ?? '' }}</td>
                            <td style="font-weight:600;color:#86efac;">{{ $conversion['target_format'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">{{ $conversion['adaptation'] ?? '-' }}</td>
                            <td>
                                @php $reach = strtolower($conversion['expected_reach'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $reach === 'high' ? 'aith-e-tag-high' : ($reach === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $conversion['expected_reach'] ?? '-' }}</span>
                            </td>
                            <td>
                                @php $effort = strtolower($conversion['effort'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $effort === 'low' ? 'aith-e-tag-high' : ($effort === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $conversion['effort'] ?? '-' }}</span>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Recycling Calendar --}}
            @if(!empty($result['recycling_calendar']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-calendar-days"></i> Recycling Calendar</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Day</th><th>Original Content</th><th>New Format</th><th>Caption Hook</th><th>Posting Time</th></tr></thead>
                        <tbody>
                        @foreach($result['recycling_calendar'] as $day)
                        <tr>
                            <td style="font-weight:600;color:#2dd4bf;">{{ $day['day'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.7);">{{ $day['original_content'] ?? '-' }}</td>
                            <td>
                                <span style="font-size:0.75rem;padding:0.125rem 0.375rem;border-radius:0.25rem;background:rgba(20,184,166,0.1);color:#2dd4bf;">{{ $day['new_format'] ?? '-' }}</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">{{ $day['caption_hook'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $day['posting_time'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Content Briefs --}}
            @if(!empty($result['content_briefs']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-file-lines"></i> Content Briefs</div>
                <div class="aith-e-grid-2">
                @foreach($result['content_briefs'] as $brief)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#2dd4bf;font-size:0.9rem;">{{ $brief['title'] ?? '' }}</span>
                        @if(isset($brief['format']))
                        <span style="font-size:0.7rem;padding:0.125rem 0.375rem;border-radius:0.25rem;background:rgba(20,184,166,0.1);color:#2dd4bf;">{{ $brief['format'] }}</span>
                        @endif
                    </div>
                    @if(isset($brief['hook']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);margin-bottom:0.375rem;padding:0.375rem;background:rgba(0,0,0,0.2);border-radius:0.25rem;border-left:2px solid #2dd4bf;">
                        <strong style="color:rgba(255,255,255,0.7);">Hook:</strong> {{ $brief['hook'] }}
                    </div>
                    @endif
                    @if(!empty($brief['key_points']))
                    <div style="margin-bottom:0.375rem;">
                        @foreach($brief['key_points'] as $point)
                        <div style="font-size:0.75rem;color:rgba(255,255,255,0.5);padding:0.125rem 0;">
                            <i class="fa-solid fa-circle" style="font-size:0.25rem;margin-right:0.375rem;vertical-align:middle;"></i>{{ $point }}
                        </div>
                        @endforeach
                    </div>
                    @endif
                    @if(isset($brief['cta']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.375rem;">
                        <strong style="color:rgba(255,255,255,0.6);">CTA:</strong> {{ $brief['cta'] }}
                    </div>
                    @endif
                    @if(!empty($brief['hashtags']))
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($brief['hashtags'] as $tag)
                        <span class="aith-e-pill aith-e-pill-green">{{ $tag }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Automation Tips --}}
            @if(!empty($result['automation_tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Automation Tips</div>
                <ul class="aith-e-list">
                    @foreach($result['automation_tips'] as $tip)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.fb-content-recycler.next_steps', []);
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
