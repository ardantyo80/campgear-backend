<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\BookingItem;
use App\Models\Wishlist;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AdminProductController extends Controller
{
    // List semua produk
    public function index()
    {
        $products = Product::with('category')->orderBy('created_at', 'desc')->get();
        return response()->json($products);
    }

    // Store produk baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'price_per_day' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
            'thumbnail' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $thumbnailPath = $request->thumbnail;
        
        // Cek apakah thumbnail adalah base64 image (upload dari file)
        if ($request->thumbnail && preg_match('/^data:image\/(\w+);base64,/', $request->thumbnail, $matches)) {
            try {
                // Decode base64
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $request->thumbnail));
                
                // Validasi: pastikan $imageData tidak kosong
                if (empty($imageData)) {
                    return response()->json(['error' => 'Gambar corrupt, coba upload ulang'], 400);
                }
                
                $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
                $filename = time() . '_' . uniqid() . '.' . $extension;
                
                $destinationPath = public_path('images/products');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                
                // Simpan file
                $bytesWritten = file_put_contents($destinationPath . '/' . $filename, $imageData);
                
                // Validasi: pastikan file berhasil disimpan
                if ($bytesWritten === false || $bytesWritten === 0) {
                    return response()->json(['error' => 'Gagal menyimpan gambar'], 500);
                }
                
                $thumbnailPath = '/images/products/' . $filename;
                
            } catch (\Exception $e) {
                Log::error('Upload gambar error: ' . $e->getMessage());
                return response()->json(['error' => 'Gagal memproses gambar: ' . $e->getMessage()], 500);
            }
        }

        $product = Product::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . uniqid(),
            'category_id' => $request->category_id,
            'description' => $request->description,
            'price_per_day' => $request->price_per_day,
            'stock' => $request->stock,
            'thumbnail' => $thumbnailPath,
            'images' => json_encode([]),
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json($product, 201);
    }

    // Show single product
    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);
        return response()->json($product);
    }

    // Update product
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'description' => 'sometimes|string',
            'price_per_day' => 'sometimes|integer|min:0',
            'stock' => 'sometimes|integer|min:0',
            'thumbnail' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $thumbnailPath = $product->thumbnail;
        
        // Cek apakah thumbnail adalah base64 image (upload dari file)
        if ($request->thumbnail && preg_match('/^data:image\/(\w+);base64,/', $request->thumbnail, $matches)) {
            try {
                // Hapus gambar lama jika ada dan bukan URL eksternal
                if ($product->thumbnail && str_starts_with($product->thumbnail, '/images/products/')) {
                    $oldPath = public_path($product->thumbnail);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                
                // Decode base64
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $request->thumbnail));
                
                // Validasi: pastikan $imageData tidak kosong
                if (empty($imageData)) {
                    return response()->json(['error' => 'Gambar corrupt, coba upload ulang'], 400);
                }
                
                $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
                $filename = time() . '_' . uniqid() . '.' . $extension;
                
                $destinationPath = public_path('images/products');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                
                // Simpan file
                $bytesWritten = file_put_contents($destinationPath . '/' . $filename, $imageData);
                
                // Validasi: pastikan file berhasil disimpan
                if ($bytesWritten === false || $bytesWritten === 0) {
                    return response()->json(['error' => 'Gagal menyimpan gambar'], 500);
                }
                
                $thumbnailPath = '/images/products/' . $filename;
                
            } catch (\Exception $e) {
                Log::error('Upload gambar error: ' . $e->getMessage());
                return response()->json(['error' => 'Gagal memproses gambar: ' . $e->getMessage()], 500);
            }
        } elseif ($request->thumbnail === null || $request->thumbnail === '') {
            // Jika thumbnail dihapus (null atau empty string)
            if ($product->thumbnail && str_starts_with($product->thumbnail, '/images/products/')) {
                $oldPath = public_path($product->thumbnail);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $thumbnailPath = null;
        }

        $product->update([
            'name' => $request->name ?? $product->name,
            'category_id' => $request->category_id ?? $product->category_id,
            'description' => $request->description ?? $product->description,
            'price_per_day' => $request->price_per_day ?? $product->price_per_day,
            'stock' => $request->stock ?? $product->stock,
            'thumbnail' => $thumbnailPath,
            'is_active' => $request->is_active ?? $product->is_active,
        ]);

        if ($request->has('name') && $request->name !== $product->name) {
            $product->slug = Str::slug($request->name) . '-' . uniqid();
            $product->save();
        }

        return response()->json($product);
    }

    // Delete product - FIXED with cascade delete
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            
            // Hapus semua relasi sebelum menghapus produk
            // 1. Hapus dari booking_items
            BookingItem::where('product_id', $id)->delete();
            
            // 2. Hapus dari wishlists
            Wishlist::where('product_id', $id)->delete();
            
            // 3. Hapus dari reviews
            Review::where('product_id', $id)->delete();
            
            // Hapus file gambar jika ada dan bukan URL eksternal
            if ($product->thumbnail && str_starts_with($product->thumbnail, '/images/products/')) {
                $imagePath = public_path($product->thumbnail);
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            
            // Hapus produk
            $product->delete();
            
            return response()->json([
                'message' => 'Produk berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Delete product error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Gagal menghapus produk: ' . $e->getMessage()
            ], 500);
        }
    }
}