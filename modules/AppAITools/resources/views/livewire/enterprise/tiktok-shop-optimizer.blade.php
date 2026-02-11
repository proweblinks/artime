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
                    <i class="fa-light fa-shop" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>TikTok Shop Optimizer</h2>
                    <p>Optimize product listings and affiliate strategies for TikTok Shop</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">TikTok Profile or Shop URL</label>
                <input type="text" wire:model="profile" class="aith-input"
                       placeholder="@username or shop URL">
                @error('profile') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Product Type (optional)</label>
                <input type="text" wire:model="productType" class="aith-input"
                       placeholder="e.g. beauty, electronics, fashion">
                @error('productType') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Price Range (optional)</label>
                <input type="text" wire:model="priceRange" class="aith-input"
                       placeholder="e.g. $10-50, $50-100">
                @error('priceRange') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-shop"></i> Optimize Shop
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
                <div class="aith-e-loading-title">Analyzing TikTok Shop...</div>
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
                <span class="aith-e-result-title">TikTok Shop Analysis</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-tiktok-shop-optimizer', 'TikTok-Shop-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-tiktok-shop-optimizer">

            {{-- Score --}}
            @php $score = $result['shop_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Shop Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent shop performance and optimization
                        @elseif($score >= 50) Good shop potential - optimization can boost sales
                        @else Significant shop improvements needed for better conversions
                        @endif
                    </div>
                </div>
            </div>

            {{-- Shop Overview --}}
            @if(isset($result['shop_overview']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-store"></i> Shop Overview</div>
                <div class="aith-e-grid-2">
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Estimated Revenue</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['shop_overview']['estimated_revenue'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Product Count</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;">{{ $result['shop_overview']['product_count'] ?? '-' }}</div>
                    </div>
                </div>
                <div class="aith-e-grid-2" style="margin-top:0.5rem;">
                    <div class="aith-e-summary-card aith-e-summary-card-orange">
                        <div class="aith-e-summary-label">Top Category</div>
                        <div class="aith-e-summary-value" style="color:#fdba74;">{{ $result['shop_overview']['top_category'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Conversion Rate</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['shop_overview']['conversion_rate'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Product Recommendations --}}
            @if(!empty($result['product_recommendations']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-boxes-stacked"></i> Product Recommendations</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Product</th><th>Category</th><th>Price Range</th><th>Demand</th><th>Competition</th><th>Profit Margin</th></tr></thead>
                        <tbody>
                        @foreach($result['product_recommendations'] as $prod)
                        <tr>
                            <td style="font-weight:600;color:#fb923c;">{{ $prod['product'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $prod['category'] ?? '-' }}</td>
                            <td>{{ $prod['price_range'] ?? '-' }}</td>
                            <td>
                                @php $demand = strtolower($prod['demand_level'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $demand === 'high' ? 'aith-e-tag-high' : ($demand === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $prod['demand_level'] ?? '-' }}</span>
                            </td>
                            <td>
                                @php $comp = strtolower($prod['competition'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $comp === 'low' ? 'aith-e-tag-high' : ($comp === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $prod['competition'] ?? '-' }}</span>
                            </td>
                            <td style="font-weight:600;color:#22c55e;">{{ $prod['profit_margin'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Affiliate Opportunities --}}
            @if(!empty($result['affiliate_opportunities']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-link"></i> Affiliate Opportunities</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Product</th><th>Commission Rate</th><th>Avg Sales</th><th>Content Angle</th></tr></thead>
                        <tbody>
                        @foreach($result['affiliate_opportunities'] as $aff)
                        <tr>
                            <td style="font-weight:600;color:#fb923c;">{{ $aff['product'] ?? '' }}</td>
                            <td style="font-weight:600;color:#22c55e;">{{ $aff['commission_rate'] ?? '-' }}</td>
                            <td>{{ $aff['avg_sales'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $aff['content_angle'] ?? '' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Content Strategy --}}
            @if(!empty($result['content_strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-video"></i> Content Strategy</div>
                <div class="aith-e-grid-2">
                @foreach($result['content_strategy'] as $strategy)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.875rem;">{{ $strategy['content_type'] ?? '' }}</span>
                        @if(isset($strategy['estimated_conversion']))
                        <span class="aith-e-pill aith-e-pill-green">{{ $strategy['estimated_conversion'] }}</span>
                        @endif
                    </div>
                    @if(isset($strategy['product_showcase']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.25rem;">
                        <strong style="color:rgba(255,255,255,0.6);">Showcase:</strong> {{ $strategy['product_showcase'] }}
                    </div>
                    @endif
                    @if(isset($strategy['example']))
                    <div style="font-size:0.8rem;color:#fb923c;">
                        <strong style="color:rgba(255,255,255,0.6);">Example:</strong> {{ $strategy['example'] }}
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Pricing Optimization --}}
            @if(isset($result['pricing_optimization']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-tags"></i> Pricing Optimization</div>
                @if(isset($result['pricing_optimization']['current_assessment']))
                <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-bottom:0.75rem;padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;">
                    {{ $result['pricing_optimization']['current_assessment'] }}
                </div>
                @endif
                @if(!empty($result['pricing_optimization']['recommendations']))
                <ul class="aith-e-list">
                    @foreach($result['pricing_optimization']['recommendations'] as $rec)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $rec }}</li>
                    @endforeach
                </ul>
                @endif
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
