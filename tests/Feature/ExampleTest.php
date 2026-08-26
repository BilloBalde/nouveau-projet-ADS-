<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    // La page d'accueil interroge produits, catégories et boutiques : sans les
    // tables, elle échoue avant même d'avoir rendu quoi que ce soit.
    use RefreshDatabase;

    /**
     * Vérifie que la vitrine répond, même sans aucune donnée.
     */
    public function test_the_application_returns_a_successful_response()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
