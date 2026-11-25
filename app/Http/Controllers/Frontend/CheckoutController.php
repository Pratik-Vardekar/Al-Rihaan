<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\HandlesCartAndCheckout;
use App\Mail\OrderAdminMail;
use App\Mail\OrderSuccessMail;
use App\Models\Address;
use App\Models\BuyNow;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\ShiprocketService;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Razorpay\Api\Api;

class CheckoutController extends Controller
{
    use HandlesCartAndCheckout;

    public function __construct(
        protected CartService $cartService,
        protected WishlistService $wishlistService
    ) {}

    /**
     * Buy Now express checkout.
     */
    public function buyNow(Request $request, $id)
    {
        if (!Auth::check()) {
            session(['url.intended' => $request->fullUrl()]);
            return redirect()->route('login')->with('login_required', true);
        }

        $product = Product::findOrFail($id);

        if ((int) ($product->quantity ?? 0) <= 0) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => false,
                    'success' => false,
                    'message' => 'Product is out of stock',
                ], 422);
            }

            return back()->with('error', 'Product is out of stock');
        }

        $qty = min(10, max(1, (int) $request->input('quantity', $request->input('qty', 1))));
        $variantId = $request->filled('variant_id') ? (int) $request->input('variant_id') : null;
        $size = $request->filled('size') ? trim((string) $request->input('size')) : null;
        $price = $request->filled('price') ? (float) $request->input('price') : null;

        // If variant_id is missing but size is specified, check database variants
        if (!$variantId && $size) {
            $matchedVariant = ProductVariant::where('product_id', $product->id)
                ->where('status', 1)
                ->whereHas('variantAttributes.attributeValue', function ($q) use ($size) {
                    $q->where('value', $size);
                })
                ->first();

            if ($matchedVariant) {
                $variantId = $matchedVariant->id;
            }
        }

        if ($qty > (int) $product->quantity) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => false,
                    'success' => false,
                    'message' => 'Only ' . $product->quantity . ' item(s) available in stock',
                ], 422);
            }

            return back()->with('error', 'Only ' . $product->quantity . ' item(s) available in stock');
        }

        BuyNow::where('user_id', Auth::id())->delete();

        $buyNowPayload = [
            'user_id' => Auth::id(),
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'quantity' => $qty,
            'coupon_code' => session('applied_coupon_code'),
        ];

        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('buy_nows', 'size') && $size) {
                $buyNowPayload['size'] = $size;
            }
        } catch (\Throwable $e) {
            // ignore schema error
        }

        BuyNow::create($buyNowPayload);

        session([
            'checkout_source' => 'buy_now',
            'buy_now_size' => $size,
            'buy_now_price' => $price,
            'buy_now_variant_id' => $variantId,
        ]);

        return redirect()->route('checkout.shipping');
    }

    /**
     * Checkout shipping & address page.
     */
    public function checkoutShipping(Request $request)
    {
        if (!Auth::check()) {
            $intended = $request->query('from') === 'cart'
                ? route('checkout.shipping', ['from' => 'cart'])
                : route('checkout.shipping');
            session(['url.intended' => $intended]);
            return redirect()->route('login')->with('login_required', true);
        }

        if ($request->query('from') === 'cart') {
            session(['checkout_source' => 'cart']);
            BuyNow::where('user_id', Auth::id())->delete();
        }

        $checkout = $this->buildCheckoutSummary();

        if ($checkout['itemCount'] === 0) {
            return redirect()->route('cart')->with('error', 'Your Cart is Empty.');
        }

        $user = auth()->user();
        $addresses = Address::where('user_id', $user->id)
            ->where('status', 1)
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        $defaultAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        $cartItems = collect($checkout['cartItems']);
        $availableCoupons = $this->buildAvailableCouponOffers(
            $cartItems,
            (float) $checkout['subtotal'],
            false,
            $checkout['couponCode'] ?? null
        );

        return view(
            'frontend.pages.checkout_shipping',
            array_merge($checkout, compact('user', 'addresses', 'defaultAddress', 'availableCoupons'))
        );
    }

    /**
     * Place order action.
     */
    public function placeOrder(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('cart')->with('login_required', true);
        }

        $request->validate([
            'payment_method' => 'required|in:cod,razorpay',
        ]);

        $user = auth()->user();
        $checkout = $this->buildCheckoutSummary();
        $paymentMethod = $request->input('payment_method', 'cod');
        $wantsJson = $request->ajax() || $request->wantsJson() || $request->boolean('ajax');

        if ($checkout['itemCount'] === 0) {
            if ($wantsJson) {
                return response()->json(['status' => false, 'message' => 'Your Cart is Empty.'], 422);
            }

            return redirect()->route('cart')->with('error', 'Your cart is empty.');
        }

        $billingPayload = $this->resolveCheckoutAddress($request, $user, 'billing');
        $sameShipping = $request->input('shipping_same_diff_address', 'same') === 'same';
        $shippingPayload = $sameShipping
            ? $billingPayload
            : $this->resolveCheckoutAddress($request, $user, 'shipping');

        $shippingPincode = preg_replace('/\D+/', '', (string) ($shippingPayload['pincode'] ?? ''));
        if (strlen($shippingPincode) === 6) {
            $serviceability = app(ShiprocketService::class)->checkServiceability($shippingPincode, $paymentMethod === 'cod');

            if ($serviceability !== null && ! $serviceability['serviceable']) {
                $message = $paymentMethod === 'cod'
                    ? 'Cash on delivery is not available for pincode ' . $shippingPincode . '. Please Choose Online Payment or Another Address.'
                    : 'Sorry, we do not deliver to pincode ' . $shippingPincode . ' yet.';

                if ($wantsJson) {
                    return response()->json(['status' => false, 'message' => $message], 422);
                }

                return back()->withInput()->with('error', $message);
            }
        }

        if (!empty($billingPayload['phone']) && empty($user->phone)) {
            $userDigits = preg_replace('/\D+/', '', $billingPayload['phone']);
            if (strlen($userDigits) >= 10) {
                $user->phone = '+91' . substr($userDigits, -10);
                $user->save();
            }
        }
        if (!empty($billingPayload['name']) && empty($user->name)) {
            $user->name = $billingPayload['name'];
            $user->save();
        }

        DB::beginTransaction();

        try {
            $grandTotal = $checkout['grandTotal'];
            $payNow = 0;
            $paymentType = 'full';

            if ($paymentMethod === 'razorpay') {
                $payNow = $grandTotal;
                $paymentType = 'full';
            } else {
                // 10% Advance commented code:
                // $payNow = round($grandTotal * 0.10, 2);
                // $paymentType = 'partial';
                $payNow = 0;
                $paymentType = 'cod';
            }

            if (!empty($checkout['couponCode']) && Order::isCouponUsedByUser($user->id, $checkout['couponCode'])) {
                session()->forget(['applied_coupon_code', 'applied_coupon_discount']);
                try {
                    Cart::where('user_id', $user->id)->update(['coupon_code' => null]);
                    BuyNow::where('user_id', $user->id)->update(['coupon_code' => null]);
                } catch (\Throwable $e) {
                }
                return back()->with('error', 'You have already used coupon code "' . $checkout['couponCode'] . '". Each coupon can only be used once per customer.');
            }

            $orderPayload = [
                'user_id' => $user->id,
                'order_id' => 'ORD' . time() . rand(100, 999),
                'invoice_no' => Order::generateNextInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'subtotal' => $checkout['subtotal'],
                'discount' => $checkout['discount'],
                'shipping_charge' => $checkout['shippingCharge'],
                'grand_total' => $checkout['grandTotal'],
                'paid_amount' => 0,
                'due_amount' => $checkout['grandTotal'],
                'payment_method' => $paymentMethod,
                'payment_type' => $paymentType,
                'payment_gateway' => $payNow > 0 ? 'razorpay' : null,
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'notes' => $checkout['couponCode']
                    ? 'Coupon: ' . strtoupper($checkout['couponCode'])
                    : null,
                'coupon_code' => $checkout['couponCode']
                    ? strtoupper($checkout['couponCode'])
                    : null,
                'coupon_discount' => $checkout['discount'] ?? 0,
                'gst_rate' => 18,
                'gst_amount' => $checkout['gstTotal'] ?? 0,
            ];

            $order = Order::create($orderPayload);

            OrderAddress::create(array_merge($shippingPayload, [
                'order_id' => $order->id,
                'type' => 'shipping',
            ]));

            OrderAddress::create(array_merge($billingPayload, [
                'order_id' => $order->id,
                'type' => 'billing',
            ]));

            $itemsToProcess = $checkout['cartItems'];
            if (session('checkout_source') === 'buy_now') {
                $buyItems = $this->resolveBuyNowItems();
                if ($buyItems->isNotEmpty()) {
                    $itemsToProcess = $buyItems;
                }
            }

            if (!\Illuminate\Support\Facades\Schema::hasColumn('order_items', 'size')) {
                \Illuminate\Support\Facades\Schema::table('order_items', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('size')->nullable()->after('variant_id');
                });
            }
            if (!\Illuminate\Support\Facades\Schema::hasColumn('carts', 'size')) {
                \Illuminate\Support\Facades\Schema::table('carts', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('size')->nullable()->after('variant_id');
                });
            }

            OrderItem::where('order_id', $order->id)->delete();

            $groupedCartItems = collect($itemsToProcess)->groupBy(function ($item) {
                return $item->product_id . '_' . ($item->variant_id ?? '0') . '_' . ($item->size ?? '');
            });

            foreach ($groupedCartItems as $group) {
                $cartItem = $group->first();
                if (!$cartItem || !$cartItem->product) {
                    continue;
                }

                $quantity = (int) $group->sum('quantity');
                $price = (float) ($cartItem->resolved_selling_price ?? $cartItem->custom_selling_price ?? 0);
                if ($price <= 0) {
                    $price = ($cartItem->product->selling_price > 0 ? $cartItem->product->selling_price : $cartItem->product->price) ?? $cartItem->product->price ?? 0;
                    if (!empty($cartItem->variant)) {
                        if ((float) ($cartItem->variant->selling_price ?? 0) > 0) {
                            $price = (float) $cartItem->variant->selling_price;
                        } elseif ((float) ($cartItem->variant->price ?? 0) > 0) {
                            $price = (float) $cartItem->variant->price;
                        }
                    }
                }

                $itemSize = $cartItem->size ?? null;
                if (empty($itemSize) && !empty($cartItem->variant)) {
                    $attr = $cartItem->variant->variantAttributes ? $cartItem->variant->variantAttributes->first() : null;
                    if ($attr && optional($attr->attributeValue)->value) {
                        $itemSize = $attr->attributeValue->value;
                    } elseif (!empty($cartItem->variant->sku)) {
                        $itemSize = $cartItem->variant->sku;
                    }
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'variant_id' => $cartItem->variant_id ?? null,
                    'size' => $itemSize,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $price * $quantity,
                ]);
            }

            if ($payNow == 0) {
                $this->decreaseOrderStock($order);
            }

            if ($payNow == 0 && !empty($checkout['coupon']) && $checkout['discount'] > 0) {
                $checkout['coupon']->increment('used_count');
            }

            $razorpayPayload = null;

            if ($payNow > 0) {
                $razorpayKey = config('services.razorpay.key');
                $razorpaySecret = config('services.razorpay.secret');

                if (empty($razorpayKey) || empty($razorpaySecret)) {
                    throw new \RuntimeException('Online payment gateway is temporarily unavailable. Please contact support or try again later.');
                }

                $api = new Api($razorpayKey, $razorpaySecret);
                $amountPaise = (int) round(((float) $payNow) * 100);

                if ($amountPaise < 100) {
                    throw new \RuntimeException('Order amount must be at least ₹1 for online payment.');
                }

                $razorpayOrder = $api->order->create([
                    'receipt' => $order->order_id,
                    'amount' => $amountPaise,
                    'currency' => 'INR',
                    'payment_capture' => 1,
                    'notes' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_id,
                    ],
                ]);

                $order->update([
                    'razorpay_order_id' => $razorpayOrder['id'],
                    'transaction_id' => $razorpayOrder['id'],
                ]);

                $razorpayPayload = [
                    'key' => $razorpayKey,
                    'amount' => $amountPaise,
                    'currency' => 'INR',
                    'name' => config('app.name', 'Al Rihan Perfumes'),
                    'description' => 'Order ' . $order->order_id,
                    'order_id' => $razorpayOrder['id'],
                    'prefill' => [
                        'name' => $billingPayload['name'] ?? $user->name,
                        'email' => $user->email,
                        'contact' => $billingPayload['phone'] ?? $user->phone,
                    ],
                    'notes' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_id,
                    ],
                    'theme' => [
                        'color' => '#8B5A2B',
                    ],
                ];
            } else {
                $this->clearCheckoutCart($user->id);
            }

            DB::commit();

            if ($payNow > 0) {
                if ($wantsJson) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Order created. Complete payment to confirm.',
                        'order_id' => $order->id,
                        'order_number' => $order->order_id,
                        'payment_type' => $paymentType,
                        'pay_now' => $payNow,
                        'due_amount' => $order->due_amount,
                        'razorpay' => $razorpayPayload,
                        'verify_url' => route('checkout.razorpay.verify'),
                    ]);
                }

                return redirect()
                    ->route('checkout.shipping')
                    ->with('error', 'Please enable JavaScript to complete Razorpay payment.');
            }

            $this->ensureOrderInvoice($order);

            $orderId = $order->id;
            dispatch(function () use ($orderId) {
                try {
                    $freshOrder = Order::find($orderId);
                    if ($freshOrder) {
                        $this->sendOrderSuccessEmail($freshOrder);
                    }
                } catch (\Throwable $e) {
                    Log::error('Background order email failed: ' . $e->getMessage());
                }
            })->afterResponse();

            session()->flash('last_order_id', $order->order_id);
            session()->flash('success', 'Order placed successfully!');

            if ($wantsJson) {
                return response()->json([
                    'status' => true,
                    'message' => 'Order placed successfully!',
                    'order_id' => $order->id,
                    'order_number' => $order->order_id,
                    'redirect_url' => route('checkout.success'),
                ]);
            }

            return redirect()
                ->route('checkout.success')
                ->with('last_order_id', $order->order_id)
                ->with('success', 'Order placed successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Checkout place order failed: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
                'payment_method' => $paymentMethod,
            ]);

            $errorMessage = $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Unable to place order. Please try again.';

            if ($wantsJson) {
                return response()->json([
                    'status' => false,
                    'message' => $errorMessage,
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', $errorMessage);
        }
    }

    /**
     * Verify online Razorpay payment.
     */
    public function verifyRazorpayPayment(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $order = Order::where('id', $request->order_id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
        }

        if ($order->payment_method !== 'razorpay' && $order->payment_gateway !== 'razorpay') {
            return response()->json(['status' => false, 'message' => 'Invalid payment method for this order.'], 422);
        }

        if ($order->payment_status === 'paid') {
            $this->clearCheckoutCart(Auth::id());

            return response()->json([
                'status' => true,
                'message' => 'Payment already verified.',
                'redirect_url' => route('checkout.success'),
            ]);
        }

        try {
            $api = new Api(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );

            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);

            $storedRazorpayOrderId = $order->razorpay_order_id ?: $order->transaction_id;

            if ($storedRazorpayOrderId && $storedRazorpayOrderId !== $request->razorpay_order_id) {
                return response()->json(['status' => false, 'message' => 'Razorpay order mismatch.'], 422);
            }

            $payment = $api->payment->fetch($request->razorpay_payment_id);
            $paidAmount = $payment['amount'] / 100;
            $remainingAmount = $order->grand_total - $paidAmount;

            DB::table('order_payments')->insert([
                'order_id' => $order->id,
                'amount' => $paidAmount,
                'payment_method' => 'razorpay',
                'transaction_id' => $request->razorpay_payment_id,
                'status' => 'success',
                'paid_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $order->update([
                'payment_status' => ($remainingAmount <= 0) ? 'paid' : 'partial',
                'payment_id' => $request->razorpay_payment_id,
                'razorpay_order_id' => $request->razorpay_order_id,
                'transaction_id' => $request->razorpay_payment_id,
                'paid_amount' => $paidAmount,
                'due_amount' => max(0, $remainingAmount),
                'order_status' => 'processing',
            ]);

            if (!empty($order->notes) && str_starts_with($order->notes, 'Coupon: ')) {
                $couponCode = trim(substr($order->notes, strlen('Coupon: ')));
                if ($couponCode !== '') {
                    Coupon::whereRaw('UPPER(code) = ?', [strtoupper($couponCode)])->increment('used_count');
                }
            }

            // Decrease product stock
            $this->decreaseOrderStock($order);
            $this->ensureOrderInvoice($order);
            $this->clearCheckoutCart(Auth::id());

            $orderId = $order->id;
            dispatch(function () use ($orderId) {
                try {
                    $freshOrder = Order::find($orderId);
                    if ($freshOrder) {
                        $this->sendOrderSuccessEmail($freshOrder);
                    }
                } catch (\Throwable $e) {
                    Log::error('Background order email failed: ' . $e->getMessage());
                }

                $shiprocket = app(ShiprocketService::class);
                $freshOrder = Order::find($orderId);
                if ($freshOrder && $shiprocket->autoCreateEnabled()) {
                    try {
                        $shiprocket->createOrder($freshOrder);
                    } catch (\Throwable $e) {
                        $shiprocket->recordError($freshOrder, $e);
                    }
                }
            })->afterResponse();

            session()->flash('last_order_id', $order->order_id);
            session()->flash('success', 'Payment successful! Order placed.');

            return response()->json([
                'status' => true,
                'message' => 'Payment verified successfully.',
                'redirect_url' => route('checkout.success'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Razorpay verify failed: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Payment verification failed. Please contact support if amount was deducted.',
            ], 400);
        }
    }

    /**
     * Dismiss failed or cancelled Razorpay order.
     */
    public function dismissRazorpayPayment(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $orderId = $request->input('order_id');
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Order ID required.'], 400);
        }

        $order = Order::where('id', $orderId)
            ->where('user_id', Auth::id())
            ->where('order_status', 'pending')
            ->where('payment_status', 'pending')
            ->first();

        if ($order && (float) ($order->paid_amount ?? 0) <= 0) {
            OrderItem::where('order_id', $order->id)->delete();
            OrderAddress::where('order_id', $order->id)->delete();
            $order->delete();
        }

        return response()->json(['status' => true, 'message' => 'Payment dismissed.']);
    }

    /**
     * Checkout success thank you page.
     */
    public function checkoutSuccess()
    {
        $orderId = session('last_order_id');
        $order = null;

        if ($orderId && Auth::check()) {
            $order = Order::where('order_id', $orderId)
                ->where('user_id', Auth::id())
                ->first();
        }

        return view('frontend.pages.checkout_success', compact('order', 'orderId'));
    }

    protected function decreaseOrderStock(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            $qty = (int) $item->quantity;
            if ($qty <= 0) {
                continue;
            }

            if ($item->variant_id) {
                $variant = ProductVariant::where('id', $item->variant_id)->lockForUpdate()->first();
                if ($variant && $variant->quantity >= $qty) {
                    $variant->decrement('quantity', $qty);
                }
            } else {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                if ($product && $product->quantity >= $qty) {
                    $product->decrement('quantity', $qty);
                }
            }
        }
    }

    protected function sendOrderSuccessEmail(Order $order): void
    {
        $order->loadMissing(['user', 'items.product', 'items.variant', 'shippingAddress', 'billingAddress', 'addresses', 'orderPayments', 'refunds']);

        $customerEmail = $order->user?->email;
        if ($customerEmail) {
            try {
                Mail::to($customerEmail)->send(new OrderSuccessMail($order));
            } catch (\Throwable $e) {
                Log::error('Order success email failed: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'order_number' => $order->order_id,
                    'email' => $customerEmail,
                ]);
            }
        }

        $adminEmail = config('mail.admin_order_email');
        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new OrderAdminMail($order));
            } catch (\Throwable $e) {
                Log::error('Order admin email failed: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'order_number' => $order->order_id,
                    'email' => $adminEmail,
                ]);
            }
        }
    }
}
