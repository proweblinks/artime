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
                    <h2>Shopping Tag Optimizer</h2>
                    <p>Optimize product tagging strategy for Instagram Shopping</p>
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
                    <i class="fa-light fa-bag-shopping"></i> Optimize Shopping
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
                <div class="aith-e-loading-title">Analyzing shopping strategy...</div>
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
                <span class="aith-e-result-title">Shopping Optimization Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-ig-shopping-optimizer', 'Shopping-Optimization')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-ig-shopping-optimizer">

            {{-- Score --}}
            @php $score = $result['shop_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Shop Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent shopping optimization
                        @elseif($score >= 50) Good shopping strategy with improvements
                        @else Significant shopping optimization needed
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
                        <div class="aith-e-summary-label">Product Visibility</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;">{{ $result['shop_overview']['product_visibility'] ?? '-' }}</div>
                    </div>
                </div>
                <div class="aith-e-grid-2" style="margin-top:0.5rem;">
                    <div class="aith-e-summary-card aith-e-summary-card-orange">
                        <div class="aith-e-summary-label">Tag Usage</div>
                        <div class="aith-e-summary-value" style="color:#fdba74;">{{ $result['shop_overview']['tag_usage'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Conversion Rate</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['shop_overview']['conversion_rate'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Tagging Strategy --}}
            @if(!empty($result['tagging_strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-tags"></i> Tagging Strategy</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Content Type</th><th>Tag Placement</th><th>Products/Post</th><th>Best Practices</th></tr></thead>
                        <tbody>
                        @foreach($result['tagging_strategy'] as $strategy)
                        <tr>
                            <td style="font-weight:600;color:#f97316;">{{ $strategy['content_type'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.7);">{{ $strategy['tag_placement'] ?? '-' }}</td>
                            <td style="font-weight:600;color:#fff;">{{ $strategy['products_per_post'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $strategy['best_practices'] ?? '-' }}</td>
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
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Product</th><th>Category</th><th>Price Range</th><th>Demand</th><th>Competition</th><th>Margin</th></tr></thead>
                        <tbody>
                        @foreach($result['product_recommendations'] as $prod)
                        <tr>
                            <td style="font-weight:600;color:#fb923c;">{{ $prod['product'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $prod['category'] ?? '-' }}</td>
                            <td>{{ $prod['price_range'] ?? '-' }}</td>
                            <td>
                                @php $demand = strtolower($prod['demand'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $demand === 'high' ? 'aith-e-tag-high' : ($demand === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $prod['demand'] ?? '-' }}</span>
                            </td>
                            <td>
                                @php $comp = strtolower($prod['competition'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $comp === 'low' ? 'aith-e-tag-high' : ($comp === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $prod['competition'] ?? '-' }}</span>
                            </td>
                            <td style="font-weight:600;color:#22c55e;">{{ $prod['margin'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Content Integration --}}
            @if(!empty($result['content_integration']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-puzzle-piece"></i> Content Integration</div>
                <div class="aith-e-grid-2">
                @foreach($result['content_integration'] as $integration)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.375rem;">
                        @if(isset($integration['format']))
                        <span style="font-size:0.75rem;padding:0.125rem 0.375rem;border-radius:0.25rem;background:rgba(249,115,22,0.1);color:#f97316;">{{ $integration['format'] }}</span>
                        @endif
                    </div>
                    @if(isset($integration['shopping_feature']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);margin-bottom:0.25rem;">
                        <strong style="color:rgba(255,255,255,0.7);">Feature:</strong> {{ $integration['shopping_feature'] }}
                    </div>
                    @endif
                    @if(isset($integration['conversion_tip']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.25rem;">
                        <strong style="color:rgba(255,255,255,0.6);">Conversion Tip:</strong> {{ $integration['conversion_tip'] }}
                    </div>
                    @endif
                    @if(isset($integration['example']))
                    <div style="font-size:0.8rem;color:#fb923c;">
                        <strong style="color:rgba(255,255,255,0.6);">Example:</strong> {{ $integration['example'] }}
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
                <div class="aith-e-section-card-title"><i class="fa-light fa-money-check-dollar"></i> Pricing Optimization</div>
                @if(isset($result['pricing_optimization']['assessment']))
                <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-bottom:0.75rem;padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;">
                    {{ $result['pricing_optimization']['assessment'] }}
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

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.ig-shopping-optimizer.next_steps', []);
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
