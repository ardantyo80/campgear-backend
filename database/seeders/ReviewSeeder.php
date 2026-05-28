<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        Review::create([
            'booking_id' => 1,
            'user_id' => 2,
            'product_id' => 1,
            'rating' => 5,
            'comment' => 'Tenda sangat bagus, waterproof dan mudah dipasang!',
        ]);
        
        Review::create([
            'booking_id' => 2,
            'user_id' => 2,
            'product_id' => 3,
            'rating' => 4,
            'comment' => 'Kompor portable oke, tapi gasnya agak boros.',
        ]);
    }
}