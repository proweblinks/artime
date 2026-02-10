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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#6366f1,#9333ea);">
                    <i class="fa-light fa-cart-shopping" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Digital Product Architect</h2>
                    <p>Design and price digital products for your audience</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">YouTube Channel URL</label>
                <input type="url" wire:model="url" class="aith-input"
                       placeholder="https://youtube.com/@channel">
                @error('url') <div class="aith-e-field-error">{{ $message }}</div> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Your Expertise (optional)</label>
                <input type="text" wire:model="expertise" class="aith-input"
                       placeholder="e.g. web development, photography, fitness coaching">
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-cart-shopping"></i> Design Products
                    <span style="margin-left:0.5rem;opacity:0.6;font-size:0.8rem;">3 credits</span>
                </span>
                <span wire:loading wire:target="analyze">
                    <i class="fa-light fa-spinner-third fa-spin"></i> Analyzing...
                </span>
            </button>
            @endif

            @if($isLoading)
            {{-- Loading Steps --}}
            <div class="aith-e-loading" x-data="{ step: 0 }" x-init="
                let steps = {{ count($loadingSteps) }};
                let interval = setInterval(() => { if(step < steps - 1) step++; }, 2500);
                $wire.on('loadingComplete', () => clearInterval(interval));
            ">
                <div class="aith-e-loading-title">Designing digital products...</div>
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
                <span class="aith-e-result-title">Digital Product Blueprint</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-digital-product-architect', 'Digital-Product-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-digital-product-architect">

            {{-- Score --}}
            @php $score = $result['product_readiness_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Product Readiness Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent - your audience is ready for digital products
                        @elseif($score >= 50) Good potential - build audience trust with free content first
                        @else Early stage - focus on growing engagement before launching products
                        @endif
                    </div>
                </div>
            </div>

            {{-- Creator Profile --}}
            @if(isset($result['creator_profile']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-user"></i> Creator Profile</div>
                <div class="aith-e-grid-2">
                    @foreach($result['creator_profile'] as $key => $val)
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

            {{-- Product Ideas --}}
            @if(!empty($result['product_ideas']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-box-open"></i> Product Ideas</div>
                @foreach($result['product_ideas'] as $product)
                <div class="aith-e-section-card" style="margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $product['name'] ?? '' }}</span>
                        <div style="display:flex;gap:0.5rem;align-items:center;">
                            @if(isset($product['type']))
                            <span class="aith-e-tag" style="background:rgba(99,102,241,0.15);color:#a5b4fc;">{{ $product['type'] }}</span>
                            @endif
                            @if(isset($product['difficulty']))
                            @php $diff = strtolower($product['difficulty']); @endphp
                            <span class="aith-e-tag {{ $diff === 'easy' ? 'aith-e-tag-easy' : ($diff === 'hard' ? 'aith-e-tag-hard' : 'aith-e-tag-medium') }}">{{ $product['difficulty'] }}</span>
                            @endif
                        </div>
                    </div>
                    @if(isset($product['description']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.5rem;">{{ $product['description'] }}</div>
                    @endif
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.5rem;">
                        @if(isset($product['suggested_price']))
                        <span class="aith-e-pill aith-e-pill-blue"><i class="fa-light fa-tag" style="font-size:0.65rem;"></i> {{ $product['suggested_price'] }}</span>
                        @endif
                        @if(isset($product['estimated_monthly_revenue']))
                        <span class="aith-e-pill aith-e-pill-green"><i class="fa-light fa-chart-line-up" style="font-size:0.65rem;"></i> {{ $product['estimated_monthly_revenue'] }}/mo</span>
                        @endif
                        @if(isset($product['development_time']))
                        <span class="aith-e-pill aith-e-pill-orange"><i class="fa-light fa-clock" style="font-size:0.65rem;"></i> {{ $product['development_time'] }}</span>
                        @endif
                    </div>
                    @if(!empty($product['content_outline']))
                    <div>
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Content Outline</span>
                        <ul class="aith-e-list" style="margin-top:0.25rem;">
                            @foreach($product['content_outline'] as $item)
                            <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Launch Plan --}}
            @if(!empty($result['launch_plan']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-rocket"></i> Launch Plan</div>
                @foreach($result['launch_plan'] as $idx => $phase)
                <div style="display:flex;gap:0.75rem;padding:0.75rem 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                    <span class="aith-e-step-badge">{{ $idx + 1 }}</span>
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                            <span style="font-weight:600;color:#fff;font-size:0.875rem;">{{ $phase['phase'] ?? '' }}</span>
                            @if(isset($phase['duration']))
                            <span class="aith-e-tag" style="background:rgba(99,102,241,0.15);color:#a5b4fc;">{{ $phase['duration'] }}</span>
                            @endif
                        </div>
                        @if(isset($phase['goal']))
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.375rem;">{{ $phase['goal'] }}</div>
                        @endif
                        @if(!empty($phase['actions']))
                        <ul class="aith-e-list">
                            @foreach($phase['actions'] as $action)
                            <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $action }}</li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Pricing Strategy --}}
            @if(isset($result['pricing_strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-tag"></i> Pricing Strategy</div>
                <div class="aith-e-grid-2">
                    @if(isset($result['pricing_strategy']['anchor_price']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Anchor Price</span>
                        <div style="font-size:0.875rem;color:#6366f1;font-weight:600;margin-top:0.125rem;">{{ $result['pricing_strategy']['anchor_price'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['pricing_strategy']['discount_strategy']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Discount Strategy</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['pricing_strategy']['discount_strategy'] }}</div>
                    </div>
                    @endif
                </div>
                @if(!empty($result['pricing_strategy']['bundle_ideas']))
                <div style="margin-top:0.75rem;">
                    @if(count($result['pricing_strategy']['bundle_ideas']) >= 3)
                    {{-- 3-tier visual pricing --}}
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Bundle Tiers</span>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem;margin-top:0.375rem;">
                        @foreach(array_slice($result['pricing_strategy']['bundle_ideas'], 0, 3) as $tierIdx => $bundle)
                        <div style="padding:0.75rem;border-radius:0.5rem;background:rgba({{ $tierIdx === 1 ? '99,102,241,0.12' : '255,255,255,0.03' }});border:1px solid rgba({{ $tierIdx === 1 ? '99,102,241,0.3' : '255,255,255,0.06' }});text-align:center;{{ $tierIdx === 1 ? 'transform:scale(1.03);' : '' }}">
                            <div style="font-size:0.65rem;color:rgba(255,255,255,0.35);text-transform:uppercase;margin-bottom:0.25rem;">{{ $tierIdx === 0 ? 'Basic' : ($tierIdx === 1 ? 'Popular' : 'Premium') }}</div>
                            <div style="font-size:0.8rem;color:#fff;font-weight:600;">{{ $bundle }}</div>
                        </div>
                        @endforeach
                    </div>
                    @if(count($result['pricing_strategy']['bundle_ideas']) > 3)
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-top:0.5rem;">
                        @foreach(array_slice($result['pricing_strategy']['bundle_ideas'], 3) as $bundle)
                        <span class="aith-e-tag" style="background:rgba(99,102,241,0.15);color:#a5b4fc;">{{ $bundle }}</span>
                        @endforeach
                    </div>
                    @endif
                    @else
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Bundle Ideas</span>
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-top:0.375rem;">
                        @foreach($result['pricing_strategy']['bundle_ideas'] as $bundle)
                        <span class="aith-e-tag" style="background:rgba(99,102,241,0.15);color:#a5b4fc;">{{ $bundle }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endif
            </div>
            @endif

            {{-- Platform Recommendations --}}
            @if(!empty($result['platform_recommendations']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-globe"></i> Platform Recommendations</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Platform</th><th>Best For</th><th>Fee Structure</th></tr></thead>
                        <tbody>
                        @foreach($result['platform_recommendations'] as $plat)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $plat['platform'] ?? '' }}</td>
                            <td>{{ $plat['best_for'] ?? '' }}</td>
                            <td>{{ $plat['fee_structure'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
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
