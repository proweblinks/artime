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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#ec4899,#e11d48);">
                    <i class="fa-light fa-palette" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Aesthetic & Brand Analyzer</h2>
                    <p>Analyze visual consistency and brand cohesion</p>
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
                <label class="aith-label">Brand Style (optional)</label>
                <input type="text" wire:model="brandStyle" class="aith-input"
                       placeholder="e.g. minimal, bold, pastel, dark, editorial">
                @error('brandStyle') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-palette"></i> Analyze Aesthetic
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
                <div class="aith-e-loading-title">Analyzing aesthetic and brand...</div>
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
                <span class="aith-e-result-title">Aesthetic & Brand Analysis Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-ig-aesthetic-analyzer', 'Aesthetic-Brand-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-ig-aesthetic-analyzer">

            {{-- Score --}}
            @php $score = $result['aesthetic_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Aesthetic Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent visual brand consistency
                        @elseif($score >= 50) Good aesthetic with room for improvement
                        @else Significant aesthetic improvements needed
                        @endif
                    </div>
                </div>
            </div>

            {{-- Grid Analysis --}}
            @if(isset($result['grid_analysis']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-grid-2"></i> Grid Analysis</div>
                @if(isset($result['grid_analysis']['consistency_score']))
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Consistency Score</span>
                    @php $cs = intval($result['grid_analysis']['consistency_score']); @endphp
                    <span class="aith-e-score-circle {{ $cs >= 80 ? 'aith-e-score-high' : ($cs >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}" style="width:2.5rem;height:2.5rem;font-size:0.85rem;">
                        {{ $cs }}
                    </span>
                </div>
                @endif
                @if(!empty($result['grid_analysis']['color_palette']))
                <div style="margin-bottom:0.75rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;">Color Palette</span>
                    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                        @foreach($result['grid_analysis']['color_palette'] as $color)
                        <span style="display:inline-flex;align-items:center;gap:0.375rem;font-size:0.8rem;padding:0.25rem 0.5rem;border-radius:9999px;background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.7);">
                            <span style="width:0.75rem;height:0.75rem;border-radius:50%;background:{{ $color }};display:inline-block;flex-shrink:0;"></span>
                            {{ $color }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
                <div class="aith-e-grid-2">
                    @if(isset($result['grid_analysis']['dominant_style']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Dominant Style</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['grid_analysis']['dominant_style'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['grid_analysis']['mood']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Mood</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['grid_analysis']['mood'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Brand Cohesion --}}
            @if(isset($result['brand_cohesion']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-fingerprint"></i> Brand Cohesion</div>
                <div class="aith-e-grid-2">
                    @if(isset($result['brand_cohesion']['logo_presence']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Logo Presence</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['brand_cohesion']['logo_presence'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['brand_cohesion']['font_consistency']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Font Consistency</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['brand_cohesion']['font_consistency'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['brand_cohesion']['color_adherence']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Color Adherence</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['brand_cohesion']['color_adherence'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['brand_cohesion']['voice_consistency']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Voice Consistency</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['brand_cohesion']['voice_consistency'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Content Mix --}}
            @if(isset($result['content_mix']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-pie"></i> Content Mix</div>
                <div class="aith-e-grid-2">
                    @php
                        $mixItems = [
                            ['key' => 'reels_pct', 'label' => 'Reels', 'color' => '#ec4899'],
                            ['key' => 'carousels_pct', 'label' => 'Carousels', 'color' => '#8b5cf6'],
                            ['key' => 'stories_pct', 'label' => 'Stories', 'color' => '#06b6d4'],
                            ['key' => 'posts_pct', 'label' => 'Posts', 'color' => '#f97316'],
                        ];
                    @endphp
                    @foreach($mixItems as $item)
                    @if(isset($result['content_mix'][$item['key']]))
                    <div class="aith-e-section-card" style="margin-bottom:0;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                            <span style="font-weight:600;color:#fff;font-size:0.85rem;">{{ $item['label'] }}</span>
                            <span style="font-weight:600;color:{{ $item['color'] }};font-size:0.85rem;">{{ $result['content_mix'][$item['key']] }}</span>
                        </div>
                        <div style="width:100%;height:0.375rem;background:rgba(255,255,255,0.06);border-radius:9999px;overflow:hidden;">
                            <div style="height:100%;background:{{ $item['color'] }};border-radius:9999px;width:{{ intval($result['content_mix'][$item['key']]) }}%;transition:width 0.5s;"></div>
                        </div>
                        @if(isset($result['content_mix']['recommended_mix'][$item['key']]))
                        <div style="font-size:0.7rem;color:rgba(255,255,255,0.4);margin-top:0.25rem;">
                            Recommended: {{ $result['content_mix']['recommended_mix'][$item['key']] }}
                        </div>
                        @endif
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Improvement Areas --}}
            @if(!empty($result['improvement_areas']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-line-up"></i> Improvement Areas</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Area</th><th>Current</th><th>Recommended</th><th>Impact</th></tr></thead>
                        <tbody>
                        @foreach($result['improvement_areas'] as $area)
                        <tr>
                            <td style="font-weight:600;color:#ec4899;">{{ $area['area'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $area['current'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.7);">{{ $area['recommended'] ?? '-' }}</td>
                            <td>
                                @php $impact = strtolower($area['impact'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $impact === 'high' ? 'aith-e-tag-high' : ($impact === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $area['impact'] ?? '-' }}</span>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Style Recommendations --}}
            @if(!empty($result['style_recommendations']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-wand-magic-sparkles"></i> Style Recommendations</div>
                <div class="aith-e-grid-2">
                @foreach($result['style_recommendations'] as $rec)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="font-weight:600;color:#fff;font-size:0.875rem;margin-bottom:0.375rem;">{{ $rec['element'] ?? '' }}</div>
                    @if(isset($rec['suggestion']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);margin-bottom:0.375rem;">{{ $rec['suggestion'] }}</div>
                    @endif
                    @if(!empty($rec['examples']))
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($rec['examples'] as $example)
                        <span class="aith-e-pill aith-e-pill-green">{{ $example }}</span>
                        @endforeach
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
                $nextSteps = config('appaitools.enterprise_tools.ig-aesthetic-analyzer.next_steps', []);
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
