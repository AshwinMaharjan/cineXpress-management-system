<?php
/**
 * all_movies.php — Cinema Hall Management System
 * Improved: DRY queries, XSS-safe output, dynamic categories, better structure
 */
include("connect.php");

// Fetch ALL categories dynamically — no hardcoded catid loops
$cat_sql = "SELECT * FROM category ORDER BY catid ASC";
$cat_res = mysqli_query($con, $cat_sql);
$categories = [];
while ($cat = mysqli_fetch_assoc($cat_res)) {
    $categories[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Movies — CinemaHall</title>
    <link rel="stylesheet" href="css/all_movies.css">
    <!-- Optional: Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Optional: Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
</head>
<body>

<!-- ════════════════════════════════════════════
     PAGE HEADER
════════════════════════════════════════════ -->
<header class="page-header">
    <div class="page-header__inner">
        <i class="fa-solid fa-film page-header__icon"></i>
        <div>
            <h1 class="page-header__title">Now <span>Showing</span></h1>
            <p class="page-header__sub">Browse our latest movies across all categories</p>
        </div>
    </div>
</header>

<!-- ════════════════════════════════════════════
     MAIN CONTENT
════════════════════════════════════════════ -->
<main class="movies-page">

    <?php if (empty($categories)): ?>
        <!-- No categories at all -->
        <div class="empty-state">
            <i class="fa-solid fa-circle-exclamation empty-state__icon"></i>
            <p>No movie categories found.</p>
        </div>

    <?php else: ?>

        <?php foreach ($categories as $category): 
            $catid   = (int) $category['catid'];
            $catname = htmlspecialchars($category['catname'], ENT_QUOTES, 'UTF-8');

            // Fetch movies for this category
            $stmt = mysqli_prepare($con,
                "SELECT movies.*, category.catname
                 FROM movies
                 INNER JOIN category ON category.catid = movies.catid
                 WHERE movies.catid = ?
                 ORDER BY movies.movieid DESC"
            );
            mysqli_stmt_bind_param($stmt, 'i', $catid);
            mysqli_stmt_execute($stmt);
            $res   = mysqli_stmt_get_result($stmt);
            $movies = mysqli_fetch_all($res, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);

            // Skip categories with no movies (optional — remove if you want to show empty sections)
            if (empty($movies)) continue;
        ?>

        <!-- ── Category Section ── -->
        <section class="movie-section" id="category-<?= $catid ?>">

            <!-- Section Title -->
            <div class="section-header">
                <div class="section-header__line"></div>
                <h2 class="section-header__title">
                    <?= $catname ?> <span>Movies</span>
                </h2>
                <div class="section-header__line"></div>
            </div>

            <!-- Movie Grid -->
            <div class="movie-grid">
                <?php foreach ($movies as $movie):
                    $title   = htmlspecialchars($movie['title'],   ENT_QUOTES, 'UTF-8');
                    $image   = htmlspecialchars($movie['image'],   ENT_QUOTES, 'UTF-8');
                    $trailer = htmlspecialchars($movie['trailer'], ENT_QUOTES, 'UTF-8');
                    $cat     = htmlspecialchars($movie['catname'], ENT_QUOTES, 'UTF-8');
                    // Optional fields — use empty string if column doesn't exist
                    $rating  = isset($movie['rating'])   ? htmlspecialchars($movie['rating'],   ENT_QUOTES, 'UTF-8') : '';
                    $duration= isset($movie['duration']) ? htmlspecialchars($movie['duration'], ENT_QUOTES, 'UTF-8') : '';
                    $year    = isset($movie['year'])     ? htmlspecialchars($movie['year'],     ENT_QUOTES, 'UTF-8') : '';
                ?>
                <article class="movie-card">

                    <!-- Poster -->
                    <div class="movie-card__poster">
                        <img 
                            src="admin/uploads/<?= $image ?>" 
                            alt="<?= $title ?> poster"
                            loading="lazy"
                            onerror="this.src='images/placeholder.jpg'"
                        >

                        <!-- Overlay on hover -->
                        <div class="movie-card__overlay">
                            <a 
                                href="play_video.php?file=<?= $trailer ?>" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="btn-trailer"
                                aria-label="Watch trailer for <?= $title ?>"
                            >
                                <i class="fa-solid fa-circle-play btn-trailer__icon"></i>
                                Watch Trailer
                            </a>

                            <a 
                                href="book_ticket.php?movieid=<?= (int)$movie['movieid'] ?>" 
                                class="btn-book"
                                aria-label="Book ticket for <?= $title ?>"
                            >
                                <i class="fa-solid fa-ticket btn-book__icon"></i>
                                Book Ticket
                            </a>
                        </div>

                        <!-- Category Badge -->
                        <span class="movie-card__badge"><?= $cat ?></span>

                        <?php if ($rating): ?>
                        <!-- Rating Badge -->
                        <span class="movie-card__rating">
                            <i class="fa-solid fa-star"></i> <?= $rating ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="movie-card__info">
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
                    </div>

                </article>
                <?php endforeach; ?>
            </div><!-- /.movie-grid -->

        </section><!-- /.movie-section -->

        <?php endforeach; ?>

    <?php endif; ?>

</main>

</body>
</html>
