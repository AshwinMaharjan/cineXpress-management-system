<?php
/**
 * homepage.php — Cinema Hall Management System
 * Improved: Hero section, cinematic search bar, movie grid,
 *           prepared statements, XSS-safe output, DRY code.
 */
include("connect.php");

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
    // Build WHERE clauses dynamically so we only JOIN what's needed
    $conditions = ["movies.title LIKE ?"];
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
         ORDER BY movies.movieid DESC"
    );
}

mysqli_stmt_execute($stmt);
$movies = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

/* ── Total movie count for hero badge ────────────────────── */
$total_movies = (int)(mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM movies"))[0] ?? 0);
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
    <link rel="stylesheet" href="css/homepage.css">
</head>
<body>

<!-- ════════════════════════════════════════════════════════
     HERO SECTION
════════════════════════════════════════════════════════ -->
<section class="hero">

    <!-- Animated background film strips -->
    <div class="hero__filmstrip hero__filmstrip--left"  aria-hidden="true"></div>
    <div class="hero__filmstrip hero__filmstrip--right" aria-hidden="true"></div>

    <!-- Radial gold glow -->
    <div class="hero__glow" aria-hidden="true"></div>

    <div class="hero__inner">

        <!-- Eyebrow label -->
        <p class="hero__eyebrow">
            <i class="fa-solid fa-film"></i>
            &nbsp;Your Premium Cinema Experience
        </p>

        <!-- Headline -->
        <h1 class="hero__title">
            Discover &amp; Book<br>
            <span>Your Next</span> Movie Night
        </h1>

        <!-- Subtitle -->
        <p class="hero__subtitle">
            Browse <?= $total_movies ?> movies across Hollywood, Bollywood, Kollywood &amp; more.
            Book your seats in seconds.
        </p>

        <!-- Stats row -->
        <div class="hero__stats">
            <div class="hero__stat">
                <span class="hero__stat-value"><?= $total_movies ?>+</span>
                <span class="hero__stat-label">Movies</span>
            </div>
            <div class="hero__stat-divider" aria-hidden="true"></div>
            <div class="hero__stat">
                <?php
                $hall_count = (int)(mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM theater"))[0] ?? 0);
                ?>
                <span class="hero__stat-value"><?= $hall_count ?>+</span>
                <span class="hero__stat-label">Theaters</span>
            </div>
            <div class="hero__stat-divider" aria-hidden="true"></div>
            <div class="hero__stat">
                <?php
                $cat_count = count($categories);
                ?>
                <span class="hero__stat-value"><?= $cat_count ?></span>
                <span class="hero__stat-label">Categories</span>
            </div>
        </div>

    </div><!-- /.hero__inner -->
</section><!-- /.hero -->


<!-- ════════════════════════════════════════════════════════
     SEARCH BAR
════════════════════════════════════════════════════════ -->
<div class="search-wrapper">
    <form class="search-form" action="index.php" method="post" role="search">

        <!-- Movie name -->
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

        <!-- Theater name -->
        <div class="search-form__field">
            <i class="fa-solid fa-building search-form__icon"></i>
            <input
                type="text"
                name="theater_name"
                class="search-form__input"
                placeholder="Search theaters…"
                value="<?= htmlspecialchars($theater_name, ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="off"
            >
        </div>

        <!-- Category dropdown -->
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

        <!-- Submit -->
        <button type="submit" name="btnSearch" class="search-form__btn">
            <i class="fa-solid fa-search"></i>
            Search
        </button>

    </form>
</div><!-- /.search-wrapper -->


<!-- ════════════════════════════════════════════════════════
     SECTION LABEL  (Now Showing / Search Results)
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
                <a href="index.php" class="section-bar__reset">
                    <i class="fa-solid fa-xmark"></i> Clear
                </a>
            <?php else: ?>
                <h2 class="section-bar__title">
                    <i class="fa-solid fa-clapperboard section-bar__icon"></i>
                    Now Showing
                    <span class="section-bar__count"><?= count($movies) ?> movies</span>
                </h2>
            <?php endif; ?>
        </div>

        <a href="all_movies.php" class="section-bar__view-all">
            View All <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div><!-- /.section-bar -->


    <!-- ════════════════════════════════════════════════════
         MOVIE GRID
    ════════════════════════════════════════════════════ -->
    <?php if (empty($movies)): ?>

        <!-- Empty / no results -->
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
            <a href="index.php" class="empty-state__btn">
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

                <!-- Poster -->
                <div class="movie-card__poster">
                    <img src="admin/<?php echo $movie['image']; ?>">

                    <!-- Hover overlay -->
                    <div class="movie-card__overlay">
                        <!-- Trailer -->
                        <a
                            href="play_video.php?file=<?= $trailer ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn-trailer"
                            aria-label="Watch trailer for <?= $title ?>"
                            onclick="event.stopPropagation()"
                        >
                            <i class="fa-solid fa-circle-play"></i>
                            Trailer
                        </a>

                        <!-- Details -->
                        <a
                            href="movie_details.php?movieid=<?= $movieid ?>"
                            class="btn-details"
                            aria-label="View details for <?= $title ?>"
                        >
                            <i class="fa-solid fa-circle-info"></i>
                            Details
                        </a>
                    </div>

                    <!-- Category badge -->
                    <span class="movie-card__badge"><?= $cat ?></span>

                    <?php if ($rating): ?>
                    <span class="movie-card__rating">
                        <i class="fa-solid fa-star"></i>
                        <?= $rating ?>
                    </span>
                    <?php endif; ?>
                </div><!-- /.movie-card__poster -->

                <!-- Card bottom info — entire card clicks to details -->
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
        <a href="all_movies.php" class="cta-strip__btn">
            <i class="fa-solid fa-ticket"></i>
            Browse All Movies
        </a>
    </div>
</section>

</body>
</html>
