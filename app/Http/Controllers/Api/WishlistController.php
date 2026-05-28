<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    // Get user's wishlist
    public function index(Request $request)
    {
        $wishlists = Wishlist::where('user_id', $request->user()->id)
            ->with('product')
            ->get();

        // Tambahkan stock_limited info
        $wishlists->each(function ($item) {
            if ($item->product) {
                $item->product->stock_limited = $item->product->isStockLimited();
                $item->product->average_rating = $item->product->averageRating();
            }
        });

        return response()->json($wishlists);
    }

    // Add to wishlist
    public function store(Request $request, $productId)
{
    $user = $request->user();
    
    // Cek apakah product ada
    $product = Product::find($productId);
    
    if (!$product) {
        return response()->json([
            'message' => 'Product not found'
        ], 404);
    }
    
    // Cek apakah sudah ada di wishlist
    $exists = Wishlist::where('user_id', $user->id)
        ->where('product_id', $productId)
        ->exists();

    if ($exists) {
        return response()->json([
            'message' => 'Product already in wishlist'
        ], 409);
    }

    $wishlist = Wishlist::create([
        'user_id' => $user->id,
        'product_id' => $productId,
    ]);

    return response()->json([
        'message' => 'Added to wishlist',
        'wishlist' => $wishlist->load('product')
    ], 201);
}

    // Remove from wishlist
    public function destroy(Request $request, $productId)
    {
        $wishlist = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->firstOrFail();

        $wishlist->delete();

        return response()->json([
            'message' => 'Removed from wishlist'
        ]);
    }
}