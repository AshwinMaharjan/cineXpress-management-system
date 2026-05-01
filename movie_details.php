<?php
/**
 * movie_details.php — CineXpress Movie Details Page
 * Shows full details for a single movie fetched by movieid GET param.
 */
include("connect.php");
include("header.php");

// ── Validate movieid ─────────────────────────────────────────
if (!isset($_GET['movieid']) || !is_numeric($_GET['movieid'])) {
    header("Location: index.php");
    exit();
}

$movieid = (int) $_GET['movieid'];

// ── Fetch movie with category ────────────────────────────────
$stmt = mysqli_prepare($con,
    "SELECT movies.*, category.catname
     FROM movies
     INNER JOIN category ON category.catid = movies.catid
     WHERE movies.movieid = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $movieid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$movie  = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// ── 404 if not found ─────────────────────────────────────────
if (!$movie) {
    header("Location: index.php");
    exit();
}

// ── Safe output variables ────────────────────────────────────
$title       = htmlspecialchars($movie['title'],       ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars($movie['description'], ENT_QUOTES, 'UTF-8');
$cat         = htmlspecialchars($movie['catname'],     ENT_QUOTES, 'UTF-8');
$rating      = htmlspecialchars($movie['rating']       ?? '', ENT_QUOTES, 'UTF-8');
$release     = $movie['release_date'] ? date('F j, Y', strtotime($movie['release_date'])) : '';
$trailer     = htmlspecialchars($movie['trailer']      ?? '', ENT_QUOTES, 'UTF-8');
$movie_type  = $movie['movie_type'] ?? 'coming_soon';
$image_path  = 'admin/' . htmlspecialchars($movie['image'], ENT_QUOTES, 'UTF-8');
$type_label  = $movie_type === 'now_showing' ? 'Now Showing' : 'Coming Soon';
$type_class  = $movie_type === 'now_showing' ? 'badge--now'  : 'badge--soon';

// ── Fetch related movies (same category, exclude current) ────
$stmt2 = mysqli_prepare($con,
    "SELECT movieid, title, image, rating
     FROM movies
     WHERE catid = ? AND movieid != ? AND movie_type = 'now_showing'
     ORDER BY movieid DESC
     LIMIT 4"
);
mysqli_stmt_bind_param($stmt2, "ii", $movie['catid'], $movieid);
mysqli_stmt_execute($stmt2);
$related = mysqli_fetch_all(mysqli_stmt_get_result($stmt2), MYSQLI_ASSOC);
mysqli_stmt_close($stmt2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> — CineXpress</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/icon.ico">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --gold:       #D4AF37;
            --gold-light: #F0D060;
            --dark:       #0a0a0f;
            --dark-2:     #12121a;
            --dark-3:     #1c1c28;
            --dark-4:     #242433;
            --text:       #e8e8f0;
            --text-muted: #8888aa;
            --radius:     12px;
        }

        body {
            background: var(--dark);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        /* ── BACKDROP HERO ─────────────────────────────── */
        .detail-backdrop {
            position: relative;
            width: 100%;
            min-height: 520px;
            display: flex;
            align-items: flex-end;
            overflow: hidden;
            margin-top: 70px; /* header offset */
        }

        .detail-backdrop__bg {
            position: absolute;
            inset: 0;
            background-image: url('<?= $image_path ?>');
            background-size: cover;
            background-position: center top;
            filter: blur(6px) brightness(0.35);
            transform: scale(1.05);
        }

        .detail-backdrop__gradient {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to bottom,
                transparent 0%,
                rgba(10,10,15,0.6) 50%,
                var(--dark) 100%
            );
        }
/* ── TRAILER MODAL ─────────────────────────────── */
.trailer-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(6px);
    align-items: center;
    justify-content: center;
}

.trailer-modal.open {
    display: flex;
}

.trailer-modal__box {
    position: relative;
    width: 90%;
    max-width: 900px;
    aspect-ratio: 16/9;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 32px 80px rgba(0,0,0,0.9);
    border: 1px solid rgba(212,175,55,0.2);
}

.trailer-modal__close {
    position: absolute;
    top: -44px;
    right: 0;
    background: none;
    border: none;
    color: #fff;
    font-size: 1.6rem;
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.2s;
    line-height: 1;
}

.trailer-modal__close:hover { opacity: 1; }

.trailer-modal__iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}
        .detail-backdrop__content {
            position: relative;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px 40px;
            display: flex;
            gap: 48px;
            align-items: flex-end;
        }

        /* ── POSTER ────────────────────────────────────── */
        .detail-poster {
            flex-shrink: 0;
            width: 240px;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 24px 64px rgba(0,0,0,0.8), 0 0 0 1px rgba(212,175,55,0.2);
            transform: translateY(40px);
        }

        .detail-poster img {
            width: 100%;
            display: block;
            aspect-ratio: 2/3;
            object-fit: cover;
        }

        /* ── HERO INFO ─────────────────────────────────── */
        .detail-hero-info {
            flex: 1;
            padding-bottom: 8px;
        }

        .detail-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .badge {
            padding: 4px 14px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .badge--cat {
            background: rgba(212,175,55,0.15);
            color: var(--gold);
            border: 1px solid rgba(212,175,55,0.3);
        }

        .badge--now {
            background: rgba(34,197,94,0.15);
            color: #4ade80;
            border: 1px solid rgba(34,197,94,0.3);
        }

        .badge--soon {
            background: rgba(251,146,60,0.15);
            color: #fb923c;
            border: 1px solid rgba(251,146,60,0.3);
        }

        .detail-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 900;
            line-height: 1.1;
            color: #fff;
            margin-bottom: 20px;
        }

        .detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            margin-bottom: 28px;
        }

        .detail-meta__item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .detail-meta__item i {
            color: var(--gold);
            font-size: 0.85rem;
        }

        .detail-meta__item strong {
            color: var(--text);
            font-weight: 600;
        }

        .rating-stars {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .rating-stars i { color: var(--gold); }

        .detail-actions {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .btn-book {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 32px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #000;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 8px;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(212,175,55,0.4);
        }

        .btn-trailer {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            background: rgba(255,255,255,0.08);
            color: var(--text);
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 8px;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.12);
            transition: background 0.2s, transform 0.2s;
        }

        .btn-trailer:hover {
            background: rgba(255,255,255,0.14);
            transform: translateY(-2px);
        }

        /* ── MAIN CONTENT ──────────────────────────────── */
        .detail-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 24px 80px;
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 48px;
        }

        /* ── DESCRIPTION ───────────────────────────────── */
        .detail-section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-section-title i { color: var(--gold); font-size: 1.1rem; }

        .detail-description {
            font-size: 1rem;
            line-height: 1.85;
            color: var(--text-muted);
        }

        /* ── SIDEBAR ───────────────────────────────────── */
        .detail-sidebar {}

        .detail-info-card {
            background: var(--dark-3);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 28px;
        }

        .detail-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 0.88rem;
        }

        .detail-info-row:last-child { border-bottom: none; }

        .detail-info-row__label {
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-info-row__label i { color: var(--gold); width: 14px; }

        .detail-info-row__value {
            color: var(--text);
            font-weight: 600;
            text-align: right;
        }

        /* ── RELATED ───────────────────────────────────── */
        .related-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .related-card {
            text-decoration: none;
            border-radius: 8px;
            overflow: hidden;
            background: var(--dark-3);
            border: 1px solid rgba(255,255,255,0.06);
            transition: transform 0.2s, border-color 0.2s;
        }

        .related-card:hover {
            transform: translateY(-4px);
            border-color: rgba(212,175,55,0.3);
        }

        .related-card img {
            width: 100%;
            aspect-ratio: 2/3;
            object-fit: cover;
            display: block;
        }

        .related-card__info {
            padding: 10px;
        }

        .related-card__title {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 4px;
        }

        .related-card__rating {
            font-size: 0.75rem;
            color: var(--gold);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* ── BOOK CTA ──────────────────────────────────── */
        .book-cta {
            background: linear-gradient(135deg, var(--dark-3), var(--dark-4));
            border: 1px solid rgba(212,175,55,0.2);
            border-radius: var(--radius);
            padding: 28px 24px;
            text-align: center;
        }

        .book-cta h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: #fff;
            margin-bottom: 8px;
        }

        .book-cta p {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .book-cta .btn-book {
            width: 100%;
            justify-content: center;
        }

        /* ── BACK LINK ─────────────────────────────────── */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.88rem;
            margin-bottom: 32px;
            transition: color 0.2s;
        }

        .back-link:hover { color: var(--gold); }

        /* ── RESPONSIVE ────────────────────────────────── */
        @media (max-width: 900px) {
            .detail-backdrop__content {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding-bottom: 32px;
            }

            .detail-poster {
                width: 180px;
                transform: none;
            }

            .detail-meta { justify-content: center; }
            .detail-actions { justify-content: center; }
            .detail-badges { justify-content: center; }

            .detail-main {
                grid-template-columns: 1fr;
                padding-top: 40px;
            }
        }

        @media (max-width: 500px) {
            .detail-poster { width: 140px; }
            .detail-title  { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

<!-- ── BACKDROP HERO ──────────────────────────────────────── -->
<div class="detail-backdrop">
    <div class="detail-backdrop__bg"></div>
    <div class="detail-backdrop__gradient"></div>

    <div class="detail-backdrop__content">

        <!-- Poster -->
        <div class="detail-poster">
            <img
                src="<?= $image_path ?>"
                alt="<?= $title ?> poster"
                onerror="this.src='images/placeholder.jpg'"
            >
        </div>

        <!-- Hero info -->
        <div class="detail-hero-info">

            <div class="detail-badges">
                <span class="badge badge--cat"><?= $cat ?></span>
                <span class="badge <?= $type_class ?>"><?= $type_label ?></span>
            </div>

            <h1 class="detail-title"><?= $title ?></h1>

            <div class="detail-meta">
                <?php if ($rating): ?>
                <div class="detail-meta__item">
                    <div class="rating-stars">
                        <i class="fa-solid fa-star"></i>
                        <strong><?= $rating ?> / 10</strong>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($release): ?>
                <div class="detail-meta__item">
                    <i class="fa-regular fa-calendar"></i>
                    <strong><?= $release ?></strong>
                </div>
                <?php endif; ?>

                <div class="detail-meta__item">
                    <i class="fa-solid fa-layer-group"></i>
                    <strong><?= $cat ?></strong>
                </div>
            </div>

            <div class="detail-actions">
                <?php if ($movie_type === 'now_showing'): ?>
                <a href="booking.php?movieid=<?= $movieid ?>" class="btn-book">
                    <i class="fa-solid fa-ticket"></i>
                    Book Tickets
                </a>
                <?php endif; ?>

                <?php if ($trailer): ?>
                <?php if ($trailer): ?>
<button type="button" class="btn-trailer" onclick="openTrailer()">
    <i class="fa-solid fa-circle-play"></i>
    Watch Trailer
</button>
<?php endif; ?>
                <?php endif; ?>
            </div>

        </div><!-- /.detail-hero-info -->
    </div><!-- /.detail-backdrop__content -->
</div><!-- /.detail-backdrop -->


<!-- ── MAIN CONTENT ───────────────────────────────────────── -->
<div class="detail-main">

    <!-- Left column -->
    <div>
        <a href="index.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Movies
        </a>

        <!-- Synopsis -->
        <h2 class="detail-section-title">
            <i class="fa-solid fa-align-left"></i> Description
        </h2>
        <p class="detail-description"><?= nl2br($description) ?></p>
    </div>

    <!-- Right sidebar -->
    <div class="detail-sidebar">

        <!-- Movie Info Card -->
        <div class="detail-info-card">
            <h2 class="detail-section-title" style="margin-bottom:8px;">
                <i class="fa-solid fa-circle-info"></i> Movie Info
            </h2>

            <div class="detail-info-row">
                <span class="detail-info-row__label">
                    <i class="fa-solid fa-layer-group"></i> Category
                </span>
                <span class="detail-info-row__value"><?= $cat ?></span>
            </div>

            <?php if ($rating): ?>
            <div class="detail-info-row">
                <span class="detail-info-row__label">
                    <i class="fa-solid fa-star"></i> Rating
                </span>
                <span class="detail-info-row__value"><?= $rating ?> / 10</span>
            </div>
            <?php endif; ?>

            <?php if ($release): ?>
            <div class="detail-info-row">
                <span class="detail-info-row__label">
                    <i class="fa-regular fa-calendar"></i> Release Date
                </span>
                <span class="detail-info-row__value"><?= $release ?></span>
            </div>
            <?php endif; ?>

            <div class="detail-info-row">
                <span class="detail-info-row__label">
                    <i class="fa-solid fa-film"></i> Status
                </span>
                <span class="detail-info-row__value"><?= $type_label ?></span>
            </div>
        </div>

        <!-- Book CTA -->
        <?php if ($movie_type === 'now_showing'): ?>
        <div class="book-cta">
            <h3>Ready to Watch?</h3>
            <p>Secure your seats before they sell out.</p>
            <a href="booking.php?movieid=<?= $movieid ?>" class="btn-book">
                <i class="fa-solid fa-ticket"></i>
                Book Tickets Now
            </a>
        </div>
        <?php else: ?>
        <div class="book-cta">
            <h3>Coming Soon</h3>
            <p>This movie is not yet showing. Stay tuned!</p>
        </div>
        <?php endif; ?>

        <!-- Related Movies -->
        <?php if (!empty($related)): ?>
        <div style="margin-top: 32px;">
            <h2 class="detail-section-title">
                <i class="fa-solid fa-clapperboard"></i> More in <?= $cat ?>
            </h2>
            <div class="related-grid">
                <?php foreach ($related as $r): ?>
                <a href="movie_details.php?movieid=<?= (int)$r['movieid'] ?>" class="related-card">
                    <img
                        src="admin/<?= htmlspecialchars($r['image'], ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?>"
                        onerror="this.src='images/placeholder.jpg'"
                    >
                    <div class="related-card__info">
                        <div class="related-card__title">
                            <?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php if ($r['rating']): ?>
                        <div class="related-card__rating">
                            <i class="fa-solid fa-star"></i>
                            <?= htmlspecialchars($r['rating'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.detail-sidebar -->
</div><!-- /.detail-main -->
<!-- Trailer Modal -->
<?php if ($trailer): ?>
<div class="trailer-modal" id="trailerModal" onclick="handleBackdropClick(event)">
    <div class="trailer-modal__box">
        <button class="trailer-modal__close" onclick="closeTrailer()" aria-label="Close trailer">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <iframe
            id="trailerFrame"
            class="trailer-modal__iframe"
            src=""
            allow="autoplay; encrypted-media"
            allowfullscreen
        ></iframe>
    </div>
</div>
<?php endif; ?>

<?php include("footer.php"); ?>
<script>
    // Convert any YouTube URL to embed format
    function getEmbedUrl(url) {
        // Handles: youtu.be/ID, youtube.com/watch?v=ID, youtube.com/embed/ID
        const patterns = [
            /youtu\.be\/([^?&]+)/,
            /youtube\.com\/watch\?v=([^?&]+)/,
            /youtube\.com\/embed\/([^?&]+)/
        ];
        for (const pattern of patterns) {
            const match = url.match(pattern);
            if (match) return 'https://www.youtube.com/embed/' + match[1] + '?autoplay=1';
        }
        return url; // fallback: use as-is
    }

    function openTrailer() {
        const url = '<?= addslashes($trailer) ?>';
        document.getElementById('trailerFrame').src = getEmbedUrl(url);
        document.getElementById('trailerModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeTrailer() {
        document.getElementById('trailerFrame').src = ''; // stops video
        document.getElementById('trailerModal').classList.remove('open');
        document.body.style.overflow = '';
    }

    // Close when clicking the dark backdrop (not the video box)
    function handleBackdropClick(e) {
        if (e.target === document.getElementById('trailerModal')) closeTrailer();
    }

    // Close on Escape key
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeTrailer();
    });
</script>
</body>
</html>