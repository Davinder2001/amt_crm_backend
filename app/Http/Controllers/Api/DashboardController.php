<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Task;
use App\Models\StoreVendor;
use App\Models\CustomerCredit;

class DashboardController extends Controller
{
    public function cardsSummery()
    {
        $monthlySales = InvoiceItem::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum(DB::raw('quantity * unit_price'));

        $expenses = Expense::sum('price');
        $revenue = InvoiceItem::sum(DB::raw('quantity * unit_price'));

        $task = Task::count();
        $invoices = Invoice::count();
        $employees = User::role('employee')->count();
        $vendors = StoreVendor::count();
        $customers = Customer::count();
        $receiveable = CustomerCredit::sum('total_due');
        $payable = 2935;

        return response()->json([
            ['name' => 'Monthly Sales', 'count' => round($monthlySales, 2)],
            ['name' => 'Expenses', 'count' => round($expenses, 2)],
            ['name' => 'Revenue', 'count' => round($revenue, 2)],
            ['name' => 'Task', 'count' => round($task, 2)],
            ['name' => 'Invoices', 'count' => $invoices],
            ['name' => 'Employees', 'count' => $employees],
            ['name' => 'Vendor', 'count' => $vendors],
            ['name' => 'Customers', 'count' => $customers],
            ['name' => 'Receiveable', 'count' => round($receiveable, 2)],
            ['name' => 'Payable', 'count' => round($payable, 2)],
        ]);
    }




    public function saleStat(Request $request)
    {
        return response()->json([
            'daily_sales' => [100, 120, 130, 90, 110],
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        ]);
    }

    public function revenueStat(Request $request)
    {
        return response()->json([
            'monthly_revenue' => [10000, 12000, 11000, 13000, 9000],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
        ]);
    }
}
