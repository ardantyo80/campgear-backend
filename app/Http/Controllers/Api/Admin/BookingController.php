<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    // List semua booking
    public function index(Request $request)
    {
        $query = Booking::with('user', 'items.product');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();
        return response()->json($bookings);
    }

    // Show single booking
    public function show($id)
    {
        $booking = Booking::with('user', 'items.product', 'review')->findOrFail($id);
        return response()->json($booking);
    }

    // Update booking status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,confirmed,completed,cancelled',
            'payment_status' => 'sometimes|in:unpaid,paid,expired,refunded',
        ]);

        $booking = Booking::findOrFail($id);
        $booking->status = $request->status;

        if ($request->has('payment_status')) {
            $booking->payment_status = $request->payment_status;
        }

        $booking->save();

        return response()->json($booking);
    }

    // Get statistics for dashboard
    public function stats()
    {
        $totalBookings = Booking::count();
        $pendingBookings = Booking::where('status', 'pending')->count();
        $paidBookings = Booking::where('status', 'paid')->count();
        $completedBookings = Booking::where('status', 'completed')->count();
        
        $totalRevenue = Booking::where('status', 'paid')
            ->orWhere('status', 'confirmed')
            ->orWhere('status', 'completed')
            ->sum('total_price');

        $recentBookings = Booking::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_bookings' => $totalBookings,
            'pending_bookings' => $pendingBookings,
            'paid_bookings' => $paidBookings,
            'completed_bookings' => $completedBookings,
            'total_revenue' => $totalRevenue,
            'recent_bookings' => $recentBookings,
        ]);
    }
}