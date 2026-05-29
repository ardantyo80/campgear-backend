<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Product;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    protected $midtrans;

    public function __construct(MidtransService $midtrans)
    {
        $this->midtrans = $midtrans;
    }

    // Create new booking (sebelum bayar)
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        
        // Hitung total hari
        $startDate = new \DateTime($request->start_date);
        $endDate = new \DateTime($request->end_date);
        $totalDays = $startDate->diff($endDate)->days;

        if ($totalDays < 1) {
            return response()->json(['error' => 'Minimum rental period is 1 day'], 422);
        }

        DB::beginTransaction();

        try {
            $bookingNumber = Booking::generateBookingNumber();
            $totalPrice = 0;
            $bookingItems = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Cek stok
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Product {$product->name} stock is insufficient");
                }
                
                // ✅ KURANGI STOK
                $product->stock -= $item['quantity'];
                $product->save();

                $subtotal = $item['quantity'] * $product->price_per_day * $totalDays;
                $totalPrice += $subtotal;

                $bookingItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price_per_day' => $product->price_per_day,
                    'subtotal' => $subtotal,
                ];
            }

            // Create booking
            $booking = Booking::create([
                'booking_number' => $bookingNumber,
                'user_id' => $user->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'total_days' => $totalDays,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'notes' => $request->notes,
            ]);

            // Create booking items
            foreach ($bookingItems as $item) {
                $booking->items()->create($item);
            }

            DB::commit();

            return response()->json([
                'message' => 'Booking created successfully',
                'booking' => $booking->load('items.product'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Generate Midtrans payment token
    public function pay(Request $request, $id)
    {
        $booking = Booking::with('items.product')->findOrFail($id);
        
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($booking->payment_status === 'paid') {
            return response()->json(['error' => 'Booking already paid'], 422);
        }

        $uniqueOrderId = $booking->booking_number . '-' . time();

        $transactionDetails = [
            'order_id' => $uniqueOrderId,
            'gross_amount' => $booking->total_price,
        ];

        $customerDetails = [
            'first_name' => $booking->user->name,
            'email' => $booking->user->email,
            'phone' => $booking->user->phone ?? '',
        ];

        $itemDetails = [];
        foreach ($booking->items as $item) {
            $itemDetails[] = [
                'id' => $item->product_id,
                'price' => $item->price_per_day * $booking->total_days,
                'quantity' => $item->quantity,
                'name' => $item->product->name,
            ];
        }

        $params = [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
            'item_details' => $itemDetails,
        ];

        $result = $this->midtrans->createTransaction($params);

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 500);
        }

        return response()->json([
            'snap_token' => $result['snap_token'],
            'booking_number' => $booking->booking_number,
        ]);
    }

    // Update status after payment success (called from frontend)
    public function updateStatus(Request $request)
    {
        $request->validate([
            'booking_number' => 'required|string',
            'transaction_id' => 'required|string',
            'payment_type' => 'nullable|string',
        ]);
        
        $booking = Booking::where('booking_number', $request->booking_number)->first();
        
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
        
        $booking->update([
            'payment_status' => 'paid',
            'status' => 'paid',
            'midtrans_transaction_id' => $request->transaction_id,
        ]);
        
        return response()->json(['message' => 'Status updated successfully']);
    }

    // Get user's bookings
    public function index(Request $request)
    {
        $bookings = Booking::where('user_id', $request->user()->id)
            ->with('items.product')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($bookings);
    }

    // Get single booking detail
    public function show(Request $request, $id)
    {
        $booking = Booking::with('items.product', 'review')
            ->findOrFail($id);
        
        if ($booking->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($booking);
    }

    // Cancel booking (if still pending) - ✅ RETURN STOCK
    public function cancel(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['error' => 'Booking cannot be cancelled'], 422);
        }

        // ✅ KEMBALIKAN STOK
        foreach ($booking->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->stock += $item->quantity;
                $product->save();
            }
        }

        $booking->update([
            'status' => 'cancelled',
            'payment_status' => 'expired',
        ]);

        return response()->json(['message' => 'Booking cancelled successfully']);
    }

    // Midtrans webhook handler - ✅ RETURN STOCK if expired
    public function webhook(Request $request)
    {
        Log::info('Webhook received:', $request->all());
        
        try {
            $payload = $request->getContent();
            $notification = json_decode($payload, true);
            
            if (!$notification) {
                Log::error('Webhook: Invalid payload');
                return response()->json(['error' => 'Invalid payload'], 400);
            }
            
            $orderId = $notification['order_id'] ?? null;
            
            if (!$orderId) {
                Log::error('Webhook: order_id not found', $notification);
                return response()->json(['error' => 'order_id not found'], 400);
            }
            
            $baseOrderId = explode('-', $orderId);
            if (count($baseOrderId) >= 3) {
                $bookingNumber = $baseOrderId[0] . '-' . $baseOrderId[1] . '-' . $baseOrderId[2];
            } else {
                $bookingNumber = $orderId;
            }
            
            $booking = Booking::where('booking_number', $bookingNumber)->first();
            
            if (!$booking) {
                Log::error('Webhook: Booking not found', [
                    'order_id' => $orderId, 
                    'booking_number' => $bookingNumber
                ]);
                return response()->json(['error' => 'Booking not found'], 404);
            }
            
            $transactionStatus = $notification['transaction_status'] ?? null;
            $fraudStatus = $notification['fraud_status'] ?? null;
            
            Log::info('Processing webhook', [
                'booking_number' => $booking->booking_number,
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus
            ]);
            
            if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
                if ($fraudStatus == 'accept' || !$fraudStatus) {
                    $booking->update([
                        'payment_status' => 'paid',
                        'status' => 'paid',
                        'midtrans_transaction_id' => $notification['transaction_id'] ?? null
                    ]);
                    Log::info('Booking updated to paid', ['booking_number' => $booking->booking_number]);
                }
            } elseif ($transactionStatus == 'pending') {
                $booking->update([
                    'payment_status' => 'pending',
                    'status' => 'pending',
                    'midtrans_transaction_id' => $notification['transaction_id'] ?? null
                ]);
                Log::info('Booking updated to pending', ['booking_number' => $booking->booking_number]);
            } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
                // ✅ KEMBALIKAN STOK JIKA PAYMENT EXPIRED/DENY/CANCEL
                foreach ($booking->items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->stock += $item->quantity;
                        $product->save();
                    }
                }
                
                $booking->update([
                    'payment_status' => 'expired',
                    'status' => 'cancelled',
                ]);
                Log::info('Booking cancelled/expired - stock returned', ['booking_number' => $booking->booking_number]);
            }
            
            return response()->json(['message' => 'Webhook processed successfully']);
            
        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}