<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CheckManagerRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_manager_can_access_superuser_routes(): void
    {
        Route::middleware('superuser')->get('/test-superuser-admin', function () {
            return 'ok';
        });

        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'username' => 'admin_test',
            'role_id' => User::ROLE_ADMIN,
            'phone' => '0600000000',
            'status' => 'Active',
            'token' => '',
        ]);

        $manager = User::create([
            'name' => 'Manager Test',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'username' => 'manager_test',
            'role_id' => User::ROLE_MANAGER,
            'phone' => '0600000001',
            'status' => 'Active',
            'token' => '',
        ]);

        $this->actingAs($admin)->get('/test-superuser-admin')->assertOk();
        $this->actingAs($manager)->get('/test-superuser-admin')->assertOk();
    }

    public function test_customer_is_blocked_from_superuser_routes(): void
    {
        Route::middleware('superuser')->get('/test-superuser-customer', function () {
            return 'ok';
        });

        $customer = User::create([
            'name' => 'Customer Test',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'username' => 'customer_test',
            'role_id' => User::ROLE_CUSTOMER,
            'phone' => '0600000002',
            'status' => 'Active',
            'token' => '',
        ]);

        $this->actingAs($customer)->get('/test-superuser-customer')->assertForbidden();
    }
}
