<?php
/**
 * homepage.php — Cinema Hall Management System
 * Changes:
 *   1. Added "Coming Soon" KPI stat between Movies and Categories
 *   2. Trailer button opens YouTube modal (same page)
 *   3. Details button opens movie-details modal (same page, AJAX)
 */
include("connect.php");

/* ── AJAX: movie details endpoint ─────────────────────────── */
if (isset($_GET['ajax_movie']) && is_numeric($_GET['ajax_movie'])) {
    $mid  = (int) $_GET['ajax_movie'];
    $stmt = mysqli_prepare($con,
        "SELECT movies.*, category.catname
         FROM movies
         INNER JOIN category ON category.catid = movies.catid
         WHERE movies.movieid = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $mid);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    header('Content-Type: application/json');
    echo json_encode($row ?: []);
    exit;
}

/* ── Search state ─────────────────────────────────────────── */
$movie_search = '';
$theater_name = '';
$catid        = '';
$is_search    = isset($_POST['btnSearch']);

if ($is_search) {
    $movie_search = trim($_POST['movie_search'] ?? '');
    $theater_name = trim($_POST['theater_name'] ?? '');
    $catid        = trim($_POST['catid']         ?? '');
}

/* ── Fetch categories for dropdown ───────────────────────── */
$cat_res    = mysqli_query($con, "SELECT catid, catname FROM category ORDER BY catname ASC");
$categories = mysqli_fetch_all($cat_res, MYSQLI_ASSOC);

/* ── Fetch movies (search OR default) ────────────────────── */
if ($is_search) {
    $conditions = ["movies.title LIKE ?", "movies.movie_type = 'coming_soon'"];
    $types      = 's';
    $params     = ["%{$movie_search}%"];

    if ($catid !== '') {
        $conditions[] = "movies.catid = ?";
        $types        .= 'i';
        $params[]      = (int) $catid;
    }

    $where = implode(' AND ', $conditions);
    $stmt  = mysqli_prepare($con,
        "SELECT movies.*, category.catname
         FROM movies
         INNER JOIN category ON category.catid = movies.catid
         WHERE {$where}
         ORDER BY movies.movieid DESC"
    );
    mysqli_stmt_bind_param($stmt, $types, ...$params);
} else {
    $stmt = mysqli_prepare($con,
        "SELECT movies.*, category.catname
         FROM movies
         INNER JOIN category ON category.catid = movies.catid
         WHERE movies.movie_type = 'coming_soon'
         ORDER BY movies.movieid DESC"
    );
}

mysqli_stmt_execute($stmt);
$movies = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

/* ── Total movie count for hero badge ────────────────────── */
$total_movies = (int)(mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM movies"))[0] ?? 0);

/* ── Coming Soon count ────────────────────────────────────── */
$coming_soon_count = (int)(mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM movies WHERE movie_type = 'coming_soon'"))[0] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinemaHall — Home</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Page Styles -->
    <link rel="stylesheet" href="css/comingsoon.css">

    <style>
        /* ── Modal base ──────────────────────────────────────── */
        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.82);
            backdrop-filter: blur(6px);
            z-index: 9000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-backdrop.is-open {
            display: flex;
        }

        /* ── Trailer modal ───────────────────────────────────── */
        .trailer-modal__box {
            position: relative;
            width: 100%;
            max-width: 860px;
            background: #0d0d0d;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,.8);
            animation: modalIn .3s cubic-bezier(.34,1.56,.64,1);
        }
        .trailer-modal__ratio {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 */
            height: 0;
        }
        .trailer-modal__ratio iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        .trailer-modal__title {
            padding: .75rem 1rem;
            color: #fff;
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            letter-spacing: .02em;
        }

        /* ── Details modal ───────────────────────────────────── */
        .details-modal__box {
            position: relative;
            width: 100%;
            max-width: 780px;
            background: #111;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,.8);
            animation: modalIn .3s cubic-bezier(.34,1.56,.64,1);
            display: flex;
            flex-direction: column;
        }
        .details-modal__body {
            display: flex;
            gap: 0;
            max-height: 88vh;
            overflow: hidden;
        }
        .details-modal__poster {
            flex: 0 0 260px;
            min-height: 380px;
            background: #1a1a1a;
            overflow: hidden;
        }
        .details-modal__poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .details-modal__poster-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #444;
            font-size: 3rem;
        }
        .details-modal__info {
            flex: 1;
            padding: 2rem 1.8rem;
            overflow-y: auto;
            color: #e0e0e0;
        }
        .details-modal__badge {
            display: inline-block;
            background: rgba(212,175,55,.15);
            color: #d4af37;
            border: 1px solid rgba(212,175,55,.3);
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: .25rem .7rem;
            border-radius: 50px;
            margin-bottom: .8rem;
        }
        .details-modal__title {
            font-family: 'Playfair Display', serif;
            font-size: 1.7rem;
            color: #fff;
            margin: 0 0 .5rem;
            line-height: 1.2;
        }
        .details-modal__meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem .9rem;
            margin-bottom: 1.2rem;
        }
        .details-modal__meta-item {
            font-size: .82rem;
            color: #888;
            display: flex;
            align-items: center;
            gap: .3rem;
        }
        .details-modal__meta-item i {
            color: #d4af37;
            font-size: .75rem;
        }
        .details-modal__rating {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(212,175,55,.1);
            border: 1px solid rgba(212,175,55,.25);
            color: #d4af37;
            font-weight: 700;
            font-size: .9rem;
            padding: .3rem .8rem;
            border-radius: 50px;
            margin-bottom: 1.2rem;
        }
        .details-modal__divider {
            border: none;
            border-top: 1px solid #2a2a2a;
            margin: 1rem 0;
        }
        .details-modal__label {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #555;
            margin-bottom: .4rem;
        }
        .details-modal__description {
            font-size: .9rem;
            line-height: 1.7;
            color: #bbb;
        }
        .details-modal__actions {
            display: flex;
            gap: .8rem;
            margin-top: 1.5rem;
        }
        .btn-book-now {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: #d4af37;
            color: #000;
            font-weight: 700;
            font-size: .9rem;
            padding: .7rem 1.4rem;
            border-radius: 8px;
            text-decoration: none;
            transition: background .2s, transform .15s;
        }
        .btn-book-now:hover {
            background: #e8c84a;
            transform: translateY(-1px);
        }
        .btn-watch-trailer {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: transparent;
            color: #d4af37;
            font-weight: 600;
            font-size: .9rem;
            padding: .7rem 1.4rem;
            border-radius: 8px;
            border: 1px solid rgba(212,175,55,.4);
            cursor: pointer;
            transition: background .2s, border-color .2s;
        }
        .btn-watch-trailer:hover {
            background: rgba(212,175,55,.1);
            border-color: #d4af37;
        }

        /* ── Shared modal close button ───────────────────────── */
        .modal-close {
            position: absolute;
            top: .8rem;
            right: .8rem;
            z-index: 10;
            width: 34px;
            height: 34px;
            background: rgba(255,255,255,.12);
            border: none;
            border-radius: 50%;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .2s;
        }
        .modal-close:hover { background: rgba(255,255,255,.25); }

        /* ── Loading spinner ─────────────────────────────────── */
        .modal-spinner {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            color: #888;
            gap: 1rem;
        }
        .modal-spinner i {
            font-size: 2rem;
            color: #d4af37;
            animation: spin .9s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Animation ───────────────────────────────────────── */
        @keyframes modalIn {
            from { opacity: 0; transform: scale(.92) translateY(20px); }
            to   { opacity: 1; transform: scale(1)   translateY(0);    }
        }

        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 640px) {
            .details-modal__body { flex-direction: column; }
            .details-modal__poster { flex: 0 0 200px; min-height: 200px; }
        }
    </style>
</head>
<body>


<!-- ════════════════════════════════════════════════════════
     SEARCH BAR
════════════════════════════════════════════════════════ -->
<div class="search-wrapper">
    <form class="search-form" action="coming_soon.php" method="post" role="search">

        <div class="search-form__field">
            <i class="fa-solid fa-magnifying-glass search-form__icon"></i>
            <input
                type="text"
                name="movie_search"
                class="search-form__input"
                placeholder="Search movies…"
                value="<?= htmlspecialchars($movie_search, ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="off"
            >
        </div>

        <div class="search-form__field search-form__field--select">
            <i class="fa-solid fa-layer-group search-form__icon"></i>
            <select name="catid" class="search-form__select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option
                    value="<?= (int)$cat['catid'] ?>"
                    <?= $catid == $cat['catid'] ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($cat['catname'], ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
            <i class="fa-solid fa-chevron-down search-form__chevron"></i>
        </div>

        <button type="submit" name="btnSearch" class="search-form__btn">
            <i class="fa-solid fa-search"></i>
            Search
        </button>

    </form>
</div><!-- /.search-wrapper -->


<!-- ════════════════════════════════════════════════════════
     SECTION LABEL
════════════════════════════════════════════════════════ -->
<main class="homepage-main">

    <div class="section-bar">
        <div class="section-bar__left">
            <?php if ($is_search): ?>
                <h2 class="section-bar__title">
                    <i class="fa-solid fa-search section-bar__icon"></i>
                    Search Results
                    <span class="section-bar__count"><?= count($movies) ?> found</span>
                </h2>
                <a href="coming_soon.php" class="section-bar__reset">
                    <i class="fa-solid fa-xmark"></i> Clear
                </a>
            <?php else: ?>
                <h2 class="section-bar__title">
                    <i class="fa-solid fa-clapperboard section-bar__icon"></i>
                    Coming Soon
                    <span class="section-bar__count"><?= count($movies) ?> movies</span>
                </h2>
            <?php endif; ?>
        </div>

        <a href="movies.php" class="section-bar__view-all">
            View All <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div><!-- /.section-bar -->


    <!-- ════════════════════════════════════════════════════
         MOVIE GRID
    ════════════════════════════════════════════════════ -->
    <?php if (empty($movies)): ?>

        <div class="empty-state">
            <div class="empty-state__icon-wrap">
                <i class="fa-solid fa-film-slash empty-state__icon"></i>
            </div>
            <h3 class="empty-state__title">No Movies Found</h3>
            <p class="empty-state__sub">
                <?= $is_search
                    ? 'Try adjusting your search terms or clearing the filters.'
                    : 'No movies are currently in the database.' ?>
            </p>
            <?php if ($is_search): ?>
            <a href="coming_soon.php" class="empty-state__btn">
                <i class="fa-solid fa-rotate-left"></i> Reset Search
            </a>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <div class="movie-grid">
            <?php foreach ($movies as $i => $movie):
                $title   = htmlspecialchars($movie['title'],   ENT_QUOTES, 'UTF-8');
                $image   = htmlspecialchars($movie['image'],   ENT_QUOTES, 'UTF-8');
                $trailer = htmlspecialchars($movie['trailer'], ENT_QUOTES, 'UTF-8');
                $cat     = htmlspecialchars($movie['catname'], ENT_QUOTES, 'UTF-8');
                $movieid = (int) $movie['movieid'];
                $rating  = isset($movie['rating'])   ? htmlspecialchars($movie['rating'],   ENT_QUOTES, 'UTF-8') : '';
                $duration= isset($movie['duration']) ? htmlspecialchars($movie['duration'], ENT_QUOTES, 'UTF-8') : '';
                $year    = isset($movie['year'])     ? htmlspecialchars($movie['year'],     ENT_QUOTES, 'UTF-8') : '';
            ?>
            <article class="movie-card" style="--i:<?= $i ?>">

                <div class="movie-card__poster">
                    <img src="admin/<?= $image ?>" alt="<?= $title ?> poster" loading="lazy">

                    <div class="movie-card__overlay">

                        <!-- ★ Trailer button — now opens modal -->
                        <button
                            type="button"
                            class="btn-trailer js-trailer-btn"
                            data-trailer="<?= $trailer ?>"
                            data-title="<?= $title ?>"
                            aria-label="Watch trailer for <?= $title ?>"
                        >
                            <i class="fa-solid fa-circle-play"></i>
                            Trailer
                        </button>

                        <!-- ★ Details button — now opens modal -->
                        <button
                            type="button"
                            class="btn-details js-details-btn"
                            data-movieid="<?= $movieid ?>"
                            aria-label="View details for <?= $title ?>"
                        >
                            <i class="fa-solid fa-circle-info"></i>
                            Details
                        </button>

                    </div>

                    <span class="movie-card__badge"><?= $cat ?></span>

                    <?php if ($rating): ?>
                    <span class="movie-card__rating">
                        <i class="fa-solid fa-star"></i>
                        <?= $rating ?>
                    </span>
                    <?php endif; ?>
                </div><!-- /.movie-card__poster -->

                <a href="movie_details.php?movieid=<?= $movieid ?>" class="movie-card__info" aria-label="<?= $title ?>">
                    <h3 class="movie-card__title"><?= $title ?></h3>
                    <div class="movie-card__meta">
                        <?php if ($year): ?>
                        <span class="movie-card__meta-item">
                            <i class="fa-regular fa-calendar"></i> <?= $year ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($duration): ?>
                        <span class="movie-card__meta-item">
                            <i class="fa-regular fa-clock"></i> <?= $duration ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <span class="movie-card__book">
                        Book Ticket <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </a>

            </article>
            <?php endforeach; ?>
        </div><!-- /.movie-grid -->

    <?php endif; ?>

</main><!-- /.homepage-main -->


<!-- ════════════════════════════════════════════════════════
     FOOTER CTA STRIP
════════════════════════════════════════════════════════ -->
<section class="cta-strip">
    <div class="cta-strip__inner">
        <div class="cta-strip__text">
            <h3>Ready to book your seats?</h3>
            <p>Select a movie and choose your perfect seats instantly.</p>
        </div>
        <a href="movies.php" class="cta-strip__btn">
            <i class="fa-solid fa-ticket"></i>
            Browse All Movies
        </a>
    </div>
</section>


<!-- ════════════════════════════════════════════════════════
     TRAILER MODAL
════════════════════════════════════════════════════════ -->
<div id="trailerModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-label="Movie Trailer">
    <div class="trailer-modal__box">
        <button class="modal-close js-modal-close" aria-label="Close trailer">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <p class="trailer-modal__title" id="trailerModalTitle"></p>
        <div class="trailer-modal__ratio">
            <iframe
                id="trailerIframe"
                src=""
                allow="autoplay; encrypted-media; picture-in-picture"
                allowfullscreen
                title="Movie Trailer"
            ></iframe>
        </div>
    </div>
</div>


<!-- ════════════════════════════════════════════════════════
     DETAILS MODAL
════════════════════════════════════════════════════════ -->
<div id="detailsModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-label="Movie Details">
    <div class="details-modal__box">
        <button class="modal-close js-modal-close" aria-label="Close details">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div id="detailsModalContent" class="details-modal__body">
            <!-- Filled by JS -->
            <div class="modal-spinner">
                <i class="fa-solid fa-circle-notch"></i>
                <span>Loading…</span>
            </div>
        </div>
    </div>
</div>


<!-- ════════════════════════════════════════════════════════
     MODAL JAVASCRIPT
════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    /* ── helpers ─────────────────────────────────────────── */

    /**
     * Extract a YouTube video ID from various URL formats:
     *   https://www.youtube.com/watch?v=VIDEO_ID
     *   https://youtu.be/VIDEO_ID
     *   https://www.youtube.com/embed/VIDEO_ID
     *   Just the raw ID itself
     */
    function extractYouTubeId(raw) {
        if (!raw) return null;
        raw = raw.trim();

        // Already an embed URL → grab the ID segment
        const embedMatch = raw.match(/youtube\.com\/embed\/([^?&]+)/);
        if (embedMatch) return embedMatch[1];

        // youtu.be short URL
        const shortMatch = raw.match(/youtu\.be\/([^?&]+)/);
        if (shortMatch) return shortMatch[1];

        // Full watch URL
        const watchMatch = raw.match(/[?&]v=([^&]+)/);
        if (watchMatch) return watchMatch[1];

        // Plain ID (11 alphanumeric chars)
        if (/^[\w-]{11}$/.test(raw)) return raw;

        return null;
    }

    function openModal(el) {
        el.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(el) {
        el.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    /* ── Trailer modal ───────────────────────────────────── */
    const trailerModal = document.getElementById('trailerModal');
    const trailerIframe = document.getElementById('trailerIframe');
    const trailerTitle  = document.getElementById('trailerModalTitle');

    document.querySelectorAll('.js-trailer-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const raw   = btn.dataset.trailer || '';
            const title = btn.dataset.title   || 'Trailer';
            const ytId  = extractYouTubeId(raw);

            trailerTitle.textContent = title;

            if (ytId) {
                trailerIframe.src = 'https://www.youtube.com/embed/' + ytId + '?autoplay=1&rel=0';
            } else if (raw) {
                // Fallback: treat as a direct file URL (MP4, etc.)
                trailerIframe.src = raw;
            } else {
                trailerIframe.src = '';
            }

            openModal(trailerModal);
        });
    });

    /* ── Details modal ───────────────────────────────────── */
    const detailsModal   = document.getElementById('detailsModal');
    const detailsContent = document.getElementById('detailsModalContent');

    document.querySelectorAll('.js-details-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const movieid = btn.dataset.movieid;

            // Show spinner
            detailsContent.innerHTML =
                '<div class="modal-spinner"><i class="fa-solid fa-circle-notch"></i><span>Loading…</span></div>';
            openModal(detailsModal);

            // Fetch movie data from same page's AJAX endpoint
            fetch('homepage.php?ajax_movie=' + encodeURIComponent(movieid))
                .then(function (r) { return r.json(); })
                .then(function (movie) {
                    if (!movie || !movie.movieid) {
                        detailsContent.innerHTML =
                            '<div class="modal-spinner"><span>Movie not found.</span></div>';
                        return;
                    }
                    renderDetails(movie);
                })
                .catch(function () {
                    detailsContent.innerHTML =
                        '<div class="modal-spinner"><span>Failed to load details. Please try again.</span></div>';
                });
        });
    });

    function esc(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderDetails(m) {
        const ytId    = extractYouTubeId(m.trailer || '');
        const imgSrc  = m.image ? 'admin/' + esc(m.image) : '';
        const rating  = m.rating   ? m.rating   : '';
        const year    = m.year     ? m.year     : '';
        const duration= m.duration ? m.duration : '';
        const desc    = m.description || m.desc || m.synopsis || m.plot || '';
        const cat     = m.catname  ? m.catname  : '';
        const cast    = m.cast     ? m.cast     : '';
        const director= m.director ? m.director : '';
        const language= m.language ? m.language : '';

        let posterHTML = '';
        if (imgSrc) {
            posterHTML = '<img src="' + esc(imgSrc) + '" alt="' + esc(m.title) + ' poster">';
        } else {
            posterHTML = '<div class="details-modal__poster-placeholder"><i class="fa-solid fa-film"></i></div>';
        }

        let metaHTML = '';
        if (year)     metaHTML += '<span class="details-modal__meta-item"><i class="fa-regular fa-calendar"></i>' + esc(year)     + '</span>';
        if (duration) metaHTML += '<span class="details-modal__meta-item"><i class="fa-regular fa-clock"></i>'    + esc(duration) + '</span>';
        if (language) metaHTML += '<span class="details-modal__meta-item"><i class="fa-solid fa-language"></i>'   + esc(language) + '</span>';
        if (cat)      metaHTML += '<span class="details-modal__meta-item"><i class="fa-solid fa-tag"></i>'        + esc(cat)      + '</span>';

        let extraHTML = '';
        if (director) extraHTML += '<p class="details-modal__label">Director</p><p class="details-modal__description">' + esc(director) + '</p><hr class="details-modal__divider">';
        if (cast)     extraHTML += '<p class="details-modal__label">Cast</p><p class="details-modal__description">'     + esc(cast)     + '</p><hr class="details-modal__divider">';
        if (desc)     extraHTML += '<p class="details-modal__label">Description</p><p class="details-modal__description">' + esc(desc)     + '</p>';

        let trailerBtnHTML = '';
        if (ytId || m.trailer) {
            trailerBtnHTML =
                '<button type="button" class="btn-watch-trailer js-details-trailer-btn" ' +
                'data-trailer="' + esc(m.trailer) + '" data-title="' + esc(m.title) + '">' +
                '<i class="fa-solid fa-circle-play"></i> Watch Trailer</button>';
        }

        detailsContent.innerHTML =
            '<div class="details-modal__poster">' + posterHTML + '</div>' +
            '<div class="details-modal__info">' +
                (cat ? '<span class="details-modal__badge">' + esc(cat) + '</span>' : '') +
                '<h2 class="details-modal__title">' + esc(m.title) + '</h2>' +
                (rating ? '<div class="details-modal__rating"><i class="fa-solid fa-star"></i>' + esc(rating) + '</div>' : '') +
                '<div class="details-modal__meta-row">' + metaHTML + '</div>' +
                '<hr class="details-modal__divider">' +
                extraHTML +
                '<div class="details-modal__actions">' +
                    '<a href="booking.php?movieid=' + esc(m.movieid) + '" class="btn-book-now">' +
                        '<i class="fa-solid fa-ticket"></i> Book Tickets' +
                    '</a>' +
                    trailerBtnHTML +
                '</div>' +
            '</div>';

        // Bind trailer button inside details modal
        const innerTrailerBtn = detailsContent.querySelector('.js-details-trailer-btn');
        if (innerTrailerBtn) {
            innerTrailerBtn.addEventListener('click', function () {
                closeModal(detailsModal);
                const raw   = this.dataset.trailer;
                const title = this.dataset.title;
                const ytId2 = extractYouTubeId(raw);
                trailerTitle.textContent = title;
                trailerIframe.src = ytId2
                    ? 'https://www.youtube.com/embed/' + ytId2 + '?autoplay=1&rel=0'
                    : (raw || '');
                openModal(trailerModal);
            });
        }
    }

    /* ── Close buttons ───────────────────────────────────── */
    document.querySelectorAll('.js-modal-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const modal = btn.closest('.modal-backdrop');
            closeModal(modal);
            // Stop YouTube autoplay by clearing src
            const iframe = modal.querySelector('iframe');
            if (iframe) iframe.src = '';
        });
    });

    // Click backdrop to close
    [trailerModal, detailsModal].forEach(function (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal(modal);
                const iframe = modal.querySelector('iframe');
                if (iframe) iframe.src = '';
            }
        });
    });

    // ESC key to close
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        [trailerModal, detailsModal].forEach(function (modal) {
            if (modal.classList.contains('is-open')) {
                closeModal(modal);
                const iframe = modal.querySelector('iframe');
                if (iframe) iframe.src = '';
            }
        });
    });

})();
</script>

</body>
</html>