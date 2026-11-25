<style>
    .pageFooter .footLinks li,
    .pageFooter .footLinks li a {
        color: #777 !important;
        font-weight: 400;
    }

    .pageFooter .footLinks li a {
        text-decoration: none;
    }

    .pageFooter .footLinks li a:hover {
        color: #bf9a7c !important;
    }
</style>

@php
    $fs = $footerContactSetting ?? null;

    $footerPhone = $fs?->contact_phone ?: '+91 98922 51015';
    $footerEmail = $fs?->contact_email ?: 'watchinnovativeventures@gmail.com';
    $footerAddress =
        $fs?->contact_address ?: 'D-201, AcharatBaug, Phirozshah Mehta Road, Santacruz West, Mumbai - 400054.';
    $footerTelHref = preg_replace('/[^\d+]/', '', $footerPhone);

    $kidsImage = asset($fs?->foot_kids_image ?: 'frontend/assets/images/footKids.png');
    $kidsShadow = asset($fs?->foot_kids_shadow_image ?: 'frontend/assets/images/footKidshadow.png');

    $formTitle = $fs?->form_title ?: 'Reach out to us!';
    $formSubtitle = $fs?->form_subtitle ?: "Start your child's journey of fun-filled learning with us";

    $enrollSmall = $fs?->enroll_title_small ?: 'Enroll Your Child in';
    $enrollHighlight = $fs?->enroll_title_highlight ?: 'The Word Hunt';
    $enrollSuffix = $fs?->enroll_title_suffix ?: 'Challenge Today!';
    $enrollBtnText = $fs?->enroll_button_text ?: 'Enroll Now';
    $enrollBtnUrl = $fs?->enroll_button_url ?: '#';
    $enrollRibbon = asset($fs?->enroll_ribbon_image ?: 'assets/frontend/images/enrollRibbon.png');
    $enrollProduct = asset($fs?->enroll_product_image ?: 'assets/frontend/images/enrollImg.png');

    $connectTag = asset($fs?->connect_tag_image ?: 'assets/frontend/images/connectTag.svg');

    // SOCIAL MEDIA LINKS - DYNAMIC
    $facebookUrl = $fs?->facebook_url ?: '#';
    $twitterUrl = $fs?->twitter_url ?: '#';
    $instagramUrl = $fs?->instagram_url ?: '#';
    $youtubeUrl = $fs?->youtube_url ?: 'https://www.youtube.com/@WatchInnvovativeVentures';
    $linkedinUrl =
        $fs?->linkedin_url ?:
        ($fs?->twitter_url && str_contains($fs->twitter_url, 'linkedin')
            ? $fs->twitter_url
            : 'https://www.linkedin.com/');
    $amazonUrl = $fs?->amazon_url ?: '#';
    $amazonLogo = asset($fs?->amazon_logo_image ?: 'assets/frontend/images/amazonlogo.svg');

    // Dynamic WhatsApp Number & URL resolution
    $rawWhatsapp = $fs?->whatsapp_number;
    if ($rawWhatsapp && str_contains($rawWhatsapp, 'twitter.com')) {
        $rawWhatsapp = null;
    }
    $whatsappDisplayNumber = $rawWhatsapp ?: $footerPhone;

    $whatsappDigits = preg_replace('/[^\d]/', '', $whatsappDisplayNumber);
    if (strlen($whatsappDigits) === 10) {
        $whatsappDigits = '91' . $whatsappDigits;
    }
    $whatsappUrl = !empty($whatsappDigits) ? 'https://wa.me/' . $whatsappDigits : null;

    $copyrightText = $fs?->copyright_text ?: '© 2026 Watch. All Rights Reserved.';
@endphp

<footer class="alrihan-footer">

    <!-- Newsletter -->
    <div class="alrihan-newsletter">
        <div class="alrihan-newsletter-content">
            <h2 class="section-heading">Enter the World of Al Rihan</h2>
            <p>Receive fragrance inspiration, collection updates, and exclusive brand news.</p>
        </div>

        <div class="alrihan-newsletter-box">
            <form class="alrihan-newsletter-form" id="footerNewsletterForm" action="{{ route('newsletter.subscribe') }}"
                method="POST">
                @csrf
                <input type="email" name="email" id="newsletterEmailInput" placeholder="Enter your email address"
                    required autocomplete="email">
                <button type="submit" id="newsletterSignup">
                    <span class="nlBtnText">SUBSCRIBE</span>
                    <span class="spinner-border spinner-border-sm nlBtnSpinner d-none ms-1" role="status"
                        aria-hidden="true" style="width: 14px; height: 14px; border-width: 2px;"></span>
                </button>
            </form>
            <div id="newsletterAlertContainer" class="mt-2" style="font-size: 13px;"></div>
        </div>
    </div>

    <!-- Footer Main -->
    <div class="alrihan-footer-main">

        <!-- Brand -->
        <div class="alrihan-footer-brand">
            <a href="/" class="alrihan-footer-logo">
                <img src="{{ asset('frontend/assets/images/color-logo.svg') }}" alt="Al Rihan Perfumes">
            </a>

            <h3>Beyond Fragrance. Into Luxury.</h3>

            <p>
                Discover the art of fine perfumery with distinctive
                fragrances crafted to leave a lasting impression.
            </p>

            <div class="alrihan-social-links">
                <!-- Instagram -->
                <a href="https://www.instagram.com/alrihanperfumes/" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="5" />
                        <circle cx="12" cy="12" r="4" />
                        <circle cx="17.5" cy="6.5" r="0.8" fill="currentColor" stroke="none" />
                    </svg>
                </a>

                <a href="https://www.facebook.com/alraihanperfumes01/" aria-label="Facebook" target="_blank"
                    rel="noopener noreferrer">
                    <!-- Facebook SVG -->
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M13.5 21v-8h2.7l.4-3.1h-3.1V8c0-.9.3-1.5 1.6-1.5h1.7V3.7c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3v2H7.3V13h2.8v8h3.4z" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Explore -->
        <div class="alrihan-footer-column">
            <h4>Explore</h4>
            <ul>
                <li><a href="/">Home</a></li>
                <li><a href="{{ route('product.listing') }}">Our Collections</a></li>
                <li><a href="{{ route('best.sellers') }}">Bestsellers</a></li>
                <li><a href="{{ route('gifting') }}">Gift Sets</a></li>
                <li><a href="{{ route('contact.us') }}">Contact Us</a></li>
            </ul>
        </div>

        <!-- Customer Care -->
        <div class="alrihan-footer-column">
            <h4>Customer Care</h4>
            <ul>
                <li><a href="{{ route('faqs.center') }}">FAQs</a></li>
                <li><a href="{{ route('shipping.policy') }}">Shipping & Delivery</a></li>
                <li><a href="{{ route('return.policy') }}">Returns & Refunds</a></li>
            </ul>
        </div>

        <!-- Contact -->

        <div class="alrihan-footer-contact">
            <h4>Contact Information</h4>

            <div class="alrihan-contact-item">
                <i data-lucide="map-pin"></i>
                <p>
                    Shop no. 03, Zaitoon Apartment,
                    Goregaon Station Rd, opposite IB Patel School,
                    Goregaon West, Mumbai, Maharashtra 400104
                </p>
            </div>

            <div class="alrihan-contact-item">
                <i data-lucide="phone"></i>
                <a href="tel:07021342265">070213 42265</a>
            </div>

            <div class="alrihan-contact-item">
                <i data-lucide="mail"></i>
                <a href="mailto:info@alrehanperfumes.com">
                    info@alrehanperfumes.com
                </a>
            </div>
        </div>


        <!-- Copyright -->
        <div class="alrihan-footer-bottom">
            <p>© 2026 AL Rihan Perfume. All rights reserved. | Design By <a href="https://goyalinfotech.com/"
                    target="_blank">Goyal Infotech</a></p>

            <div>
                <a href="{{ route('terms.and.conditions') }}">Term of Use</a>
                <span>|</span>
                <a href="{{ route('privacy.policy') }}">Privacy Policy</a>
                <span>|</span>
                <a href="{{ route('compliance.page') }}">Compliance</a>
            </div>
        </div>

    </div>
</footer>

<script>
    // Set current year in copyright
    const currentYearEl = document.getElementById('currentYear');
    if (currentYearEl) {
        currentYearEl.textContent = new Date().getFullYear();
    }

    // Newsletter submit with loader
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('footerNewsletterForm');
        if (!form) return;

        const emailInput = document.getElementById('newsletterEmailInput');
        const submitBtn = document.getElementById('newsletterSignup');
        const spinner = submitBtn ? submitBtn.querySelector('.nlBtnSpinner') : null;
        const btnText = submitBtn ? submitBtn.querySelector('.nlBtnText') : null;
        const alertBox = document.getElementById('newsletterAlertContainer');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const emailVal = emailInput ? emailInput.value.trim() : '';
            if (!emailVal) {
                if (emailInput) emailInput.focus();
                return;
            }

            // Show loader state
            if (spinner) spinner.classList.remove('d-none');
            if (btnText) btnText.textContent = 'Sending...';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.85';
                submitBtn.style.pointerEvents = 'none';
            }
            if (emailInput) emailInput.readOnly = true;
            if (alertBox) alertBox.innerHTML = '';

            const tokenInput = form.querySelector('input[name="_token"]');
            const token = tokenInput ? tokenInput.value : '';

            fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        email: emailVal
                    })
                })
                .then(async function(res) {
                    const data = await res.json().catch(function() {
                        return {};
                    });
                    if (!res.ok) {
                        var errorMsg = data.message || (data.errors && data.errors.email ? data
                            .errors.email[0] : 'Unable to subscribe. Please try again.');
                        throw new Error(errorMsg);
                    }
                    return data;
                })
                .then(function(data) {
                    if (data.status === 'info') {
                        if (alertBox) {
                            alertBox.innerHTML =
                                '<div class="alert alert-info py-2 px-3 mb-2" style="font-size:13px;border-radius:6px;">' +
                                data.message + '</div>';
                        }
                        if (typeof window.showToast === 'function') {
                            window.showToast(data.message, 'info');
                        }
                    } else {
                        var msg = data.message ||
                            'Thank you for subscribing! Check your email for a confirmation.';
                        if (alertBox) {
                            alertBox.innerHTML =
                                '<div class="alert alert-success py-2 px-3 mb-2" style="font-size:13px;border-radius:6px;">' +
                                msg + '</div>';
                        }
                        if (typeof window.showToast === 'function') {
                            window.showToast(msg, 'success');
                        }
                        form.reset();
                    }
                })
                .catch(function(err) {
                    var errText = err.message || 'Something went wrong. Please try again.';
                    if (alertBox) {
                        alertBox.innerHTML =
                            '<div class="alert alert-danger py-2 px-3 mb-2" style="font-size:13px;border-radius:6px;">' +
                            errText + '</div>';
                    }
                    if (typeof window.showToast === 'function') {
                        window.showToast(errText, 'error');
                    }
                })
                .finally(function() {
                    if (spinner) spinner.classList.add('d-none');
                    if (btnText) btnText.textContent = 'SUBSCRIBE';
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '';
                        submitBtn.style.pointerEvents = '';
                    }
                    if (emailInput) emailInput.readOnly = false;
                });
        });
    });
</script>
