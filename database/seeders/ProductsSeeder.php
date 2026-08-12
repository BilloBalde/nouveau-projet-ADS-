<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductsSeeder extends Seeder
{
    /**
     * 'price_sale' = prix de revient (coût d'achat), 'price' = prix de vente
     * affiché au client. Convention reprise de ProductController::store()
     * (price = price_sale * 1.3), respectée ici pour rester cohérent.
     */
    public function run()
    {
        $products = [
            ['sku' => 'CBF-PNT-001', 'libelle' => 'Peinture blanche 4L', 'description' => 'Pot de peinture blanche 4 litres, intérieur/extérieur.', 'revient' => 180000],
            ['sku' => 'CBF-PNT-002', 'libelle' => 'Peinture noire 1L', 'description' => 'Pot de peinture noire 1 litre.', 'revient' => 55000],
            ['sku' => 'CBF-PNC-001', 'libelle' => 'Pinceau large', 'description' => 'Pinceau plat 4 pouces, manche bois.', 'revient' => 8000],

            ['sku' => 'CBF-MRT-001', 'libelle' => 'Marteau menuisier', 'description' => 'Marteau tête acier 500g, manche fibre.', 'revient' => 45000],
            ['sku' => 'CBF-PEL-001', 'libelle' => 'Pelle carrée', 'description' => 'Pelle carrée manche bois, usage bâtiment.', 'revient' => 60000],
            ['sku' => 'CBF-PIO-001', 'libelle' => 'Pioche', 'description' => 'Pioche double usage, manche bois renforcé.', 'revient' => 70000],
            ['sku' => 'CBF-SCI-001', 'libelle' => 'Scie à métaux', 'description' => 'Scie à métaux avec lame de rechange.', 'revient' => 30000],

            ['sku' => 'CBF-CAD-001', 'libelle' => 'Cadenas moyen 40mm', 'description' => 'Cadenas laiton anse acier, 40mm.', 'revient' => 25000],
            ['sku' => 'CBF-SER-001', 'libelle' => 'Serrure de porte 3 points', 'description' => 'Serrure de porte 3 points avec clés.', 'revient' => 85000],
            ['sku' => 'CBF-VER-001', 'libelle' => 'Verrou de sécurité', 'description' => 'Verrou de sécurité pour porte ou portail.', 'revient' => 35000],

            ['sku' => 'CBF-TUY-001', 'libelle' => 'Tuyau PVC 3m Ø100', 'description' => 'Tuyau PVC diamètre 100mm, longueur 3m.', 'revient' => 35000],
            ['sku' => 'CBF-ROB-001', 'libelle' => 'Robinet inox', 'description' => 'Robinet de puisage inox 1/2 pouce.', 'revient' => 45000],
            ['sku' => 'CBF-RAC-001', 'libelle' => 'Raccord PVC coudé', 'description' => 'Raccord coudé 90°, PVC pression.', 'revient' => 12000],

            ['sku' => 'CBF-FIL-001', 'libelle' => 'Fil électrique rouleau 100m', 'description' => 'Câble électrique souple 2.5mm², rouleau 100m.', 'revient' => 250000],
            ['sku' => 'CBF-AMP-001', 'libelle' => 'Ampoule LED 12W', 'description' => 'Ampoule LED culot standard, 12W.', 'revient' => 12000],
            ['sku' => 'CBF-DIS-001', 'libelle' => 'Disjoncteur 20A', 'description' => 'Disjoncteur modulaire 20A, 1 pôle.', 'revient' => 60000],

            ['sku' => 'CBF-CLO-001', 'libelle' => 'Clous assortis 1kg', 'description' => 'Sachet de clous assortis, 1kg.', 'revient' => 15000],
            ['sku' => 'CBF-VIS-001', 'libelle' => 'Boîte de vis assorties', 'description' => 'Boîte de vis à bois assorties.', 'revient' => 20000],
            ['sku' => 'CBF-BRW-001', 'libelle' => 'Brouette', 'description' => 'Brouette chantier, roue gonflable.', 'revient' => 350000],
        ];

        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(
                ['sku' => $product['sku']],
                [
                    'libelle' => $product['libelle'],
                    'description' => $product['description'],
                    'image' => 'ib profile.jpg',
                    'price_sale' => $product['revient'],
                    'price' => round($product['revient'] * 1.3, 2),
                    'pcs' => 0,
                ]
            );
        }
    }
}
