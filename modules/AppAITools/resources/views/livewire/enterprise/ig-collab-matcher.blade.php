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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#14b8a6,#06b6d4);">
                    <i class="fa-light fa-handshake" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Collab Post Matcher</h2>
                    <p>Find ideal collab partners based on audience overlap</p>
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
                <label class="aith-label">Niche (optional)</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. beauty, tech, lifestyle, fitness">
                @error('niche') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Follower Count (optional)</label>
                <input type="text" wire:model="followerCount" class="aith-input"
                       placeholder="e.g. 10K, 100K, 1M">
                @error('followerCount') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            @include('appaitools::livewire.enterprise._youtube-connect', ['youtubeField' => 'youtubeChannel'])
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-handshake"></i> Find Collab Partners
                </span>
                <span wire:loading wire:target="analyze">
                    <i class="fa-light fa-spinner-third fa-spin"></i> Matching...
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
                <div class="aith-e-loading-title">Finding collab partners...</div>
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
                <span class="aith-e-result-title">Collab Match Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-ig-collab-matcher', 'Collab-Match-Results')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Search
                    </button>
                </div>
            </div>

            <div id="pdf-content-ig-collab-matcher">

            {{-- Score --}}
            @php $score = $result['collab_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Collab Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent collab potential - strong audience overlap opportunities found
                        @elseif($score >= 50) Good collab potential - several promising partnership matches
                        @else Limited matches found - consider broadening your niche or follower range
                        @endif
                    </div>
                </div>
            </div>

            {{-- Creator Matches --}}
            @if(!empty($result['creator_matches']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-users"></i> Creator Matches</div>
                <div class="aith-e-grid-2">
                @foreach($result['creator_matches'] as $match)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#2dd4bf;font-size:0.9rem;">{{ $match['handle'] ?? '' }}</span>
                        @if(isset($match['overlap_score']))
                        @php $os = intval($match['overlap_score']); @endphp
                        <span class="aith-e-tag {{ $os >= 80 ? 'aith-e-tag-high' : ($os >= 50 ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $os }}% overlap</span>
                        @endif
                    </div>
                    <div class="aith-e-grid-3" style="margin-bottom:0.375rem;">
                        @if(isset($match['followers']))
                        <div>
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Followers</span>
                            <div style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $match['followers'] }}</div>
                        </div>
                        @endif
                        @if(isset($match['niche']))
                        <div>
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Niche</span>
                            <div style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $match['niche'] }}</div>
                        </div>
                        @endif
                        @if(isset($match['engagement_rate']))
                        <div>
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Engagement</span>
                            <div style="font-size:0.85rem;color:#22c55e;">{{ $match['engagement_rate'] }}</div>
                        </div>
                        @endif
                    </div>
                    @if(isset($match['collab_idea']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.25rem;">
                        <strong style="color:rgba(255,255,255,0.6);">Collab Idea:</strong> {{ $match['collab_idea'] }}
                    </div>
                    @endif
                    @if(isset($match['collab_format']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);">
                        <strong style="color:rgba(255,255,255,0.5);">Format:</strong> {{ $match['collab_format'] }}
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Outreach Templates --}}
            @if(!empty($result['outreach_templates']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-envelope"></i> Outreach Templates</div>
                @foreach($result['outreach_templates'] as $tplIdx => $template)
                <div x-data="{ open: false }" style="padding:0.75rem;border-radius:0.5rem;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.06);margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;" @click="open = !open">
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            @if(isset($template['approach']))
                            <span class="aith-e-tag aith-e-tag-medium">{{ $template['approach'] }}</span>
                            @endif
                            <span style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $template['subject'] ?? 'Outreach Message ' . ($tplIdx + 1) }}</span>
                        </div>
                        <i class="fa-light" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" style="color:rgba(255,255,255,0.4);font-size:0.75rem;"></i>
                    </div>
                    <div x-show="open" x-collapse style="margin-top:0.75rem;">
                        @if(isset($template['message']))
                        <div style="margin-bottom:0.5rem;">
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.25rem;">Message</span>
                            <pre style="white-space:pre-wrap;font-size:0.8rem;color:rgba(255,255,255,0.5);margin:0;font-family:monospace;">{{ $template['message'] }}</pre>
                        </div>
                        @endif
                        @if(!empty($template['key_points']))
                        <div>
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.25rem;">Key Points</span>
                            <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                                @foreach($template['key_points'] as $point)
                                <span class="aith-e-pill aith-e-pill-green">{{ $point }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Collab Formats --}}
            @if(!empty($result['collab_formats']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-grid-2"></i> Collab Formats</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Format</th><th>Benefits</th><th>Effort</th><th>Reach Multiplier</th></tr></thead>
                        <tbody>
                        @foreach($result['collab_formats'] as $fmt)
                        <tr>
                            <td style="font-weight:600;color:#2dd4bf;">{{ $fmt['format'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.7);">{{ $fmt['benefits'] ?? '-' }}</td>
                            <td>
                                @php $effort = strtolower($fmt['effort'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $effort === 'low' ? 'aith-e-tag-high' : ($effort === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $fmt['effort'] ?? '-' }}</span>
                            </td>
                            <td style="font-weight:600;color:#22c55e;">{{ $fmt['reach_multiplier'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Strategy --}}
            @if(isset($result['strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chess"></i> Strategy</div>
                <div class="aith-e-grid-2">
                    @foreach($result['strategy'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">
                            @if(is_array($val))
                                {{ implode(', ', $val) }}
                            @else
                                {{ $val }}
                            @endif
                        </div>
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
                $nextSteps = config('appaitools.enterprise_tools.ig-collab-matcher.next_steps', []);
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
