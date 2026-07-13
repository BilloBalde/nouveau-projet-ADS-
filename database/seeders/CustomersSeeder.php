<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomersSeeder extends Seeder
{
    public function run()
    {
        $customers = [
            [
                'mark' => 'AMD001',
                'customerName' => 'Client Alpha',
                'tel' => '610000001',
                'address' => 'Conakry',
                'email' => 'alpha@example.com',
                'total_taken' => 0,
                'total_repaid' => 0,
                'balance' => 0,
                'fidelite' => 1,
            ],
            [
                'mark' => 'AMD002',
                'customerName' => 'Client Beta',
                'tel' => '610000002',
                'address' => 'Labe',
                'email' => 'beta@example.com',
                'total_taken' => 0,
                'total_repaid' => 0,
                'balance' => 0,
                'fidelite' => 1,
            ],
            [
                'mark' => 'AMD003',
                'customerName' => 'Client Gamma',
                'tel' => '610000003',
                'address' => 'Kankan',
                'email' => 'gamma@example.com',
                'total_taken' => 0,
                'total_repaid' => 0,
                'balance' => 0,
                'fidelite' => 1,
            ],
        ];

        foreach ($customers as $customer) {
            DB::table('customers')->updateOrInsert(
                ['mark' => $customer['mark']],
                $customer
            );
        }
    }
}
