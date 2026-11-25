<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CartService
{
    public const SESSION_KEY = 'guest_cart';

    public function getCartCount(): int
    {
        if (Auth::check()) {
            return (int) Cart::where('user_id', Auth::id())->whereHas('product')->count();
        }

        $guestCart = $this->getGuestCart();
        if (empty($guestCart)) {
            return 0;
        }

        $uniqueProductIds = [];
        foreach (array_keys($guestCart) as $k) {
            $pId = (int) explode('_', (string) $k)[0];
            if ($pId > 0) {
                $uniqueProductIds[$pId] = true;
            }
        }

        return count($uniqueProductIds);
    }

    public function getCartProductIds(): array
    {
        if (Auth::check()) {
            return Cart::where('user_id', Auth::id())
                ->whereHas('product')
                ->pluck('product_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $guestCart = $this->getGuestCart();
        $pIds = [];
        foreach (array_keys($guestCart) as $k) {
            $pId = (int) explode('_', (string) $k)[0];
            if ($pId > 0) {
                $pIds[] = $pId;
            }
        }

        return array_values(array_unique($pIds));
    }

    public function isInCart(int $productId, ?int $variantId = null, ?string $size = null): bool
    {
        if (Auth::check()) {
            $q = Cart::where('user_id', Auth::id())->where('product_id', $productId);
            return $q->exists();
        }

        $guestCart = $this->getGuestCart();
        foreach (array_keys($guestCart) as $k) {
            $pId = (int) explode('_', (string) $k)[0];
            if ($pId === $productId) {
                return true;
            }
        }

        return false;
    }

    public function getGuestCart(): array
    {
        return session(self::SESSION_KEY, []);
    }

    public function buildGuestKey(int $productId, ?int $variantId = null, ?string $size = null): string
    {
        $key = (string) $productId;
        if ($variantId) {
            $key .= '_' . $variantId;
        }
        if ($size) {
            $key .= '_' . trim($size);
        }
        return $key;
    }

    public function addItem(int $productId, int $qty = 1, ?int $variantId = null, ?string $size = null): int
    {
        $product = Product::findOrFail($productId);
        $availableStock = (int) ($product->quantity ?? 0);
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if ($variant && isset($variant->quantity)) {
                $availableStock = (int) $variant->quantity;
            }
        }

        if ($availableStock <= 0) {
            return $this->getCartCount();
        }

        $qty = max(1, min($availableStock, $qty));

        if (Auth::check()) {
            // Find if item already exists for this product
            $cartItem = Cart::where('user_id', Auth::id())
                ->where('product_id', $productId)
                ->first();

            if ($cartItem) {
                $newQty = min($availableStock, (int) $cartItem->quantity + $qty);
                $cartItem->update([
                    'quantity' => $newQty,
                    'variant_id' => $variantId ?: $cartItem->variant_id,
                    'size' => $size ?: $cartItem->size,
                ]);

                // Deduplicate any other accidental rows for this product
                Cart::where('user_id', Auth::id())
                    ->where('product_id', $productId)
                    ->where('id', '!=', $cartItem->id)
                    ->delete();
            } else {
                Cart::create([
                    'user_id' => Auth::id(),
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'size' => $size,
                    'quantity' => $qty,
                ]);
            }

            return $this->getCartCount();
        }

        $guestCart = $this->getGuestCart();
        $targetKey = $this->buildGuestKey($productId, $variantId, $size);

        // Check if any key for this product already exists in guest cart
        $existingKey = null;
        if (isset($guestCart[$targetKey])) {
            $existingKey = $targetKey;
        } else {
            foreach (array_keys($guestCart) as $k) {
                $pId = (int) explode('_', (string) $k)[0];
                if ($pId === $productId) {
                    $existingKey = $k;
                    break;
                }
            }
        }

        if ($existingKey !== null) {
            $oldQty = is_array($guestCart[$existingKey]) ? ($guestCart[$existingKey]['quantity'] ?? 1) : (int) $guestCart[$existingKey];
            $newQty = min($availableStock, $oldQty + $qty);

            // If we have size or variant and old key didn't, upgrade key to targetKey
            if ($existingKey !== $targetKey && ($variantId || $size)) {
                unset($guestCart[$existingKey]);
                $guestCart[$targetKey] = $newQty;
            } else {
                $guestCart[$existingKey] = $newQty;
            }
        } else {
            $guestCart[$targetKey] = $qty;
        }

        // Clean up any other keys for the same product to prevent duplicates
        $activeKey = $targetKey;
        if (!isset($guestCart[$activeKey]) && isset($existingKey)) {
            $activeKey = $existingKey;
        }
        foreach (array_keys($guestCart) as $k) {
            $pId = (int) explode('_', (string) $k)[0];
            if ($pId === $productId && $k !== $activeKey) {
                unset($guestCart[$k]);
            }
        }

        session([self::SESSION_KEY => $guestCart]);
        session()->save();

        return $this->getCartCount();
    }

    public function getCartItems(): Collection
    {
        if (Auth::check()) {
            $items = Cart::with([
                'product.gstSetting',
                'variant.variantAttributes.attribute',
                'variant.variantAttributes.attributeValue',
            ])
                ->where('user_id', Auth::id())
                ->whereHas('product')
                ->latest()
                ->get();

            // Deduplicate if user has multiple rows for the same product_id
            $grouped = $items->groupBy('product_id');
            if ($grouped->contains(fn($group) => $group->count() > 1)) {
                $dedupedItems = collect();
                foreach ($grouped as $productId => $group) {
                    if ($group->count() === 1) {
                        $dedupedItems->push($group->first());
                    } else {
                        // Prefer item with size or variant
                        $primary = $group->first(fn($item) => !empty($item->size) || !empty($item->variant_id)) ?: $group->first();
                        $totalQty = min(10, $group->sum('quantity'));
                        $primary->update(['quantity' => $totalQty]);

                        foreach ($group as $dup) {
                            if ($dup->id !== $primary->id) {
                                $dup->delete();
                            }
                        }
                        $dedupedItems->push($primary);
                    }
                }
                return $dedupedItems->values();
            }

            return $items;
        }

        $guestCart = $this->getGuestCart();
        if (empty($guestCart)) {
            return collect();
        }

        $parsedEntries = [];
        $seenProductIds = [];
        $hasDuplicates = false;

        foreach ($guestCart as $key => $val) {
            $parts = explode('_', (string) $key);
            $pId = (int) ($parts[0] ?? 0);
            if ($pId <= 0) continue;

            $vId = null;
            $itemSize = null;
            if (count($parts) === 2) {
                if (is_numeric($parts[1])) {
                    $vId = (int) $parts[1];
                } else {
                    $itemSize = $parts[1];
                }
            } elseif (count($parts) >= 3) {
                $vId = is_numeric($parts[1]) ? (int) $parts[1] : null;
                $itemSize = implode('_', array_slice($parts, 2));
            }

            $qty = is_array($val) ? (int) ($val['quantity'] ?? 1) : (int) $val;
            $qty = max(1, min(10, $qty));

            if (isset($seenProductIds[$pId])) {
                $hasDuplicates = true;
            }
            $seenProductIds[$pId] = true;

            $parsedEntries[] = [
                'key' => (string) $key,
                'product_id' => $pId,
                'variant_id' => $vId,
                'size' => $itemSize,
                'quantity' => $qty,
            ];
        }

        // Deduplicate guest cart items: merge same product_id into single item
        if ($hasDuplicates) {
            $byProduct = collect($parsedEntries)->groupBy('product_id');
            $dedupedParsed = [];
            $cleanedSessionCart = [];

            foreach ($byProduct as $pId => $group) {
                // Prefer the item with size or variant
                $primary = $group->first(fn($e) => !empty($e['size']) || !empty($e['variant_id'])) ?: $group->first();
                $totalQty = min(10, $group->sum('quantity'));
                $primary['quantity'] = $totalQty;

                $dedupedParsed[] = $primary;
                $cleanedSessionCart[$primary['key']] = $totalQty;
            }

            $parsedEntries = $dedupedParsed;
            session([self::SESSION_KEY => $cleanedSessionCart]);
            session()->save();
        }

        $productIds = array_unique(array_column($parsedEntries, 'product_id'));
        $variantIds = array_filter(array_unique(array_column($parsedEntries, 'variant_id')));

        $products = Product::with('gstSetting')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $variants = !empty($variantIds)
            ? ProductVariant::with(['variantAttributes.attribute', 'variantAttributes.attributeValue'])
                ->whereIn('id', $variantIds)
                ->get()
                ->keyBy('id')
            : collect();

        return collect($parsedEntries)->map(function ($entry) use ($products, $variants) {
            $product = $products->get($entry['product_id']);
            if (!$product) {
                return null;
            }

            $variant = !empty($entry['variant_id']) ? $variants->get($entry['variant_id']) : null;

            return (object) [
                'id' => 'guest_' . $entry['key'],
                'cart_key' => $entry['key'],
                'product_id' => $entry['product_id'],
                'variant_id' => $entry['variant_id'],
                'variant' => $variant,
                'size' => $entry['size'],
                'quantity' => (int) $entry['quantity'],
                'product' => $product,
                'is_guest' => true,
            ];
        })->filter()->values();
    }

    public function mergeGuestCartToUser(int $userId): void
    {
        $guestCart = $this->getGuestCart();
        if (empty($guestCart)) {
            return;
        }

        foreach ($guestCart as $key => $qty) {
            $parts = explode('_', (string) $key);
            $pId = (int) ($parts[0] ?? 0);
            if ($pId <= 0) continue;

            $vId = null;
            $size = null;
            if (count($parts) === 2) {
                if (is_numeric($parts[1])) {
                    $vId = (int) $parts[1];
                } else {
                    $size = $parts[1];
                }
            } elseif (count($parts) >= 3) {
                $vId = is_numeric($parts[1]) ? (int) $parts[1] : null;
                $size = implode('_', array_slice($parts, 2));
            }

            $qty = is_array($qty) ? (int) ($qty['quantity'] ?? 1) : (int) $qty;
            $qty = max(1, min(10, $qty));

            $cartItem = Cart::where('user_id', $userId)->where('product_id', $pId)->first();

            if ($cartItem) {
                $newQty = min(10, $cartItem->quantity + $qty);
                $cartItem->update([
                    'quantity' => $newQty,
                    'variant_id' => $vId ?: $cartItem->variant_id,
                    'size' => $size ?: $cartItem->size,
                ]);
            } else {
                Cart::create([
                    'user_id' => $userId,
                    'product_id' => $pId,
                    'variant_id' => $vId,
                    'size' => $size,
                    'quantity' => $qty,
                ]);
            }
        }

        session()->forget(self::SESSION_KEY);
    }

    public function updateGuestItem(int $productId, int $qty, ?string $cartKey = null, ?int $variantId = null, ?string $size = null): void
    {
        $guestCart = $this->getGuestCart();

        if ($qty < 1) {
            $this->removeGuestItem($productId, $cartKey);
            return;
        }

        $product = Product::find($productId);
        $availableStock = $product ? (int) ($product->quantity ?? 0) : 0;
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if ($variant && isset($variant->quantity)) {
                $availableStock = (int) $variant->quantity;
            }
        }
        $maxStock = $availableStock > 0 ? $availableStock : 1;
        $qty = min($maxStock, max(1, $qty));

        $cleanKey = $cartKey ? (str_starts_with($cartKey, 'guest_') ? substr($cartKey, 6) : $cartKey) : null;

        // If specific key exists, update that key
        if ($cleanKey && isset($guestCart[$cleanKey])) {
            $guestCart[$cleanKey] = $qty;
            // Remove any other duplicate key for the same product
            foreach (array_keys($guestCart) as $k) {
                $pId = (int) explode('_', (string) $k)[0];
                if ($pId === $productId && $k !== $cleanKey) {
                    unset($guestCart[$k]);
                }
            }
        } else {
            // Find existing key for this productId
            $foundKey = null;
            foreach (array_keys($guestCart) as $k) {
                $pId = (int) explode('_', (string) $k)[0];
                if ($pId === $productId) {
                    $foundKey = $k;
                    break;
                }
            }

            if ($foundKey !== null) {
                $guestCart[$foundKey] = $qty;
                // Remove any other duplicate key for the same product
                foreach (array_keys($guestCart) as $k) {
                    $pId = (int) explode('_', (string) $k)[0];
                    if ($pId === $productId && $k !== $foundKey) {
                        unset($guestCart[$k]);
                    }
                }
            } else {
                $newKey = $this->buildGuestKey($productId, $variantId, $size);
                $guestCart[$newKey] = $qty;
            }
        }

        session([self::SESSION_KEY => $guestCart]);
        session()->save();
    }

    public function removeGuestItem(int $productId, ?string $cartKey = null): void
    {
        $guestCart = $this->getGuestCart();

        if ($cartKey) {
            $cleanKey = str_starts_with($cartKey, 'guest_') ? substr($cartKey, 6) : $cartKey;
            unset($guestCart[$cleanKey]);
        }

        // Also remove any entries for this product
        unset($guestCart[$productId]);
        foreach (array_keys($guestCart) as $k) {
            $pId = (int) explode('_', (string) $k)[0];
            if ($pId === $productId) {
                unset($guestCart[$k]);
            }
        }

        session([self::SESSION_KEY => $guestCart]);
        session()->save();
    }
}
