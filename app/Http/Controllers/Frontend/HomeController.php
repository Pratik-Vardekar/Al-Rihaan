<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\HandlesProductListing;
use App\Models\Category;
use App\Models\Gallery;
use App\Models\HomeFaq;
use App\Models\HomeFaqSection;
use App\Models\HomeHighlight;
use App\Models\HomeServiceItem;
use App\Models\HomeServiceSectionSetting;
use App\Models\HowToPlaySection;
use App\Models\LearnFunSection;
use App\Models\PlayFunSection;
use App\Models\Product;
use App\Models\Review;
use App\Models\Slider;
use App\Models\SpecialOffer;
use App\Models\Watches;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    use HandlesProductListing;

    /**
     * Storefront Home Page.
     */
    public function index()
    {
        $sliders = Slider::where('status', 1)
            ->orderBy('sort_order', 'asc')
            ->latest('id')
            ->get();

        $categories = Category::where('status', 1)
            ->withStartingFromPrice()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $bestSellingProducts = Product::where('status', 1)
            ->where('is_best_selling', 1)
            ->with(['brand', 'category'])
            ->withCount([
                'reviews as reviews_count' => fn($query) => $query->where('status', 1),
            ])
            ->latest()
            ->take(8)
            ->get();

        $specialOffer = SpecialOffer::where('is_active', 1)
            ->orderBy('sort_order')
            ->first();

        $this->attachProductReviewMeta($bestSellingProducts);

        $newArrivalsProducts = Product::where('status', 1)
            ->where('is_new_arrival', 1)
            ->with(['brand', 'category'])
            ->withCount([
                'reviews as reviews_count' => fn($query) => $query->where('status', 1),
            ])
            ->latest()
            ->take(8)
            ->get();

        if ($newArrivalsProducts->isEmpty()) {
            $newArrivalsProducts = Product::where('status', 1)
                ->with(['brand', 'category'])
                ->withCount([
                    'reviews as reviews_count' => fn($query) => $query->where('status', 1),
                ])
                ->latest()
                ->take(8)
                ->get();
        }

        $this->attachProductReviewMeta($newArrivalsProducts);
        $newArrivals = $newArrivalsProducts;

        $wishlistIds = [];
        try {
            $wishlistIds = app(\App\Services\WishlistService::class)->getProductIds();
        } catch (\Throwable $e) {
            $wishlistIds = [];
        }

        $galleries = Gallery::where('status', 1)
            ->latest()
            ->take(2)
            ->get();

        $playFunSection = PlayFunSection::first();
        $learnFunSection = LearnFunSection::first();
        $howToPlaySection = HowToPlaySection::first();

        $homeFaqs = HomeFaq::active()
            ->ordered()
            ->get();
        $homeFaqSection = HomeFaqSection::first();

        $homeTestimonials = Review::with(['user', 'product'])
            ->where('status', 1)
            ->whereNotNull('review')
            ->where('review', '!=', '')
            ->latest()
            ->take(8)
            ->get();

        $homeServiceItems = HomeServiceItem::where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $homeServiceSectionSetting = null;
        try {
            if (Schema::hasTable('home_service_section_settings')) {
                $homeServiceSectionSetting = HomeServiceSectionSetting::first();
            }
        } catch (\Throwable $e) {
            $homeServiceSectionSetting = null;
        }

        $homeHighlights = HomeHighlight::where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $watchBanners = Watches::where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('frontend.pages.index', compact(
            'sliders',
            'categories',
            'bestSellingProducts',
            'newArrivalsProducts',
            'newArrivals',
            'wishlistIds',
            'galleries',
            'playFunSection',
            'learnFunSection',
            'howToPlaySection',
            'homeFaqs',
            'homeFaqSection',
            'homeTestimonials',
            'homeHighlights',
            'homeServiceItems',
            'homeServiceSectionSetting',
            'watchBanners',
            'specialOffer'
        ));
    }
    /**
     * Best Sellers Page.
     */
    public function bestSellers(Request $request)
    {
        return $this->buildListingPage(
            request: $request,
            filterAction: route('best.sellers'),
            clearFilterUrl: route('best.sellers'),
            presetFilter: 'best_selling',
            customTitle: 'Best Sellers',
            customSubtitle: 'Discover our best-selling fragrances, loved by customers for their style, quality, and popularity.',
            customBreadcrumb: 'Best Sellers'
        );
    }

    /**
     * New Arrivals Page.
     */
    public function newarrivals(Request $request)
    {
        return $this->buildListingPage(
            request: $request,
            filterAction: route('new.arrivals'),
            clearFilterUrl: route('new.arrivals'),
            presetFilter: 'new_arrival',
            customTitle: 'New Arrivals',
            customSubtitle: 'Explore our newest fragrance arrivals and latest luxury collections.',
            customBreadcrumb: 'New Arrivals'
        );
    }

    /**
     * Combo Offers Page.
     */
    public function comboOffers(Request $request)
    {
        try {
            if (!Schema::hasColumn('products', 'is_combo_offer')) {
                Schema::table('products', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->boolean('is_combo_offer')->default(0)->after('is_signature');
                });
            }
        } catch (\Throwable $e) {
        }

        return $this->buildListingPage(
            request: $request,
            filterAction: route('combo.offers'),
            clearFilterUrl: route('combo.offers'),
            presetFilter: 'combo_offers',
            customTitle: 'Combo Offers',
            customSubtitle: 'Explore our exclusive fragrance sets and curated bundle offers.',
            customBreadcrumb: 'Combo Offers'
        );
    }

    /**
     * Gifting Collection Page.
     */
    public function gifting(Request $request)
    {
        try {
            if (!Schema::hasColumn('products', 'is_combo_offer')) {
                Schema::table('products', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->boolean('is_combo_offer')->default(0)->after('is_signature');
                });
            }
        } catch (\Throwable $e) {
        }

        return $this->buildListingPage(
            request: $request,
            filterAction: route('gifting'),
            clearFilterUrl: route('gifting'),
            presetFilter: 'gifting',
            customTitle: 'Gifting Collection',
            customSubtitle: 'Thoughtfully curated fragrance combo sets crafted to delight your loved ones on every special occasion.',
            customBreadcrumb: 'Gifting'
        );
    }
}
