<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\HandlesProductListing;
use App\Models\Product;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    use HandlesProductListing;

    public function __construct(
        protected CartService $cartService,
        protected WishlistService $wishlistService
    ) {}

    /**
     * Display the customer's wishlist.
     */
    public function wishlist()
    {
        $user = Auth::user();
        $wishlistItems = $this->wishlistService->getItems();
        $products = $wishlistItems->pluck('product')->filter();
        $this->attachProductReviewMeta($products);
        $cartProductIds = $this->cartService->getCartProductIds();

        return view('frontend.pages.myaccount_wishlist', compact('user', 'wishlistItems', 'cartProductIds'));
    }

    /**
     * Add product to wishlist.
     */
    public function addToWishlist(Request $request, $productId)
    {
        $this->wishlistService->add((int) $productId);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'in_wishlist' => true,
                'wishlist_count' => $this->wishlistService->getCount(),
                'message' => 'Added to Wishlist',
            ]);
        }

        return back()->with('success', 'Added to Wishlist');
    }

    /**
     * Remove product from wishlist.
     */
    public function removeFromWishlist(Request $request, Product $product)
    {
        $this->wishlistService->remove((int) $product->id);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'success' => true,
                'in_wishlist' => false,
                'wishlist_count' => $this->wishlistService->getCount(),
                'message' => 'Removed From Wishlist',
            ]);
        }

        return back()->with('success', 'Removed from wishlist');
    }

    /**
     * Toggle product in wishlist.
     */
    public function toggleWishlist(Request $request, $productId)
    {
        $inWishlist = $this->wishlistService->toggle((int) $productId);
        $count = $this->wishlistService->getCount();
        $message = $inWishlist ? 'Added to Wishlist' : 'Removed from Wishlist';

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'success' => true,
                'status' => $inWishlist ? 'added' : 'removed',
                'in_wishlist' => $inWishlist,
                'wishlist_count' => $count,
                'count' => $count,
                'message' => $message,
                'redirect' => null,
            ]);
        }

        return back()->with('success', $message);
    }
}
