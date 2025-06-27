<?php

namespace App\Services\InvoiceServices;

use App\Models\Customer;
use App\Models\CustomerHistory;
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
}
