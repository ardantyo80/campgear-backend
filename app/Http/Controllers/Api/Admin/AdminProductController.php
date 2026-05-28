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
    // Fungsi upload ke ImgBB
    private function uploadToImgBB($base64Image)
    {
        $apiKey = env('IMGBB_API_KEY');
        
        // Remove base64 header if exists
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image)) {
            $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
        }
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.imgbb.com/1/upload?key={$apiKey}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['image' => $base64Image],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode !== 200) {
            Log::error('ImgBB upload failed', ['http_code' => $httpCode, 'response' => $response]);
            return null;
        }
        
        $data = json_decode($response, true);
        return $data['data']['url'] ?? null;
    }

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
        if ($request->thumbnail && preg_match('/^data:image\/(\w+);base64,/', $request->thumbnail)) {
            try {
                $uploadedUrl = $this->uploadToImgBB($request->thumbnail);
                
                if ($uploadedUrl) {
                    $thumbnailPath = $uploadedUrl;
                } else {
                    return response()->json(['error' => 'Gagal upload gambar ke ImgBB'], 500);
                }
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
        if ($request->thumbnail && preg_match('/^data:image\/(\w+);base64,/', $request->thumbnail)) {
            try {
                $uploadedUrl = $this->uploadToImgBB($request->thumbnail);
                
                if ($uploadedUrl) {
                    $thumbnailPath = $uploadedUrl;
                } else {
                    return response()->json(['error' => 'Gagal upload gambar ke ImgBB'], 500);
                }
            } catch (\Exception $e) {
                Log::error('Upload gambar error: ' . $e->getMessage());
                return response()->json(['error' => 'Gagal memproses gambar: ' . $e->getMessage()], 500);
            }
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

    // Delete product
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            
            // Hapus semua relasi sebelum menghapus produk
            BookingItem::where('product_id', $id)->delete();
            Wishlist::where('product_id', $id)->delete();
            Review::where('product_id', $id)->delete();
            
            $product->delete();
            
            return response()->json(['message' => 'Produk berhasil dihapus']);
            
        } catch (\Exception $e) {
            Log::error('Delete product error: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menghapus produk: ' . $e->getMessage()], 500);
        }
    }
}