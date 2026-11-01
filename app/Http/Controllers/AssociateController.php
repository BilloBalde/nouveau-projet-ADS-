<?php

namespace App\Http\Controllers;

use App\Models\Associate;
use App\Services\AssociesService;
use Illuminate\Http\Request;

/**
 * Associés : leur fiche, leur compte de l'année, et la clôture annuelle où
 * le bénéfice est partagé et chacun est soldé.
 */
class AssociateController extends Controller
{
    public function __construct(private AssociesService $associes)
    {
        $this->middleware('auth.check');
        $this->middleware('permission:associates.manage');
    }

    public function index(Request $request)
    {
        $annee = $this->anneeDemandee($request->query('annee'));
        $associes = Associate::orderByDesc('active')->orderBy('name')->get();

        $lignes = $associes->map(fn (Associate $a) => (object) [
            'associe' => $a,
            'compte' => $this->associes->compte($a, $annee),
        ]);

        return view('associates.index', [
            'annee' => $annee,
            'lignes' => $lignes,
            'cloturee' => $this->associes->estCloturee($annee),
            'totaux' => [
                'part' => $lignes->sum(fn ($l) => $l->compte['part']),
                'sorties' => $lignes->sum(fn ($l) => $l->compte['sorties']),
                'solde' => $lignes->sum(fn ($l) => $l->compte['solde']),
            ],
        ]);
    }

    public function create()
    {
        return view('associates.form', ['associe' => new Associate(['active' => true])]);
    }

    public function store(Request $request)
    {
        $associe = Associate::create($this->valider($request));

        return redirect()->route('associates.show', $associe)->with('success', "Associé « {$associe->name} » enregistré.");
    }

    public function show(Request $request, Associate $associate)
    {
        $annee = $this->anneeDemandee($request->query('annee'));

        return view('associates.show', [
            'associe' => $associate,
            'annee' => $annee,
            'compte' => $this->associes->compte($associate, $annee),
            'sorties' => $this->associes->sortiesDe($associate, $annee)->get(),
            'historique' => $associate->closings()->orderByDesc('year')->get(),
        ]);
    }

    public function edit(Associate $associate)
    {
        return view('associates.form', ['associe' => $associate]);
    }

    public function update(Request $request, Associate $associate)
    {
        $associate->update($this->valider($request));

        return redirect()->route('associates.show', $associate)->with('success', 'Fiche de l\'associé mise à jour.');
    }

    /** Fin d'année : partage du bénéfice et solde de chaque associé. */
    public function exercice(int|string|null $annee = null)
    {
        $annee = $this->anneeDemandee($annee);
        $associes = $this->associes->associesDeLAnnee($annee);

        $lignes = $associes->map(fn (Associate $a) => (object) [
            'associe' => $a,
            'compte' => $this->associes->compte($a, $annee),
        ]);
        $benefice = $this->associes->beneficeDeLAnnee($annee);
        $parts = (float) $lignes->sum(fn ($l) => $l->compte['part']);

        return view('associates.annee', [
            'annee' => $annee,
            'lignes' => $lignes,
            'cloturee' => $this->associes->estCloturee($annee),
            'anneeEnAttente' => $this->associes->anneeAnterieureOuverte($annee),
            'benefice' => $benefice,
            'parts' => $parts,
            'resteBoutique' => $benefice - $parts,
            'totaux' => [
                'report' => $lignes->sum(fn ($l) => $l->compte['report']),
                'sorties' => $lignes->sum(fn ($l) => $l->compte['sorties']),
                'a_verser' => $lignes->sum(fn ($l) => $l->compte['a_verser']),
                'a_reporter' => $lignes->sum(fn ($l) => $l->compte['a_reporter']),
            ],
        ]);
    }

    public function enregistrerParts(Request $request, int $annee)
    {
        $data = $request->validate([
            'parts' => 'required|array',
            'parts.*' => 'nullable|numeric|min:0',
        ], [
            'parts.*.numeric' => 'Une part doit être un montant.',
            'parts.*.min' => 'Une part ne peut pas être négative.',
        ]);

        $connus = Associate::whereIn('id', array_keys($data['parts']))->pluck('id')->all();
        $parts = array_intersect_key($data['parts'], array_flip($connus));

        try {
            $this->associes->enregistrerParts($annee, $parts);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('associates.annee', $annee)->with('success', "Parts du bénéfice $annee enregistrées.");
    }

    public function cloturer(int $annee)
    {
        try {
            $clotures = $this->associes->cloturer($annee);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $verse = (float) $clotures->sum('paid');

        return redirect()->route('associates.annee', $annee)->with('success', sprintf(
            'Année %d clôturée : %s à verser aux associés.',
            $annee,
            francs($verse)
        ));
    }

    private function valider(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:40',
            'notes' => 'nullable|string|max:2000',
            'active' => 'nullable|boolean',
        ], [
            'name.required' => 'Le nom de l\'associé est obligatoire.',
        ]);

        return [...$data, 'active' => $request->boolean('active')];
    }

    /** Année demandée, bornée à une plage raisonnable ; l'année en cours par défaut. */
    private function anneeDemandee(int|string|null $annee): int
    {
        $annee = (int) $annee;

        return $annee >= 2000 && $annee <= (int) now()->year + 1 ? $annee : (int) now()->year;
    }
}
