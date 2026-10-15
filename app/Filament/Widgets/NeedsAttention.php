<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Officials\OfficialResource;
use App\Filament\Resources\Parts\PartResource;
use App\Models\Official;
use App\Models\Part;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

/** Data-quality checklist; each row links to the list already filtered to the affected records. */
class NeedsAttention extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    protected string $view = 'filament.widgets.needs-attention';

    public function getItems(): array
    {
        $dupCodes = DB::table('officials')->where('member_code', '!=', '')
            ->groupBy('member_code')->havingRaw('count(*) > 1')->pluck('member_code')->count();

        return [
            ['label' => 'Chưa có ảnh chân dung', 'count' => Official::where('image', '')->count(), 'url' => OfficialResource::getUrl('index').'?tab=noimage'],
            ['label' => 'Chưa thuộc tỉnh/thành nào', 'count' => Official::whereNull('part_id')->count(), 'url' => OfficialResource::getUrl('index').'?tab=nopart'],
            ['label' => 'Trùng Mã số', 'count' => $dupCodes, 'url' => OfficialResource::getUrl('index').'?tab=dupcode'],
            ['label' => 'Đang bị ẩn khỏi trang công khai', 'count' => Official::where('is_active', false)->count(), 'url' => OfficialResource::getUrl('index').'?tab=hidden'],
            ['label' => 'Tỉnh/thành chưa có nhân sự', 'count' => Part::doesntHave('officials')->count(), 'url' => PartResource::getUrl('index').'?tab=empty'],
        ];
    }
}
