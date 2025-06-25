<?php

namespace App\Http\Controllers\Api;

use App\Models\Package;
use App\Models\Payment;
use App\Models\BusinessCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\SelectedCompanyService;
use Illuminate\Support\Facades\Validator;

use App\Http\Resources\PaymentAndBillingResource;

class PaymentAndBillingController extends Controller
{
    /**
     * Get the billing information for the currently authenticated user.
     *
     */
    public function adminBilling()
    {
        $userId = Auth::id();

        $payments = Payment::where('user_id', $userId)->get();

        return response()->json([
            'success'  => true,
            'payments' => PaymentAndBillingResource::collection($payments),
        ]);
    }


    /**
     * Upgrade the package for the currently selected company.
     *
     */
public function upgradePackage(Request $request)
{
    $activeCompany      = SelectedCompanyService::getSelectedCompanyOrFail();
    $packageId          = $activeCompany->company->package_id;
    $businessId         = $activeCompany->company->business_category;

    // Get all related packages except the current one
    $otherPackages = BusinessCategory::with(['packages' => function ($query) use ($packageId) {
        $query->where('id', '!=', $packageId);
    }])->findOrFail($businessId)->packages;

    return response()->json([
        'success' => true,
        'packages' => $otherPackages
    ]);
}

    /**
     * Request a refund for a payment by transaction ID.
     *
     */
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

        $superAdmins = User::withoutGlobalScopes()->role('super-admin')->get();

        if (!$superAdmins) {
            return response()->json([
                'success' => false,
                'message' => 'No super admins found to notify.',
            ], 404);
        };

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

        $paymentDate     = $payment->payment_date;
        $now             = now()->startOfDay();
        $paymentDateOnly = Carbon::createFromFormat('d/m/Y', $paymentDate)->startOfDay();
        $diffInDays      = $paymentDateOnly->diffInDays($now);

        if ($diffInDays > 15) {
            return response()->json([
                'success' => false,
                'message' => 'Refund requests can only be made within 15 days of payment.',
                'payment_date' => $paymentDate,
            ], 400);
        }



        $payment->refund = 'request processed';
        $payment->refund_reason = $data['reason'];
        $payment->save();

        foreach ($superAdmins as $admin) {
            $admin->notify(new SystemNotification(
                'Refund Request Submitted',
                "A refund request was submitted for transaction #{$transaction_id}.",
                'warning',
                url("/admin/refunds/{$payment->id}")
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund request submitted successfully.',
            'data'    => $payment,
        ]);
    }


    /**
     * Get all refund requests with optional filters.
     *
     */
    public function getRefundRequests(Request $request)
    {
        $query = Payment::query();

        if ($request->has('status')) {
            $query->where('refund', $request->input('status'));
        } else {
            $query->whereNotNull('refund');
        }

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

    /**
     * Approve a refund request by transaction ID.
     *
     */
    public function approveRefundRequest($transactionId)
    {

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found for the given transaction ID.',
            ], 404);
        }

        if ($payment->refund !== 'request processed') {
            return response()->json([
                'success' => false,
                'message' => 'Only processed refund requests can be approved.',
                'current_refund_status' => $payment->refund,
            ], 400);
        }

        $payment->refund = 'refund approved';
        $payment->save();

        if ($payment->user) {
            $payment->user->notify(new SystemNotification(
                'Refund Approved',
                "Your refund request for transaction #{$transactionId} has been approved.",
                'success',
                url("/user/payments/{$payment->id}")
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund request approved successfully.',
            'data'    => $payment,
        ]);
    }


    /**
     * Mark a refund as completed.
     *
     */
    public function markRefunded($transactionId)
    {
        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found for the given transaction ID.',
            ], 404);
        }

        if ($payment->refund !== 'refund approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved refund requests can be marked as refunded.',
                'current_refund_status' => $payment->refund,
            ], 400);
        }

        $payment->refund = 'refunded';
        $payment->save();

        if ($payment->user) {
            $payment->user->notify(new SystemNotification(
                'Refund Completed',
                "Your refund for transaction #{$transactionId} has been successfully processed.",
                'success',
                url("/user/payments/{$payment->id}")
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund marked as completed.',
            'data'    => $payment,
        ]);
    }

    /**
     * Decline a refund request.
     *
     */
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

        if ($payment->refund !== 'request processed') {
            return response()->json([
                'success' => false,
                'message' => 'Refund cannot be declined in current state.',
                'current_refund_status' => $payment->refund,
            ], 400);
        }

        $payment->refund = 'refund declined';
        $payment->decline_reason = $request->input('reason');
        $payment->save();

        if ($payment->user) {
            $payment->user->notify(new SystemNotification(
                'Refund Declined',
                "Your refund request for transaction #{$transactionId} has been declined. Reason: " . $request->input('reason'),
                'error',
                url("/user/payments/{$payment->id}")
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund request declined successfully.',
            'data'    => $payment,
        ]);
    }
}
