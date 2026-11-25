@extends('layouts.front')

@section('title', 'Contact Us')

@section('meta_description', $contactSetting->meta_description ?? 'Get in touch with us for any queries or support.')

@section('meta_keywords', $contactSetting->meta_keywords ?? 'contact, perfume, support, enquiries')

@section('content')
    <section class="contact-page">

        <!-- HERO -->
        {{-- <div class="contact-hero">
        <h1>Get In <span>Touch</span></h1>

        <div class="contact-breadcrumb">
            <a href="/">Home</a>
            <i data-lucide="chevron-right"></i>
            <span>Contact Us</span>
        </div>
    </div> --}}


        <!-- CONTACT MAIN -->
        <div class="contact-container">

            <!-- LEFT INFORMATION -->
            <div class="contact-information">

                <span class="contact-label">CONTACT INFORMATION</span>
                <h2 class="section-heading">Let's Start a Conversation</h2>

                <p class="contact-info-description">
                    Whether you need assistance with an order, product information
                    or anything else, our team is always happy to assist you.
                </p>


                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i data-lucide="mail"></i>
                    </div>
                    <div>
                        <span>Email Us</span>
                        <a href="mailto:info@alrehanperfumes.com">
                            info@alrehanperfumes.com
                        </a>
                    </div>
                </div>


                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i data-lucide="phone"></i>
                    </div>
                    <div>
                        <span>Call Us</span>
                        <a href="tel:+917021342265">
                            +91 70213 42265
                        </a>
                    </div>
                </div>


                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i data-lucide="map-pin"></i>
                    </div>
                    <div>
                        <span>Visit Our Store</span>
                        <a href="https://maps.app.goo.gl/zu44R7pjbNoVyCbc9" target="_blank" rel="noopener">
                            Shop No. 03, Zaitoon Apartment,<br>
                            Goregaon Station Road, Opposite IB Patel School,<br>
                            Goregaon West, Mumbai,<br>
                            Maharashtra – 400104
                        </a>
                    </div>
                </div>




                <!-- SOCIAL -->
                <div class="contact-social">
                    <span>FOLLOW US</span>

                    <div class="contact-social-links">
                        <!-- Instagram -->
                        <a href="https://www.instagram.com/alrihanperfumes/" target="_blank" aria-label="Instagram">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                                stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="5" />
                                <circle cx="12" cy="12" r="4" />
                                <circle cx="17.5" cy="6.5" r="0.8" fill="currentColor" stroke="none" />
                            </svg>
                        </a>

                        <!-- Facebook -->
                        <a href="#" aria-label="Facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M13.5 21v-8h2.7l.4-3.1h-3.1V8c0-.9.3-1.5 1.6-1.5h1.7V3.7c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3v2H7.3V13h2.8v8h3.4z" />
                            </svg>
                        </a>
                        </a>
                    </div>
                </div>

            </div>


            <!-- RIGHT FORM -->
            <div class="contact-form-wrapper">

                <div class="contact-form-heading">
                    <span class="contact-label">SEND US A MESSAGE</span>
                    <h2 class="section-heading">How Can We Help You?</h2>
                    <p>Fill out the form below and we'll get back to you shortly.</p>
                </div>


                <form class="contact-form" id="contactForm" method="POST" action="{{ route('contact.enquiry.store') }}">
                    @csrf

                    @if (session('success'))
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
                            <i data-lucide="check-circle" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
                            <i data-lucide="alert-circle" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                    @endif

                    @if (isset($errors) && $errors->any())
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="contact-form-row">

                        <div class="contact-field">
                            <label for="contactName">Full Name *</label>
                            <input type="text" id="contactName" name="full_name" value="{{ old('full_name') }}"
                                placeholder="Enter your full name" required>
                        </div>

                        <div class="contact-field">
                            <label for="contactEmail">Email Address *</label>
                            <input type="email" id="contactEmail" name="email" value="{{ old('email') }}"
                                placeholder="Enter your email" required>
                        </div>

                    </div>


                    <div class="contact-form-row">

                        <div class="contact-field">
                            <label for="contactPhone">Phone Number *</label>
                            <input type="tel" id="contactPhone" name="contact_no" value="{{ old('contact_no') }}"
                                placeholder="Enter your phone number" required>
                        </div>

                        <div class="contact-field">
                            <label for="contactSubject">Subject</label>

                            <select id="contactSubject" name="subject">
                                <option value="">Select Subject</option>
                                <option value="Order Enquiry" {{ old('subject') == 'Order Enquiry' ? 'selected' : '' }}>
                                    Order Enquiry</option>
                                <option value="Product Information"
                                    {{ old('subject') == 'Product Information' ? 'selected' : '' }}>Product Information
                                </option>
                                <option value="Shipping & Delivery"
                                    {{ old('subject') == 'Shipping & Delivery' ? 'selected' : '' }}>Shipping & Delivery
                                </option>
                                <option value="Returns & Refunds"
                                    {{ old('subject') == 'Returns & Refunds' ? 'selected' : '' }}>Returns & Refunds
                                </option>
                                <option value="Wholesale Enquiry"
                                    {{ old('subject') == 'Wholesale Enquiry' ? 'selected' : '' }}>Wholesale Enquiry
                                </option>
                                <option value="Other" {{ old('subject') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                    </div>
                    <div class="contact-field">
                        <label for="contactMessage">Your Message *</label>
                        <textarea id="contactMessage" name="comment" rows="5" placeholder="Tell us how we can help you..." required>{{ old('comment') }}</textarea>
                    </div>


                    <label class="contact-privacy">
                        <input type="checkbox" required>
                        <span>
                            I agree to the processing of my information
                            according to the Privacy Policy.
                        </span>
                    </label>


                    <button type="submit" class="contact-submit-btn">
                        Send Message
                        <i data-lucide="arrow-right"></i>
                    </button>

                    <p class="contact-form-note">
                        We typically respond within 24–48 business hours.
                    </p>

                </form>

            </div>

        </div>


        <!-- MAP -->
        <div class="contact-map-section">
            <!-- Section Heading -->
            <div class="category-heading reveal">
                <span class="section-subtitle">FIND OUR LOCATION</span>
                <h2 class="section-heading">Visit Our Store.</h2>
            </div>

            <div class="contact-map">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d30150.067211117028!2d72.84479!3d19.162050000000004!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be7b752a4162c6b%3A0x7bc0bcbf8ee491e2!2sAl%20Raihan%20Perfumes%20%7C%20Best%20perfume%20shop%20in%20Mumbai%20%7C%20Ittar%20%7C%20Perfume%20%7C%20Fragrance%20shop%20in%20Goregaon%20west!5e0!3m2!1sen!2sus!4v1794491350443!5m2!1sen!2sus"
                    width="100%" height="100%" style="border:0;" loading="lazy" allowfullscreen
                    referrerpolicy="no-referrer-when-downgrade" title="Al Rehan Perfumes Location">
                </iframe>
            </div>

        </div>


        <!-- FAQ -->
        <div class="contact-faq">
            <div class="category-heading reveal">
                <span class="section-subtitle">NEED HELP?</span>
                <h2 class="section-heading">Frequently Asked Questions</h2>
            </div>

            <div class="contact-faq-list">

                <details class="contact-faq-item">
                    <summary>
                        <span>How can I track my order?</span>
                        <span class="faq-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                    </summary>
                    <div class="faq-answer">
                        <p>Once your order is shipped, you will receive tracking
                            details through your registered contact information.</p>
                    </div>
                </details>

                <details class="contact-faq-item">
                    <summary>
                        <span>How long does delivery take?</span>
                        <span class="faq-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                    </summary>
                    <div class="faq-answer">
                        <p>Delivery timelines depend on your location and shipping
                            destination. Please contact our team for assistance.</p>
                    </div>
                </details>

                <details class="contact-faq-item">
                    <summary>
                        <span>Can I get help choosing a fragrance?</span>
                        <span class="faq-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                    </summary>
                    <div class="faq-answer">
                        <p>Our team will be happy to help you find a fragrance
                            that suits your preferences.</p>
                    </div>
                </details>

                <details class="contact-faq-item">
                    <summary>
                        <span>How can I enquire about bulk orders?</span>
                        <span class="faq-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                    </summary>
                    <div class="faq-answer">
                        <p>Select Wholesale Enquiry in the contact form and
                            share your requirements with us.</p>
                    </div>
                </details>

            </div>

        </div>

    </section>
@endsection

@push('styles')
    <style>
        /* Additional styles */
        .alert {
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .form-control:focus {
            border-color: #fa5862;
            box-shadow: 0 0 0 0.2rem rgba(250, 88, 98, 0.15);
        }

        @media (max-width: 767px) {
            .loginWrap {
                border-radius: 12px !important;
            }

            .loginImg {
                min-height: 250px;
            }

            .watchFrame {
                height: 200px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('contactForm');
            const submitBtn = document.getElementById('contactSubmitBtn');
            const btnText = document.getElementById('submitBtnText');
            const spinner = document.getElementById('submitBtnSpinner');
            const arrow = document.getElementById('submitBtnArrow');

            if (form && submitBtn) {
                form.addEventListener('submit', function(e) {
                    if (!form.checkValidity()) {
                        return; // Let standard validation highlight missing/invalid fields
                    }

                    // Show loader state
                    if (btnText) btnText.textContent = 'Sending...';
                    if (spinner) spinner.classList.remove('d-none');
                    if (arrow) arrow.style.display = 'none';

                    submitBtn.style.pointerEvents = 'none';
                    submitBtn.style.opacity = '0.85';

                    // Prevent double submission
                    setTimeout(function() {
                        submitBtn.disabled = true;
                    }, 50);
                });
            }
        });
    </script>
@endpush
