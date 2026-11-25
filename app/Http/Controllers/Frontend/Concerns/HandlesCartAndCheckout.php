<?php

namespace App\Http\Controllers\Frontend\Concerns;

use App\Models\Address;
use App\Models\BuyNow;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait HandlesCartAndCheckout
{
    public function resolveCheckoutCartItems()
    {
        $source = session('checkout_source', 'cart');
        $cartItems = $this->cartService->getCartItems();

        if ($source === 'buy_now' || ($source !== 'cart' && $cartItems->isEmpty())) {
            $buyNowItems = $this->resolveBuyNowItems();
            if ($buyNowItems->isNotEmpty()) {
                return $buyNowItems;
            }
        }

        return $cartItems;
    }

    public function resolveBuyNowItems()
    {
        if (!Auth::check()) {
            return collect();
        }

        $buyNow = BuyNow::where('user_id', Auth::id())->latest()->first();

        if (!$buyNow || !$buyNow->product_id) {
            return collect();
        }

        $product = $buyNow->product ?: Product::find($buyNow->product_id);

        if (!$product) {
            return collect();
        }

        $product->loadMissing('gstSetting');

        $variantId = $buyNow->variant_id ?: session('buy_now_variant_id');
        $variant = null;
        if ($variantId) {
            $variant = ProductVariant::with(['variantAttributes.attribute', 'variantAttributes.attributeValue'])->find($variantId);
        }

        $size = session('buy_now_size') ?: ($buyNow->size ?? null);
        $resolvedPrice = null;
        $resolvedOldPrice = null;

        if ($variant) {
            $resolvedSelling = (float) ($variant->selling_price > 0 ? $variant->selling_price : $variant->price);
            $resolvedMrp = (float) ($variant->price > 0 ? $variant->price : $variant->selling_price);
            $resolvedPrice = $resolvedSelling > 0 ? $resolvedSelling : (float) $product->selling_price;
            $resolvedOldPrice = $resolvedMrp > 0 ? $resolvedMrp : (float) $product->price;

            $attr = $variant->variantAttributes ? $variant->variantAttributes->first() : null;
            $size = $attr ? optional($attr->attributeValue)->value : ($variant->sku ?: $size);
        } elseif (!empty($size)) {
            // Check if any product variant matches this size
            $matchedVariant = ProductVariant::where('product_id', $product->id)
                ->where('status', 1)
                ->whereHas('variantAttributes.attributeValue', function ($q) use ($size) {
                    $q->where('value', $size);
                })
                ->with(['variantAttributes.attribute', 'variantAttributes.attributeValue'])
                ->first();

            if ($matchedVariant) {
                $variant = $matchedVariant;
                $resolvedSelling = (float) ($variant->selling_price > 0 ? $variant->selling_price : $variant->price);
                $resolvedMrp = (float) ($variant->price > 0 ? $variant->price : $variant->selling_price);
                $resolvedPrice = $resolvedSelling > 0 ? $resolvedSelling : (float) $product->selling_price;
                $resolvedOldPrice = $resolvedMrp > 0 ? $resolvedMrp : (float) $product->price;
            } else {
                // Fallback size formulas (matches product_details.blade.php)
                $baseSelling = (float) ($product->selling_price > 0 ? $product->selling_price : $product->price);
                $baseMrp = (float) ($product->price > 0 ? $product->price : $product->selling_price);

                if (preg_match('/5[\.\s]?5/i', (string) $size)) {
                    $resolvedPrice = (float) max(199, round($baseSelling * 0.25));
                    $resolvedOldPrice = (float) max(299, round($baseMrp * 0.25));
                    $size = '5.5 ML';
                } elseif (preg_match('/12/i', (string) $size)) {
                    $resolvedPrice = (float) max(399, round($baseSelling * 0.4));
                    $resolvedOldPrice = (float) max(499, round($baseMrp * 0.4));
                    $size = '12 ML';
                } elseif (preg_match('/50/i', (string) $size)) {
                    $resolvedPrice = (float) max(899, round($baseSelling * 0.7));
                    $resolvedOldPrice = (float) max(1199, round($baseMrp * 0.7));
                    $size = '50 ML';
                } elseif (preg_match('/100/i', (string) $size)) {
                    $resolvedPrice = (float) round($baseSelling);
                    $resolvedOldPrice = (float) round($baseMrp);
                    $size = '100 ML';
                }
            }
        }

        if ($resolvedPrice === null && session()->has('buy_now_price')) {
            $resolvedPrice = (float) session('buy_now_price');
        }

        return collect([(object) [
            'id' => 'buynow_' . $buyNow->id,
            'product_id' => $buyNow->product_id,
            'variant_id' => $variant ? $variant->id : $buyNow->variant_id,
            'variant' => $variant,
            'size' => $size,
            'custom_selling_price' => $resolvedPrice,
            'custom_price' => $resolvedOldPrice,
            'quantity' => max(1, (int) $buyNow->quantity),
            'product' => $product,
        ]]);
    }

    public function buildAvailableCouponOffers($cartItems, float $subtotal, bool $includeIneligible = false, ?string $appliedCode = null)
    {
        $appliedCode = strtoupper((string) $appliedCode);
        $userId = Auth::id();

        return Coupon::where('status', 1)
            ->latest()
            ->get()
            ->map(fn(Coupon $coupon) => $coupon->buildAvailableOfferForCart(collect($cartItems), $subtotal, $includeIneligible, $userId))
            ->filter(function ($offer) {
                return $offer && !empty($offer['can_apply']);
            })
            ->sortByDesc(fn(array $offer) => ((int) $offer['can_apply'] * 1000000) + (float) $offer['discount'])
            ->values();
    }

    public function buildCheckoutSummary(): array
    {
        return $this->buildCartSummary($this->resolveCheckoutCartItems());
    }

    public function buildCartSummary($cartItems): array
    {
        $subtotal = 0;
        $gstTotal = 0;
        $gstRateLabel = 'GST (18% inclusive)';
        $itemCount = 0;
        $gstRates = [];

        foreach ($cartItems as $cartItem) {
            if (!$cartItem->product) {
                continue;
            }

            $itemCount++;
            $originalPrice = (float) ($cartItem->product->price ?? 0);
            $sellingPrice = (float) ($cartItem->product->selling_price ?? 0);
            if ($sellingPrice <= 0) {
                $sellingPrice = $originalPrice;
            }

            if (!empty($cartItem->variant)) {
                if ((float) ($cartItem->variant->selling_price ?? 0) > 0) {
                    $sellingPrice = (float) $cartItem->variant->selling_price;
                }
                if ((float) ($cartItem->variant->price ?? 0) > 0) {
                    $originalPrice = (float) $cartItem->variant->price;
                }
            } elseif (!empty($cartItem->custom_selling_price) && (float) $cartItem->custom_selling_price > 0) {
                $sellingPrice = (float) $cartItem->custom_selling_price;
                if (!empty($cartItem->custom_price) && (float) $cartItem->custom_price > 0) {
                    $originalPrice = (float) $cartItem->custom_price;
                }
            }

            $cartItem->resolved_selling_price = $sellingPrice;
            $cartItem->resolved_price = $originalPrice;

            $quantity = (int) $cartItem->quantity;
            $itemSubtotal = $sellingPrice * $quantity;

            // GET GST RATE FROM PRODUCT
            $gstRate = $cartItem->product->getGstRate();
            $gstRates[] = $gstRate;

            // Calculate GST inclusive - Formula: (Price × Rate) / (100 + Rate)
            $itemGst = ($itemSubtotal * $gstRate) / (100 + $gstRate);

            $subtotal += $itemSubtotal;
            $gstTotal += $itemGst;
        }

        $subtotal = round($subtotal, 2);
        $gstTotal = round($gstTotal, 2);

        // Determine GST label
        if (!empty($gstRates)) {
            $uniqueRates = array_values(array_unique($gstRates));
            if (count($uniqueRates) === 1) {
                $gstRateLabel = 'GST (' . rtrim(rtrim(number_format($uniqueRates[0], 2), '0'), '.') . '% inclusive)';
            } else {
                $gstRateLabel = 'GST (inclusive)';
            }
        }

        // Shipping calculation
        $shippingCharge = 0;
        $shippingSetting = ShippingSetting::first();
        if ($shippingSetting && $subtotal > 0) {
            $freeShippingAmount = (float) $shippingSetting->free_shipping_amount;
            $shippingCharge = $subtotal >= $freeShippingAmount ? 0 : (float) $shippingSetting->shipping_charge;
        }

        // Coupon logic
        $discount = 0.0;
        $coupon = null;
        $couponCode = session('applied_coupon_code');

        if (!$couponCode && Auth::check()) {
            $buyNowCoupon = BuyNow::where('user_id', Auth::id())->latest()->value('coupon_code');
            if ($buyNowCoupon) {
                $couponCode = $buyNowCoupon;
                session(['applied_coupon_code' => $couponCode]);
            }
        }

        if ($couponCode) {
            $coupon = Coupon::whereRaw('UPPER(code) = ?', [strtoupper($couponCode)])
                ->where('status', 1)
                ->first();

            if ($coupon) {
                if (Auth::check() && $coupon->isUsedByUser(Auth::id())) {
                    session()->forget(['applied_coupon_code', 'applied_coupon_discount']);
                    try {
                        Cart::where('user_id', Auth::id())->update(['coupon_code' => null]);
                        BuyNow::where('user_id', Auth::id())->update(['coupon_code' => null]);
                    } catch (\Throwable $e) {
                    }
                    $coupon = null;
                    $couponCode = null;
                } else {
                    $result = $coupon->calculateDiscountForItems(collect($cartItems), $subtotal, Auth::id());
                    if ($result['discount'] > 0) {
                        $discount = $result['discount'];
                        session(['applied_coupon_discount' => $discount]);
                    } else {
                        session()->forget(['applied_coupon_code', 'applied_coupon_discount']);
                        $coupon = null;
                        $couponCode = null;
                    }
                }
            } else {
                session()->forget(['applied_coupon_code', 'applied_coupon_discount']);
                $couponCode = null;
            }
        }

        // Recalculate included GST proportionally after discount
        if ($subtotal > 0 && $discount > 0) {
            $ratio = max(0, ($subtotal - $discount) / $subtotal);
            $gstTotal = round($gstTotal * $ratio, 2);
        }

        $grandTotal = max(0, round($subtotal - $discount + $shippingCharge, 2));

        return compact(
            'cartItems',
            'subtotal',
            'gstTotal',
            'gstRateLabel',
            'shippingCharge',
            'discount',
            'coupon',
            'couponCode',
            'grandTotal',
            'itemCount'
        );
    }

    public function wantsCouponJson(Request $request): bool
    {
        return $request->ajax()
            || $request->wantsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    public function cartSummaryJsonPayload(array $summary): array
    {
        return [
            'subtotal' => (float) $summary['subtotal'],
            'discount' => (float) $summary['discount'],
            'gstTotal' => (float) $summary['gstTotal'],
            'gstRateLabel' => $summary['gstRateLabel'] ?? 'GST (18% inclusive)',
            'shippingCharge' => (float) $summary['shippingCharge'],
            'grandTotal' => (float) $summary['grandTotal'],
            'coupon_code' => $summary['couponCode'] ?? null,
            'coupon_type' => $summary['coupon']->type ?? null,
            'coupon_value' => isset($summary['coupon']) ? (float) $summary['coupon']->value : null,
        ];
    }

    public function couponActionResponse(Request $request, bool $success, string $message, ?array $summary = null)
    {
        if ($this->wantsCouponJson($request)) {
            if ($summary === null) {
                $summary = $this->buildCartSummary($this->resolveCheckoutCartItems());
            }

            return response()->json(array_merge([
                'status' => $success,
                'message' => $message,
            ], $this->cartSummaryJsonPayload($summary)), $success ? 200 : 422);
        }

        return $success
            ? back()->with('success', $message)
            : back()->with('error', $message);
    }

    public function formatCouponOffersForJson($offers, ?string $appliedCode = null): array
    {
        $appliedCode = strtoupper((string) $appliedCode);

        return $offers->map(function (array $offer) use ($appliedCode) {
            /** @var \App\Models\Coupon $coupon */
            $coupon = $offer['coupon'];

            return [
                'coupon' => [
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => (float) $coupon->value,
                ],
                'discount' => (float) $offer['discount'],
                'can_apply' => (bool) $offer['can_apply'],
                'message' => $offer['message'],
                'minimum_amount' => $offer['minimum_amount'],
                'maximum_discount' => $offer['maximum_discount'],
                'eligible_subtotal' => (float) $offer['eligible_subtotal'],
                'meets_minimum' => (bool) $offer['meets_minimum'],
                'applicability' => $offer['applicability'],
                'eligible_items' => $offer['eligible_items'],
                'is_applied' => $appliedCode !== '' && strtoupper($coupon->code) === $appliedCode,
            ];
        })->values()->all();
    }

    public function resolveCheckoutAddress(Request $request, $user, string $prefix, bool $allowSaved = true): array
    {
        if ($allowSaved) {
            $addressId = $request->input($prefix . '_address_id');

            if ($addressId && $addressId !== 'new') {
                $address = Address::where('id', $addressId)
                    ->where('user_id', $user->id)
                    ->where('status', 1)
                    ->firstOrFail();

                return $this->addressToOrderPayload($address);
            }
        }

        $validated = $request->validate([
            $prefix . '_name' => 'required|string|max:255',
            $prefix . '_email' => 'nullable|email|max:255',
            $prefix . '_phone' => 'required|string|max:20',
            $prefix . '_address_line_1' => 'required|string|max:500',
            $prefix . '_address_line_2' => 'nullable|string|max:500',
            $prefix . '_city' => 'required|string|max:255',
            $prefix . '_state' => 'required|string|max:255',
            $prefix . '_pincode' => 'required|string|max:10',
        ], [], [
            $prefix . '_name' => 'name',
            $prefix . '_email' => 'email',
            $prefix . '_phone' => 'phone',
            $prefix . '_address_line_1' => 'address line 1',
            $prefix . '_address_line_2' => 'address line 2',
            $prefix . '_city' => 'city',
            $prefix . '_state' => 'state',
            $prefix . '_pincode' => 'pincode',
        ]);

        $shouldSave = $request->boolean('save_address')
            || ($prefix === 'billing' && $request->boolean('save_billing_address'));

        if ($shouldSave) {
            if ($request->boolean('is_default') || ($prefix === 'billing' && $request->boolean('billing_is_default'))) {
                Address::where('user_id', $user->id)->update(['is_default' => false]);
            }

            $isDefault = $prefix === 'billing'
                ? $request->boolean('billing_is_default')
                : $request->boolean('is_default');

            if (!$isDefault && !Address::where('user_id', $user->id)->where('status', 1)->exists()) {
                $isDefault = true;
            }

            Address::create([
                'user_id' => $user->id,
                'name' => $validated[$prefix . '_name'],
                'phone' => $validated[$prefix . '_phone'],
                'address' => $validated[$prefix . '_address_line_1'],
                'landmark' => $validated[$prefix . '_address_line_2'] ?? null,
                'city' => $validated[$prefix . '_city'],
                'state' => $validated[$prefix . '_state'],
                'country' => 'India',
                'pincode' => $validated[$prefix . '_pincode'],
                'type' => 'home',
                'is_default' => $isDefault,
                'status' => 1,
            ]);
        }

        return [
            'name' => $validated[$prefix . '_name'],
            'phone' => $validated[$prefix . '_phone'],
            'alternate_phone' => null,
            'company_name' => null,
            'gstin' => null,
            'address' => $validated[$prefix . '_address_line_1'],
            'landmark' => $validated[$prefix . '_address_line_2'] ?? null,
            'city' => $validated[$prefix . '_city'],
            'state' => $validated[$prefix . '_state'],
            'country' => 'India',
            'pincode' => $validated[$prefix . '_pincode'],
        ];
    }

    public function addressToOrderPayload(Address $address): array
    {
        $street = $address->address;

        if (!$street && ($address->flat || $address->area)) {
            $street = trim(($address->flat ?? '') . ', ' . ($address->area ?? ''), ', ');
        }

        return [
            'name' => $address->name,
            'phone' => $address->phone,
            'alternate_phone' => $address->alternate_phone,
            'company_name' => null,
            'gstin' => null,
            'address' => $street,
            'landmark' => $address->landmark,
            'city' => $address->city,
            'state' => $address->state,
            'country' => 'India',
            'pincode' => $address->pincode,
        ];
    }

    public function clearCheckoutCart(int $userId): void
    {
        Cart::where('user_id', $userId)->delete();
        BuyNow::where('user_id', $userId)->delete();
        session()->forget(['applied_coupon_code', 'applied_coupon_discount', 'checkout_source']);
    }

    public function ensureOrderInvoice(Order $order): void
    {
        $rawInvoice = $order->getRawOriginal('invoice_no');
        if (!empty($rawInvoice) && preg_match('/^INV\d{3,8}$/', (string) $rawInvoice)) {
            return;
        }

        $order->update([
            'invoice_no' => Order::generateNextInvoiceNumber($order->id),
            'invoice_date' => $order->invoice_date ?? now()->toDateString(),
        ]);
    }
}
