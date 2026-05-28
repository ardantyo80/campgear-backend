<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'booking_number', 'user_id', 'start_date', 'end_date', 'total_days',
        'total_price', 'status', 'payment_status', 'midtrans_transaction_id', 'notes'
    ];

    // Relasi
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(BookingItem::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    // Helper: generate booking number
    public static function generateBookingNumber()
    {
        return 'BOOK-' . date('Ymd') . '-' . strtoupper(uniqid());
    }
}