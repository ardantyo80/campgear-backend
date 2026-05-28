<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Tenda', 'slug' => 'tenda'],
            ['name' => 'Cooking Gear', 'slug' => 'cooking-gear'],
            ['name' => 'Carrier & Backpack', 'slug' => 'carrier-backpack'],
            ['name' => 'Sleeping Bag', 'slug' => 'sleeping-bag'],
            ['name' => 'Peralatan Camping', 'slug' => 'peralatan-camping'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'name' => $category['name'],
                'slug' => $category['slug'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}