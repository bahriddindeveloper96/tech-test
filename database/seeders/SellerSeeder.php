<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SellerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sellers = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'seller1@example.com',
                'phone' => '+998901234567',
                'password' => Hash::make('password'),
                'role' => 'seller',
                'status' => 'active',
                'company_name' => 'Tech Store LLC',
                'company_address' => '123 Main Street, Tashkent',
                'company_phone' => '+998901234568',
                'company_email' => 'info@techstore.uz',
                'company_tax_number' => '123456789',
                'company_registration_number' => 'REG123456',
            ],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'seller2@example.com',
                'phone' => '+998901234569',
                'password' => Hash::make('password'),
                'role' => 'seller',
                'status' => 'active',
                'company_name' => 'Mobile World',
                'company_address' => '456 Second Street, Tashkent',
                'company_phone' => '+998901234570',
                'company_email' => 'info@mobileworld.uz',
                'company_tax_number' => '987654321',
                'company_registration_number' => 'REG654321',
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Johnson',
                'email' => 'seller3@example.com', 
                'phone' => '+998901234571',
                'password' => Hash::make('password'),
                'role' => 'seller',
                'status' => 'active',
                'company_name' => 'Gadget Hub',
                'company_address' => '789 Third Street, Tashkent',
                'company_phone' => '+998901234572',
                'company_email' => 'info@gadgethub.uz',
                'company_tax_number' => '456789123',
                'company_registration_number' => 'REG789123',
            ],
        ];

        foreach ($sellers as $seller) {
            User::create($seller);
        }
    }
}
