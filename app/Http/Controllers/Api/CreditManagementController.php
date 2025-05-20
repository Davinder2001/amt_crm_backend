<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\CustomerCredit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\CustomerCreditResource;


class CreditManagementController extends Controller
{

    public function index()
    {
        $credits = CustomerCredit::with('invoice', 'customer')->where('status', '!=', 'paid')->get();
        return response()->json([
            'status'    => true,
            'message'   => 'Credit management API is active.',
            'data'      => $credits,
        ]);
    }

    public function users()
    {
        $credits = CustomerCredit::with('customer', 'invoice')->get();

        $grouped = $credits->groupBy(function ($credit) {
            return $credit->customer->number;
        });

        $users = $grouped->map(function ($creditGroup) {
            $first = $creditGroup->first();

            return [
                'customer_id'    => $first->customer->id,
                'name'           => $first->customer->name,
                'number'         => $first->customer->number,
                'total_invoices' => $creditGroup->count(),
                'total_due'      => $creditGroup->sum('total_due'),
                'amount_paid'    => $creditGroup->sum('amount_paid'),
                'outstanding'    => $creditGroup->sum('outstanding'),
            ];
        })->values();

        return response()->json([
            'status'  => true,
            'message' => 'Unique users with outstanding credits fetched.',
            'data'    => $users,
        ]);
    }


    public function show($customerId)
    {
        $credits = CustomerCredit::with('invoice', 'customer')->where('customer_id', $customerId)->where('status', '!=', 'paid')->get();

        if ($credits->isEmpty()) {
            return response()->json([
                'status'   => true,
                'message'  => 'No due credits found.',
                'customer' => null,
            ]);
        }

        return response()->json([
            'status'   => true,
            'customer' => new CustomerCreditResource($credits),
        ]);
    }


    public function closeDue(Request $request, $creditId)
    {
        $selectedCredit     = CustomerCredit::with('customer')->findOrFail($creditId);
        $customerId         = $selectedCredit->customer_id;
        $credits            = CustomerCredit::where('customer_id', $customerId)->where('status', '!=', 'paid')->orderBy('id')->get();
        $totalOutstanding   = $credits->sum('outstanding');

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1|max:' . $totalOutstanding,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $amountToPay = $request->input('amount');

        DB::transaction(function () use ($credits, $amountToPay) {
            $remaining = $amountToPay;

            foreach ($credits as $credit) {
                if ($remaining <= 0) break;

                $payable = min($credit->outstanding, $remaining);

                $credit->amount_paid += $payable;
                $credit->outstanding = $credit->total_due - $credit->amount_paid;
                $credit->status = $credit->outstanding <= 0 ? 'paid' : 'partial';
                $credit->save();

                $remaining -= $payable;
            }
        });

        $updatedCredits = CustomerCredit::with('invoice', 'customer')->where('customer_id', $customerId)->where('status', '!=', 'paid')->get();

        if ($updatedCredits->isEmpty()) {
            return response()->json([
                'status'   => true,
                'message'  => 'All dues cleared.',
                'customer' => null,
            ]);
        }

        return response()->json([
            'status'   => true,
            'message'  => 'Payment of ₹' . $amountToPay . ' applied successfully.',
            'customer' => new CustomerCreditResource($updatedCredits),
        ]);
    }
}
