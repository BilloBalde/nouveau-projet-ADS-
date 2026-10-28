<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Fund;
use App\Models\InventorySession;
use App\Models\Product;
use App\Models\Store;
use App\Services\InventaireService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Comptages de stock et chiffre d'affaires qui s'en déduit.
 *
 * Le parcours suit la façon de travailler du gérant : il imprime une feuille
 * de comptage, compte dans la boutique, puis saisit au bureau.
 *
 *   1. ouvrir un comptage (boutique + date)       → create / store
 *   2. imprimer la feuille, sans stock théorique   → feuille
 *   3. saisir les quantités comptées au bureau     → edit / valider
 *   4. lire le chiffre d'affaires de la période    → show / rapport
 */
class InventorySessionController extends Controller
{
    public function __construct(private InventaireService $inventaire)
    {
        $this->middleware('auth.check');
        // Réservé au mode de vente qui déduit le chiffre d'affaires du stock.
        $this->middleware('mode:inventaire');
        $this->middleware('permission:stock.view')->only(['index', 'show', 'rapportPdf', 'modeOperatoire']);
        $this->middleware('permission:stock.adjust')->only(['create', 'store', 'feuille', 'edit', 'valider', 'destroy']);
    }

    /** Emplacement hors du périmètre de l'utilisateur : rien à y faire. */
    private function autoriserEmplacement(int $storeId): void
    {
        abort_unless(Auth::user()->canAccessStore($storeId), 403, 'Cet emplacement ne fait pas partie de votre périmètre.');
    }

    /**
     * Mode opératoire à imprimer et à laisser au gérant, près de sa caisse.
     *
     * Il vit dans l'application plutôt que dans un fichier à part : il reste
     * ainsi toujours disponible, et toujours d'accord avec la version
     * installée — une marche à suivre périmée est pire que pas de marche à
     * suivre du tout.
     */
    public function modeOperatoire()
    {
        $pdf = Pdf::loadView('inventaires.mode-operatoire', ['company' => Company::current()]);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions(['defaultFont' => 'sans-serif', 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->stream('mode-operatoire-inventaire.pdf');
    }

    public function index(Request $request)
    {
        $seances = InventorySession::with(['store:id,store_name', 'validator:id,name'])
            ->visibleTo(Auth::user())
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->orderByDesc('counted_on')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $stores = Store::optionsFor(Auth::user());

        return view('inventaires.index', compact('seances', 'stores'));
    }

    public function create()
    {
        $stores = Store::optionsFor(Auth::user());

        // Dernier comptage de chaque boutique : affiché pour que le gérant voie
        // quelle période le prochain comptage va couvrir.
        $derniers = InventorySession::where('status', InventorySession::VALIDEE)
            ->visibleTo(Auth::user())
            ->get()
            ->groupBy('store_id')
            ->map(fn ($seances) => $seances->sortByDesc('counted_on')->first());

        return view('inventaires.create', compact('stores', 'derniers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'counted_on' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ], [
            'counted_on.required' => 'Indiquez la date du comptage.',
            'counted_on.before_or_equal' => 'La date du comptage ne peut pas être dans le futur : on ne compte pas un stock à venir.',
        ]);

        $this->autoriserEmplacement((int) $data['store_id']);

        try {
            $seance = $this->inventaire->preparer((int) $data['store_id'], $data['counted_on']);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if (! empty($data['notes'])) {
            $seance->update(['notes' => $data['notes']]);
        }

        return redirect()->route('inventaires.feuille', $seance->id)
            ->with('success', 'Comptage '.$seance->reference.' ouvert. Imprimez la feuille, comptez, puis saisissez les quantités.');
    }

    /**
     * Feuille de comptage à imprimer, volontairement SANS le stock théorique.
     *
     * Montrer la quantité attendue pousse à la recopier au lieu de compter :
     * l'inventaire ne révèle alors plus ni les ventes ni les pertes. Le stock
     * théorique apparaît à la saisie, au bureau, pour repérer une erreur avant
     * de valider.
     */
    public function feuille(InventorySession $inventaire)
    {
        $this->autoriserEmplacement((int) $inventaire->store_id);

        $pdf = Pdf::loadView('inventaires.feuille', [
            'seance' => $inventaire->load('store', 'previousSession'),
            'produits' => $this->produitsACompter($inventaire),
            'company' => Company::current(),
        ]);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions(['defaultFont' => 'sans-serif', 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->stream('comptage-'.$inventaire->reference.'.pdf');
    }

    /** Saisie au bureau : le stock théorique est affiché, lui. */
    public function edit(InventorySession $inventaire)
    {
        $this->autoriserEmplacement((int) $inventaire->store_id);

        if ($inventaire->estValidee()) {
            return redirect()->route('inventaires.show', $inventaire->id)
                ->with('info', 'Ce comptage est déjà validé : son chiffre d\'affaires est figé.');
        }

        $termes = $this->inventaire->termesAttendus($inventaire);

        return view('inventaires.saisie', [
            'seance' => $inventaire->load('store', 'previousSession'),
            'lignes' => $this->lignesDeSaisie($inventaire, $termes),
            'funds' => Fund::active()->orderBy('name')->get(),
        ]);
    }

    public function valider(Request $request, InventorySession $inventaire)
    {
        $this->autoriserEmplacement((int) $inventaire->store_id);

        $data = $request->validate([
            'lignes' => 'required|array|min:1',
            'lignes.*.product_id' => 'required|exists:products,id',
            'lignes.*.fund_id' => 'nullable|exists:funds,id',
            'lignes.*.counted' => 'nullable|integer|min:0',
            'lignes.*.loss' => 'nullable|integer|min:0',
            'lignes.*.loss_reason' => 'nullable|string|max:255',
            'lignes.*.unit_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ], [
            'lignes.required' => 'Aucune ligne à enregistrer.',
        ]);

        // Une case laissée vide n'est pas un zéro : le produit n'a pas été
        // compté, son stock ne doit pas être touché et aucune vente ne doit en
        // être déduite. Compter zéro se dit en écrivant 0.
        $comptages = collect($data['lignes'])
            ->filter(fn ($l) => isset($l['counted']) && $l['counted'] !== null && $l['counted'] !== '')
            ->values()
            ->all();

        if ($comptages === []) {
            return back()->withInput()->with('error', 'Aucune quantité saisie : renseignez au moins un produit compté.');
        }

        try {
            $this->inventaire->valider($inventaire, $comptages);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if (array_key_exists('notes', $data)) {
            $inventaire->update(['notes' => $data['notes']]);
        }

        $seance = $inventaire->fresh();
        $nonComptes = count($data['lignes']) - count($comptages);

        return redirect()->route('inventaires.show', $seance->id)->with('success', sprintf(
            'Comptage %s validé : %s de chiffre d\'affaires sur la période.%s',
            $seance->reference,
            number_format((float) $seance->total_revenue_gnf, 0, ',', ' ').' GNF',
            $nonComptes > 0 ? ' '.$nonComptes.' produit(s) laissé(s) vide(s) n\'ont pas été comptés.' : ''
        ));
    }

    public function show(InventorySession $inventaire)
    {
        $this->autoriserEmplacement((int) $inventaire->store_id);

        if (! $inventaire->estValidee()) {
            return redirect()->route('inventaires.edit', $inventaire->id);
        }

        return view('inventaires.show', [
            'seance' => $inventaire->load(['store', 'previousSession', 'validator', 'lines.product', 'lines.fund']),
        ]);
    }

    public function rapportPdf(InventorySession $inventaire)
    {
        $this->autoriserEmplacement((int) $inventaire->store_id);
        abort_unless($inventaire->estValidee(), 404, 'Ce comptage n\'est pas encore validé.');

        $pdf = Pdf::loadView('inventaires.rapport', [
            'seance' => $inventaire->load(['store', 'previousSession', 'validator', 'lines.product']),
            'company' => Company::current(),
        ]);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOptions(['defaultFont' => 'sans-serif', 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->stream('ca-'.$inventaire->reference.'.pdf');
    }

    /** Un brouillon se supprime ; une séance validée, jamais. */
    public function destroy(InventorySession $inventaire)
    {
        $this->autoriserEmplacement((int) $inventaire->store_id);

        if ($inventaire->estValidee()) {
            return back()->with('error', 'Un comptage validé ne se supprime pas : son chiffre d\'affaires est établi et le stock a été aligné dessus.');
        }

        $reference = $inventaire->reference;
        $inventaire->delete();

        return redirect()->route('inventaires.index')->with('success', 'Comptage '.$reference.' supprimé.');
    }

    // -----------------------------------------------------------------

    /**
     * Produits à faire figurer sur la feuille, dans l'ordre d'affichage.
     *
     * L'ordre est le même que celui de l'écran de saisie : le gérant saisit en
     * suivant sa feuille, ligne après ligne.
     */
    private function produitsACompter(InventorySession $seance)
    {
        $termes = $this->inventaire->termesAttendus($seance);
        $ids = collect($termes)->pluck('product_id')->unique();

        return Product::whereIn('id', $ids)->orderBy('libelle')->get(['id', 'libelle', 'sku', 'unite']);
    }

    /**
     * Lignes de l'écran de saisie : un produit, son stock théorique et d'où il
     * vient, pour que le gérant voie l'écart avant de valider.
     */
    private function lignesDeSaisie(InventorySession $seance, array $termes)
    {
        $produits = Product::whereIn('id', collect($termes)->pluck('product_id')->unique())
            ->orderBy('libelle')
            ->get()
            ->keyBy('id');

        return collect($termes)
            ->map(fn ($t) => (object) ($t + ['produit' => $produits[$t['product_id']] ?? null]))
            ->filter(fn ($l) => $l->produit !== null)
            ->sortBy([fn ($a, $b) => strcmp($a->produit->libelle, $b->produit->libelle), fn ($a, $b) => ($a->fund_id ?? 0) <=> ($b->fund_id ?? 0)])
            ->values();
    }
}
