<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        
        $totalBookings = Booking::count();
        $pendingBookings = Booking::where('status', 'pending')->count();
        $totalRevenue = Booking::whereIn('status', ['paid', 'confirmed', 'completed'])->sum('total_price');

        // Chart data: last 7 days bookings
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = Booking::whereDate('created_at', $date)->count();
            $chartData[] = [
                'date' => $date,
                'count' => $count,
            ];
        }

        return response()->json([
            'total_users' => $totalUsers,
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'total_bookings' => $totalBookings,
            'pending_bookings' => $pendingBookings,
            'total_revenue' => $totalRevenue,
            'chart_data' => $chartData,
        ]);
    }
}