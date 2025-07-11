<?php

namespace App\Services\InvoiceServices;

use App\Models\Customer;
use App\Models\CustomerHistory;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\CustomerCredit;

class InvoiceHelperService
{
    /**
     * Create or get existing customer.
     */
    public static function createCustomer(array $data, int $companyId): Customer
    {
        return Customer::firstOrCreate(
            ['number' => $data['number'], 'company_id' => $companyId],
            [
                'name'    => $data['client_name'],
                'email'   => $data['email']    ?? null,
                'address' => $data['address']  ?? null,
                'pincode' => $data['pincode']  ?? null,
            ]
        );
    }

    /**
     * Store customer purchase history
     */
    public static function createCustomerHistory(Customer $customer, array $items, int $invoiceId, string $invoiceDate, string $invoiceNumber, float $subtotal): void
    {
        CustomerHistory::create([
            'customer_id'   => $customer->id,
            'items'         => $items,
            'invoice_id'    => $invoiceId,
            'purchase_date' => $invoiceDate,
            'invoice_no'    => $invoiceNumber,
            'subtotal'      => $subtotal,
        ]);
    }

    /**
     * Store customer credit info
     */
    public static function createCreditHistory(Customer $customer, array $data, float $finalAmount, int $invoiceId, int $companyId): void
    {
        $amountPaid = null;
        $totalDue   = $finalAmount;

        if ($data['creditPaymentType'] === 'partial') {
            $amountPaid = $data['partialAmount'] ?? 0;
            $totalDue   = $finalAmount - $amountPaid;
        } elseif ($data['creditPaymentType'] === 'full') {
            $amountPaid = 0;
            $totalDue   = $finalAmount;
        }

        CustomerCredit::create([
            'customer_id' => $customer->id,
            'invoice_id'  => $invoiceId,
            'total_due'   => $finalAmount,
            'amount_paid' => $amountPaid,
            'outstanding' => $totalDue,
            'company_id'  => $companyId,
            'status'      => 'due',
        ]);
    }


    public static function createDeliveryTask($invoice, $deliveryBoyId): void
    {
        $deliveryUser = User::find($deliveryBoyId);
        if (!$deliveryUser) {
            return;
        }

        $role = $deliveryUser->getRoleNames()->first();

        Task::create([
            'name'          => 'Deliver Invoice #' . $invoice->invoice_number,
            'description'   => 'Deliver invoice to ' . $invoice->client_name . ' at ' . ($invoice->delivery_address ?? 'N/A'),
            'assigned_by'   => Auth::id(),
            'assigned_to'   => $deliveryBoyId,
            'company_id'    => $invoice->company_id,
            'assigned_role' => $role ?? 'delivery_boy',
            'start_date'    => Carbon::now(),
            'end_date'      => Carbon::now()->addDays(1),
            'attachments'   => [],
            'notify'        => true,
            'status'        => 'pending',
        ]);
    }



    public static function sendInvoiceEmail(string $email, $invoice, $company): void
    {
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice'           => $invoice,
            'company_name'      => $company->company_name,
            'show_signature'    => $company->company_signature,
            'company_address'   => $company->address ?? 'N/A',
            'company_phone'     => $company->phone ?? 'N/A',
            'company_gstin'     => $company->gstin ?? 'N/A',
            'company_logo'      => $company->company_logo ?? 'N/A',
            'issued_by'         => Auth::user()->name,
            'footer_note'       => 'Thank you for your business',
            'show_signature'    => true,
        ]);

        Mail::send([], [], function ($message) use ($email, $pdf) {
            $message->to($email)
                ->subject('Invoice')
                ->attachData($pdf->output(), 'invoice.pdf', [
                    'mime' => 'application/pdf',
                ]);
        });
    }
}
