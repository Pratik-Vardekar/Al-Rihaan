<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $cleanEmail = strtolower(trim($request->email));
        $subscriber = NewsletterSubscriber::where('email', $cleanEmail)->first();

        if ($subscriber) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'info',
                    'success' => true,
                    'message' => 'You are already subscribed to our newsletter!',
                ]);
            }
            return back()->with('info', 'You are already subscribed to our newsletter!');
        }

        NewsletterSubscriber::create([
            'email' => $cleanEmail,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => 'Thank you for subscribing to our newsletter!',
            ]);
        }

        return back()->with('success', 'Thank you for subscribing to our newsletter!');
    }
}
