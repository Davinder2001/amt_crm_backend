<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\InvoiceItem;

class SalesController extends Controller
{
    /**
     * Show current month's sales and comparison with last month.
     */
    public function sales(Request $request)
    {
        $year = now()->year;

        $sales = InvoiceItem::selectRaw("
        MONTH(created_at) as month,
        description,
        SUM(quantity) as total_quantity,
        SUM(quantity * unit_price) as total_sales
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
                            'quantity'  => (int) $item->total_quantity,
                            'sales'     => round($item->total_sales, 2),
                        ];
                    })->values(),
                ];
            })->values();


        return response()->json([
            'year' => $year,
            'data' => $sales,
        ]);
    }


    /**
     * Determine sales trend.
     */
    private function getSalesTrend($current, $last): string
    {
        if ($current > $last) {
            return 'up';
        } elseif ($current < $last) {
            return 'down';
        } else {
            return 'same';
        }
    }
}
