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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#f97316,#ef4444);">
                    <i class="fa-light fa-bag-shopping" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Shop Listing Optimizer</h2>
                    <p>Optimize Facebook Shop listings for maximum conversions</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">Facebook Shop URL</label>
                <input type="text" wire:model="pageUrl" class="aith-input"
                       placeholder="https://facebook.com/yourpage/shop">
                @error('pageUrl') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Product Type (optional)</label>
                <input type="text" wire:model="productType" class="aith-input"
                       placeholder="e.g. clothing, accessories, beauty, home decor">
                @error('productType') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Price Range (optional)</label>
                <input type="text" wire:model="priceRange" class="aith-input"
                       placeholder="e.g. $10-50, $50-100, $100+">
                @error('priceRange') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-bag-shopping"></i> Analyze Shop
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
                <div class="aith-e-loading-title">Analyzing shop listings...</div>
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
                <span class="aith-e-result-title">Shop Optimization Analysis</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-fb-shop-optimizer', 'Shop-Optimization')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-fb-shop-optimizer">

            {{-- Score --}}
            @php $score = $result['shop_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Shop Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent shop optimization - strong conversion potential
                        @elseif($score >= 50) Good shop setup - optimization can boost conversions significantly
                        @else Significant shop optimization needed - focus on listings and presentation
                        @endif
                    </div>
                </div>
            </div>

            {{-- Shop Overview --}}
            @if(isset($result['shop_overview']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-store"></i> Shop Overview</div>
                <div class="aith-e-grid-3">
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Estimated Revenue</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['shop_overview']['estimated_revenue'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Product Count</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;">{{ $result['shop_overview']['product_count'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Top Category</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['shop_overview']['top_category'] ?? '-' }}</div>
                    </div>
                </div>
                <div class="aith-e-grid-2" style="margin-top:0.5rem;">
                    <div class="aith-e-summary-card aith-e-summary-card-orange">
                        <div class="aith-e-summary-label">Conversion Rate</div>
                        <div class="aith-e-summary-value" style="color:#fdba74;">{{ $result['shop_overview']['conversion_rate'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Shop Rating</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['shop_overview']['shop_rating'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Listing Optimization --}}
            @if(!empty($result['listing_optimization']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-list-check"></i> Listing Optimization</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Area</th><th>Current Assessment</th><th>Improvement</th><th>Impact</th></tr></thead>
                        <tbody>
                        @foreach($result['listing_optimization'] as $listing)
                        <tr>
                            <td style="font-weight:600;color:#fb923c;">{{ $listing['area'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">{{ $listing['current_assessment'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.7);">{{ $listing['improvement'] ?? '-' }}</td>
                            <td>
                                @php $impact = strtolower($listing['impact'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $impact === 'high' ? 'aith-e-tag-high' : ($impact === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $listing['impact'] ?? '-' }}</span>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Product Recommendations --}}
            @if(!empty($result['product_recommendations']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-boxes-stacked"></i> Product Recommendations</div>
                <div class="aith-e-grid-2">
                @foreach($result['product_recommendations'] as $prod)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#fb923c;font-size:0.9rem;">{{ $prod['product'] ?? '' }}</span>
                        @if(isset($prod['category']))
                        <span style="font-size:0.7rem;padding:0.125rem 0.375rem;border-radius:0.25rem;background:rgba(249,115,22,0.1);color:#f97316;">{{ $prod['category'] }}</span>
                        @endif
                    </div>
                    @if(isset($prod['price_range']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);margin-bottom:0.25rem;">
                        <strong style="color:rgba(255,255,255,0.7);">Price Range:</strong> {{ $prod['price_range'] }}
                    </div>
                    @endif
                    <div style="display:flex;gap:0.375rem;flex-wrap:wrap;margin-bottom:0.375rem;">
                        @if(isset($prod['demand']))
                        @php $demand = strtolower($prod['demand'] ?? ''); @endphp
                        <span class="aith-e-tag {{ $demand === 'high' ? 'aith-e-tag-high' : ($demand === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $prod['demand'] }} demand</span>
                        @endif
                        @if(isset($prod['competition']))
                        @php $comp = strtolower($prod['competition'] ?? ''); @endphp
                        <span class="aith-e-tag {{ $comp === 'low' ? 'aith-e-tag-high' : ($comp === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $prod['competition'] }} competition</span>
                        @endif
                    </div>
                    @if(isset($prod['margin']))
                    <div style="font-size:0.8rem;color:#22c55e;margin-bottom:0.25rem;">
                        <strong style="color:rgba(255,255,255,0.6);">Margin:</strong> {{ $prod['margin'] }}
                    </div>
                    @endif
                    @if(isset($prod['content_angle']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">
                        <strong style="color:rgba(255,255,255,0.6);">Content Angle:</strong> {{ $prod['content_angle'] }}
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Conversion Funnel --}}
            @if(isset($result['conversion_funnel']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-filter"></i> Conversion Funnel</div>
                <div class="aith-e-grid-2">
                    {{-- Awareness --}}
                    <div class="aith-e-section-card" style="margin-bottom:0;border-left:3px solid #3b82f6;">
                        <div style="font-weight:600;color:#93c5fd;font-size:0.875rem;margin-bottom:0.375rem;">
                            <i class="fa-light fa-eye" style="margin-right:0.25rem;"></i> Awareness
                        </div>
                        @if(!empty($result['conversion_funnel']['awareness']))
                        @foreach($result['conversion_funnel']['awareness'] as $item)
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);padding:0.125rem 0;">
                            <i class="fa-solid fa-circle" style="font-size:0.25rem;margin-right:0.375rem;vertical-align:middle;"></i>{{ $item }}
                        </div>
                        @endforeach
                        @endif
                    </div>
                    {{-- Consideration --}}
                    <div class="aith-e-section-card" style="margin-bottom:0;border-left:3px solid #8b5cf6;">
                        <div style="font-weight:600;color:#c4b5fd;font-size:0.875rem;margin-bottom:0.375rem;">
                            <i class="fa-light fa-magnifying-glass" style="margin-right:0.25rem;"></i> Consideration
                        </div>
                        @if(!empty($result['conversion_funnel']['consideration']))
                        @foreach($result['conversion_funnel']['consideration'] as $item)
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);padding:0.125rem 0;">
                            <i class="fa-solid fa-circle" style="font-size:0.25rem;margin-right:0.375rem;vertical-align:middle;"></i>{{ $item }}
                        </div>
                        @endforeach
                        @endif
                    </div>
                    {{-- Purchase --}}
                    <div class="aith-e-section-card" style="margin-bottom:0;border-left:3px solid #22c55e;">
                        <div style="font-weight:600;color:#86efac;font-size:0.875rem;margin-bottom:0.375rem;">
                            <i class="fa-light fa-cart-shopping" style="margin-right:0.25rem;"></i> Purchase
                        </div>
                        @if(!empty($result['conversion_funnel']['purchase']))
                        @foreach($result['conversion_funnel']['purchase'] as $item)
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);padding:0.125rem 0;">
                            <i class="fa-solid fa-circle" style="font-size:0.25rem;margin-right:0.375rem;vertical-align:middle;"></i>{{ $item }}
                        </div>
                        @endforeach
                        @endif
                    </div>
                    {{-- Retention --}}
                    <div class="aith-e-section-card" style="margin-bottom:0;border-left:3px solid #f59e0b;">
                        <div style="font-weight:600;color:#fbbf24;font-size:0.875rem;margin-bottom:0.375rem;">
                            <i class="fa-light fa-arrows-rotate" style="margin-right:0.25rem;"></i> Retention
                        </div>
                        @if(!empty($result['conversion_funnel']['retention']))
                        @foreach($result['conversion_funnel']['retention'] as $item)
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);padding:0.125rem 0;">
                            <i class="fa-solid fa-circle" style="font-size:0.25rem;margin-right:0.375rem;vertical-align:middle;"></i>{{ $item }}
                        </div>
                        @endforeach
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Pricing Strategy --}}
            @if(isset($result['pricing_strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-money-check-dollar"></i> Pricing Strategy</div>
                @if(isset($result['pricing_strategy']['current_assessment']))
                <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-bottom:0.75rem;padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;">
                    {{ $result['pricing_strategy']['current_assessment'] }}
                </div>
                @endif
                @if(!empty($result['pricing_strategy']['recommendations']))
                <div style="margin-bottom:0.75rem;">
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.25rem;font-weight:600;">Recommendations</div>
                    <ul class="aith-e-list">
                        @foreach($result['pricing_strategy']['recommendations'] as $rec)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $rec }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if(!empty($result['pricing_strategy']['bundle_ideas']))
                <div style="margin-bottom:0.75rem;">
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.25rem;font-weight:600;">Bundle Ideas</div>
                    <ul class="aith-e-list">
                        @foreach($result['pricing_strategy']['bundle_ideas'] as $bundle)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $bundle }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if(isset($result['pricing_strategy']['discount_strategy']))
                <div style="padding:0.5rem;background:rgba(249,115,22,0.08);border-radius:0.375rem;border-left:3px solid #f97316;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.25rem;">Discount Strategy</span>
                    <div style="font-size:0.85rem;color:#fb923c;">{{ $result['pricing_strategy']['discount_strategy'] }}</div>
                </div>
                @endif
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.fb-shop-optimizer.next_steps', []);
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
