<?php
include("connect.php");
include("header.php");

// Get movie ID from URL
$movieid = isset($_GET['id']) ? intval($_GET['id']) : 0;


// Fetch movie details
$stmt = $con->prepare("SELECT m.*, c.catname FROM movies m LEFT JOIN category c ON m.catid = c.catid WHERE m.movieid = ?");
$stmt->bind_param("i", $movieid);
$stmt->execute();
$result = $stmt->get_result();

$movie = $result->fetch_assoc();
$stmt->close();

// Fetch related movies from same category
$related = [];
if (!empty($movie['catid'])) {
    $rel_stmt = $con->prepare("SELECT movieid, title, image, rating FROM movies WHERE catid = ? AND movieid != ? LIMIT 4");
    $rel_stmt->bind_param("ii", $movie['catid'], $movieid);
    $rel_stmt->execute();
    $rel_result = $rel_stmt->get_result();
    while ($row = $rel_result->fetch_assoc()) {
        $related[] = $row;
    }
    $rel_stmt->close();
}

// Extract YouTube embed ID from trailer URL
function getYoutubeEmbedUrl($url) {
    if (empty($url)) return null;
    preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches);
    if (isset($matches[1])) {
        return "https://www.youtube.com/embed/" . $matches[1] . "?autoplay=1&rel=0";
    }
    return $url;
}

$embedUrl = getYoutubeEmbedUrl($movie['trailer']);

// Format release date
$releaseDate = !empty($movie['release_date']) ? date("F j, Y", strtotime($movie['release_date'])) : "TBA";
$releaseYear = !empty($movie['release_date']) ? date("Y", strtotime($movie['release_date'])) : "";

// Star rating helper
function renderStars($rating) {
    $rating = floatval($rating);
    $full   = floor($rating / 2);
    $half   = ($rating / 2 - $full) >= 0.5 ? 1 : 0;
    $empty  = 5 - $full - $half;
    $stars  = str_repeat('<i class="fas fa-star"></i>', $full);
    if ($half) $stars .= '<i class="fas fa-star-half-alt"></i>';
    $stars .= str_repeat('<i class="far fa-star"></i>', $empty);
    return $stars;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= htmlspecialchars($movie['title']) ?> — CinemaX</title>
    <link rel="stylesheet" href="css/movie_details.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>

<!-- ░░░ HERO SECTION ░░░ -->
<section class="hero" style="--poster: url('<?= htmlspecialchars($movie['image']) ?>')">
    <div class="hero__overlay"></div>

    <div class="hero__content container">

        <!-- Poster -->
        <div class="hero__poster">
            <img src="<?= htmlspecialchars($movie['image']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" />
            <?php if (!empty($movie['trailer'])): ?>
            <button class="play-btn" id="openTrailerBtn" aria-label="Play Trailer">
                <span class="play-btn__ring"></span>
                <i class="fas fa-play"></i>
            </button>
            <?php endif; ?>
        </div>

        <!-- Meta -->
        <div class="hero__meta">

            <?php if (!empty($movie['catname'])): ?>
            <span class="badge badge--category">
                <i class="fas fa-tag"></i>
                <?= htmlspecialchars($movie['catname']) ?>
            </span>
            <?php endif; ?>

            <h1 class="hero__title">
                <?= htmlspecialchars($movie['title']) ?>
                <?php if ($releaseYear): ?>
                <span class="hero__year">(<?= $releaseYear ?>)</span>
                <?php endif; ?>
            </h1>

            <!-- Rating Row -->
            <div class="rating-row">
                <div class="stars"><?= renderStars($movie['rating']) ?></div>
                <span class="rating-score"><?= number_format($movie['rating'], 1) ?> / 10</span>
                <span class="rating-label">IMDb Style</span>
            </div>

            <!-- Info Pills -->
            <div class="info-pills">
                <div class="pill">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?= $releaseDate ?></span>
                </div>
                <?php if (!empty($movie['catname'])): ?>
                <div class="pill">
                    <i class="fas fa-film"></i>
                    <span><?= htmlspecialchars($movie['catname']) ?></span>
                </div>
                <?php endif; ?>
                <div class="pill pill--rating">
                    <i class="fas fa-star"></i>
                    <span><?= number_format($movie['rating'], 1) ?></span>
                </div>
            </div>

            <!-- Overview -->
            <div class="overview">
                <h3 class="overview__label">Overview</h3>
                <p class="overview__text"><?= nl2br(htmlspecialchars($movie['description'])) ?></p>
            </div>

            <!-- CTA Buttons -->
            <div class="hero__actions">
                <a href="booking.php?id=<?= $movie['movieid'] ?>" class="btn btn--primary">
                    <i class="fas fa-ticket-alt"></i> Book Tickets
                </a>
                <?php if (!empty($movie['trailer'])): ?>
                <button class="btn btn--outline" id="openTrailerBtn2">
                    <i class="fas fa-play-circle"></i> Watch Trailer
                </button>
                <?php endif; ?>
                <button class="btn btn--ghost" id="shareBtn" title="Share">
                    <i class="fas fa-share-alt"></i>
                </button>
            </div>

        </div>
    </div>
</section>

<!-- ░░░ TRAILER MODAL ░░░ -->
<?php if (!empty($movie['trailer'])): ?>
<div class="modal-overlay" id="trailerModal" role="dialog" aria-modal="true" aria-label="Trailer">
    <div class="modal">
        <button class="modal__close" id="closeTrailerBtn" aria-label="Close">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal__video-wrap">
            <iframe id="trailerIframe" src="" allowfullscreen
                allow="autoplay; encrypted-media"
                title="<?= htmlspecialchars($movie['title']) ?> Trailer">
            </iframe>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ░░░ SHARE TOAST ░░░ -->
<div class="toast" id="shareToast">
    <i class="fas fa-check-circle"></i> Link copied to clipboard!
</div>

<!-- ░░░ RELATED MOVIES ░░░ -->
<?php if (!empty($related)): ?>
<section class="related container">
    <div class="section-header">
        <h2 class="section-title">
            <span class="accent-line"></span>
            More Like This
        </h2>
        <a href="movies.php?cat=<?= $movie['catid'] ?>" class="see-all">
            See All <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <div class="related__grid">
        <?php foreach ($related as $rel): ?>
        <a href="movie_details.php?id=<?= $rel['movieid'] ?>" class="related-card">
            <div class="related-card__img-wrap">
                <img src="<?= htmlspecialchars($rel['image']) ?>" alt="<?= htmlspecialchars($rel['title']) ?>" loading="lazy"/>
                <div class="related-card__overlay">
                    <i class="fas fa-info-circle"></i>
                </div>
            </div>
            <div class="related-card__body">
                <h4 class="related-card__title"><?= htmlspecialchars($rel['title']) ?></h4>
                <div class="related-card__rating">
                    <i class="fas fa-star"></i>
                    <span><?= number_format($rel['rating'], 1) ?></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ░░░ BACK BUTTON ░░░ -->
<div class="container back-wrap">
    <a href="javascript:history.back()" class="back-link">
        <i class="fas fa-chevron-left"></i> Back
    </a>
</div>

<script>
(function () {
    const embedUrl   = <?= json_encode($embedUrl) ?>;

    /* ── Trailer Modal ── */
    const modal      = document.getElementById('trailerModal');
    const iframe     = document.getElementById('trailerIframe');
    const openBtns   = [document.getElementById('openTrailerBtn'), document.getElementById('openTrailerBtn2')];
    const closeBtn   = document.getElementById('closeTrailerBtn');

    function openModal() {
        if (!modal) return;
        iframe.src = embedUrl;
        modal.classList.add('is-active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('is-active');
        iframe.src = '';
        document.body.style.overflow = '';
    }

    openBtns.forEach(btn => btn && btn.addEventListener('click', openModal));
    closeBtn && closeBtn.addEventListener('click', closeModal);
    modal && modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });

    /* ── Share ── */
    const shareBtn  = document.getElementById('shareBtn');
    const shareToast = document.getElementById('shareToast');

    shareBtn && shareBtn.addEventListener('click', function () {
        navigator.clipboard.writeText(window.location.href).then(function () {
            shareToast.classList.add('is-visible');
            setTimeout(() => shareToast.classList.remove('is-visible'), 2800);
        });
    });

    /* ── Scroll reveal ── */
    const revealEls = document.querySelectorAll('.related-card, .overview, .hero__meta');
    const observer  = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    revealEls.forEach(el => observer.observe(el));
})();
</script>

</body>
</html>
