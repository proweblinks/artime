<?php

namespace Modules\AppVideoWizard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AppVideoWizard\Models\StockMedia;
use Modules\AppVideoWizard\Models\StockMediaReport;

class StockMediaReportController extends Controller
{
    public function report(Request $request, StockMedia $stockMedia)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $ip = $request->ip();

        $exists = StockMediaReport::where('stock_media_id', $stockMedia->id)
            ->where('ip_address', $ip)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'You have already reported this item.'], 409);
        }

        StockMediaReport::create([
            'stock_media_id' => $stockMedia->id,
            'ip_address' => $ip,
            'reason' => $request->input('reason'),
        ]);

        $stockMedia->increment('report_count');

        return response()->json(['message' => 'Report submitted. Thank you for your feedback.']);
    }
}
