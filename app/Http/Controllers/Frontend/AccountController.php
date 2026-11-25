<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    /**
     * Customer Profile Page.
     */
    public function myAccountProfile()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        return view('frontend.pages.myaccount_profile', compact('user'));
    }

    /**
     * Update customer profile info.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($request->only('name', 'email', 'phone'));

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Customer orders listing page.
     */
    public function myAccountOrders(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $status = $request->input('status');

        $orders = Order::with(['items.product.brand'])
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('order_status', '!=', 'pending')
                    ->orWhere('payment_status', 'paid')
                    ->orWhere('payment_status', 'partial')
                    ->orWhere(function ($sub) {
                        $sub->where('order_status', 'pending')
                            ->whereNull('payment_gateway')
                            ->where('payment_status', '!=', 'failed');
                    });
            })
            ->when($status === 'delivered', fn($q) => $q->where('order_status', 'delivered'))
            ->when($status === 'cancelled', fn($q) => $q->where('order_status', 'cancelled'))
            ->when($status === 'returns', function ($q) {
                $q->whereNotNull('return_requested_at');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $this->cleanupOrderItemsForOrders($orders);

        return view('frontend.pages.myaccount_orders', compact('user', 'orders'));
    }

    /**
     * Customer order details page.
     */
    public function myAccountOrderDetails(Order $order)
    {
        if ($order->user_id != auth()->id()) {
            abort(403);
        }

        if ($order->order_status === 'pending' && $order->payment_status === 'pending' && $order->payment_gateway === 'razorpay' && (float) ($order->paid_amount ?? 0) <= 0) {
            abort(404);
        }

        $this->cleanupOrderItemsForOrders($order);

        $order->load(['items.product', 'shippingAddress', 'addresses']);

        return view('frontend.pages.order_details', compact('order'));
    }

    /**
     * Cancel order request from customer.
     */
    public function cancelOrder(Request $request, Order $order)
    {
        if ($order->user_id != auth()->id()) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:255',
            'note' => 'nullable|string|max:1000',
        ]);

        if (! in_array($order->order_status, ['pending', 'confirmed', 'processing'])) {
            return back()->with('error', 'This order cannot be cancelled now.');
        }

        if ($order->cancel_requested_at && ! $order->cancel_approved_at && ! $order->cancel_rejected_at) {
            return back()->with('error', 'Cancellation request already submitted.');
        }

        $order->update([
            'cancel_requested_at' => now(),
            'cancel_request_reason' => $request->reason,
            'cancel_request_note' => $request->note,
            'cancel_approved_at' => null,
            'cancel_rejected_at' => null,
            'cancel_decision_note' => null,
        ]);

        $message = 'Cancellation request sent to admin.';

        if ($order->payment_method === 'razorpay' && (float) $order->paid_amount > 0) {
            $message .= ' If approved, your refund will be credited to the original payment method within 2-3 working days.';
        }

        return back()->with('success', $message);
    }

    /**
     * Return/Exchange request from customer.
     */
    public function returnOrder(Request $request, Order $order)
    {
        if ($order->user_id != auth()->id()) {
            abort(403);
        }

        $request->validate([
            'request_type' => 'required|in:return,exchange',
            'reason' => 'required|string|max:255',
            'note' => 'nullable|string|max:1000',
        ]);

        if (
            strtolower($order->order_status ?? '') !== 'delivered' ||
            ! $order->returnDeadline() ||
            now()->gt($order->returnDeadline())
        ) {
            return back()->with('error', 'Return or exchange is no longer available for this order.');
        }

        if ($order->return_requested_at && ! $order->return_approved_at && ! $order->return_rejected_at) {
            return back()->with('error', 'Return or exchange request already submitted.');
        }

        $order->update([
            'return_requested_at' => now(),
            'return_request_type' => $request->request_type,
            'return_reason' => $request->reason,
            'return_request_note' => $request->note,
            'return_approved_at' => null,
            'return_rejected_at' => null,
            'return_decision_note' => null,
            'pickup_date' => null,
            'returned_at' => null,
        ]);

        $message = ucfirst($request->request_type) . ' request submitted successfully.';

        if (
            $request->request_type === 'return' &&
            $order->payment_method === 'razorpay' &&
            (float) $order->paid_amount > 0
        ) {
            $message .= ' If approved, your refund will be credited to the original payment method within 2-3 working days.';
        }

        return back()->with('success', $message);
    }

    /**
     * Cancel return/exchange request.
     */
    public function cancelReturn(Order $order)
    {
        if ($order->user_id != auth()->id()) {
            abort(403);
        }

        if (
            ! $order->return_requested_at ||
            $order->return_approved_at ||
            $order->return_rejected_at
        ) {
            return back()->with('error', 'No pending return or exchange request found.');
        }

        $order->update([
            'return_requested_at' => null,
            'return_request_type' => null,
            'return_reason' => null,
            'return_request_note' => null,
            'return_approved_at' => null,
            'return_rejected_at' => null,
            'return_decision_note' => null,
            'pickup_date' => null,
        ]);

        return back()->with('success', 'Return or exchange request cancelled.');
    }

    /**
     * Customer change password page.
     */
    public function myAccountChangePassword()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        return view('frontend.pages.myaccount_changepassword', ['user' => Auth::user()]);
    }

    /**
     * Update customer password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        $user->update([
            'password' => $request->password,
            'plain_password' => $request->password,
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    protected function cleanupOrderItemsForOrders($orders): void
    {
        $orderList = $orders instanceof \Illuminate\Support\Collection || $orders instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $orders
            : collect([$orders]);

        $orderIds = $orderList->pluck('id')->filter()->all();
        if (empty($orderIds)) {
            return;
        }

        $deletedAny = false;

        $duplicateItemIds = OrderItem::whereIn('order_id', $orderIds)
            ->select('id', 'order_id', 'product_id', 'variant_id')
            ->get()
            ->groupBy(fn($item) => $item->order_id . '_' . $item->product_id . '_' . ($item->variant_id ?? '0'))
            ->filter(fn($group) => $group->count() > 1)
            ->flatMap(fn($group) => $group->slice(1)->pluck('id'))
            ->all();

        if (!empty($duplicateItemIds)) {
            OrderItem::whereIn('id', $duplicateItemIds)->delete();
            $deletedAny = true;
        }

        foreach ($orderList as $ord) {
            $ordItems = OrderItem::where('order_id', $ord->id)->with('product')->get();
            if ($ordItems->count() <= 1) {
                continue;
            }

            $orderSubtotal = (float) ($ord->subtotal > 0 ? $ord->subtotal : $ord->grand_total);
            $itemsSum = (float) $ordItems->sum(function ($it) {
                $p = (float) ($it->price > 0 ? $it->price : ($it->product->selling_price ?? $it->product->price ?? 0));
                $q = max(1, (int) $it->quantity);
                return (float) ($it->total > 0 ? $it->total : ($p * $q));
            });

            if ($orderSubtotal > 0 && ($itemsSum - $orderSubtotal) > 1.0) {
                $matchingItem = $ordItems->first(function ($it) use ($orderSubtotal) {
                    $p = (float) ($it->price > 0 ? $it->price : ($it->product->selling_price ?? $it->product->price ?? 0));
                    $q = max(1, (int) $it->quantity);
                    $lineTotal = (float) ($it->total > 0 ? $it->total : ($p * $q));
                    return abs($lineTotal - $orderSubtotal) < 1.0;
                });

                $invalidIds = [];
                if ($matchingItem) {
                    $invalidIds = $ordItems->where('id', '!=', $matchingItem->id)->pluck('id')->all();
                } else {
                    $invalidIds = $ordItems->filter(function ($it) use ($orderSubtotal) {
                        $p = (float) ($it->price > 0 ? $it->price : ($it->product->selling_price ?? $it->product->price ?? 0));
                        $q = max(1, (int) $it->quantity);
                        $lineTotal = (float) ($it->total > 0 ? $it->total : ($p * $q));
                        return $lineTotal > ($orderSubtotal + 1.0);
                    })->pluck('id')->all();
                }

                if (!empty($invalidIds)) {
                    OrderItem::whereIn('id', $invalidIds)->delete();
                    $deletedAny = true;
                }
            }
        }

        if ($deletedAny) {
            foreach ($orderList as $ord) {
                $ord->load(['items.product.brand']);
            }
        }
    }
}

