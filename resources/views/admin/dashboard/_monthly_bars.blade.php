@php
    /**
     * @var array $bars   monthly trend data
     * @var string $color 'purple'(system) | 'green'(google)
     */
    $isGreen = ($color ?? 'purple') === 'green';
    $maxCount = collect($bars)->max('count') ?: 1;
    $countColor = $isGreen ? '#059669' : '#667eea';
    $barStyle = $isGreen ? 'background: linear-gradient(180deg, #10b981, #059669);' : '';
@endphp
@foreach($bars as $m)
    @php
        $height = max(4, ($m['count'] / $maxCount) * 140);
        $tip = $m['label'] . '：' . $m['count'] . '件';
        if ($m['avg_rating'] !== null) {
            $tip .= '（平均' . number_format($m['avg_rating'], 1) . '★）';
        }
        if ($m['diff'] !== null) {
            $tip .= ' / 前月比 ' . ($m['diff'] >= 0 ? '+' : '') . $m['diff'] . '件';
        }
    @endphp
    <div class="daily-bar-wrapper">
        @if($m['diff'] === null)
            <span class="daily-bar-diff flat">―</span>
        @elseif($m['diff'] > 0)
            <span class="daily-bar-diff up">+{{ $m['diff'] }}</span>
        @elseif($m['diff'] < 0)
            <span class="daily-bar-diff down">{{ $m['diff'] }}</span>
        @else
            <span class="daily-bar-diff flat">±0</span>
        @endif
        <span class="daily-bar-count" style="color: {{ $countColor }};">{{ $m['count'] }}</span>
        <div class="daily-bar" style="height: {{ $height }}px;{{ $barStyle ? ' ' . $barStyle : '' }}">
            <div class="daily-tooltip">{{ $tip }}</div>
        </div>
        <span class="daily-bar-date">{{ $m['short'] }}</span>
    </div>
@endforeach
