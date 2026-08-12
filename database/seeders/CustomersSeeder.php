<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomersSeeder extends Seeder
{
    public function run()
    {
        $customers = [
            ['mark' => 'CBF-0001', 'customerName' => 'Ibrahima Diallo', 'tel' => '625112233', 'address' => 'Madina, Conakry', 'email' => null],
            ['mark' => 'CBF-0002', 'customerName' => 'Mariam Camara', 'tel' => '621445566', 'address' => 'Matoto, Conakry', 'email' => null],
            ['mark' => 'CBF-0003', 'customerName' => 'Alpha Oumar Bah', 'tel' => '628778899', 'address' => 'Ratoma, Conakry', 'email' => null],
            ['mark' => 'CBF-0004', 'customerName' => 'Fatoumata Barry', 'tel' => '622334455', 'address' => 'Kaloum, Conakry', 'email' => null],
            ['mark' => 'CBF-0005', 'customerName' => 'Ousmane Sylla', 'tel' => '629667788', 'address' => 'Dixinn, Conakry', 'email' => null],
            ['mark' => 'CBF-0006', 'customerName' => 'Aïcha Condé', 'tel' => '624998877', 'address' => 'Matam, Conakry', 'email' => null],
        ];

        foreach ($customers as $customer) {
            DB::table('customers')->updateOrInsert(
                ['mark' => $customer['mark']],
                $customer + ['total_taken' => 0, 'total_repaid' => 0, 'balance' => 0, 'fidelite' => 1]
            );
        }
    }
}
