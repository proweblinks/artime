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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#ef4444,#ec4899);">
                    <i class="fa-light fa-clapperboard" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>YouTube → Reels Converter</h2>
                    <p>Convert YouTube videos into Instagram Reels strategies</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">YouTube Video URL</label>
                <input type="url" wire:model="youtubeUrl" class="aith-input"
                       placeholder="https://youtube.com/watch?v=...">
                @error('youtubeUrl') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Reels Style Preference (optional)</label>
                <input type="text" wire:model="reelsStyle" class="aith-input"
                       placeholder="e.g. aesthetic, educational, trending audio, quick tips">
                @error('reelsStyle') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-clapperboard"></i> Convert to Reels
                </span>
                <span wire:loading wire:target="analyze">
                    <i class="fa-light fa-spinner-third fa-spin"></i> Converting...
                </span>
                <span style="margin-left:0.5rem;opacity:0.6;font-size:0.8rem;">3 credits</span>
            </button>
            @endif

            @if($isLoading)
            <div class="aith-e-loading" x-data="{ step: 0 }" x-init="
                let steps = {{ count($loadingSteps) }};
                let interval = setInterval(() => { if(step < steps - 1) step++; }, 2500);
                $wire.on('loadingComplete', () => clearInterval(interval));
            ">
                <div class="aith-e-loading-title">Converting YouTube to Reels...</div>
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
            <div class="aith-e-result-header">
                <span class="aith-e-result-title">Reels Conversion Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-ig-yt-reels-converter', 'YT-Reels-Conversion')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Conversion
                    </button>
                </div>
            </div>

            <div id="pdf-content-ig-yt-reels-converter">

            {{-- YouTube Insights Card --}}
            @if(isset($result['youtube_insights']))
            @php $yt = $result['youtube_insights']; @endphp
            <div class="aith-e-section-card" style="border-left:3px solid #FF0000;">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;">
                    <i class="fa-brands fa-youtube" style="color:#FF0000;font-size:1rem;"></i>
                    <span style="font-weight:700;color:#fff;font-size:0.9rem;">YouTube Insights</span>
                    <span style="margin-left:auto;font-size:0.65rem;padding:0.125rem 0.5rem;border-radius:9999px;background:rgba(255,0,0,0.15);color:#ff6b6b;">REAL DATA</span>
                </div>
                <div style="display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
                    @if(!empty($yt['thumbnail']))
                    <img src="{{ $yt['thumbnail'] }}" alt="" style="width:120px;height:68px;border-radius:0.375rem;object-fit:cover;flex-shrink:0;">
                    @endif
                    <div style="flex:1;min-width:200px;">
                        <div style="font-weight:600;color:#fff;font-size:0.875rem;margin-bottom:0.375rem;">{{ $yt['title'] ?? '' }}</div>
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.5rem;">{{ $yt['channel'] ?? '' }}</div>
                        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                            <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><i class="fa-light fa-eye" style="margin-right:0.25rem;"></i> {{ $yt['views'] ?? '0' }}</span>
                            <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><i class="fa-light fa-thumbs-up" style="margin-right:0.25rem;"></i> {{ $yt['likes'] ?? '0' }}</span>
                            <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><i class="fa-light fa-clock" style="margin-right:0.25rem;"></i> {{ $yt['duration'] ?? '' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Score --}}
            @php $score = $result['adaptation_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Adaptation Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent Reels adaptation potential
                        @elseif($score >= 50) Good adaptation potential with adjustments
                        @else Significant reworking needed for Reels format
                        @endif
                    </div>
                </div>
            </div>

            {{-- Reels Adaptations --}}
            @if(!empty($result['reels_adaptations']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-film"></i> Reels Adaptations</div>
                <div class="aith-e-grid-2">
                @foreach($result['reels_adaptations'] as $adapt)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="font-weight:600;color:#fff;font-size:0.875rem;margin-bottom:0.375rem;">{{ $adapt['segment'] ?? '' }}</div>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.375rem;">
                        @if(isset($adapt['duration']))
                        <span style="font-size:0.75rem;padding:0.125rem 0.375rem;border-radius:0.25rem;background:rgba(236,72,153,0.1);color:#ec4899;">{{ $adapt['duration'] }}</span>
                        @endif
                        @if(isset($adapt['format']))
                        <span style="font-size:0.75rem;padding:0.125rem 0.375rem;border-radius:0.25rem;background:rgba(168,85,247,0.1);color:#a855f7;">{{ $adapt['format'] }}</span>
                        @endif
                    </div>
                    @if(isset($adapt['hook']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.25rem;">
                        <strong style="color:rgba(255,255,255,0.6);">Hook:</strong> {{ $adapt['hook'] }}
                    </div>
                    @endif
                    @if(isset($adapt['caption']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);">
                        <strong style="color:rgba(255,255,255,0.5);">Caption:</strong> {{ $adapt['caption'] }}
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Hook Rewrites --}}
            @if(!empty($result['hook_rewrites']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-bolt"></i> Hook Rewrites</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Original Angle</th><th>Reels Hook</th><th>Style</th><th>Why Effective</th></tr></thead>
                        <tbody>
                        @foreach($result['hook_rewrites'] as $hook)
                        <tr>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $hook['original_angle'] ?? '' }}</td>
                            <td>
                                <div style="font-weight:600;color:#ec4899;font-size:0.85rem;">{{ $hook['reels_hook'] ?? '' }}</div>
                            </td>
                            <td><span class="aith-e-tag aith-e-tag-medium">{{ $hook['style'] ?? '' }}</span></td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $hook['why_effective'] ?? '' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Hashtag Strategy --}}
            @if(isset($result['hashtag_strategy']))
            <div class="aith-e-section-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                    <div class="aith-e-section-card-title" style="margin-bottom:0;"><i class="fa-light fa-hashtag"></i> Hashtag Strategy</div>
                    <button onclick="
                        let all = @js(implode(' ', array_merge($result['hashtag_strategy']['primary'] ?? [], $result['hashtag_strategy']['niche'] ?? [], $result['hashtag_strategy']['trending'] ?? [])));
                        navigator.clipboard.writeText(all).then(() => {
                            this.innerText = 'Copied!';
                            setTimeout(() => { this.innerHTML = '<i class=\'fa-light fa-copy\'></i> Copy All'; }, 2000);
                        });
                    " class="aith-btn-secondary" style="font-size:0.75rem;padding:0.25rem 0.5rem;">
                        <i class="fa-light fa-copy"></i> Copy All
                    </button>
                </div>
                @if(!empty($result['hashtag_strategy']['primary']))
                <div style="margin-bottom:0.5rem;">
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.25rem;font-weight:600;">Primary</div>
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($result['hashtag_strategy']['primary'] as $tag)
                        <span class="aith-e-pill aith-e-pill-green">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(!empty($result['hashtag_strategy']['niche']))
                <div style="margin-bottom:0.5rem;">
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.25rem;font-weight:600;">Niche</div>
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($result['hashtag_strategy']['niche'] as $tag)
                        <span style="font-size:0.75rem;padding:0.125rem 0.5rem;border-radius:9999px;background:rgba(59,130,246,0.1);color:#93c5fd;">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(!empty($result['hashtag_strategy']['trending']))
                <div>
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.25rem;font-weight:600;">Trending</div>
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($result['hashtag_strategy']['trending'] as $tag)
                        <span style="font-size:0.75rem;padding:0.125rem 0.5rem;border-radius:9999px;background:rgba(245,158,11,0.1);color:#fcd34d;">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif

            {{-- Audio Suggestions --}}
            @if(!empty($result['audio_suggestions']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-music"></i> Audio Suggestions</div>
                @foreach($result['audio_suggestions'] as $audio)
                <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div style="flex-shrink:0;">
                        @php $aType = strtolower($audio['type'] ?? ''); @endphp
                        <span class="aith-e-tag {{ $aType === 'trending' ? 'aith-e-tag-high' : 'aith-e-tag-medium' }}">{{ $audio['type'] ?? '' }}</span>
                    </div>
                    <div>
                        <div style="font-size:0.85rem;color:#fff;font-weight:500;">{{ $audio['description'] ?? '' }}</div>
                        @if(isset($audio['why_fits']))
                        <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-top:0.125rem;">{{ $audio['why_fits'] }}</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Caption Rewrites --}}
            @if(!empty($result['caption_rewrites']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-pen-nib"></i> Caption Rewrites</div>
                @foreach($result['caption_rewrites'] as $capIdx => $caption)
                <div class="aith-e-section-card" style="margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        @if(isset($caption['style']))
                        <span class="aith-e-tag aith-e-tag-medium">{{ $caption['style'] }}</span>
                        @endif
                        <button onclick="enterpriseCopy(document.getElementById('ig-cap-{{ $capIdx }}').textContent, 'Caption copied!')" class="aith-e-btn-copy">
                            <i class="fa-light fa-copy"></i> Copy
                        </button>
                    </div>
                    <div id="ig-cap-{{ $capIdx }}" style="font-size:0.85rem;color:#fff;padding:0.75rem;background:rgba(236,72,153,0.08);border-radius:0.375rem;border-left:3px solid #ec4899;margin-bottom:0.5rem;line-height:1.5;">
                        {{ $caption['caption'] ?? '' }}
                    </div>
                    @if(isset($caption['cta']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">
                        <strong style="color:rgba(255,255,255,0.6);">CTA:</strong> {{ $caption['cta'] }}
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Cover Image Tips --}}
            @if(!empty($result['cover_image_tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-image"></i> Cover Image Tips</div>
                @foreach($result['cover_image_tips'] as $tip)
                <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    @if(isset($tip['style']))
                    <span class="aith-e-tag aith-e-tag-medium">{{ $tip['style'] }}</span>
                    @endif
                    <div style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $tip['tip'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Cross Platform Tips --}}
            @if(!empty($result['cross_platform_tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Cross-Platform Tips</div>
                <ul class="aith-e-list">
                    @foreach($result['cross_platform_tips'] as $tip)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.ig-yt-reels-converter.next_steps', []);
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

            @if(session('error'))
            <div class="aith-e-error">{{ session('error') }}</div>
            @endif
        </div>

        @include('appaitools::livewire.enterprise._enterprise-history')
    </div>
</div>
