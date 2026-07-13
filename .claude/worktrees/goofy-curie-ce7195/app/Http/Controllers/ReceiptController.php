<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Payment;
use App\Models\StockTransfer;
use App\Models\TransactionFacture;
use App\Models\Sale;
use App\Models\User;

class ReceiptController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    public function payment(Payment $payment)
    {
        $payment->load(['facture.customer', 'facture.store']);
        $facture = $payment->facture;
        $customer = $facture?->customer;
        $company = Company::latest()->first();

        return view('receipts.payment', [
            'payment' => $payment,
            'facture' => $facture,
            'customer' => $customer,
            'company' => $company,
        ]);
    }

    public function transaction(TransactionFacture $transaction)
    {
        $transaction->load('customer');
        $company = Company::latest()->first();

        return view('receipts.transaction', [
            'transaction' => $transaction,
            'customer' => $transaction->customer,
            'company' => $company,
            'user' => auth()->user(),
        ]);
    }

    public function transfer(StockTransfer $transfer)
    {
        $transfer->load(['product', 'fromStore', 'toStore']);
        $company = Company::latest()->first();
        $receiptNumber = generateReceiptNumber('TRF', $transfer->id);

        return view('receipts.transfer', [
            'transfer' => $transfer,
            'company' => $company,
            'receiptNumber' => $receiptNumber,
            'user' => auth()->user(),
        ]);
    }
}
