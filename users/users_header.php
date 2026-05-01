

<?php

include("connect.php");
/**
 * header.php — CineXpress Global Site Header
 * Improved: real logo image (images/logo.png), scroll-shrink effect,
 *           active-indicator underline, avatar dropdown, session-safe,
 *           full mobile drawer with backdrop, ARIA-accessible.
 */
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineXpress</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Header Styles -->
    <link rel="stylesheet" href="../css/header.css">
</head>
<body>

<!-- ══════════════════════════════════════════════════════════
     SITE HEADER
══════════════════════════════════════════════════════════ -->
<header class="site-header" id="siteHeader">
    <div class="header-inner">

        <!-- ── LOGO (image + text, links to index.php) ────────── -->
        <a href="../index.php" class="logo" aria-label="CineXpress — Go to homepage">
            <img
                src="../images/logo.png"
                alt="CineXpress logo"
                class="logo__img"
                onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
            >
            <!-- Fallback SVG icon shown only if logo.png fails to load -->
            <span class="logo__icon-fallback" style="display:none" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 18 18" fill="none">
                    <rect x="2" y="4" width="14" height="10" rx="1.5" stroke="#D4AF37" stroke-width="1.5"/>
                    <path d="M2 7h14M2 11h14" stroke="#D4AF37" stroke-width="1"/>
                    <circle cx="5"  cy="9" r="1" fill="#D4AF37"/>
                    <circle cx="13" cy="9" r="1" fill="#D4AF37"/>
                </svg>
            </span>
            
        </a>

        <!-- ── DESKTOP NAVIGATION ────────────────────────────── -->
        <nav class="main-nav" aria-label="Main navigation">
            <a href="dashboard.php"
               class="nav-link <?= $current_page === 'index.php'       ? 'active' : '' ?>">
                <i class="fa-solid fa-film nav-link__icon"></i>Dashboard
            </a>
            <a href="../movies.php"
               class="nav-link <?= $current_page === 'movies.php'  ? 'active' : '' ?>">
                <i class="fa-solid fa-clapperboard nav-link__icon"></i>Movies
            </a>
            <a href="../coming_soon.php"
               class="nav-link <?= $current_page === 'coming_soon.php' ? 'active' : '' ?>">
                <i class="fa-regular fa-clock nav-link__icon"></i>Coming Soon
            </a>
            <a href="my_bookings.php"
   class="nav-link <?= $current_page === 'my_bookings.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-ticket nav-link__icon"></i>My Bookings
</a>
        </nav>

        <!-- ── HEADER ACTIONS (auth) ─────────────────────────── -->
<div class="header-actions">

    <!-- Always show user avatar — this header is users-only -->
    <div class="avatar-wrap" id="avatarWrap">
        <button
            class="avatar"
            id="avatarBtn"
            aria-haspopup="true"
            aria-expanded="false"
            aria-label="Account menu"
            title="My Account"
        >
            <?php
            $avatar_file = $_SESSION['avatar'] ?? null;
            $avatar_path = $avatar_file ? '../uploads/avatars/' . htmlspecialchars($avatar_file, ENT_QUOTES, 'UTF-8') : null;
            if ($avatar_path && file_exists('../' . $avatar_path)): ?>
                <img src="<?= $avatar_path ?>" alt="Profile photo" class="avatar__img">
            <?php else: ?>
                <?= strtoupper(substr(htmlspecialchars($_SESSION['username'] ?? 'U', ENT_QUOTES, 'UTF-8'), 0, 2)) ?>
            <?php endif; ?>
        </button>

        <!-- Dropdown -->
        <div class="avatar-dropdown" id="avatarDropdown" role="menu">
            <div class="avatar-dropdown__header">
    <span class="avatar-dropdown__name">
        <?= htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>
    </span>
    <span class="avatar-dropdown__role">
        <?= htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </span>
</div>
            <div class="avatar-dropdown__divider"></div>
            <a href="my_bookings.php" class="avatar-dropdown__item" role="menuitem">
                <i class="fa-solid fa-ticket"></i> My Bookings
            </a>
            <div class="avatar-dropdown__divider"></div>
            <a href="../logout.php" class="avatar-dropdown__item avatar-dropdown__item--danger" role="menuitem">
                <i class="fa-solid fa-right-from-bracket"></i> Sign Out
            </a>
        </div>
    </div>

</div>
        <!-- ── HAMBURGER (mobile) ────────────────────────────── -->
        <button
            class="menu-toggle"
            id="menuToggle"
            aria-label="Open navigation menu"
            aria-expanded="false"
            aria-controls="mobileNav"
        >
            <span class="menu-toggle__bar"></span>
            <span class="menu-toggle__bar"></span>
            <span class="menu-toggle__bar"></span>
        </button>

    </div><!-- /.header-inner -->

    <!-- ── MOBILE DRAWER ─────────────────────────────────────── -->
    <div class="mobile-nav" id="mobileNav" aria-hidden="true">

        <!-- Drawer nav links -->
        <nav class="mobile-nav__links" aria-label="Mobile navigation">
            <a href="dashboard.php"
               class="nav-link <?= $current_page === 'index.php'       ? 'active' : '' ?>">
                <i class="fa-solid fa-film"></i> Dashboard
            </a>
            <a href="../movies.php"
               class="nav-link <?= $current_page === 'movies.php'  ? 'active' : '' ?>">
                <i class="fa-solid fa-clapperboard"></i> Movies
            </a>
            <a href="../coming_soon.php"
               class="nav-link <?= $current_page === 'coming_soon.php' ? 'active' : '' ?>">
                <i class="fa-regular fa-clock"></i> Coming Soon
            </a>
            <a href="my_bookings.php"
   class="nav-link <?= $current_page === 'my_bookings.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-ticket"></i> My Bookings
</a>
        </nav>

        <!-- Drawer auth -->
        <div class="mobile-auth">
    <div class="mobile-auth__user">
        <div class="mobile-auth__avatar">
            <?php
            $avatar_file = $_SESSION['avatar'] ?? null;
            $avatar_path = $avatar_file ? '../uploads/avatars/' . htmlspecialchars($avatar_file, ENT_QUOTES, 'UTF-8') : null;
            if ($avatar_path && file_exists('../' . $avatar_path)): ?>
                <img src="<?= $avatar_path ?>" alt="Profile photo" class="avatar__img">
            <?php else: ?>
                <?= strtoupper(substr(htmlspecialchars($_SESSION['username'] ?? 'U', ENT_QUOTES, 'UTF-8'), 0, 2)) ?>
            <?php endif; ?>
        </div>
        <div>
    <p class="mobile-auth__name">
        <?= htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>
    </p>
    <p class="mobile-auth__role">
        <?= htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </p>
</div>
    </div>
    <div class="mobile-auth__btns">
        <a href="my_bookings.php" class="btn-ghost">
            <i class="fa-solid fa-ticket"></i> My Bookings
        </a>
        <a href="logout.php" class="btn-ghost btn-ghost--danger">
            <i class="fa-solid fa-right-from-bracket"></i> Sign Out
        </a>
    </div>
</div>


</header><!-- /.site-header -->

<!-- Backdrop for mobile drawer -->
<div class="nav-backdrop" id="navBackdrop" aria-hidden="true"></div>

<!-- ══════════════════════════════════════════════════════════
     HEADER JAVASCRIPT
══════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    /* ── Elements ── */
    const header    = document.getElementById('siteHeader');
    const toggle    = document.getElementById('menuToggle');
    const mobileNav = document.getElementById('mobileNav');
    const backdrop  = document.getElementById('navBackdrop');
    const avatarBtn = document.getElementById('avatarBtn');
    const avatarDdl = document.getElementById('avatarDropdown');

    /* ══════════════════════════════════════════════════════
       1. Scroll-shrink: add .scrolled class after 20px
    ══════════════════════════════════════════════════════ */
    function onScroll() {
        header.classList.toggle('scrolled', window.scrollY > 20);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll(); // run once on load

    /* ══════════════════════════════════════════════════════
       2. Mobile drawer toggle
    ══════════════════════════════════════════════════════ */
    function openDrawer() {
        mobileNav.classList.add('open');
        backdrop.classList.add('visible');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Close navigation menu');
        mobileNav.setAttribute('aria-hidden', 'false');
        toggle.classList.add('is-open');
        document.body.style.overflow = 'hidden'; // prevent scroll behind
    }

    function closeDrawer() {
        mobileNav.classList.remove('open');
        backdrop.classList.remove('visible');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open navigation menu');
        mobileNav.setAttribute('aria-hidden', 'true');
        toggle.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    toggle.addEventListener('click', function () {
        mobileNav.classList.contains('open') ? closeDrawer() : openDrawer();
    });

    backdrop.addEventListener('click', closeDrawer);

    /* Close on Escape key */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeDrawer();
            closeAvatar();
        }
    });

    /* ══════════════════════════════════════════════════════
       3. Avatar dropdown
    ══════════════════════════════════════════════════════ */
    function openAvatar() {
        if (!avatarBtn || !avatarDdl) return;
        avatarDdl.classList.add('open');
        avatarBtn.setAttribute('aria-expanded', 'true');
    }

    function closeAvatar() {
        if (!avatarBtn || !avatarDdl) return;
        avatarDdl.classList.remove('open');
        avatarBtn.setAttribute('aria-expanded', 'false');
    }

    if (avatarBtn) {
        avatarBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            avatarDdl.classList.contains('open') ? closeAvatar() : openAvatar();
        });

        /* Close when clicking outside */
        document.addEventListener('click', function (e) {
            if (!document.getElementById('avatarWrap').contains(e.target)) {
                closeAvatar();
            }
        });
    }

})();
</script>

</body>
</html>
