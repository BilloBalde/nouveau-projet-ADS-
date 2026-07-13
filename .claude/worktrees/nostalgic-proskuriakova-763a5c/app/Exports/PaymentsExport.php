<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $payments;

    public function __construct($payments)
    {
        $this->payments = $payments;
    }

    public function collection()
    {
        return $this->payments;
    }

    public function headings(): array
    {
        return [
            'Facture', 'Versement', 'Total Payé', 'Reste à Payer', 'Payé par', 'Note', 'Date Paiement'
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->numeroFacture,
            $payment->versement,
            $payment->total_paye,
            $payment->reste,
            $payment->paid_by,
            $payment->note,
            $payment->created_at,
        ];
    }
}