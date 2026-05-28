<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'price_per_day',
        'stock', 'thumbnail', 'images', 'is_active'
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
    ];

    // Relasi
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function bookingItems()
    {
        return $this->hasMany(BookingItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    // Helper: stok terbatas
    public function isStockLimited()
    {
        return $this->stock < 2;
    }

    // Helper: rating rata-rata
    public function averageRating()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }
}