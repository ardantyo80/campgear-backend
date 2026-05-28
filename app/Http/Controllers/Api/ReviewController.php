<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // Submit review after booking completed
    public function store(Request $request, $bookingId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        
        // Cek booking milik user dan status completed
        $booking = Booking::where('user_id', $user->id)
            ->where('id', $bookingId)
            ->where('status', 'completed')
            ->firstOrFail();

        // Cek apakah sudah pernah review
        $existingReview = Review::where('booking_id', $bookingId)->exists();
        if ($existingReview) {
            return response()->json([
                'message' => 'You have already reviewed this booking'
            ], 409);
        }

        // Review untuk setiap product dalam booking? Atau satu review per booking?
        // Untuk simplicity, kita buat satu review per booking dengan product_id pertama
        $product = $booking->items->first()->product;
        
        $review = Review::create([
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Review submitted successfully',
            'review' => $review
        ], 201);
    }

    // Get reviews for a product
    public function productReviews($productSlug)
    {
        $product = \App\Models\Product::where('slug', $productSlug)->firstOrFail();
        
        $reviews = Review::where('product_id', $product->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $averageRating = $reviews->avg('rating') ?? 0;
        $totalReviews = $reviews->count();

        return response()->json([
            'average_rating' => round($averageRating, 1),
            'total_reviews' => $totalReviews,
            'reviews' => $reviews
        ]);
    }

    // Get user's reviews
    public function userReviews(Request $request)
    {
        $reviews = Review::where('user_id', $request->user()->id)
            ->with('product', 'booking')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reviews);
    }
}