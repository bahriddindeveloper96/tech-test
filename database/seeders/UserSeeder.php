<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@techmarket.uz',
            'password' => Hash::make('password'),
            'phone' => '+998901234560',
            'role' => 'admin',
            'status' => 'active'
        ]);

        // Create test sellers
        $sellers = [
            [
                'first_name' => 'Seller',
                'last_name' => 'One',
                'email' => 'seller1@techmarket.uz',
                'phone' => '+998901234567',
                'company' => [
                    'name' => 'TechShop One',
                    'address' => 'Toshkent, Chilonzor',
                    'phone' => '+998712345678',
                    'email' => 'info@techshop1.uz',
                    'tax_number' => '123456789',
                    'registration_number' => 'REG123456'
                ],
                'translations' => [
                    'uz' => [
                        'bio' => 'Texnika do\'koni egasi',
                        'address' => 'Toshkent, Chilonzor tumani'
                    ],
                    'ru' => [
                        'bio' => 'Владелец магазина техники',
                        'address' => 'Ташкент, Чиланзарский район'
                    ],
                    'en' => [
                        'bio' => 'Tech store owner',
                        'address' => 'Tashkent, Chilanzar district'
                    ]
                ]
            ],
            [
                'first_name' => 'Seller',
                'last_name' => 'Two',
                'email' => 'seller2@techmarket.uz',
                'phone' => '+998901234568',
                'company' => [
                    'name' => 'TechShop Two',
                    'address' => 'Toshkent, Yunusobod',
                    'phone' => '+998712345679',
                    'email' => 'info@techshop2.uz',
                    'tax_number' => '123456790',
                    'registration_number' => 'REG123457'
                ],
                'translations' => [
                    'uz' => [
                        'bio' => 'Elektron qurilmalar do\'koni egasi',
                        'address' => 'Toshkent, Yunusobod tumani'
                    ],
                    'ru' => [
                        'bio' => 'Владелец магазина электроники',
                        'address' => 'Ташкент, Юнусабадский район'
                    ],
                    'en' => [
                        'bio' => 'Electronics store owner',
                        'address' => 'Tashkent, Yunusabad district'
                    ]
                ]
            ]
        ];

        foreach ($sellers as $sellerData) {
            $seller = User::create([
                'first_name' => $sellerData['first_name'],
                'last_name' => $sellerData['last_name'],
                'email' => $sellerData['email'],
                'password' => Hash::make('password'),
                'phone' => $sellerData['phone'],
                'role' => 'seller',
                'status' => 'active',
                'company_name' => $sellerData['company']['name'],
                'company_address' => $sellerData['company']['address'],
                'company_phone' => $sellerData['company']['phone'],
                'company_email' => $sellerData['company']['email'],
                'company_tax_number' => $sellerData['company']['tax_number'],
                'company_registration_number' => $sellerData['company']['registration_number']
            ]);

            foreach ($sellerData['translations'] as $locale => $translation) {
                $seller->translations()->create([
                    'locale' => $locale,
                    'bio' => $translation['bio'],
                    'address' => $translation['address']
                ]);
            }
        }

        // Create test customers
        $customers = [
            [
                'first_name' => 'Customer',
                'last_name' => 'One',
                'email' => 'customer1@techmarket.uz',
                'phone' => '+998901234571',
                'translations' => [
                    'uz' => [
                        'bio' => 'Oddiy foydalanuvchi',
                        'address' => 'Toshkent, Mirzo Ulug\'bek tumani'
                    ],
                    'ru' => [
                        'bio' => 'Обычный пользователь',
                        'address' => 'Ташкент, Мирзо-Улугбекский район'
                    ],
                    'en' => [
                        'bio' => 'Regular user',
                        'address' => 'Tashkent, Mirzo Ulugbek district'
                    ]
                ]
            ],
            [
                'first_name' => 'Customer',
                'last_name' => 'Two',
                'email' => 'customer2@techmarket.uz',
                'phone' => '+998901234572',
                'translations' => [
                    'uz' => [
                        'bio' => 'Premium foydalanuvchi',
                        'address' => 'Toshkent, Sergeli tumani'
                    ],
                    'ru' => [
                        'bio' => 'Премиум пользователь',
                        'address' => 'Ташкент, Сергелийский район'
                    ],
                    'en' => [
                        'bio' => 'Premium user',
                        'address' => 'Tashkent, Sergeli district'
                    ]
                ]
            ]
        ];

        foreach ($customers as $customerData) {
            $customer = User::create([
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'email' => $customerData['email'],
                'password' => Hash::make('password'),
                'phone' => $customerData['phone'],
                'role' => 'user',
                'status' => 'active'
            ]);

            foreach ($customerData['translations'] as $locale => $translation) {
                $customer->translations()->create([
                    'locale' => $locale,
                    'bio' => $translation['bio'],
                    'address' => $translation['address']
                ]);
            }
        }
    }
}
