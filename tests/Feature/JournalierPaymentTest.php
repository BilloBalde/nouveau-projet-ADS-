<?php

namespace Tests\Feature;

use App\Http\Controllers\JournalierController;
use App\Models\Journalier;
use App\Models\PaiementJournalier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JournalierPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        DB::purge('sqlite');

        Schema::create('journaliers', function ($table) {
            $table->id();
            $table->string('nomPrenant');
            $table->double('montant');
            $table->text('contenu');
            $table->double('paid')->default(0);
            $table->double('reste');
            $table->date('datePrise');
            $table->timestamps();
        });

        Schema::create('paiement_journaliers', function ($table) {
            $table->id();
            $table->string('reference');
            $table->unsignedBigInteger('journalier_id');
            $table->decimal('versement', 15, 2);
            $table->string('paid_by')->default('cash');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('paiement_journaliers');
        Schema::dropIfExists('journaliers');

        parent::tearDown();
    }

    private function makeDette(float $montant = 10000): Journalier
    {
        return Journalier::create([
            'nomPrenant' => 'Client Test',
            'montant' => $montant,
            'contenu' => 'Dette de test',
            'paid' => 0,
            'reste' => $montant,
            'datePrise' => today()->toDateString(),
        ]);
    }

    public function test_pay_submit_records_partial_payment_and_updates_reste(): void
    {
        $dette = $this->makeDette(10000);

        $request = Request::create('/journalier/paiement', 'POST', [
            'journalier_id' => $dette->id,
            'versement' => '4000',
            'paid_by' => 'cash',
            'notes' => 'Premier versement',
        ]);

        app(JournalierController::class)->paySubmit($request);

        $dette->refresh();
        $this->assertEquals(4000, $dette->paid);
        $this->assertEquals(6000, $dette->reste);
        $this->assertEquals(1, PaiementJournalier::count());
        $this->assertStringStartsWith('PJJ', PaiementJournalier::first()->reference);
    }

    public function test_pay_submit_accumulates_multiple_partial_payments(): void
    {
        $dette = $this->makeDette(10000);

        app(JournalierController::class)->paySubmit(Request::create('/journalier/paiement', 'POST', [
            'journalier_id' => $dette->id,
            'versement' => '4000',
            'paid_by' => 'cash',
            'notes' => 'Premier versement',
        ]));

        app(JournalierController::class)->paySubmit(Request::create('/journalier/paiement', 'POST', [
            'journalier_id' => $dette->id,
            'versement' => '6000',
            'paid_by' => 'cash',
            'notes' => 'Solde',
        ]));

        $dette->refresh();
        $this->assertEquals(10000, $dette->paid);
        $this->assertEquals(0, $dette->reste);
        $this->assertEquals(2, PaiementJournalier::count());
    }

    public function test_pay_submit_rejects_payment_exceeding_remaining_balance(): void
    {
        $dette = $this->makeDette(10000);

        $request = Request::create('/journalier/paiement', 'POST', [
            'journalier_id' => $dette->id,
            'versement' => '15000',
            'paid_by' => 'cash',
            'notes' => 'Trop',
        ]);

        app(JournalierController::class)->paySubmit($request);

        $dette->refresh();
        $this->assertEquals(0, $dette->paid);
        $this->assertEquals(10000, $dette->reste);
        $this->assertEquals(0, PaiementJournalier::count());
    }

    public function test_pay_submit_rejects_when_already_settled(): void
    {
        $dette = $this->makeDette(5000);

        // Le contrôleur recalcule le "reste" à partir de la somme des paiements
        // enregistrés (source de vérité), pas depuis journaliers.reste : on doit donc
        // simuler le solde via un vrai paiement plutôt que via un update() direct.
        PaiementJournalier::create([
            'reference' => 'PJJ-TEST-SETTLED',
            'journalier_id' => $dette->id,
            'versement' => 5000,
            'paid_by' => 'cash',
            'notes' => 'Solde initial simulé',
        ]);

        $request = Request::create('/journalier/paiement', 'POST', [
            'journalier_id' => $dette->id,
            'versement' => '1000',
            'paid_by' => 'cash',
            'notes' => 'Trop tard',
        ]);

        app(JournalierController::class)->paySubmit($request);

        // Toujours 1 seul paiement : celui simulé en amont, aucun nouveau versement
        // n'a dû être accepté sur une dette déjà soldée.
        $this->assertEquals(1, PaiementJournalier::count());
    }
}
