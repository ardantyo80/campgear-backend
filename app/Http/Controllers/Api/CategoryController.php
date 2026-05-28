<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    // List semua kategori
    public function index()
    {
        $categories = Category::withCount('products')->get();
        return response()->json($categories);
    }
}