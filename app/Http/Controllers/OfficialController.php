<?php

namespace App\Http\Controllers;

use App\Models\Official;
use Illuminate\Http\Request;

class OfficialController extends Controller
{
    /** Keeps the old URL shape: /nhan-su-cac-tinh-thanh/{part}/{name-slug}-{seq}. The slugs are cosmetic; seq identifies the record. */
    public function show(Request $request, string $part, string $official)
    {
        abort_unless(preg_match('/(\d+)$/', preg_replace('/\.html$/i', '', $official), $m), 404);

        $record = Official::published()
            ->where('seq', (int) $m[1])
            ->with('part')
            ->firstOrFail();

        return view('officials.show', [
            'official' => $record,
            'print' => $request->has('print'),
        ]);
    }
}
