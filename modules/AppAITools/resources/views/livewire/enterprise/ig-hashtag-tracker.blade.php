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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#06b6d4,#0d9488);">
                    <i class="fa-light fa-hashtag" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Hashtag Performance Tracker</h2>
                    <p>Analyze hashtag performance and find winning combinations</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">Niche</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. fitness, food, fashion, travel">
                @error('niche') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Content Type (optional)</label>
                <input type="text" wire:model="contentType" class="aith-input"
                       placeholder="e.g. reels, carousels, stories, posts">
                @error('contentType') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            @include('appaitools::livewire.enterprise._youtube-connect', ['youtubeField' => 'youtubeChannel'])
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-hashtag"></i> Track Hashtags
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
                <div class="aith-e-loading-title">Analyzing hashtag performance...</div>
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
                <span class="aith-e-result-title">Hashtag Performance Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-ig-hashtag-tracker', 'Hashtag-Performance')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-ig-hashtag-tracker">

            {{-- Score --}}
            @php $score = $result['hashtag_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Hashtag Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent hashtag strategy potential
                        @elseif($score >= 50) Good hashtag combinations available
                        @else Needs significant hashtag optimization
                        @endif
                    </div>
                </div>
            </div>

            {{-- Primary Hashtags Table --}}
            @if(!empty($result['primary_hashtags']))
            <div class="aith-e-section-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                    <div class="aith-e-section-card-title" style="margin-bottom:0;"><i class="fa-light fa-hashtag"></i> Primary Hashtags</div>
                    <button onclick="
                        let tags = @js(collect($result['primary_hashtags'])->pluck('tag')->implode(' '));
                        navigator.clipboard.writeText(tags).then(() => {
                            this.innerText = 'Copied!';
                            setTimeout(() => { this.innerHTML = '<i class=\'fa-light fa-copy\'></i> Copy All'; }, 2000);
                        });
                    " class="aith-btn-secondary" style="font-size:0.75rem;padding:0.25rem 0.5rem;">
                        <i class="fa-light fa-copy"></i> Copy All
                    </button>
                </div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Tag</th><th>Avg Reach</th><th>Competition</th><th>Trending</th></tr></thead>
                        <tbody>
                        @foreach($result['primary_hashtags'] as $hashtag)
                        <tr>
                            <td style="font-weight:600;color:#06b6d4;">{{ $hashtag['tag'] ?? '' }}</td>
                            <td>{{ $hashtag['avg_reach'] ?? '-' }}</td>
                            <td>
                                @php $comp = strtolower($hashtag['competition'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $comp === 'low' ? 'aith-e-tag-high' : ($comp === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $hashtag['competition'] ?? '-' }}</span>
                            </td>
                            <td>
                                @if(isset($hashtag['trending']) && $hashtag['trending'])
                                <span style="color:#22c55e;"><i class="fa-solid fa-arrow-trend-up"></i> Yes</span>
                                @else
                                <span style="color:rgba(255,255,255,0.4);">No</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Secondary Hashtags --}}
            @if(!empty($result['secondary_hashtags']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-hashtag"></i> Secondary Hashtags</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Tag</th><th>Avg Reach</th><th>Purpose</th></tr></thead>
                        <tbody>
                        @foreach($result['secondary_hashtags'] as $hashtag)
                        <tr>
                            <td style="font-weight:600;color:#0d9488;">{{ $hashtag['tag'] ?? '' }}</td>
                            <td>{{ $hashtag['avg_reach'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $hashtag['purpose'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Hashtag Sets --}}
            @if(!empty($result['hashtag_sets']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-layer-group"></i> Hashtag Sets</div>
                @foreach($result['hashtag_sets'] as $set)
                <div class="aith-e-section-card" style="margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $set['name'] ?? '' }}</span>
                        @if(isset($set['estimated_reach']))
                        <span class="aith-e-tag aith-e-tag-medium">{{ $set['estimated_reach'] }} reach</span>
                        @endif
                    </div>
                    @if(!empty($set['tags']))
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-bottom:0.5rem;">
                        @foreach($set['tags'] as $tag)
                        <span class="aith-e-pill aith-e-pill-green">{{ $tag }}</span>
                        @endforeach
                    </div>
                    @endif
                    @if(isset($set['best_for']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">
                        <strong style="color:rgba(255,255,255,0.6);">Best for:</strong> {{ $set['best_for'] }}
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Banned / Shadowban Check --}}
            @if(!empty($result['banned_shadowban_check']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-shield-exclamation"></i> Banned / Shadowban Check</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Tag</th><th>Status</th><th>Risk</th></tr></thead>
                        <tbody>
                        @foreach($result['banned_shadowban_check'] as $check)
                        <tr>
                            <td style="font-weight:600;color:#06b6d4;">{{ $check['tag'] ?? '' }}</td>
                            <td>
                                @php $status = strtolower($check['status'] ?? ''); @endphp
                                @if($status === 'safe')
                                <span style="color:#22c55e;font-weight:600;"><i class="fa-solid fa-check-circle"></i> Safe</span>
                                @elseif($status === 'risky')
                                <span style="color:#fbbf24;font-weight:600;"><i class="fa-solid fa-exclamation-triangle"></i> Risky</span>
                                @elseif($status === 'banned')
                                <span style="color:#ef4444;font-weight:600;"><i class="fa-solid fa-ban"></i> Banned</span>
                                @else
                                <span style="color:rgba(255,255,255,0.5);">{{ $check['status'] ?? '-' }}</span>
                                @endif
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $check['risk'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Strategy Tips --}}
            @if(!empty($result['strategy_tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Strategy Tips</div>
                <ul class="aith-e-list">
                    @foreach($result['strategy_tips'] as $tip)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.ig-hashtag-tracker.next_steps', []);
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
