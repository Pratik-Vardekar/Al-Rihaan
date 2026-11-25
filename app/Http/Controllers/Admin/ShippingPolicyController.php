<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShippingPolicyController extends Controller
{
    public function index()
    {
        $shippingPolicy = ShippingPolicy::orderBy('sort_order')->first();

        if (!$shippingPolicy) {
            $shippingPolicy = ShippingPolicy::create([
                'title' => 'Shipping & Delivery Policy',
                'slug' => 'shipping-policy',
                'content' => '<p>At <strong>Al Rihan Perfumes</strong>, we take great pride in ensuring that your luxury fragrances are delivered promptly, safely, and securely to your doorstep.</p><h3>Order Processing</h3><p>All orders are processed within 24 to 48 hours (excluding Sundays and national holidays). You will receive an SMS and email notification with your tracking details once your package is dispatched.</p><h3>Delivery Timelines</h3><p>Standard delivery across metro cities typically takes <strong>3 to 5 business days</strong>. For rest of India, delivery may take <strong>5 to 7 business days</strong>.</p><h3>Packaging</h3><p>Every bottle is sealed and securely wrapped with tamper-evident cushioned packaging to preserve fragrance integrity during transit.</p>',
                'meta_title' => 'Shipping & Delivery Policy | Al Rihan Perfumes',
                'meta_description' => 'Read Al Rihan Perfumes shipping and delivery policy, estimated delivery timelines, and order tracking information.',
                'meta_keywords' => 'shipping policy, delivery timelines, perfume delivery, al rihan order tracking',
                'is_active' => true,
                'sort_order' => 0
            ]);
        }

        return view('admin.shipping-policy.edit', compact('shippingPolicy'));
    }

    public function create()
    {
        return view('admin.shipping-policy.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $shippingPolicy = ShippingPolicy::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'content' => $request->content,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
            'is_active' => $request->has('is_active'),
            'sort_order' => (int) ShippingPolicy::max('sort_order') + 1
        ]);

        return redirect()->route('shipping-policy.edit', $shippingPolicy->id)
            ->with('success', 'Shipping Policy page created successfully.');
    }

    public function edit(ShippingPolicy $shippingPolicy)
    {
        return view('admin.shipping-policy.edit', compact('shippingPolicy'));
    }

    public function update(Request $request, ShippingPolicy $shippingPolicy)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $shippingPolicy->update([
            'title' => $request->title,
            'slug' => $request->filled('slug') ? Str::slug($request->slug) : Str::slug($request->title),
            'content' => $request->content,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('shipping-policy.edit', $shippingPolicy->id)
            ->with('success', 'Shipping Policy page updated successfully.');
    }

    public function destroy(ShippingPolicy $shippingPolicy)
    {
        $shippingPolicy->delete();

        return redirect()->route('shipping-policy.index')
            ->with('success', 'Shipping Policy page deleted successfully.');
    }

    public function toggleStatus(ShippingPolicy $shippingPolicy)
    {
        $shippingPolicy->update(['is_active' => !$shippingPolicy->is_active]);
        $status = $shippingPolicy->is_active ? 'activated' : 'deactivated';

        return redirect()->route('shipping-policy.edit', $shippingPolicy->id)
            ->with('success', "Shipping Policy page has been {$status} successfully.");
    }
}
