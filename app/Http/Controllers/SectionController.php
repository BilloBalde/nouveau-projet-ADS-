<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Sections du site : l'entrepôt / magasin principal et chaque boutique.
 * Une section = un emplacement de premier niveau (non rattaché) et tout ce
 * qui lui est rattaché (entrepôts, stocks internes).
 *
 * Le propriétaire (permission « stores.all ») voit toutes les sections et
 * peut « entrer » dans l'une d'elles : toute l'application se limite alors à
 * cette section, jusqu'au retour à la vue globale. Un employé est toujours
 * dans la section de son magasin d'affectation.
 */
class SectionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    public function index()
    {
        $user = auth()->user();

        // Tout l'arbre en une requête, puis les chiffres agrégés par
        // emplacement : le coût ne dépend pas du nombre de sections.
        $stores = Store::orderByDesc('is_main')->orderBy('store_name')->get();
        $children = $stores->groupBy('parent_id');
        $descendants = function (int $id) use (&$descendants, $children) {
            $ids = [$id];
            foreach ($children->get($id, collect()) as $child) {
                $ids = array_merge($ids, $descendants($child->id));
            }
            return $ids;
        };

        $stock = StoreProduct::selectRaw('store_id, SUM(quantity) as qty')->groupBy('store_id')->pluck('qty', 'store_id');
        $today = Sale::whereBetween('created_at', [Carbon::today(), Carbon::today()->endOfDay()])
            ->selectRaw('store_id, SUM(prixTotal) as ca')->groupBy('store_id')->pluck('ca', 'store_id');
        $staff = User::whereNotNull('store_id')->selectRaw('store_id, COUNT(*) as n')->groupBy('store_id')->pluck('n', 'store_id');

        $sections = $stores->whereNull('parent_id')
            ->filter(fn ($s) => ! $user->isRestrictedToStore() || in_array($s->id, $user->accessibleStoreIds(), true) || $s->id === $user->assignedStoreId())
            ->map(function ($s) use ($descendants, $stock, $today, $staff, $stores) {
                $ids = $descendants($s->id);
                return (object) [
                    'store'     => $s,
                    'locations' => $stores->whereIn('id', array_diff($ids, [$s->id]))->values(),
                    'stock'     => (int) collect($ids)->sum(fn ($id) => $stock[$id] ?? 0),
                    'ca_today'  => (float) collect($ids)->sum(fn ($id) => $today[$id] ?? 0),
                    'staff'     => (int) collect($ids)->sum(fn ($id) => $staff[$id] ?? 0),
                ];
            })->values();

        // Employé rattaché à un sous-emplacement : sa section est l'emplacement
        // de premier niveau qui le contient.
        if ($sections->isEmpty() && $user->isRestrictedToStore() && ($own = $user->currentSection())) {
            $sections = collect([(object) [
                'store' => $own, 'locations' => collect(), 'stock' => (int) ($stock[$own->id] ?? 0),
                'ca_today' => (float) ($today[$own->id] ?? 0), 'staff' => (int) ($staff[$own->id] ?? 0),
            ]]);
        }

        $current = $user->currentSection();

        return view('sections.index', compact('sections', 'current'));
    }

    /** Entrer dans une section, ou revenir à la vue globale (section_id vide). */
    public function switch(Request $request)
    {
        $user = auth()->user();

        if ($user->isRestrictedToStore()) {
            abort(403, 'Votre compte est rattaché à votre magasin : vous ne pouvez pas changer de section.');
        }

        $data = $request->validate(['section_id' => 'nullable|exists:stores,id']);

        if (empty($data['section_id'])) {
            $request->session()->forget('section_id');

            return redirect()->route('home')->with('success', 'Vue globale : toutes les sections.');
        }

        $store = Store::findOrFail($data['section_id']);
        $request->session()->put('section_id', $store->id);

        return redirect()->route('home')->with('success', 'Section « ' . $store->store_name . ' » : l\'application est limitée à cette section.');
    }
}
