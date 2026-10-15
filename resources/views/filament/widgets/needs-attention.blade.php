@php($items = $this->getItems())
<x-filament-widgets::widget>
    <x-filament::section heading="Cần chú ý" description="Dữ liệu cần bổ sung hoặc kiểm tra">
        <ul class="attention-list">
            @foreach ($items as $item)
                <li>
                    <a href="{{ $item['url'] }}" class="attention-row {{ $item['count'] ? 'has-issue' : 'is-ok' }}">
                        <span class="attention-dot" aria-hidden="true"></span>
                        <span class="attention-label">{{ $item['label'] }}</span>
                        <span class="attention-count">{{ $item['count'] ? number_format($item['count']) : 'Ổn' }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
