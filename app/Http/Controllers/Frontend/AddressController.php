<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /**
     * Customer addresses listing page.
     */
    public function myAccountAddresses()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $addresses = Address::where('user_id', $user->id)
            ->where('status', 1)
            ->latest()
            ->get();

        return view('frontend.pages.myaccount_addresses', compact('user', 'addresses'));
    }

    /**
     * Store new customer address.
     */
    public function storeAddress(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address_line_1' => 'required|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'pincode' => 'required|string|max:10',
            'is_default' => 'nullable|boolean',
        ]);

        $isDefault = $request->boolean('is_default');
        $hasAddresses = Address::where('user_id', $user->id)->where('status', 1)->exists();

        if (!$hasAddresses) {
            $isDefault = true;
        }

        if ($isDefault) {
            Address::where('user_id', $user->id)->update(['is_default' => false]);
        }

        Address::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'address' => $validated['address_line_1'],
            'landmark' => $validated['address_line_2'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'country' => 'India',
            'pincode' => $validated['pincode'],
            'type' => 'home',
            'is_default' => $isDefault,
            'status' => 1,
        ]);

        return back()->with('success', 'Address added successfully.');
    }

    /**
     * Delete an address.
     */
    public function destroyAddress(Address $address)
    {
        if ($address->user_id !== auth()->id()) {
            abort(403);
        }

        $wasDefault = $address->is_default;
        $userId = $address->user_id;
        $address->delete();

        if ($wasDefault) {
            $next = Address::where('user_id', $userId)
                ->where('status', 1)
                ->latest()
                ->first();

            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return back()->with('success', 'Address deleted successfully.');
    }

    /**
     * Quick save default address changes.
     */
    public function saveAddress(Request $request)
    {
        $user = auth()->user();
        $address = Address::where('user_id', $user->id)->where('is_default', 1)->first();
        if ($address) {
            $address->update($request->all());
        }
        return back()->with('success', 'Address saved successfully.');
    }

    /**
     * Set selected address as default.
     */
    public function setDefaultAddress(Address $address)
    {
        if ($address->user_id !== auth()->id()) {
            abort(403);
        }

        Address::where('user_id', auth()->id())->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return back()->with('success', 'Default address updated. It will be used as billing on checkout.');
    }
}

