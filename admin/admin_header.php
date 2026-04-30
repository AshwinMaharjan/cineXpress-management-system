<?php
// ── Active link helper ──────────────────────────────────────────────────────
$current_page = basename($_SERVER['PHP_SELF']);

function is_active(string $page): string {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}

// ── Admin session info (adjust to your session keys) ───────────────────────
$admin_name   = $_SESSION['admin_name']   ?? 'Super Admin';
$admin_email  = $_SESSION['admin_email']  ?? 'super_admin@gmail.com';
$admin_pic    = $_SESSION['admin_pic']    ?? '';   // e.g. "avatar123.png"
$admin_avatar = strtoupper(substr($admin_name, 0, 1)); // initials fallback

// Build the avatar image path; fall back to initials if no pic is set
$admin_pic_path = !empty($admin_pic)
    ? '../uploads/avatars/' . htmlspecialchars($admin_pic)
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CineBook &mdash; Admin Panel</title>
    <link rel="stylesheet" href="../css/admin_header.css" />
    <style>
        /* Avatar image inside the header button */
        .admin-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
        }

        /* Avatar image inside the dropdown header */
        .dropdown-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
        }
    </style>
</head>
<body>
<header class="admin-header" id="adminHeader">
    <div class="admin-header__inner">

        <!-- ── Logo ──────────────────────────────────────────── -->
        <a href="../index.php" class="admin-logo">
            <img src="../images/logo.png" alt="Logo" class="admin-logo__icon">
            <span class="admin-logo__text">
                <small class="admin-logo__badge">Admin</small>
            </span>
        </a>

        <!-- ── Primary Nav ───────────────────────────────────── -->
        <nav class="admin-nav" id="adminNav" aria-label="Admin Navigation">
            <ul class="admin-nav__list">

                <li class="admin-nav__item">
                    <a href="dashboard.php"
                       class="admin-nav__link <?= is_active('dashboard.php') ?>">
                        <span class="nav-icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                                <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                            </svg>
                        </span>
                        Dashboard
                    </a>
                </li>

                <li class="admin-nav__item">
                    <a href="categories.php"
                       class="admin-nav__link <?= is_active('categories.php') ?>">
                        <span class="nav-icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                                <line x1="7" y1="7" x2="7.01" y2="7"/>
                            </svg>
                        </span>
                        Categories
                    </a>
                </li>

                <li class="admin-nav__item">
                    <a href="movies.php"
                       class="admin-nav__link <?= is_active('movies.php') ?>">
                        <span class="nav-icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/>
                                <line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/>
                                <line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/>
                                <line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/>
                                <line x1="17" y1="7" x2="22" y2="7"/>
                            </svg>
                        </span>
                        Movies
                    </a>
                </li>

                <li class="admin-nav__item">
                    <a href="revenue.php"
                       class="admin-nav__link <?= is_active('revenue.php') ?>">
                        <span class="nav-icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="1" x2="12" y2="23"/>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                        </span>
                        Revenue
                    </a>
                </li>

                <li class="admin-nav__item">
                    <a href="viewallusers.php"
                       class="admin-nav__link <?= is_active('viewallusers.php') ?>">
                        <span class="nav-icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </span>
                        Users
                    </a>
                </li>

                <li class="admin-nav__item">
                    <a href="viewallbooking.php"
                       class="admin-nav__link <?= is_active('viewallbooking.php') ?>">
                        <span class="nav-icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/>
                                <line x1="9" y1="2" x2="9" y2="22" stroke-dasharray="3 3"/>
                            </svg>
                        </span>
                        Bookings
                    </a>
                </li>

            </ul>
        </nav>

        <!-- ── Right Controls ────────────────────────────────── -->
        <div class="admin-header__right">

            <!-- Admin Avatar Dropdown -->
            <div class="admin-dropdown" id="adminDropdown">
                <button class="admin-avatar-btn" id="avatarBtn"
                        aria-haspopup="true" aria-expanded="false"
                        aria-label="Admin menu">
                    <div class="admin-avatar">
                        <?php if ($admin_pic_path): ?>
                            <img src="<?= $admin_pic_path ?>"
                                 alt="<?= htmlspecialchars($admin_name) ?>"
                                 class="admin-avatar-img"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <span style="display:none;"><?= htmlspecialchars($admin_avatar) ?></span>
                        <?php else: ?>
                            <?= htmlspecialchars($admin_avatar) ?>
                        <?php endif; ?>
                    </div>
                    <div class="admin-avatar-info">
                        <span class="avatar-name"><?= htmlspecialchars($admin_name) ?></span>
                        <span class="avatar-role">Super Admin</span>
                    </div>
                    <svg class="dropdown-caret" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div class="admin-dropdown__menu" id="dropdownMenu" role="menu">
                    <div class="dropdown-header">
                        <div class="dropdown-avatar">
                            <?php if ($admin_pic_path): ?>
                                <img src="<?= $admin_pic_path ?>"
                                     alt="<?= htmlspecialchars($admin_name) ?>"
                                     class="dropdown-avatar-img"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <span style="display:none;"><?= htmlspecialchars($admin_avatar) ?></span>
                            <?php else: ?>
                                <?= htmlspecialchars($admin_avatar) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="dropdown-name"><?= htmlspecialchars($admin_name) ?></p>
                            <p class="dropdown-email"><?= htmlspecialchars($admin_email) ?></p>
                        </div>
                    </div>

                    <div class="dropdown-divider"></div>

                    <div class="dropdown-divider"></div>

                    <a href="logout.php" class="dropdown-item dropdown-item--danger" role="menuitem">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Logout
                    </a>
                </div>
            </div><!-- /admin-dropdown -->

            <!-- Hamburger (Mobile) -->
            <button class="hamburger" id="hamburger"
                    aria-label="Toggle navigation" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>

        </div><!-- /admin-header__right -->

    </div><!-- /admin-header__inner -->

    <!-- Mobile Nav Drawer -->
    <div class="mobile-nav" id="mobileNav" aria-hidden="true">
        <ul class="mobile-nav__list">
            <li><a href="dashboard.php"      class="mobile-nav__link <?= is_active('dashboard.php') ?>">📊 Dashboard</a></li>
            <li><a href="categories.php"     class="mobile-nav__link <?= is_active('categories.php') ?>">🏷️ Categories</a></li>
            <li><a href="movies.php"          class="mobile-nav__link <?= is_active('movies.php') ?>">🎬 Movies</a></li>
            <li><a href="revenue.php"         class="mobile-nav__link <?= is_active('revenue.php') ?>">💰 Revenue</a></li>
            <li><a href="viewallusers.php"    class="mobile-nav__link <?= is_active('viewallusers.php') ?>">👥 Users</a></li>
            <li><a href="viewallbooking.php"  class="mobile-nav__link <?= is_active('viewallbooking.php') ?>">🎟️ Bookings</a></li>
            <li class="mobile-nav__divider"></li>
            <li><a href="profile.php"         class="mobile-nav__link">👤 My Profile</a></li>
            <li><a href="settings.php"        class="mobile-nav__link">⚙️ Settings</a></li>
            <li><a href="logout.php"          class="mobile-nav__link mobile-nav__link--danger">🚪 Logout</a></li>
        </ul>
    </div>

</header>
<!-- /ADMIN HEADER -->

<!-- Overlay (mobile) -->
<div class="nav-overlay" id="navOverlay"></div>

<script>
(function () {
    /* ── Scroll shadow ───────────────────────────────────── */
    const header = document.getElementById('adminHeader');
    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 10);
    }, { passive: true });

    /* ── Avatar dropdown ─────────────────────────────────── */
    const avatarBtn    = document.getElementById('avatarBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');
    const dropdown     = document.getElementById('adminDropdown');

    avatarBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = dropdown.classList.toggle('open');
        avatarBtn.setAttribute('aria-expanded', isOpen);
    });

    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) {
            dropdown.classList.remove('open');
            avatarBtn.setAttribute('aria-expanded', 'false');
        }
    });

    /* ── Hamburger / mobile nav ──────────────────────────── */
    const hamburger = document.getElementById('hamburger');
    const mobileNav = document.getElementById('mobileNav');
    const overlay   = document.getElementById('navOverlay');

    function toggleMobileNav(force) {
        const isOpen = typeof force === 'boolean'
            ? force
            : !mobileNav.classList.contains('open');

        mobileNav.classList.toggle('open', isOpen);
        hamburger.classList.toggle('open', isOpen);
        overlay.classList.toggle('open', isOpen);
        hamburger.setAttribute('aria-expanded', isOpen);
        mobileNav.setAttribute('aria-hidden', !isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    hamburger.addEventListener('click', () => toggleMobileNav());
    overlay.addEventListener('click',   () => toggleMobileNav(false));

    /* Close mobile nav on link click */
    mobileNav.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => toggleMobileNav(false));
    });
})();
</script>