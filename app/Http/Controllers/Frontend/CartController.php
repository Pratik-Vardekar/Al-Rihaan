<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\HandlesCartAndCheckout;
use App\Models\BuyNow;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    use HandlesCartAndCheckout;

    public function __construct(
        protected CartService $cartService,
        protected WishlistService $wishlistService
    ) {}

    /**
     * Display the shopping cart page with recommendations and summary.
     */
    public function cart()
    {
        $summary = $this->buildCartSummary($this->cartService->getCartItems());
        $cartItems = collect($summary['cartItems']);
        $subtotal = (float) $summary['subtotal'];

        $availableCoupons = $this->buildAvailableCouponOffers(
            $this->cartService->getCartItems(),
            $subtotal,
            false,
            $summary['couponCode'] ?? null
        );

        $cartProductIds = $cartItems->map(fn($item) => $item->product_id ?? $item->product?->id)->filter()->unique()->toArray();
        $cartBrandIds = $cartItems->map(fn($item) => $item->product?->brand_id)->filter()->unique()->toArray();
        $cartCategoryIds = $cartItems->map(fn($item) => $item->product?->category_id)->filter()->unique()->toArray();

        $suggestedQuery = Product::where('status', 1)
            ->when(!empty($cartProductIds), fn($q) => $q->whereNotIn('id', $cartProductIds))
            ->with(['brand', 'category'])
            ->withCount([
                'reviews as reviews_count' => fn($query) => $query->where('status', 1),
            ]);

        $suggestedProducts = collect();

        // 1. Recommend products from same brands or categories
        if (!empty($cartCategoryIds) || !empty($cartBrandIds)) {
            $suggestedProducts = (clone $suggestedQuery)
                ->where(function ($q) use ($cartCategoryIds, $cartBrandIds) {
                    if (!empty($cartCategoryIds)) {
                        $q->whereIn('category_id', $cartCategoryIds);
                    }
                    if (!empty($cartBrandIds)) {
                        $q->orWhereIn('brand_id', $cartBrandIds);
                    }
                })
                ->inRandomOrder()
                ->take(12)
                ->get();
        }

        // 2. Augment with featured, best-selling, trending, or new arrival products
        if ($suggestedProducts->count() < 8) {
            $existingIds = array_merge($cartProductIds, $suggestedProducts->pluck('id')->toArray());
            $needed = 12 - $suggestedProducts->count();

            $popularProducts = (clone $suggestedQuery)
                ->whereNotIn('id', $existingIds)
                ->where(function ($q) {
                    $q->where('is_featured', 1)
                        ->orWhere('is_best_selling', 1)
                        ->orWhere('is_trending', 1)
                        ->orWhere('is_new_arrival', 1);
                })
                ->inRandomOrder()
                ->take($needed)
                ->get();

            $suggestedProducts = $suggestedProducts->merge($popularProducts);
        }

        // 3. Fallback to active products if still below 4
        if ($suggestedProducts->count() < 4) {
            $existingIds = array_merge($cartProductIds, $suggestedProducts->pluck('id')->toArray());
            $needed = 12 - $suggestedProducts->count();

            $fallbackProducts = (clone $suggestedQuery)
                ->whereNotIn('id', $existingIds)
                ->inRandomOrder()
                ->take($needed)
                ->get();

            $suggestedProducts = $suggestedProducts->merge($fallbackProducts);
        }

        $wishlistIds = $this->wishlistService->getProductIds();

        return view('frontend.pages.cart', array_merge($summary, compact('availableCoupons', 'suggestedProducts', 'wishlistIds')));
    }

    /**
     * Add product to cart.
     */
    public function addToCart(Request $request, $product_id)
    {
        $productId = (int) $product_id;
        $product = Product::findOrFail($productId);

        $variantId = $request->filled('variant_id') ? (int) $request->input('variant_id') : null;
        $size = $request->filled('size') ? trim((string) $request->input('size')) : ($request->filled('ml') ? trim((string) $request->input('ml')) : null);

        // Determine available backend stock for this product / variant
        $availableStock = (int) ($product->quantity ?? 0);
        if ($variantId) {
            $variant = \App\Models\ProductVariant::find($variantId);
            if ($variant && isset($variant->quantity)) {
                $availableStock = (int) $variant->quantity;
            }
        }

        if ($availableStock <= 0) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => false,
                    'success' => false,
                    'message' => 'Product is currently out of stock',
                ], 422);
            }

            return back()->with('error', 'Product is currently out of stock');
        }

        $rawQty = (int) $request->input('quantity', $request->input('qty', 1));
        if ($rawQty < 1) {
            $rawQty = 1;
        }

        if ($rawQty > $availableStock) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => false,
                    'success' => false,
                    'message' => "Only {$availableStock} unit(s) available in stock. Cannot add extra.",
                ], 422);
            }

            return back()->with('error', "Only {$availableStock} unit(s) available in stock. Cannot add extra.");
        }

        if ($this->cartService->isInCart($productId, $variantId, $size)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => true,
                    'success' => true,
                    'already_in_cart' => true,
                    'message' => 'Product already exists in cart',
                    'cart_count' => $this->cartService->getCartCount(),
                    'product_id' => $productId,
                    'cart_url' => route('cart'),
                ]);
            }

            return back()->with('success', 'Product already exists in cart');
        }

        $qty = min($availableStock, $rawQty);
        $cartCount = $this->cartService->addItem($productId, $qty, $variantId, $size);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'success' => true,
                'already_in_cart' => false,
                'message' => 'Product Added to Cart Successfully',
                'cart_count' => $cartCount,
                'product_id' => $productId,
                'cart_url' => route('cart'),
                'is_guest' => !Auth::check(),
            ]);
        }

        return back()->with('success', 'Added to cart');
    }

    /**
     * Update guest cart quantity.
     */
    public function updateGuestCart(Request $request, Product $product)
    {
        if (Auth::check()) {
            return redirect()->route('cart');
        }

        $variantId = $request->filled('variant_id') ? (int) $request->input('variant_id') : null;
        $size = $request->filled('size') ? trim((string) $request->input('size')) : null;

        $availableStock = (int) ($product->quantity ?? 0);
        if ($variantId) {
            $variant = \App\Models\ProductVariant::find($variantId);
            if ($variant && isset($variant->quantity)) {
                $availableStock = (int) $variant->quantity;
            }
        }
        $maxStock = $availableStock > 0 ? $availableStock : 1;

        $qty = min($maxStock, max(1, (int) $request->input('quantity', 1)));
        $cartKey = $request->input('cart_key');

        $this->cartService->updateGuestItem($product->id, $qty, $cartKey, $variantId, $size);

        return back()->with('success', 'Cart updated');
    }

    /**
     * Remove item from guest cart.
     */
    public function removeGuestCart(Request $request, Product $product)
    {
        if (Auth::check()) {
            return redirect()->route('cart');
        }

        $cartKey = $request->input('cart_key');
        $this->cartService->removeGuestItem($product->id, $cartKey);

        return back()->with('success', 'Item Removed from Cart');
    }

    /**
     * Update authenticated user's cart item.
     */
    public function updateCart(Request $request, Cart $cart)
    {
        if ($cart->user_id !== Auth::id()) {
            abort(403);
        }

        $product = $cart->product;
        $availableStock = $product ? (int) ($product->quantity ?? 0) : 0;
        if ($cart->variant_id) {
            $variant = \App\Models\ProductVariant::find($cart->variant_id);
            if ($variant && isset($variant->quantity)) {
                $availableStock = (int) $variant->quantity;
            }
        }
        $maxStock = $availableStock > 0 ? $availableStock : 1;

        $qty = min($maxStock, max(1, (int) $request->input('quantity', 1)));
        $cart->update(['quantity' => $qty]);

        // Clean up any duplicate rows for the same product in DB for this user
        Cart::where('user_id', Auth::id())
            ->where('product_id', $cart->product_id)
            ->where('id', '!=', $cart->id)
            ->delete();

        return back()->with('success', 'Cart updated');
    }

    /**
     * Remove item from authenticated user's cart.
     */
    public function removeFromCart(Cart $cart)
    {
        if ($cart->user_id !== Auth::id()) {
            abort(403);
        }

        $cart->delete();

        return back()->with('success', 'Item Removed from Cart');
    }

    /**
     * Apply coupon discount.
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string|max:50',
        ]);

        $code = strtoupper(trim($request->coupon_code));
        $cartItems = $this->resolveCheckoutCartItems();

        if ($cartItems->isEmpty()) {
            return $this->couponActionResponse(
                $request,
                false,
                'Your cart is empty.'
            );
        }

        $coupon = Coupon::whereRaw('UPPER(code) = ?', [$code])
            ->where('status', 1)
            ->first();

        if (!$coupon) {
            return $this->couponActionResponse(
                $request,
                false,
                'Invalid promo code.'
            );
        }

        if (Auth::check() && $coupon->isUsedByUser(Auth::id())) {
            return $this->couponActionResponse(
                $request,
                false,
                'You have already used this coupon code. Each coupon can only be used once per customer.'
            );
        }

        $subtotal = 0;
        foreach ($cartItems as $cartItem) {
            if (!$cartItem->product) {
                continue;
            }
            $price = (float) ($cartItem->product->selling_price ?? 0);
            if ($price <= 0) {
                $price = (float) ($cartItem->product->price ?? 0);
            }
            $subtotal += $price * $cartItem->quantity;
        }

        $result = $coupon->calculateDiscountForItems($cartItems, $subtotal, Auth::id());

        if ($result['discount'] <= 0) {
            return $this->couponActionResponse(
                $request,
                false,
                $result['message'] ?? 'Promo code cannot be applied.'
            );
        }

        session([
            'applied_coupon_code' => $coupon->code,
            'applied_coupon_discount' => $result['discount'],
        ]);

        if (Auth::check()) {
            try {
                Cart::where('user_id', Auth::id())->update(['coupon_code' => $coupon->code]);
            } catch (\Throwable $e) {
            }

            try {
                BuyNow::where('user_id', Auth::id())->update(['coupon_code' => $coupon->code]);
            } catch (\Throwable $e) {
            }
        }

        $summary = $this->buildCartSummary($cartItems);

        return $this->couponActionResponse(
            $request,
            true,
            'Promo code applied. You saved ₹' . number_format($result['discount'], 2) . '.',
            $summary
        );
    }

    /**
     * Remove applied coupon.
     */
    public function removeCoupon(Request $request)
    {
        session()->forget(['applied_coupon_code', 'applied_coupon_discount']);

        if (Auth::check()) {
            try {
                Cart::where('user_id', Auth::id())->update(['coupon_code' => null]);
            } catch (\Throwable $e) {
            }

            try {
                BuyNow::where('user_id', Auth::id())->update(['coupon_code' => null]);
            } catch (\Throwable $e) {
            }
        }

        $summary = $this->buildCartSummary($this->resolveCheckoutCartItems());

        return $this->couponActionResponse(
            $request,
            true,
            'Promo code removed.',
            $summary
        );
    }

    /**
     * Available coupons API.
     */
    public function availableCoupons()
    {
        $cartItems = $this->resolveCheckoutCartItems();
        $summary = $this->buildCartSummary($cartItems);
        $subtotal = (float) $summary['subtotal'];
        $appliedCode = $summary['couponCode'] ?? null;

        $coupons = $this->buildAvailableCouponOffers($cartItems, $subtotal, false, $appliedCode);

        return response()->json([
            'coupons' => $this->formatCouponOffersForJson($coupons, $appliedCode),
            'subtotal' => $subtotal,
            'applied_code' => $appliedCode,
        ]);
    }
}
