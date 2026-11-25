<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\HandlesProductListing;
use App\Mail\ContactEnquiryAdminMail;
use App\Mail\ContactEnquiryUserMail;
use App\Models\AboutUsSection;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Compliance;
use App\Models\ContactEnquiry;
use App\Models\ContactSetting;
use App\Models\Gallery;
use App\Models\HelpCategory;
use App\Models\HomeBanner;
use App\Models\HomeServiceItem;
use App\Models\HomeServiceSectionSetting;
use App\Models\PrivacyPolicy;
use App\Models\Product;
use App\Models\ReturnRefund;
use App\Models\Review;
use App\Models\Service;
use App\Models\ShippingPolicy;
use App\Models\Slider;
use App\Models\SpecialOffer;
use App\Models\Store;
use App\Models\SubCategory;
use App\Models\TermsAndCondition;
use App\Models\WarrantyGuarantee;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class CmsController extends Controller
{
    use HandlesProductListing;

    public function __construct(
        protected ?WishlistService $wishlistService = null
    ) {}

    /**
     * About Us page.
     */
    public function about()
    {
        $aboutSection = AboutUsSection::first();

        if (!$aboutSection) {
            $aboutSection = AboutUsSection::create([
                'title' => 'About Us',
                'content' => 'Welcome to our perfume store.',
                'image' => null,
            ]);
        }

        $homeTestimonials = Review::with(['user', 'product'])
            ->where('status', 1)
            ->whereNotNull('review')
            ->where('review', '!=', '')
            ->latest()
            ->take(8)
            ->get();

        $brands = Brand::where('status', 1)
            ->whereHas('products', function ($q) {
                $q->where('status', 1);
            })
            ->orderBy('position')
            ->orderBy('name')
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

        return view('frontend.pages.about', compact('aboutSection', 'homeTestimonials', 'brands', 'homeServiceItems', 'homeServiceSectionSetting'));
    }

    /**
     * Our Stores page.
     */
    public function ourStores()
    {
        $stores = Store::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.pages.our_store', compact('stores'));
    }

    /**
     * Our Services page.
     */
    public function ourServices()
    {
        $services = Service::where('status', 1)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.pages.services', compact('services'));
    }

    /**
     * Explore catalog overview page.
     */
    public function explore()
    {
        $exploreSubcategories = SubCategory::where('status', 1)
            ->where('show_in_explore', 1)
            ->with('category')
            ->orderBy('id')
            ->get();

        if ($exploreSubcategories->isEmpty()) {
            $exploreSubcategories = SubCategory::where('status', 1)
                ->with('category')
                ->latest()
                ->get();
        }

        $categories = Category::where('status', 1)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $featuredProducts = Product::where('status', 1)
            ->where(function ($q) {
                $q->where('is_featured', 1)
                    ->orWhere('is_trending', 1)
                    ->orWhere('is_best_selling', 1);
            })
            ->with(['brand', 'category'])
            ->withCount([
                'reviews as reviews_count' => fn($query) => $query->where('status', 1),
            ])
            ->latest()
            ->take(8)
            ->get();

        if ($featuredProducts->isEmpty()) {
            $featuredProducts = Product::where('status', 1)
                ->with(['brand', 'category'])
                ->withCount([
                    'reviews as reviews_count' => fn($query) => $query->where('status', 1),
                ])
                ->latest()
                ->take(8)
                ->get();
        }

        $this->attachProductReviewMeta($featuredProducts);

        $services = Service::where('status', 1)
            ->orderBy('sort_order')
            ->take(6)
            ->get();

        $brands = Brand::where('status', 1)
            ->whereHas('products', function ($q) {
                $q->where('status', 1);
            })
            ->orderBy('position')
            ->get();

        $wishlistIds = [];
        try {
            if ($this->wishlistService) {
                $wishlistIds = $this->wishlistService->getProductIds();
            }
        } catch (\Throwable $e) {
            $wishlistIds = [];
        }

        $listingTitle = 'Explore Our Products & Services';

        return view('frontend.pages.explore', compact(
            'exploreSubcategories',
            'categories',
            'featuredProducts',
            'services',
            'brands',
            'wishlistIds',
            'listingTitle'
        ));
    }

    /**
     * Special offers page.
     */
    public function specialOffers()
    {
        $specialOffer = SpecialOffer::where('is_active', 1)
            ->orderBy('sort_order')
            ->first();

        return view('frontend.pages.index', compact('specialOffer'));
    }

    /**
     * Gallery listing page.
     */
    public function gallery()
    {
        $galleries = Gallery::where('status', 1)
            ->latest()
            ->paginate(6);

        return view('frontend.pages.gallery', compact('galleries'));
    }

    /**
     * Gallery details page.
     */
    public function galleryDetails($slug)
    {
        $gallery = Gallery::where('slug', $slug)
            ->where('status', 1)
            ->with('images')
            ->firstOrFail();

        return view('frontend.pages.gallery-details', compact('gallery'));
    }

    /**
     * Blogs listing page.
     */
    public function blogs()
    {
        $blogs = Blog::where('status', 1)
            ->latest()
            ->paginate(6);

        return view('frontend.pages.blogs', compact('blogs'));
    }

    /**
     * Blog details page.
     */
    public function blogsDetails($slug)
    {
        $blog = Blog::where('slug', $slug)
            ->where('status', 1)
            ->firstOrFail();

        return view('frontend.pages.blogs_details', compact('blog'));
    }

    /**
     * Privacy Policy page.
     */
    public function privacyPolicy()
    {
        $privacy = PrivacyPolicy::first();

        return view('frontend.pages.privacy_policy', compact('privacy'));
    }

    /**
     * Terms and Conditions page.
     */
    public function termsAndConditions()
    {
        $terms = TermsAndCondition::first();

        return view('frontend.pages.terms_and_conditions_page', compact('terms'));
    }

    /**
     * Return Policy page.
     */
    public function returnPolicy()
    {
        $returnRefund = ReturnRefund::where('is_active', 1)
            ->orderBy('sort_order')
            ->first();

        return view('frontend.pages.return_policy', compact('returnRefund'));
    }

    /**
     * Warranties & Guarantees page.
     */
    public function warrantiesGuarantees()
    {
        $warrantyGuarantee = WarrantyGuarantee::where('is_active', 1)
            ->orderBy('sort_order')
            ->first();

        return view('frontend.pages.warranties_guarantees', compact('warrantyGuarantee'));
    }

    /**
     * Compliance page.
     */
    public function compliance()
    {
        $compliance = null;
        try {
            $compliance = Compliance::where('is_active', 1)->orderBy('sort_order')->first();
        } catch (\Throwable $e) {
            Log::warning('Compliance query failed: ' . $e->getMessage());
        }

        if (!$compliance) {
            $compliance = (object) [
                'title' => 'Compliance & Standards',
                'meta_title' => 'Compliance & Standards | Al Rihan Perfumes',
                'meta_description' => 'Learn about Al Rihan Perfumes compliance, ethical sourcing, and IFRA safety standards.',
                'meta_keywords' => 'compliance, IFRA standards, perfume safety, al rihan quality',
                'content' => '<p>Welcome to <strong>Al Rihan Perfumes</strong> compliance page. We are committed to upholding the highest regulatory standards, ethical sourcing, and product safety in all our artisanal fragrance formulations.</p><h3>Product Safety & Quality Assurance</h3><p>Every fragrance created by Al Rihan adheres strictly to International Fragrance Association (IFRA) standards and local cosmetic regulations. Our ingredients are rigorously certified and batch-tested.</p><h3>Ethical Sourcing</h3><p>We source pure oud, natural essences, and botanical extracts exclusively from sustainable and verified suppliers worldwide.</p>',
            ];
        }

        return view('frontend.pages.compliance', compact('compliance'));
    }

    /**
     * Shipping & Delivery Policy page.
     */
    public function shippingPolicy()
    {
        $shippingPolicy = null;
        try {
            $shippingPolicy = ShippingPolicy::where('is_active', 1)->orderBy('sort_order')->first();
        } catch (\Throwable $e) {
            Log::warning('ShippingPolicy query failed: ' . $e->getMessage());
        }

        if (!$shippingPolicy) {
            $shippingPolicy = (object) [
                'title' => 'Shipping & Delivery Policy',
                'meta_title' => 'Shipping & Delivery Policy | Al Rihan Perfumes',
                'meta_description' => 'Read Al Rihan Perfumes shipping and delivery policy, estimated delivery timelines, and order tracking information.',
                'meta_keywords' => 'shipping policy, delivery timelines, perfume delivery, al rihan order tracking',
                'content' => '<p>At <strong>Al Rihan Perfumes</strong>, we take great pride in ensuring that your luxury fragrances are delivered promptly, safely, and securely to your doorstep.</p><h3>Order Processing</h3><p>All orders are processed within 24 to 48 hours (excluding Sundays and national holidays). You will receive an SMS and email notification with your tracking details once your package is dispatched.</p><h3>Delivery Timelines</h3><p>Standard delivery across metro cities typically takes <strong>3 to 5 business days</strong>. For rest of India, delivery may take <strong>5 to 7 business days</strong>.</p><h3>Packaging</h3><p>Every bottle is sealed and securely wrapped with tamper-evident cushioned packaging to preserve fragrance integrity during transit.</p>',
            ];
        }

        return view('frontend.pages.shipping_policy', compact('shippingPolicy'));
    }

    /**
     * Contact Us page.
     */
    public function contactUs()
    {
        $contactSetting = ContactSetting::firstOrCreate([]);

        return view('frontend.pages.contact_us', compact('contactSetting'));
    }

    /**
     * FAQs and Help Center page.
     */
    public function helpCenter()
    {
        $helpCategories = HelpCategory::where('status', 1)
            ->with(['activeFaqs'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $helpCategories = collect();
        try {
            $helpCategories = HelpCategory::where('status', 1)
                ->with(['activeFaqs'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        } catch (\Throwable $e) {
            Log::warning('HelpCategory query failed: ' . $e->getMessage());
        }

        return view('frontend.pages.faqs', compact('helpCategories'));
        $totalFaqs = $helpCategories->sum(function ($cat) {
            return $cat->activeFaqs ? $cat->activeFaqs->count() : 0;
        });

        $fallbackFaqs = [
            'Orders & Delivery' => [
                'icon' => 'truck',
                'faqs' => [
                    [
                        'question' => 'How can I track my order status?',
                        'answer' => "Once your order is dispatched, you will receive an SMS and email notification with your tracking number and carrier link. You can also visit our Track Order page at any time to monitor your shipment in real time."
                    ],
                    [
                        'question' => 'How long does delivery take?',
                        'answer' => "Standard delivery across major metro cities takes 3 to 5 business days. For the rest of India, delivery typically takes 5 to 7 business days. All orders are processed within 24–48 hours."
                    ],
                    [
                        'question' => 'Do you offer free delivery?',
                        'answer' => "Yes! We offer complimentary free shipping on all prepaid orders and all orders over ₹999 across India."
                    ],
                    [
                        'question' => 'Can I change my delivery address after placing an order?',
                        'answer' => "If your order has not been dispatched yet, please contact our customer care immediately via WhatsApp or phone at 070213 42265, and our team will update your shipping details."
                    ]
                ]
            ],
            'Fragrances & Quality' => [
                'icon' => 'sparkles',
                'faqs' => [
                    [
                        'question' => 'How long do Al Rihan perfumes last?',
                        'answer' => "Our fragrances are formulated as high-concentration Eau de Parfum (EDP) and Extrait de Parfum, using premium essential oils and pure oud essences that provide 8 to 12+ hours of long-lasting sillage and projection."
                    ],
                    [
                        'question' => 'Are your perfumes 100% authentic and safe?',
                        'answer' => "Yes, absolutely. All Al Rihan fragrances are certified authentic, strictly dermatologically tested, compliant with International Fragrance Association (IFRA) safety standards, and 100% cruelty-free."
                    ],
                    [
                        'question' => 'How should I store my perfumes to preserve their longevity?',
                        'answer' => "Store your perfume bottles in a cool, dry place away from direct sunlight, extreme heat, and humidity (such as bathrooms). Keeping them inside their original box helps preserve fragrance notes for years."
                    ],
                    [
                        'question' => 'Do you provide tester samples with orders?',
                        'answer' => "Yes! Every prepaid order comes with complimentary tester vials so you can sample our signature notes before opening your full-sized bottle."
                    ]
                ]
            ],
            'Returns & Exchanges' => [
                'icon' => 'rotate-ccw',
                'faqs' => [
                    [
                        'question' => 'What is your return & replacement policy?',
                        'answer' => "We offer an easy 7-day return policy for unopened, unsealed products in their original packaging. Please check our Returns & Refunds policy page for complete guidelines."
                    ],
                    [
                        'question' => 'What should I do if my package arrives damaged or leaked?',
                        'answer' => "In the unlikely event of transit damage or leakage, please reach out to our team within 48 hours of delivery with photos of the damaged bottle and outer packaging. We will dispatch an immediate replacement or process a full refund."
                    ],
                    [
                        'question' => 'How long does a refund take to process?',
                        'answer' => "Once returned items are received and inspected at our warehouse, refunds are processed within 3 to 5 business days directly to your original payment method."
                    ]
                ]
            ],
            'Payments & Gifting' => [
                'icon' => 'credit-card',
                'faqs' => [
                    [
                        'question' => 'What payment methods do you support?',
                        'answer' => "We accept all major Credit and Debit Cards (Visa, Mastercard, RuPay), UPI (Google Pay, PhonePe, Paytm), Net Banking, and Cash on Delivery (COD) in eligible pincodes."
                    ],
                    [
                        'question' => 'Can I order a perfume as a gift for someone else?',
                        'answer' => "Yes! You can enter the recipient's shipping address during checkout. We also offer luxurious gift boxing and custom greeting messages in our Gifting section."
                    ]
                ]
            ]
        ];

        return view('frontend.pages.faqs', compact('helpCategories', 'totalFaqs', 'fallbackFaqs'));
    }

    /**
     * Store Contact Enquiry.
     */
    public function storeContactEnquiry(Request $request)
    {
        $fullName = $request->input('full_name') ?: $request->input('name') ?: $request->input('contact.name');
        $email = $request->input('email') ?: $request->input('contact.email');
        $contactNo = $request->input('contact_no') ?: $request->input('phone') ?: $request->input('contact.phone') ?: '';
        $subject = $request->input('subject') ?: $request->input('contact.subject');
        $comment = $request->input('comment') ?: $request->input('body') ?: $request->input('message') ?: $request->input('contact.body') ?: '';

        if (!empty($subject) && !empty($comment)) {
            $comment = "[Subject: {$subject}] " . $comment;
        } elseif (!empty($subject)) {
            $comment = "[Subject: {$subject}]";
        }

        $request->merge([
            'full_name'  => $fullName,
            'email'      => $email,
            'contact_no' => $contactNo,
            'comment'    => $comment,
        ]);

        $data = $request->validate([
            'full_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'contact_no' => 'required|string|max:20',
            'comment'    => 'nullable|string|max:2000',
        ]);

        $enquiry = ContactEnquiry::create([
            ...$data,
            'status' => false,
        ]);

        try {
            Mail::to($enquiry->email)->send(new ContactEnquiryUserMail($enquiry));
        } catch (\Throwable $e) {
            Log::error('Contact enquiry user email failed: ' . $e->getMessage(), [
                'enquiry_id' => $enquiry->id,
                'email' => $enquiry->email,
            ]);
        }

        $adminEmail = config('mail.admin_order_email') ?: config('mail.from.address');
        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new ContactEnquiryAdminMail($enquiry));
            } catch (\Throwable $e) {
                Log::error('Contact enquiry admin email failed: ' . $e->getMessage(), [
                    'enquiry_id' => $enquiry->id,
                    'email' => $adminEmail,
                ]);
            }
        }

        return back()->with('success', 'Thank you! Your message has been sent successfully.');
    }
}
