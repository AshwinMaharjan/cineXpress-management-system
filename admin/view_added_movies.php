<?php
include("connect.php");
include("admin_header.php");

// Pagination setup
$limit = 12;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Get total count of movies
$count_query = "SELECT COUNT(*) as total FROM movies";
$count_result = $con->query($count_query);
$total_movies = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_movies / $limit);

// Fetch movies with pagination
$query = "SELECT m.*, c.catname as category_name FROM movies m LEFT JOIN category c ON m.catid = c.catid ORDER BY m.movieid DESC LIMIT $limit OFFSET $offset";
$movies = $con->query($query);

// Function to convert YouTube URL to embed URL
function getYoutubeEmbedUrl($url) {
    if (empty($url)) return '';
    $video_id = '';
    if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $matches)) {
        $video_id = $matches[1];
    } elseif (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $matches)) {
        $video_id = $matches[1];
    } elseif (preg_match('/youtube\.com\/embed\/([^\&\?\/]+)/', $url, $matches)) {
        $video_id = $matches[1];
    }
    if ($video_id) return "https://www.youtube.com/embed/" . $video_id;
    return $url;
}

function getYoutubeThumbnail($url) {
    if (empty($url)) return '';
    $video_id = '';
    if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $matches)) {
        $video_id = $matches[1];
    } elseif (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $matches)) {
        $video_id = $matches[1];
    }
    if ($video_id) return "https://img.youtube.com/vi/" . $video_id . "/mqdefault.jpg";
    return '';
}
?>

<link rel="stylesheet" href="../css/view_added_movies.css">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

<div class="vam-wrapper">

    <!-- Header -->
    <div class="vam-header">
        <div class="vam-header-left">
            <h1 class="vam-title">All Movies</h1>
            <div class="vam-stats">
                <span class="vam-badge"><?php echo $total_movies; ?> titles</span>
                <span class="vam-badge muted">Page <?php echo $page; ?> / <?php echo max(1,$total_pages); ?></span>
            </div>
        </div>
        <a href="movies.php" class="vam-add-btn">＋ Add Movie</a>
    </div>

    <?php if ($movies->num_rows > 0): ?>
    <!-- Grid -->
    <div class="vam-grid">
        <?php while ($movie = $movies->fetch_assoc()): ?>
        <div class="vam-card" onclick="openModal(<?php echo $movie['movieid']; ?>)">

            <!-- Poster -->
            <div class="vam-poster">
                <?php if ($movie['image']): ?>
                    <img src="<?php echo htmlspecialchars($movie['image']); ?>"
                         alt="<?php echo htmlspecialchars($movie['title']); ?>"
                         loading="lazy">
                <?php elseif ($movie['trailer'] && getYoutubeThumbnail($movie['trailer'])): ?>
                    <img src="<?php echo getYoutubeThumbnail($movie['trailer']); ?>"
                         alt="<?php echo htmlspecialchars($movie['title']); ?>"
                         loading="lazy">
                <?php else: ?>
                    <div class="vam-no-poster">🎬</div>
                <?php endif; ?>

                <!-- Overlay on hover -->
                <div class="vam-poster-overlay">
                    <div class="vam-overlay-actions">
                        <button class="vam-oa-btn" onclick="event.stopPropagation(); openModal(<?php echo $movie['movieid']; ?>)">👁 Details</button>
                        <a class="vam-oa-btn" href="movies.php?edit=<?php echo $movie['movieid']; ?>" onclick="event.stopPropagation()">✏️ Edit</a>
                    </div>
                </div>

                <!-- Rating pill -->
                <div class="vam-rating">⭐ <?php echo number_format($movie['rating'], 1); ?></div>

                <!-- Category pill -->
                <div class="vam-cat"><?php echo htmlspecialchars($movie['category_name'] ?? 'N/A'); ?></div>
            </div>

            <!-- Card Info -->
            <div class="vam-info">
                <p class="vam-movie-title"><?php echo htmlspecialchars($movie['title']); ?></p>
                <p class="vam-release"><?php echo date('M Y', strtotime($movie['release_date'])); ?></p>
            </div>
        </div>

        <!-- Hidden modal data -->
        <div id="modal-data-<?php echo $movie['movieid']; ?>" class="modal-data" style="display:none"
             data-title="<?php echo htmlspecialchars($movie['title']); ?>"
             data-category="<?php echo htmlspecialchars($movie['category_name'] ?? 'Uncategorized'); ?>"
             data-rating="<?php echo number_format($movie['rating'], 1); ?>"
             data-release="<?php echo date('F d, Y', strtotime($movie['release_date'])); ?>"
             data-description="<?php echo htmlspecialchars($movie['description'] ?? ''); ?>"
             data-image="<?php echo htmlspecialchars($movie['image'] ?? ''); ?>"
             data-trailer="<?php echo htmlspecialchars(getYoutubeEmbedUrl($movie['trailer'] ?? '')); ?>"
             data-movie="<?php echo htmlspecialchars($movie['movie'] ?? ''); ?>"
             data-id="<?php echo $movie['movieid']; ?>">
        </div>

        <?php endwhile; ?>
    </div>

    <?php else: ?>
    <div class="vam-empty">
        <div class="vam-empty-icon">🎬</div>
        <h2>No Movies Yet</h2>
        <p>Your collection is empty. Add your first movie!</p>
        <a href="movies.php" class="vam-add-btn">＋ Add Movie</a>
    </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="vam-pagination">
        <?php if ($page > 1): ?>
            <a href="?page=1" class="vam-pg-btn">⏮</a>
            <a href="?page=<?php echo $page - 1; ?>" class="vam-pg-btn">‹ Prev</a>
        <?php endif; ?>

        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="vam-pg-btn <?php echo ($i === $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>" class="vam-pg-btn">Next ›</a>
            <a href="?page=<?php echo $total_pages; ?>" class="vam-pg-btn">⏭</a>
        <?php endif; ?>

        <span class="vam-pg-info">
            <?php echo $offset + 1; ?>–<?php echo min($offset + $limit, $total_movies); ?> of <?php echo $total_movies; ?>
        </span>
    </div>
    <?php endif; ?>
</div>

<!-- Detail Modal -->
<div id="vam-modal" class="vam-modal-backdrop" onclick="closeModal()">
    <div class="vam-modal" onclick="event.stopPropagation()">
        <button class="vam-modal-close" onclick="closeModal()">✕</button>

        <div class="vam-modal-inner">
            <!-- Left: poster + quick actions -->
            <div class="vam-modal-left">
                <div class="vam-modal-poster">
                    <img id="modal-img" src="" alt="">
                    <div id="modal-no-poster" class="vam-no-poster" style="display:none">🎬</div>
                </div>
                <div id="modal-quick-actions" class="vam-modal-quick"></div>
            </div>

            <!-- Right: details -->
            <div class="vam-modal-right">
                <span id="modal-cat" class="vam-modal-cat"></span>
                <h2 id="modal-title" class="vam-modal-title"></h2>

                <div class="vam-modal-meta">
                    <div class="vam-meta-row"><span>⭐ Rating</span><strong id="modal-rating"></strong></div>
                    <div class="vam-meta-row"><span>📅 Release</span><strong id="modal-release"></strong></div>
                    <div class="vam-meta-row"><span>🎭 Genre</span><strong id="modal-genre"></strong></div>
                </div>

                <div class="vam-modal-desc">
                    <h4>Description</h4>
                    <p id="modal-desc"></p>
                </div>

                <div id="modal-trailer-wrap" class="vam-modal-trailer" style="display:none">
                    <h4>Trailer</h4>
                    <div class="vam-trailer-frame">
                        <iframe id="modal-trailer" src="" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                    </div>
                </div>

                <div class="vam-modal-actions">
                    <a id="modal-edit-btn" href="#" class="vam-modal-btn edit">✏️ Edit</a>
                    <a id="modal-play-btn" href="#" target="_blank" class="vam-modal-btn play" style="display:none">▶ Play</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openModal(id) {
    const data = document.getElementById('modal-data-' + id).dataset;

    document.getElementById('modal-title').textContent   = data.title;
    document.getElementById('modal-cat').textContent     = data.category;
    document.getElementById('modal-genre').textContent   = data.category;
    document.getElementById('modal-rating').textContent  = data.rating + ' / 10';
    document.getElementById('modal-release').textContent = data.release;
    document.getElementById('modal-desc').textContent    = data.description || 'No synopsis available.';
    document.getElementById('modal-edit-btn').href       = 'movies.php?edit=' + data.id;

    const img = document.getElementById('modal-img');
    const noPoster = document.getElementById('modal-no-poster');
    if (data.image) {
        img.src = data.image; img.style.display = 'block'; noPoster.style.display = 'none';
    } else {
        img.style.display = 'none'; noPoster.style.display = 'flex';
    }

    const trailerWrap = document.getElementById('modal-trailer-wrap');
    const trailerIframe = document.getElementById('modal-trailer');
    if (data.trailer) {
        trailerIframe.src = data.trailer;
        trailerWrap.style.display = 'block';
    } else {
        trailerIframe.src = '';
        trailerWrap.style.display = 'none';
    }

    const playBtn = document.getElementById('modal-play-btn');
    if (data.movie) {
        playBtn.href = data.movie;
        playBtn.style.display = 'inline-flex';
    } else {
        playBtn.style.display = 'none';
    }

    document.getElementById('vam-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('vam-modal').classList.remove('open');
    document.getElementById('modal-trailer').src = '';
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// Lazy load fade-in
document.querySelectorAll('.vam-poster img').forEach(img => {
    img.addEventListener('load', () => img.classList.add('loaded'));
    if (img.complete) img.classList.add('loaded');
});
</script>

<?php include("footer.php"); ?>Synopsis