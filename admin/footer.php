<!-- ══════════════════════════════════════════════════════════
     footer.php — CineHall Global Site Footer
     Improved: brand block with logo, newsletter strip, Font
     Awesome social icons (no broken image deps), animated
     hover effects, gold accent dividers, fully accessible.
══════════════════════════════════════════════════════════ -->

<!-- Font Awesome (include only if not already in header.php) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../css/footer.css">

<footer class="site-footer">

    <!-- ── Decorative top gold rule ── -->
    <div class="footer__rule" aria-hidden="true"></div>

    <!-- ════════════════════════════════════════════════════
         MAIN FOOTER GRID
    ════════════════════════════════════════════════════ -->
    <div class="footer__main">
        <div class="footer__inner">

            <!-- ── COL 1 : Brand / About ──────────────────── -->
            <div class="footer__col footer__col--brand">

                <!-- Logo (same as header — clickable, redirects to index.php) -->
                <a href="index.php" class="footer__logo" aria-label="CineHall — Go to homepage">
                    <img
                        src="../images/logo.png"
                        alt="CineHall logo"
                        class="footer__logo-img"
                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
                    >
                    <!-- Fallback SVG if logo.png is missing -->
                    <span class="footer__logo-fallback" style="display:none" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 18 18" fill="none">
                            <rect x="2" y="4" width="14" height="10" rx="1.5" stroke="#D4AF37" stroke-width="1.5"/>
                            <path d="M2 7h14M2 11h14" stroke="#D4AF37" stroke-width="1"/>
                            <circle cx="5"  cy="9" r="1" fill="#D4AF37"/>
                            <circle cx="13" cy="9" r="1" fill="#D4AF37"/>
                        </svg>
                    </span>
              
                </a>

                <p class="footer__brand-desc">
                    Your premium cinema experience. Book tickets for Hollywood,
                    Bollywood, Kollywood &amp; Tollywood — all in one place.
                </p>

                <!-- Social icons -->
                <div class="footer__social" aria-label="Social media links">
                    <a href="https://www.facebook.com/online_ticket_booking"
                       target="_blank" rel="noopener noreferrer"
                       class="social-btn social-btn--facebook"
                       aria-label="Follow us on Facebook"
                       title="Facebook">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>
                    <a href="https://www.instagram.com/online_ticket_booking"
                       target="_blank" rel="noopener noreferrer"
                       class="social-btn social-btn--instagram"
                       aria-label="Follow us on Instagram"
                       title="Instagram">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                    <a href="https://x.com/online_ticket_booking"
                       target="_blank" rel="noopener noreferrer"
                       class="social-btn social-btn--x"
                       aria-label="Follow us on X (Twitter)"
                       title="X / Twitter">
                        <i class="fa-brands fa-x-twitter"></i>
                    </a>
                    <a href="https://www.youtube.com/online_ticket_booking"
                       target="_blank" rel="noopener noreferrer"
                       class="social-btn social-btn--youtube"
                       aria-label="Subscribe on YouTube"
                       title="YouTube">
                        <i class="fa-brands fa-youtube"></i>
                    </a>
                    <a href="https://www.tiktok.com/online_ticket_booking"
                       target="_blank" rel="noopener noreferrer"
                       class="social-btn social-btn--tiktok"
                       aria-label="Follow us on TikTok"
                       title="TikTok">
                        <i class="fa-brands fa-tiktok"></i>
                    </a>
                </div>

            </div><!-- /.footer__col--brand -->


            <!-- ── COL 2 : Quick Links ─────────────────────── -->
            <div class="footer__col">
                <h3 class="footer__heading">
                    <i class="fa-solid fa-bolt footer__heading-icon"></i>
                    Quick Links
                </h3>
                <ul class="footer__links">
                    <li>
                        <a href="#" class="footer__link">
                            <i class="fa-solid fa-chevron-right"></i>
                            Now Showing
                        </a>
                    </li>
                    <li>
                        <a href="#" class="footer__link">
                            <i class="fa-solid fa-chevron-right"></i>
                            All Movies
                        </a>
                    </li>
                    <li>
                        <a href="#" class="footer__link">
                            <i class="fa-solid fa-chevron-right"></i>
                            Theaters
                        </a>
                    </li>
                    <li>
                        <a href="#" class="footer__link">
                            <i class="fa-solid fa-chevron-right"></i>
                            Coming Soon
                        </a>
                    </li>
                    <li>
                        <a href="#" class="footer__link">
                            <i class="fa-solid fa-chevron-right"></i>
                            About Us
                        </a>
                    </li>
                </ul>
            </div><!-- /.footer__col -->


            <!-- ── COL 3 : Business ───────────────────────── -->
            <div class="footer__col">
                <h3 class="footer__heading">
                    <i class="fa-solid fa-briefcase footer__heading-icon"></i>
                    Business
                </h3>
                <ul class="footer__links">
                    <li>
                        <a href="#" class="footer__link">
                            <i class="fa-solid fa-chevron-right"></i>
                            Advertise with Us
                        </a>
                    </li>
                    <li>
                        <a href="#" class="footer__link">
                            <i class="fa-solid fa-chevron-right"></i>
                            Become a Franchise
                        </a>
                    </li>
                    <li>
                        <a href="#" class="footer__link">
                            <i class="fa-solid fa-chevron-right"></i>
                            Privacy Policy
                        </a>
                    </li>
                    <li>
                        <a href="#" class="footer__link">
                            <i class="fa-solid fa-chevron-right"></i>
                            Terms of Service
                        </a>
                    </li>
                </ul>
            </div><!-- /.footer__col -->


            <!-- ── COL 4 : Contact / Stay in Touch ──────────── -->
            <div class="footer__col">
                <h3 class="footer__heading">
                    <i class="fa-solid fa-envelope footer__heading-icon"></i>
                    Stay in Touch
                </h3>

                <ul class="footer__contact-list">
                    <li class="footer__contact-item">
                        <span class="footer__contact-icon">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <a
                            href="https://mail.google.com/mail/?view=cm&fs=1&to=marketing@teamquest.com.np"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="footer__contact-link"
                        >
                            marketing@teamquest.com.np
                        </a>
                    </li>
                    <li class="footer__contact-item">
                        <span class="footer__contact-icon">
                            <i class="fa-solid fa-location-dot"></i>
                        </span>
                        <span class="footer__contact-text">Kathmandu, Nepal</span>
                    </li>
                    <li class="footer__contact-item">
                        <span class="footer__contact-icon">
                            <i class="fa-solid fa-clock"></i>
                        </span>
                        <span class="footer__contact-text">Open Daily · 9 AM – 11 PM</span>
                    </li>
                </ul>

                <!-- Mini newsletter form -->
                <form class="footer__newsletter" action="#" method="post" aria-label="Newsletter signup">
                    <div class="footer__newsletter-field">
                        <input
                            type="email"
                            name="newsletter_email"
                            placeholder="Your email address"
                            class="footer__newsletter-input"
                            required
                            autocomplete="email"
                        >
                        <button type="submit" class="footer__newsletter-btn" aria-label="Subscribe">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                    <p class="footer__newsletter-note">
                        Get notified about new releases &amp; offers.
                    </p>
                </form>

            </div><!-- /.footer__col -->

        </div><!-- /.footer__inner -->
    </div><!-- /.footer__main -->


    <!-- ════════════════════════════════════════════════════
         FOOTER BOTTOM BAR
    ════════════════════════════════════════════════════ -->
    <div class="footer__bottom">
        <div class="footer__bottom-inner">

            <p class="footer__copyright">
                <i class="fa-regular fa-copyright"></i>
                2025 <strong>CineXpress</strong>. All rights reserved.
            </p>

            <p class="footer__credit">
                Designed &amp; Developed by
                <span class="footer__credit-name">Ashwin Maharjan</span>
            </p>

            <div class="footer__back-top">
                <a href="#" class="footer__back-top-btn" aria-label="Back to top">
                    <i class="fa-solid fa-arrow-up"></i>
                </a>
            </div>

        </div><!-- /.footer__bottom-inner -->
    </div><!-- /.footer__bottom -->

</footer><!-- /.site-footer -->
