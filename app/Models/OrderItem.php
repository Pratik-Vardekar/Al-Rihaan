<?php

namespace App\Models;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'size',
        'quantity',
        'price',
        'total',
    ];

    /**
     * Get the display size label for this order item.
     */
    public function getSizeLabel(): ?string
    {
        if (!empty($this->size)) {
            return $this->size;
        }

        $variant = $this->relationLoaded('variant') ? $this->variant : $this->variant()->first();
        if ($variant) {
            $attr = $variant->variantAttributes ? $variant->variantAttributes->first() : null;
            if ($attr && optional($attr->attributeValue)->value) {
                return $attr->attributeValue->value;
            }
            if (!empty($variant->sku)) {
                return $variant->sku;
            }
        }

        // If variant_id was not saved, check if product has a variant matching this item's price
        if ($this->product && $this->price > 0) {
            $matchingVariant = $this->product->variants()
                ->where('price', $this->price)
                ->with('variantAttributes.attributeValue')
                ->first();
            if ($matchingVariant) {
                $attr = $matchingVariant->variantAttributes ? $matchingVariant->variantAttributes->first() : null;
                if ($attr && optional($attr->attributeValue)->value) {
                    return $attr->attributeValue->value;
                }
            }

            // Fallback: Infer standard fragrance size only if product actually has variants
            $hasDbVariants = $this->product->relationLoaded('variants')
                ? $this->product->variants->isNotEmpty()
                : $this->product->variants()->exists();

            if ($hasDbVariants) {
                $basePrice = (float) ($this->product->selling_price > 0 ? $this->product->selling_price : $this->product->price);
                $itemPrice = (float) $this->price;
                if ($basePrice > 0 && $itemPrice > 0) {
                    if (abs($itemPrice - $basePrice) < 5.0) {
                        return '100 ML';
                    }
                    $ratio = $itemPrice / $basePrice;
                    if ($ratio <= 0.32) {
                        return '5.5 ML';
                    } elseif ($ratio <= 0.55) {
                        return '12 ML';
                    } elseif ($ratio <= 0.85) {
                        return '50 ML';
                    } else {
                        return '100 ML';
                    }
                }
                return '100 ML';
            }
        }

        return null;
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
