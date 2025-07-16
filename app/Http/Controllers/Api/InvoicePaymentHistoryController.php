<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyAccount;
use App\Services\SelectedCompanyService;
use App\Models\Invoice;


class InvoicePaymentHistoryController extends Controller
{

    /**
     * Display the online payment history.
     */
    public function onlinePaymentHistory()
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $invoices = Invoice::where('company_id', $selectedCompany->company_id)->where('payment_method', 'online')->get();

        $grouped = $invoices->groupBy('bank_account_id')->map(function ($invoiceGroup, $bankAccountId) {
            $bankAccount = CompanyAccount::find($bankAccountId);

            return [
                'bank_account_id'   => $bankAccountId,
                'bank_name'         => $bankAccount->bank_name ?? 'N/A',
                'account_number'    => $bankAccount->account_number ?? 'N/A',
                'ifsc_code'         => $bankAccount->ifsc_code ?? 'N/A',
                'total_transferred' => $invoiceGroup->sum('final_amount'),
                'transactions'      => $invoiceGroup->map(function ($inv) {
                    return [
                        'invoice_number' => $inv->invoice_number,
                        'invoice_date'   => $inv->invoice_date,
                        'amount'         => $inv->final_amount,
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'status' => true,
            'data' => $grouped,
        ]);
    }


    /**
     * Display the cash payment history.
     */
    public function cashPaymentHistory()
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $invoices        = Invoice::where('company_id', $selectedCompany->company_id)->where('payment_method', 'cash')->get();

        $grouped = $invoices->groupBy('invoice_date')->map(function ($dateGroup, $date) {
            return [
                'date' => $date,
                'total' => $dateGroup->sum('final_amount'),
                'transactions' => $dateGroup->map(function ($inv) {
                    return [
                        'invoice_number' => $inv->invoice_number,
                        'amount' => $inv->final_amount,
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'status' => true,
            'payment_method' => 'cash',
            'data' => $grouped
        ]);
    }

    /**
     * Display the card payment history.
     */
    public function cardPaymentHistory()
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $invoices = Invoice::where('company_id', $selectedCompany->company_id)->where('payment_method', 'card')->get();

        $grouped = $invoices->groupBy('invoice_date')->map(function ($dateGroup, $date) {
            return [
                'date' => $date,
                'total' => $dateGroup->sum('final_amount'),
                'transactions' => $dateGroup->map(function ($inv) {
                    return [
                        'invoice_number' => $inv->invoice_number,
                        'amount' => $inv->final_amount,
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'status' => true,
            'payment_method' => 'card',
            'data' => $grouped
        ]);
    }

    /**
     * Display the credit payment history.
     */
    public function creditPaymentHistory()
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $invoices = Invoice::where('company_id', $selectedCompany->company_id)->where('payment_method', 'credit')->get();

        $grouped = $invoices->groupBy('invoice_date')->map(function ($dateGroup, $date) {
            return [
                'date' => $date,
                'total' => $dateGroup->sum('final_amount'),
                'transactions' => $dateGroup->map(function ($inv) {
                    return [
                        'invoice_number' => $inv->invoice_number,
                        'amount'         => $inv->final_amount,
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'status'         => true,
            'payment_method' => 'credit',
            'data'           => $grouped
        ]);
    }

    /**
     * Display the self-consumption payment history.
     */
    public function selfConsumptionHistory()
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $invoices        = Invoice::where('company_id', $selectedCompany->company_id)->where('payment_method', 'self')->get();

        $grouped = $invoices->groupBy('invoice_date')->map(function ($dateGroup, $date) {
            return [
                'date'  => $date,
                'total' => $dateGroup->sum('final_amount'),
                'transactions' => $dateGroup->map(function ($inv) {
                    return [
                        'invoice_number' => $inv->invoice_number,
                        'amount'         => $inv->final_amount,
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'status'         => true,
            'payment_method' => 'self consumption',
            'data'           => $grouped
        ]);
    }
}
