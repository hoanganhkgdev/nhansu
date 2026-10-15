@php
    $record = $getRecord();
    $n = (int) $record->officials_count;
    $pct = $max > 0 ? max($n > 0 ? 3 : 0, round($n / $max * 100)) : 0;
    $url = \App\Filament\Resources\Parts\PartResource::officialsUrl($record);
@endphp
<a href="{{ $url }}" class="part-count" title="Xem {{ number_format($n) }} nhân sự của đơn vị này">
    <span class="part-count-n {{ $n === 0 ? 'is-zero' : '' }}">{{ number_format($n) }}</span>
    <span class="part-count-bar"><i style="width: {{ $pct }}%"></i></span>
</a>
