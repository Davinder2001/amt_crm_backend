<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\InvoiceItem;

class RevenueController extends Controller
{
    /**
     * Show monthly revenue per item for the current year.
     */
    public function revenue()
    {
        $year = now()->year;

        $revenues = InvoiceItem::selectRaw("
                MONTH(created_at) as month,
                description,
                SUM(quantity * unit_price) as total_revenue
            ")
            ->whereYear('created_at', $year)
            ->groupBy(DB::raw('MONTH(created_at)'), 'description')
            ->orderBy(DB::raw('MONTH(created_at)'))
            ->get()
            ->groupBy('month')
            ->map(function ($items, $monthNumber) {
                return [
                    'month' => date("F", mktime(0, 0, 0, $monthNumber, 1)),
                    'items' => $items->map(function ($item) {
                        return [
                            'item_name' => $item->description ?? 'N/A',
                            'revenue'   => round($item->total_revenue, 2),
                        ];
                    })->values(),
                ];
            })->values();

        return response()->json([
            'year' => $year,
            'data' => $revenues,
        ]);
    }

    /**
     * Show monthly revenue summary for the current year.
     */
    public function monthlyRevenueSummary(Request $request)
    {
        $year = now()->year;

        $monthlyRevenue = InvoiceItem::selectRaw("
        MONTH(created_at) as month,
        SUM(quantity * unit_price) as total_revenue
    ")
            ->whereYear('created_at', $year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy(DB::raw('MONTH(created_at)'))
            ->get()
            ->keyBy('month');

        $revenueData = collect(range(1, 12))->map(function ($month) use ($monthlyRevenue) {
            return [
                'month' => date("F", mktime(0, 0, 0, $month, 1)),
                'total_revenue' => round($monthlyRevenue[$month]->total_revenue ?? 0, 2),
            ];
        });

        return response()->json([
            'year' => $year,
            'data' => $revenueData,
        ]);
    }
}
