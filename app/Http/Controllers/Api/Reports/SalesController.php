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

    public function monthlySalesSummary(Request $request)
    {
        $year = now()->year;

        $monthlySales = InvoiceItem::selectRaw("
        MONTH(created_at) as month,
        SUM(quantity * unit_price) as total_sales
    ")
            ->whereYear('created_at', $year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy(DB::raw('MONTH(created_at)'))
            ->get()
            ->keyBy('month');

        // Ensure all 12 months are included even if sales are 0
        $salesData = collect(range(1, 12))->map(function ($month) use ($monthlySales) {
            return [
                'month' => date("F", mktime(0, 0, 0, $month, 1)),
                'total_sales' => round($monthlySales[$month]->total_sales ?? 0, 2),
            ];
        });

        return response()->json([
            'year' => $year,
            'data' => $salesData
        ]);
    }

    public function topSellingItems(Request $request)
    {
        $year = now()->year;

        $topItems = InvoiceItem::selectRaw("
            description,
            SUM(quantity) as total_quantity,
            SUM(quantity * unit_price) as total_sales
        ")
            ->whereYear('created_at', $year)
            ->groupBy('description')
            ->orderByDesc('total_quantity') // Or use 'total_sales' to sort by revenue
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'item_name' => $item->description ?? 'N/A',
                    'total_quantity' => (int) $item->total_quantity,
                    'total_sales' => round($item->total_sales, 2),
                ];
            });

        return response()->json([
            'year' => $year,
            'top_items' => $topItems
        ]);
    }
}
