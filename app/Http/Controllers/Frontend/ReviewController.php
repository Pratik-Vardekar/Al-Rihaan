<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    /**
     * Store new customer review for product.
     */
    public function addReview(Request $request, Product $product)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please Login to write a Review');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $alreadyReviewed = Review::where('product_id', $product->id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($alreadyReviewed) {
            return back()->with('error', 'You have already submitted a review for this product.');
        }

        $title = trim((string) ($validated['title'] ?? ''));
        $comment = trim($validated['comment']);
        $reviewText = $title !== '' ? $title . "\n\n" . $comment : $comment;

        $imageName = null;
        if ($request->hasFile('image')) {
            $imageName = $this->saveReviewImage($request->file('image'));
        }

        Review::create([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
            'rating' => (int) $validated['rating'],
            'review' => $reviewText,
            'status' => 0,
            'image' => $imageName,
        ]);

        return back()->with('success', 'Thank you! Your review has been submitted and is pending approval.');
    }

    /**
     * List all reviews for product.
     */
    public function indexReview(Product $product)
    {
        $reviews = Review::where('product_id', $product->id)
            ->with('user')
            ->latest()
            ->get();

        return view('frontend.pages.review.index', compact('product', 'reviews'));
    }

    /**
     * Show single review details.
     */
    public function showReview(Product $product, Review $review)
    {
        $review = Review::where('id', $review->id)
            ->where('product_id', $product->id)
            ->where('status', 1)
            ->firstOrFail();

        return view('frontend.pages.review.show', compact('product', 'review'));
    }

    /**
     * Helper to save review image to both public_path and base_path.
     */
    protected function saveReviewImage($file): string
    {
        $imageName = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();

        $publicDir = public_path('backend/assets/images/review');
        $baseDir   = base_path('backend/assets/images/review');

        if (!file_exists($publicDir)) {
            @mkdir($publicDir, 0755, true);
        }
        if (!file_exists($baseDir)) {
            @mkdir($baseDir, 0755, true);
        }

        $file->move($publicDir, $imageName);

        $publicFile = $publicDir . DIRECTORY_SEPARATOR . $imageName;
        $baseFile   = $baseDir . DIRECTORY_SEPARATOR . $imageName;

        if (file_exists($publicFile) && !file_exists($baseFile)) {
            @copy($publicFile, $baseFile);
        }

        return $imageName;
    }
}

