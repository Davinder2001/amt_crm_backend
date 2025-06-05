<?php

namespace App\Http\Controllers\Api;

use App\Models\Package;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\SelectedCompanyService;
use Illuminate\Support\Facades\Validator;

use App\Http\Resources\PaymentAndBillingResource;

class PaymentAndBillingController extends Controller
{
    public function adminBilling()
    {
        $userId = Auth::id();

        $payments = Payment::where('user_id', $userId)->get();

        return response()->json([
            'success'  => true,
            'payments' => PaymentAndBillingResource::collection($payments),
        ]);
    }




    public function upgradePackage(Request $request)
    {
        // dd($id);
        $activeCompany      = SelectedCompanyService::getSelectedCompanyOrFail();
        $packageId          = $activeCompany->company->package_id;
        $activeCompanyId    = $activeCompany->company->id;
        $businessId         = $activeCompany->company->business_category;
        $canSubscribe       = Package::where('business_category_id', $businessId)->exists();
        dd($canSubscribe);
    }



    public function refundRequest(Request $request, $transaction_id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $payment = Payment::where('transaction_id', $transaction_id)->first();


        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'No payment found for the given order ID.',
            ], 404);
        }

        if ($payment->payment_status !== 'COMPLETED') {
            return response()->json([
                'success' => false,
                'message' => 'Payment was not successfully completed. Refund cannot be requested.',
                'payment_status' => $payment->payment_status,
            ], 400);
        }


        if (!is_null($payment->refund)) {
            return response()->json([
                'success' => false,
                'message' => 'Refund has already been requested or processed.',
                'refund_status' => $payment->refund,
            ], 400);
        }

        $payment->refund = 'refund_processed';
        $payment->refund_reason = $data['reason'];
        $payment->save();

        return response()->json([
            'success' => true,
            'message' => 'Refund request submitted successfully.',
            'data'    => $payment,
        ]);
    }

    public function getRefundRequests(Request $request)
    {
        $query = Payment::query();

        // Optional: filter by refund status
        if ($request->has('status')) {
            $query->where('refund', $request->input('status'));
        } else {
            // Only show payments that have any refund status (not null)
            $query->whereNotNull('refund');
        }

        // Optional: filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $refunds = $query->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'message' => 'Refund requests fetched successfully.',
            'data'    => $refunds,
        ]);
    }



    public function approveRefundRequest($transactionId)
    {
        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found for the given transaction ID.',
            ], 404);
        }

        if ($payment->refund !== 'refund_processed') {
            return response()->json([
                'success' => false,
                'message' => 'Only processed refund requests can be approved.',
                'current_refund_status' => $payment->refund,
            ], 400);
        }

        $payment->refund = 'refund_approved';
        $payment->save();

        return response()->json([
            'success' => true,
            'message' => 'Refund request approved successfully.',
            'data'    => $payment,
        ]);
    }
    public function markRefunded($transactionId)
    {
        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found for the given transaction ID.',
            ], 404);
        }

        if ($payment->refund !== 'refund_approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved refund requests can be marked as refunded.',
                'current_refund_status' => $payment->refund,
            ], 400);
        }

        $payment->refund = 'refunded';
        $payment->save();

        return response()->json([
            'success' => true,
            'message' => 'Refund marked as completed.',
            'data'    => $payment,
        ]);
    }




    public function declineRefundRequest(Request $request, $transactionId)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found for the given transaction ID.',
            ], 404);
        }

        if ($payment->refund !== 'refund_processed') {
            return response()->json([
                'success' => false,
                'message' => 'Refund cannot be declined in current state.',
                'current_refund_status' => $payment->refund,
            ], 400);
        }

        $payment->refund = 'refund_declined';
        $payment->decline_reason = $request->input('reason');
        $payment->save();

        return response()->json([
            'success' => true,
            'message' => 'Refund request declined successfully.',
            'data'    => $payment,
        ]);
    }
}
