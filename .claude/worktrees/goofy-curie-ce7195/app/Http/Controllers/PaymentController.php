<?php

namespace App\Http\Controllers;

use App\Exports\PaymentsExport;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Facture;
use App\Models\Payment;
use App\Models\Store;
use App\Models\TransactionFacture;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class PaymentController extends Controller
{

    public function updateCustomerTotals()
    {
        try {
            // Sensitive maintenance action: restrict to admin
            if (!auth()->check() || auth()->user()->role_id != 1) {
                abort(403);
            }

            $customers = Customer::all();
            $updatedCount = 0;
            
            foreach ($customers as $customer) {
                // Calculate total_taken: sum of all invoice amounts
                $totalTaken = Facture::where('customer_id', $customer->id)
                                    ->sum('montant_total');
                
                // Calculate total_repaid: sum of all payments for this customer's invoices
                $totalRepaid = Payment::whereIn('facture_id', function($query) use ($customer) {
                                        $query->select('id')
                                            ->from('factures')
                                            ->where('customer_id', $customer->id);
                                    })
                                    ->sum('versement');
                
                // Calculate balance
                $balance = $totalTaken - $totalRepaid;
                
                // Update customer record
                $customer->update([
                    'total_taken' => $totalTaken,
                    'total_repaid' => $totalRepaid,
                    'balance' => max(0, $balance) // Ensure balance doesn't go negative
                ]);
                
                $updatedCount++;
            }
            
            return "Updated {$updatedCount} customer records.";
            
        } catch (\Throwable $th) {
            return "Error: " . $th->getMessage();
        }
    }
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    public function index(Request $request)
    {
        $query = Payment::with('facture');

        // Filtre par facture
        if ($request->filled('facture_id')) {
            $query->where('facture_id', $request->facture_id);
        }

        // Filtrage par période
        $period = $request->get('period', 'daily');
        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 'daily':
                $date = $request->get('date', Carbon::now()->toDateString());
                $startDate = Carbon::parse($date)->startOfDay();
                $endDate = Carbon::parse($date)->endOfDay();
                break;
            case 'weekly':
                $weekStart = $request->get('week_start', Carbon::now()->startOfWeek()->toDateString());
                $startDate = Carbon::parse($weekStart)->startOfWeek();
                $endDate = $startDate->copy()->endOfWeek();
                break;
            case 'monthly':
                $month = $request->get('month', Carbon::now()->format('Y-m'));
                $startDate = Carbon::parse($month)->startOfMonth();
                $endDate = Carbon::parse($month)->endOfMonth();
                break;
            case 'quarterly':
                $quarter = $request->get('quarter', ceil(Carbon::now()->month / 3));
                $year = $request->get('year', Carbon::now()->year);
                $startMonth = ($quarter - 1) * 3 + 1;
                $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
                $endDate = $startDate->copy()->addMonths(3)->subDay()->endOfDay();
                break;
            case 'semestral':
                $semester = $request->get('semester', Carbon::now()->month <= 6 ? 1 : 2);
                $year = $request->get('year', Carbon::now()->year);
                $startMonth = $semester == 1 ? 1 : 7;
                $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
                $endDate = $startMonth == 1 ? Carbon::create($year, 6, 30)->endOfDay() : Carbon::create($year, 12, 31)->endOfDay();
                break;
            case 'yearly':
                $year = $request->get('year', Carbon::now()->year);
                $startDate = Carbon::create($year, 1, 1)->startOfDay();
                $endDate = Carbon::create($year, 12, 31)->endOfDay();
                break;
            case 'custom':
                $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfDay();
                $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
                break;
            default:
                $startDate = Carbon::now()->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $dataTable = $query->orderBy('created_at', 'desc')->get();

        $factures = Facture::all();

        // Passer les dates pour pré-remplir les champs
        $startDate = $startDate ? $startDate->toDateString() : null;
        $endDate = $endDate ? $endDate->toDateString() : null;

        return view('payments.index', compact('dataTable', 'factures', 'startDate', 'endDate'));
    }

    protected function getFilteredPayments($request)
    {
        $query = Payment::join('factures', 'payments.facture_id', '=', 'factures.id')
            ->select('payments.*', 'factures.numero_facture as numeroFacture');

        if ($request->filled('facture_id')) {
            $query->where('payments.facture_id', $request->facture_id);
        }

        // Filtrage par période (identique à index)
        $period = $request->get('period', 'daily');
        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 'daily':
                $date = $request->get('date', Carbon::now()->toDateString());
                $startDate = Carbon::parse($date)->startOfDay();
                $endDate = Carbon::parse($date)->endOfDay();
                break;
            case 'weekly':
                $weekStart = $request->get('week_start', Carbon::now()->startOfWeek()->toDateString());
                $startDate = Carbon::parse($weekStart)->startOfWeek();
                $endDate = $startDate->copy()->endOfWeek();
                break;
            case 'monthly':
                $month = $request->get('month', Carbon::now()->format('Y-m'));
                $startDate = Carbon::parse($month)->startOfMonth();
                $endDate = Carbon::parse($month)->endOfMonth();
                break;
            case 'quarterly':
                $quarter = $request->get('quarter', ceil(Carbon::now()->month / 3));
                $year = $request->get('year', Carbon::now()->year);
                $startMonth = ($quarter - 1) * 3 + 1;
                $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
                $endDate = $startDate->copy()->addMonths(3)->subDay()->endOfDay();
                break;
            case 'semestral':
                $semester = $request->get('semester', Carbon::now()->month <= 6 ? 1 : 2);
                $year = $request->get('year', Carbon::now()->year);
                $startMonth = $semester == 1 ? 1 : 7;
                $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
                $endDate = $startMonth == 1 ? Carbon::create($year, 6, 30)->endOfDay() : Carbon::create($year, 12, 31)->endOfDay();
                break;
            case 'yearly':
                $year = $request->get('year', Carbon::now()->year);
                $startDate = Carbon::create($year, 1, 1)->startOfDay();
                $endDate = Carbon::create($year, 12, 31)->endOfDay();
                break;
            case 'custom':
                $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfDay();
                $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
                break;
            default:
                $startDate = Carbon::now()->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
        }

        if ($startDate && $endDate) {
            $query->whereBetween('payments.created_at', [$startDate, $endDate]);
        }

        return $query->orderBy('payments.created_at', 'desc')->get();
    }

    public function exportPdf(Request $request)
    {
        $payments = $this->getFilteredPayments($request);
        $pdf = Pdf::loadView('payments.payments_pdf', compact('payments'));
        return $pdf->download('paiements.pdf');
    }

    public function exportExcel(Request $request)
    {
        $payments = $this->getFilteredPayments($request);
        return Excel::download(new PaymentsExport($payments), 'paiements.xlsx');
    }


    public function add($mark)
    {
        $customer = Customer::where('mark', $mark)->first();
        $dataTable = TransactionFacture::where('customer_id', $customer->id)->get();
        $balanceQuery = Facture::where('customer_id', $customer->id)
            ->where('statut', '!=', 'payé');
        if (auth()->user()->role_id == 3) {
            $store = Store::where('user_id', auth()->id())->first();
            if ($store) {
                $balanceQuery->where('store_id', $store->id);
            }
        }
        $balanceDue = $balanceQuery->sum('reste');
        return view('customers.add', compact('customer', 'dataTable', 'balanceDue'));
    }

    public function creation($id){
        $facture = Facture::find($id);
        // dd($depot);
        return view('factures.addPayment', compact('facture'));
    }

    public function voir($id){
        $facture = Facture::find($id);
        // dd($depot);
        return view('factures.showPayment', compact('facture'));
    }

    public function storePayment(Request $request)
    {
        $rawVersement = $request->input('versement');
        $normalizedVersement = is_string($rawVersement)
            ? str_replace([' ', ','], '', $rawVersement)
            : $rawVersement;
        $request->merge([
            'versement' => $normalizedVersement,
        ]);

        $validator = Validator::make($request->all(), [
            'paid_by' => 'required',
            'versement' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ], [
            'paid_by.required' => 'Veuillez sélectionner le moyen de paiement',
            'versement.required' => 'Veuillez entrer un montant, à la rigueur 0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $customer = Customer::findOrFail($request->customer_id);
            $versement = (float) $request->versement;
            $storeId = null;
            if (auth()->user()->role_id == 3) {
                $store = Store::where('user_id', auth()->id())->first();
                $storeId = $store?->id;
            }

            $balanceQuery = Facture::where('customer_id', $customer->id)
                ->where('statut', '!=', 'payé');
            if ($storeId) {
                $balanceQuery->where('store_id', $storeId);
            }
            $balanceDue = (float) $balanceQuery->sum('reste');

            // Check if payment exceeds customer's balance (per store if applicable)
            if ($versement > $balanceDue) {
                return redirect()->back()->with('error', 'Le montant versé dépasse le solde du client.');
            }

            // Get unpaid invoices for this customer, ordered by creation date
            $unpaidQuery = Facture::where('customer_id', $customer->id)
                                    ->where('statut', '!=', 'payé')
                ->orderBy('created_at', 'asc');
            if ($storeId) {
                $unpaidQuery->where('store_id', $storeId);
            }
            $unpaidInvoices = $unpaidQuery->get();

            if ($unpaidInvoices->isEmpty()) {
                return redirect()->back()->with('error', 'Ce client n\'a aucune facture impayée.');
            }

            $remainingPayment = $versement;
            $nextReceiptId = (Payment::max('id') ?? 0) + 1;
            $lastPaidInvoiceNumber = null;
            
            foreach ($unpaidInvoices as $invoice) {
                if ($remainingPayment <= 0) {
                    break;
                }

                // Get last payment for this invoice
                $oldPayment = Payment::where('facture_id', $invoice->id)
                                    ->orderBy('id', 'DESC')
                                    ->first();

                $previousTotal = $oldPayment ? $oldPayment->total_paye : 0;
                $previousReste = $oldPayment ? $oldPayment->reste : $invoice->montant_total;

                // Calculate how much we can apply to this invoice
                $amountToApply = min($remainingPayment, $previousReste);
                
                // Calculate new totals for this invoice
                $newTotalPaid = $previousTotal + $amountToApply;
                $newReste = $previousReste - $amountToApply;

                // Create payment record for this invoice
                $receiptNumber = generateReceiptNumber('RCF', $nextReceiptId);
                $nextReceiptId++;

                Payment::create([
                    'facture_id' => $invoice->id,
                    'versement' => $amountToApply,
                    'paid_by' => $request->paid_by,
                    'note' => $request->note,
                    'total_paye' => $newTotalPaid,
                    'reste' => $newReste,
                    'receipt_number' => $receiptNumber,
                ]);
                $lastPaidInvoiceNumber = $invoice->numero_facture;

                // Update invoice status
                $statut = $newReste == 0 ? 'payé' : 'non payé';
                $invoice->update([
                    'statut' => $statut,
                    'reste' => $newReste
                ]);

                // Deduct from remaining payment
                $remainingPayment -= $amountToApply;
            }
            // Record the transaction
            $transactionReceiptNumber = generateReceiptNumber('RCC', (TransactionFacture::max('id') ?? 0) + 1);
            $transaction = TransactionFacture::create([
                'customer_id' => $customer->id,
                'versement' => $versement,
                'balance' => $customer->balance,
                'note' => $request->note,
                'paid_by' => $request->paid_by,
                'receipt_number' => $transactionReceiptNumber,
            ]);

            // Update customer's overall balance
            $customer->update([
                'total_repaid' => $customer->total_repaid + $versement,
                'balance' => $customer->balance - $versement
            ]);

            // Success message
            $sms = "Un versement de {$versement} FG a été effectué pour le client {$customer->customerName}. ";
            $sms .= "Nouveau solde: {$customer->balance} FG.";

            if ($remainingPayment > 0) {
                $sms .= " Remarque: {$remainingPayment} FG n'a pas été utilisé (toutes les factures sont payées).";
            }

            session(['success' => $sms]);
            // Rediriger vers la dernière facture impactée par ce paiement
            if ($lastPaidInvoiceNumber) {
                return redirect()->route('factures.show', $lastPaidInvoiceNumber);
            }
            return redirect()->route('factures.index');

        } catch (\Throwable $th) {
            return back()->with('fail', 'Une erreur est survenue : ' . $th->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $facture = Facture::find($request->facture_id); // ou récupère la facture selon ta logique
      
        $validator = Validator::make($request->all(), [
            'paid_by' => 'required',
            'versement' => 'required|numeric|min:0.01|max:' . $facture->reste,
            'note' => 'nullable|string',
        ], [
            'paid_by.required' => 'Veuillez sélectionner le moyen de paiement',
            'versement.required' => 'Veuillez entrer un montant, à la rigueur 0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $facture = Facture::findOrFail($request->facture_id);

            // Dernier paiement
            $oldPayment = Payment::where('facture_id', $request->facture_id)
                                ->orderBy('id', 'DESC')
                                ->first();

            $previousTotal = $oldPayment ? $oldPayment->total_paye : 0;
            $previousReste = $oldPayment ? $oldPayment->reste : $facture->montant_total;

            $versement = $request->versement;

            // Calcul du nouveau total payé et reste
            $total_paye = $previousTotal + $versement;
            $reste = $previousReste - $versement;

            if ($reste < 0) {
                return redirect()->back()->with('error', 'Le montant versé dépasse le montant restant à payer.');
            }

            $receiptNumber = generateReceiptNumber('RCF', (Payment::max('id') ?? 0) + 1);

            // Création du paiement
            $payment = Payment::create([
                'facture_id' => $facture->id,
                'versement' => $versement,
                'paid_by' => $request->paid_by,
                'note' => $request->note,
                'total_paye' => $total_paye,
                'reste' => $reste,
                'receipt_number' => $receiptNumber,
            ]);

            // Mise à jour du statut de la facture
            $statut = $reste == 0 ? 'payé' : 'non payé';
            $facture->update([
                'statut' => $statut,
                'reste' => $reste
            ]);

            // Message de confirmation
            $client = Customer::find($facture->customer_id);
            if ($client) {
                $client->update([
                    'total_repaid' => $client->total_repaid + $versement,
                    'balance' => $client->balance - $versement
                ]);
            }
            $sms = $reste == 0
                ? "Facture {$facture->numero_facture} entièrement payée. Merci au client {$client->customerName}."
                : "Le montant de {$versement} a été versé pour la facture {$facture->numero_facture}. Montant restant: {$reste}.";

            session(['success' => $sms]);
            // Après paiement, rediriger vers la facture (détails de vente)
            return redirect()->route('factures.show', $facture->numero_facture);

        } catch (\Throwable $th) {
            return back()->with('fail', 'Une erreur est survenue : ' . $th->getMessage());
        }
    }

}