<?php 
include("connect.php");

// Check if form is submitted, otherwise reset search variables
$movie_search = '';
$catid = '';
if (isset($_POST['btnSearch'])) {
    $movie_search = $_POST['movie_search'];
    $catid = $_POST['catid'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cinema Theater - Browse Movies</title>
    <link rel="stylesheet" href="css/theater.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Main Theater Section -->
    <section class="theater-section">
        <div class="container">
            
            <!-- Section Header -->
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-line"></span>
                    Our Theater
                    <span class="title-line"></span>
                </h2>
                <p class="section-description">Browse our current lineup and book your seats</p>
            </div>

            <!-- Movies Grid -->
            <div class="movies-grid">
                <?php
                    // Default logic to show all movies
                    $sql = "SELECT theater.*, movies.*, category.catname 
                            FROM theater
                            INNER JOIN movies ON movies.movieid = theater.movieid
                            INNER JOIN category ON category.catid = movies.catid
                            ORDER BY theater.theaterid DESC";
                    
                    $res = mysqli_query($con, $sql);

                    if(mysqli_num_rows($res) > 0) {
                        while($data = mysqli_fetch_array($res)) {
                ?>
                            <div class="movie-card">
                                <!-- Movie Poster -->
                                <div class="movie-poster">
                                    <img src="admin/uploads/<?= htmlspecialchars($data['image']) ?>" 
                                         alt="<?= htmlspecialchars($data['title']) ?>"
                                         loading="lazy">
                                    <div class="poster-overlay">
                                        <a href="play_video.php?file=<?= urlencode($data['trailer']) ?>" 
                                           target="_blank" 
                                           class="play-button">
                                            <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                                                <circle cx="30" cy="30" r="29" stroke="currentColor" stroke-width="2"/>
                                                <path d="M24 20L40 30L24 40V20Z" fill="currentColor"/>
                                            </svg>
                                            <span>Watch Trailer</span>
                                        </a>
                                    </div>
                                    
                                    <!-- Category Badge -->
                                    <div class="category-badge"><?= htmlspecialchars($data['catname']) ?></div>
                                </div>

                                <!-- Movie Details -->
                                <div class="movie-details">
                                    <div class="theater-name"><?= htmlspecialchars($data['theater_name']) ?></div>
                                    <h3 class="movie-title"><?= htmlspecialchars($data['title']) ?></h3>
                                    
                                    <!-- Showtimes -->
                                    <div class="showtimes">
                                        <div class="showtimes-label">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                                <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M8 4V8L11 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                            </svg>
                                            Showtimes
                                        </div>
                                        <div class="timing-pills">
                                            <?php
                                            $timingFields = ['timing', 'timing2', 'timing3', 'timing4'];
                                            foreach ($timingFields as $field) {
                                                if (!empty($data[$field])) {
                                                    echo '<span class="timing-pill">' . htmlspecialchars($data[$field]) . '</span>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>

                                    <!-- Schedule Info -->
                                    <div class="schedule-info">
                                        <div class="info-item">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                                <rect x="2" y="3" width="10" height="9" rx="1" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M2 6H12" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M5 2V4M9 2V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                            </svg>
                                            <span><?= htmlspecialchars($data['days']) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                                <circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M7 4V7L9 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                            </svg>
                                            <span><?= htmlspecialchars($data['date']) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                                <path d="M7 12C9.76142 12 12 9.76142 12 7C12 4.23858 9.76142 2 7 2C4.23858 2 2 4.23858 2 7C2 9.76142 4.23858 12 7 12Z" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M7 9C8.10457 9 9 8.10457 9 7C9 5.89543 8.10457 5 7 5C5.89543 5 5 5.89543 5 7C5 8.10457 5.89543 9 7 9Z" stroke="currentColor" stroke-width="1.5"/>
                                            </svg>
                                            <span><?= htmlspecialchars($data['location']) ?></span>
                                        </div>
                                    </div>

                                    <!-- Price & Booking -->
                                    <div class="booking-section">
                                        <div class="price-tag">
                                            <span class="price-label">Price</span>
                                            <span class="price-value">Rs.<?= htmlspecialchars($data['price']) ?></span>
                                            <span class="price-suffix">/ ticket</span>
                                        </div>
                                        <a href="booking.php?id=<?= htmlspecialchars($data['theaterid']) ?>" 
                                           class="book-now-btn">
                                            <span>Book Now</span>
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                                <path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                <?php
                        }
                    } else {
                        echo '<div class="no-movies">
                                <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                                    <circle cx="40" cy="40" r="30" stroke="currentColor" stroke-width="2" opacity="0.3"/>
                                    <path d="M30 40H50M40 30V50" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                <h3>No Movies Available</h3>
                                <p>Check back soon for new showings</p>
                              </div>';
                    }
                ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="theater-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Cinema Theater. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
