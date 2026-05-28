<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'category_id' => 1,
                'name' => 'Tenda Kapasitas 4 Orang',
                'slug' => 'tenda-4-orang',
                'description' => 'Tenda kapasitas 4 orang, waterproof, mudah dipasang. Cocok untuk camping keluarga.',
                'price_per_day' => 75000,
                'stock' => 5,
                'thumbnail' => 'https://placehold.co/600x400/2E7D32/white?text=Tenda+4P',
                'images' => json_encode(['https://placehold.co/600x400/2E7D32/white?text=Tenda+4P']),
                'is_active' => true,
            ],
            [
                'category_id' => 1,
                'name' => 'Tenda Kapasitas 2 Orang',
                'slug' => 'tenda-2-orang',
                'description' => 'Tenda ringan kapasitas 2 orang, cocok untuk backpacker.',
                'price_per_day' => 50000,
                'stock' => 1, // stok terbatas
                'thumbnail' => 'https://placehold.co/600x400/2E7D32/white?text=Tenda+2P',
                'images' => json_encode(['https://placehold.co/600x400/2E7D32/white?text=Tenda+2P']),
                'is_active' => true,
            ],
            [
                'category_id' => 2,
                'name' => 'Kompor Portable',
                'slug' => 'kompor-portable',
                'description' => 'Kompor gas portable, ringan, mudah dibawa.',
                'price_per_day' => 25000,
                'stock' => 8,
                'thumbnail' => 'https://placehold.co/600x400/2E7D32/white?text=Kompor+Portable',
                'images' => json_encode(['https://placehold.co/600x400/2E7D32/white?text=Kompor+Portable']),
                'is_active' => true,
            ],
            [
                'category_id' => 2,
                'name' => 'Set Panci Camping 3 pcs',
                'slug' => 'set-panci-camping',
                'description' => 'Set panci camping 3 ukuran, anti lengket, mudah dibersihkan.',
                'price_per_day' => 20000,
                'stock' => 6,
                'thumbnail' => 'https://placehold.co/600x400/2E7D32/white?text=Set+Panci',
                'images' => json_encode(['https://placehold.co/600x400/2E7D32/white?text=Set+Panci']),
                'is_active' => true,
            ],
            [
                'category_id' => 3,
                'name' => 'Carrier 60L',
                'slug' => 'carrier-60l',
                'description' => 'Carrier kapasitas 60 liter, fitur waist belt, nyaman untuk trekking.',
                'price_per_day' => 45000,
                'stock' => 4,
                'thumbnail' => 'https://placehold.co/600x400/2E7D32/white?text=Carrier+60L',
                'images' => json_encode(['https://placehold.co/600x400/2E7D32/white?text=Carrier+60L']),
                'is_active' => true,
            ],
            [
                'category_id' => 4,
                'name' => 'Sleeping Bag',
                'slug' => 'sleeping-bag',
                'description' => 'Sleeping bag hangat, ringan, kompresibel.',
                'price_per_day' => 30000,
                'stock' => 10,
                'thumbnail' => 'https://placehold.co/600x400/2E7D32/white?text=Sleeping+Bag',
                'images' => json_encode(['https://placehold.co/600x400/2E7D32/white?text=Sleeping+Bag']),
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}