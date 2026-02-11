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
                    <i class="fa-light fa-images" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Carousel Content Builder</h2>
                    <p>Design high-save carousel post strategies</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">Topic</label>
                <input type="text" wire:model="topic" class="aith-input"
                       placeholder="e.g. 10 productivity tips, skincare routine, coding basics">
                @error('topic') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Niche (optional)</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. beauty, tech, fitness, education">
                @error('niche') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Slide Count (optional)</label>
                <input type="text" wire:model="slideCount" class="aith-input"
                       placeholder="e.g. 5, 7, 10 (default: 10)">
                @error('slideCount') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-images"></i> Build Carousel
                </span>
                <span wire:loading wire:target="analyze">
                    <i class="fa-light fa-spinner-third fa-spin"></i> Building...
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
                <div class="aith-e-loading-title">Building carousel strategy...</div>
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
                <span class="aith-e-result-title">Carousel Strategy Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-ig-carousel-builder', 'Carousel-Strategy')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Carousel
                    </button>
                </div>
            </div>

            <div id="pdf-content-ig-carousel-builder">

            {{-- Score --}}
            @php $score = $result['carousel_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Carousel Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) High-save carousel potential - this topic will perform exceptionally
                        @elseif($score >= 50) Good carousel potential - follow the templates for best results
                        @else Moderate save potential - refine the hook and visual strategy
                        @endif
                    </div>
                </div>
            </div>

            {{-- Carousel Templates --}}
            @if(!empty($result['carousel_templates']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-layer-group"></i> Carousel Templates</div>
                @foreach($result['carousel_templates'] as $tplIdx => $template)
                <div class="aith-e-section-card" style="margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="font-weight:600;color:#fbbf24;font-size:0.9rem;">{{ $template['title'] ?? 'Template ' . ($tplIdx + 1) }}</span>
                        @if(isset($template['estimated_saves']))
                        <span class="aith-e-tag aith-e-tag-medium">{{ $template['estimated_saves'] }} saves</span>
                        @endif
                    </div>

                    {{-- Hook Slide --}}
                    @if(isset($template['hook_slide']))
                    <div style="margin-bottom:0.5rem;padding:0.5rem;border-radius:0.375rem;background:rgba(245,158,11,0.08);border-left:3px solid #f59e0b;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.25rem;">Hook Slide</span>
                        <div style="font-size:0.85rem;color:#fbbf24;font-weight:500;">{{ $template['hook_slide'] }}</div>
                    </div>
                    @endif

                    {{-- Slides Table --}}
                    @if(!empty($template['slides']))
                    <div style="overflow-x:auto;margin-bottom:0.5rem;">
                        <table class="aith-e-table">
                            <thead><tr><th>#</th><th>Headline</th><th>Content</th><th>Visual Tip</th></tr></thead>
                            <tbody>
                            @foreach($template['slides'] as $slide)
                            <tr>
                                <td style="font-weight:600;color:#fbbf24;">{{ $slide['slide_num'] ?? '' }}</td>
                                <td style="font-weight:600;color:#fff;font-size:0.85rem;">{{ $slide['headline'] ?? '' }}</td>
                                <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">{{ $slide['content'] ?? '-' }}</td>
                                <td style="font-size:0.8rem;color:rgba(255,255,255,0.4);">{{ $slide['visual_tip'] ?? '-' }}</td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    {{-- CTA Slide --}}
                    @if(isset($template['cta_slide']))
                    <div style="padding:0.5rem;border-radius:0.375rem;background:rgba(234,88,12,0.08);border-left:3px solid #ea580c;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.25rem;">CTA Slide</span>
                        <div style="font-size:0.85rem;color:#fb923c;font-weight:500;">{{ $template['cta_slide'] }}</div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Design Tips --}}
            @if(!empty($result['design_tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-palette"></i> Design Tips</div>
                @foreach($result['design_tips'] as $tip)
                <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div style="flex-shrink:0;">
                        @php $impact = strtolower($tip['impact'] ?? ''); @endphp
                        <span class="aith-e-tag {{ $impact === 'high' ? 'aith-e-tag-high' : ($impact === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $tip['impact'] ?? '' }}</span>
                    </div>
                    <div style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $tip['tip'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Caption Templates --}}
            @if(!empty($result['caption_templates']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-pen-nib"></i> Caption Templates</div>
                @foreach($result['caption_templates'] as $capIdx => $caption)
                <div class="aith-e-section-card" style="margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        @if(isset($caption['style']))
                        <span class="aith-e-tag aith-e-tag-medium">{{ $caption['style'] }}</span>
                        @endif
                        <button onclick="enterpriseCopy(document.getElementById('carousel-cap-{{ $capIdx }}').textContent, 'Caption copied!')" class="aith-e-btn-copy">
                            <i class="fa-light fa-copy"></i> Copy
                        </button>
                    </div>
                    <div id="carousel-cap-{{ $capIdx }}" style="font-size:0.85rem;color:#fff;padding:0.75rem;background:rgba(245,158,11,0.08);border-radius:0.375rem;border-left:3px solid #f59e0b;margin-bottom:0.5rem;line-height:1.5;">
                        {{ $caption['caption'] ?? '' }}
                    </div>
                    @if(!empty($caption['hashtags']))
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($caption['hashtags'] as $tag)
                        <span class="aith-e-pill aith-e-pill-green">{{ $tag }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Posting Strategy --}}
            @if(isset($result['posting_strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-clock"></i> Posting Strategy</div>
                <div class="aith-e-grid-3">
                    @if(isset($result['posting_strategy']['best_times']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Best Times</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">
                            @if(is_array($result['posting_strategy']['best_times']))
                                {{ implode(', ', $result['posting_strategy']['best_times']) }}
                            @else
                                {{ $result['posting_strategy']['best_times'] }}
                            @endif
                        </div>
                    </div>
                    @endif
                    @if(isset($result['posting_strategy']['frequency']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Frequency</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['posting_strategy']['frequency'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['posting_strategy']['series_ideas']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Series Ideas</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">
                            @if(is_array($result['posting_strategy']['series_ideas']))
                                {{ implode(', ', $result['posting_strategy']['series_ideas']) }}
                            @else
                                {{ $result['posting_strategy']['series_ideas'] }}
                            @endif
                        </div>
                    </div>
                    @endif
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
                $nextSteps = config('appaitools.enterprise_tools.ig-carousel-builder.next_steps', []);
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
