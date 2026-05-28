<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // List semua produk (untuk customer)
    public function index(Request $request)
    {
        $query = Product::where('is_active', true)->with('category');

        // Filter by category
        if ($request->has('category')) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        $products = $query->latest()->get();

        $products->each(function ($product) {
            $product->stock_limited = $product->stock < 2;
            $product->average_rating = $product->reviews()->avg('rating') ?? 0;
        });

        return response()->json($products);
    }

    // Detail produk
    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with('category', 'reviews.user')
            ->firstOrFail();

        $product->stock_limited = $product->stock < 2;
        $product->average_rating = $product->reviews()->avg('rating') ?? 0;

        return response()->json($product);
    }

    // Products by category slug
    public function byCategory($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        
        $products = Product::where('category_id', $category->id)
            ->where('is_active', true)
            ->get();

        $products->each(function ($product) {
            $product->stock_limited = $product->stock < 2;
            $product->average_rating = $product->reviews()->avg('rating') ?? 0;
        });

        return response()->json($products);
    }
}