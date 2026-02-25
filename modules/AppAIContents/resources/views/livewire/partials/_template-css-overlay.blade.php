{{--
    Template-driven CSS overlay for creative cards.
    Variables expected: $creative, $template (CreativeLayoutTemplate or null), $brandColor
--}}
@php
    $config = $template?->config ?? null;
    $headerText = $creative->header_text ?? '';
    $descText = $creative->description_text ?? '';
    $ctaText = $creative->cta_text ?? '';
    $brandColor = $brandColor ?? ($creative->campaign?->dna?->colors[0] ?? '#DA291C');

    // Color resolver helper
    $resolveColor = function($mode) use ($brandColor) {
        if (str_starts_with($mode, '#')) return $mode;
        return match($mode) {
            'brand_color' => $brandColor,
            'brand_secondary' => $brandColor,
            'brand_light' => $brandColor . '66',
            'accent' => $brandColor . 'cc',
            'light', 'white' => '#ffffff',
            'dark', 'black' => '#111111',
            'muted' => '#888888',
            default => $mode,
        };
    };
@endphp

@if($config)
    <div style="position: relative; width: 100%; height: 100%; overflow: hidden; background: {{ $config['canvas']['background_color'] ?? '#111' }};">
        {{-- Background blocks --}}
        @foreach(($config['background_blocks'] ?? []) as $block)
            <div style="position: absolute;
                left: {{ $block['x_pct'] ?? 0 }}%;
                top: {{ $block['y_pct'] ?? 0 }}%;
                width: {{ $block['width_pct'] ?? 100 }}%;
                height: {{ $block['height_pct'] ?? 100 }}%;
                background: {{ $resolveColor($block['color_mode'] ?? 'brand_color') }};
                opacity: {{ ($block['opacity'] ?? 100) / 100 }};
                @if(isset($block['border_radius_pct']))border-radius: {{ $block['border_radius_pct'] }}%;@endif
            "></div>
        @endforeach

        {{-- Image region --}}
        @if(isset($config['image_region']))
            @php $ir = $config['image_region']; @endphp
            <div style="position: absolute;
                left: {{ $ir['x_pct'] ?? 0 }}%;
                top: {{ $ir['y_pct'] ?? 0 }}%;
                width: {{ $ir['width_pct'] ?? 100 }}%;
                height: {{ $ir['height_pct'] ?? 100 }}%;
                overflow: hidden;">
                @if($creative->image_url)
                    <img src="{{ $creative->image_url }}" alt=""
                         style="width: 100%; height: 100%; object-fit: {{ $ir['fit'] ?? 'cover' }}; object-position: {{ $ir['gravity'] ?? 'center' }}; display: block;">
                @else
                    <div class="cs-skeleton" style="width: 100%; height: 100%;"></div>
                @endif
            </div>
        @endif

        {{-- Gradient overlay --}}
        @if(isset($config['overlay']) && ($config['overlay']['type'] ?? '') === 'gradient')
            @php
                $ov = $config['overlay'];
                $dir = match($ov['gradient_direction'] ?? 'to_bottom') {
                    'to_bottom' => 'to bottom',
                    'to_top' => 'to top',
                    'to_right' => 'to right',
                    'to_left' => 'to left',
                    default => 'to bottom',
                };
                $stops = collect($ov['gradient_stops'] ?? [])->map(function($s) {
                    $hex = $s['color'] ?? '#000000';
                    $opacity = ($s['opacity'] ?? 0) / 100;
                    $pos = $s['position_pct'] ?? 0;
                    // Convert hex to rgba
                    $r = hexdec(substr($hex, 1, 2));
                    $g = hexdec(substr($hex, 3, 2));
                    $b = hexdec(substr($hex, 5, 2));
                    return "rgba({$r},{$g},{$b},{$opacity}) {$pos}%";
                })->join(', ');
            @endphp
            <div style="position: absolute; inset: 0; background: linear-gradient({{ $dir }}, {{ $stops }}); pointer-events: none;"></div>
        @endif

        {{-- Decorations --}}
        @foreach(($config['decorations'] ?? []) as $dec)
            @if(($dec['type'] ?? '') === 'bar')
                <div style="position: absolute;
                    left: {{ $dec['x_pct'] ?? 0 }}%;
                    top: {{ $dec['y_pct'] ?? 0 }}%;
                    width: {{ $dec['width_pct'] ?? 10 }}%;
                    height: {{ $dec['height_pct'] ?? 0.4 }}%;
                    background: {{ $resolveColor($dec['color_mode'] ?? 'accent') }};
                    opacity: {{ ($dec['opacity'] ?? 100) / 100 }};
                "></div>
            @elseif(($dec['type'] ?? '') === 'circle')
                @php
                    $r = ($dec['radius_pct'] ?? 5);
                    $d = $r * 2;
                @endphp
                <div style="position: absolute;
                    left: {{ ($dec['cx_pct'] ?? 50) - $r }}%;
                    top: {{ ($dec['cy_pct'] ?? 50) - $r }}%;
                    width: {{ $d }}%;
                    aspect-ratio: 1;
                    border-radius: 50%;
                    background: {{ $resolveColor($dec['color_mode'] ?? 'accent') }};
                    opacity: {{ ($dec['opacity'] ?? 100) / 100 }};
                "></div>
            @elseif(($dec['type'] ?? '') === 'wave_separator')
                @php
                    $wY = $dec['y_pct'] ?? 50;
                    $amp = $dec['amplitude_pct'] ?? 3;
                    $freq = $dec['frequency'] ?? 2;
                    $wColor = $resolveColor($dec['color_mode'] ?? 'brand_color');
                    // Generate SVG wave path
                    $points = "M0,{$amp}";
                    for ($i = 0; $i <= 100; $i += 2) {
                        $wy = $amp + $amp * sin($i / 100 * $freq * 2 * M_PI);
                        $points .= " L{$i},{$wy}";
                    }
                    $svgH = $amp * 2 + ($dec['fill_height_pct'] ?? 2) * 2;
                    $points .= " L100,{$svgH} L0,{$svgH} Z";
                @endphp
                <div style="position: absolute; left: 0; top: {{ $wY }}%; width: 100%; height: {{ $svgH }}%; overflow: hidden;">
                    <svg viewBox="0 0 100 {{ $svgH }}" preserveAspectRatio="none" style="width: 100%; height: 100%;">
                        <path d="{{ $points }}" fill="{{ $wColor }}" opacity="{{ ($dec['opacity'] ?? 100) / 100 }}"/>
                    </svg>
                </div>
            @elseif(($dec['type'] ?? '') === 'triangle')
                @php
                    $triColor = $resolveColor($dec['color_mode'] ?? 'brand_color');
                    $x1 = $dec['x1_pct'] ?? 0; $y1 = $dec['y1_pct'] ?? 0;
                    $x2 = $dec['x2_pct'] ?? 40; $y2 = $dec['y2_pct'] ?? 0;
                    $x3 = $dec['x3_pct'] ?? 0; $y3 = $dec['y3_pct'] ?? 30;
                @endphp
                <svg style="position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none;" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <polygon points="{{ $x1 }},{{ $y1 }} {{ $x2 }},{{ $y2 }} {{ $x3 }},{{ $y3 }}" fill="{{ $triColor }}" opacity="{{ ($dec['opacity'] ?? 100) / 100 }}"/>
                </svg>
            @endif
        @endforeach

        {{-- Text regions --}}
        @if(isset($config['text_regions']))
            @foreach($config['text_regions'] as $key => $region)
                @php
                    $text = match($key) {
                        'header' => $headerText,
                        'description' => $descText,
                        'cta' => $ctaText,
                        default => '',
                    };
                    if (empty(trim($text))) continue;

                    $color = $resolveColor($region['color_mode'] ?? 'light');
                    $align = $region['alignment'] ?? 'left';
                    $sizeScale = $region['size_scale'] ?? 1.0;
                    $weight = match($region['weight'] ?? 'normal') {
                        'bold' => 700, 'semibold' => 600, default => 400,
                    };
                    $transform = $region['transform'] ?? 'none';
                    $baseSize = 15; // base px for card thumbnail
                    $fontSize = round($baseSize * $sizeScale, 1);
                    $lineHeight = $region['line_height_scale'] ?? 1.2;
                    $isPill = ($region['style'] ?? null) === 'pill';
                    $shadow = $region['shadow'] ?? null;
                    $shadowCss = $shadow ? "text-shadow: {$shadow['x']}px {$shadow['y']}px {$shadow['blur']}px rgba(0,0,0," . (($shadow['opacity'] ?? 50) / 100) . ");" : '';
                @endphp

                @if($isPill)
                    <div style="position: absolute;
                        left: {{ $region['x_pct'] ?? 5 }}%;
                        top: {{ $region['y_pct'] ?? 50 }}%;
                        width: {{ $region['width_pct'] ?? 50 }}%;
                        text-align: {{ $align }};">
                        <span dir="auto" style="display: inline-block;
                            padding: 4px 14px;
                            background: {{ $resolveColor($region['pill_bg_color_mode'] ?? 'light') }};
                            color: {{ $resolveColor($region['pill_text_color_mode'] ?? 'brand_color') }};
                            border-radius: 20px;
                            font-size: {{ round($baseSize * 0.45, 1) }}px;
                            font-weight: 600;">{{ $text }}</span>
                    </div>
                @else
                    <div dir="auto" style="position: absolute;
                        left: {{ $region['x_pct'] ?? 5 }}%;
                        top: {{ $region['y_pct'] ?? 50 }}%;
                        width: {{ $region['width_pct'] ?? 90 }}%;
                        color: {{ $color }};
                        font-size: {{ $fontSize }}px;
                        font-weight: {{ $weight }};
                        line-height: {{ $lineHeight }};
                        text-align: {{ $align }};
                        text-transform: {{ $transform }};
                        {{ $shadowCss }}
                    ">{{ $text }}</div>
                @endif
            @endforeach
        @endif
    </div>
@endif
