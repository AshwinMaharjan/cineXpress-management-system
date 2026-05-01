<?php
session_start();
include("connect.php");

// ── Auth guard ──
if (!isset($_SESSION['userid'])) {
    header("Location: ../login.php");
    exit();
}

$userid = (int)$_SESSION['userid'];

// ── Fetch user ──
$user_stmt = $con->prepare("SELECT name, email, phone_number, profile_pic FROM users WHERE userid = ?");
$user_stmt->bind_param("i", $userid);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// ── Stats: only confirmed booking count toward revenue ──
// Use LEFT JOIN so users with zero booking still get a row back
$stats_stmt = $con->prepare("
    SELECT
        COUNT(b.bookingid) AS total_booking,
        COALESCE(SUM(CASE WHEN b.status = 'confirmed' THEN (LENGTH(b.seats) - LENGTH(REPLACE(b.seats, ',', '')) + 1) * m.price ELSE 0 END), 0) AS total_spent,
        COALESCE(SUM(CASE WHEN b.status = 'confirmed' THEN (LENGTH(b.seats) - LENGTH(REPLACE(b.seats, ',', '')) + 1) ELSE 0 END), 0) AS total_seats,
        COALESCE(SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END), 0) AS confirmed_count,
        COALESCE(SUM(CASE WHEN b.status = 'pending'   THEN 1 ELSE 0 END), 0) AS pending_count,
        COALESCE(SUM(CASE WHEN b.status = 'failed'    THEN 1 ELSE 0 END), 0) AS failed_count
    FROM booking b
    LEFT JOIN movies m ON b.movieid = m.movieid
    WHERE b.userid = ?
");
$stats_stmt->bind_param("i", $userid);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

// Fallback defaults if somehow null (brand new user with no booking)
if (!$stats) {
    $stats = [
        'total_booking'  => 0,
        'total_spent'     => 0,
        'total_seats'     => 0,
        'confirmed_count' => 0,
        'pending_count'   => 0,
        'failed_count'    => 0,
    ];
}

// ── Pagination ──
$per_page     = 8;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($current_page - 1) * $per_page;

// ── Filter by status ──
$filter        = $_GET['status'] ?? 'all';
$allowed_statuses = ['all', 'confirmed', 'pending', 'failed'];
if (!in_array($filter, $allowed_statuses)) $filter = 'all';

$where_clause = $filter !== 'all' ? "AND b.status = ?" : "";

// Count for pagination
$count_sql = "SELECT COUNT(*) FROM booking b WHERE b.userid = ? $where_clause";
$count_stmt = $con->prepare($count_sql);
if ($filter !== 'all') {
    $count_stmt->bind_param("is", $userid, $filter);
} else {
    $count_stmt->bind_param("i", $userid);
}
$count_stmt->execute();
$count_stmt->bind_result($total_rows);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = max(1, (int)ceil($total_rows / $per_page));
$current_page = min($current_page, $total_pages);
$offset = ($current_page - 1) * $per_page;

// ── Fetch booking ──
$book_sql = "
    SELECT
        b.bookingid,
        b.booking_date,
        b.timing,
        b.seats,
        b.status,
        m.title,
        m.image,
        m.price,
        m.movie_type,
        (LENGTH(b.seats) - LENGTH(REPLACE(b.seats, ',', '')) + 1) AS seat_count,
        (LENGTH(b.seats) - LENGTH(REPLACE(b.seats, ',', '')) + 1) * m.price AS booking_total
    FROM booking b
    JOIN movies m ON b.movieid = m.movieid
    WHERE b.userid = ? $where_clause
    ORDER BY b.booking_date DESC, b.bookingid DESC
    LIMIT ? OFFSET ?
";

$book_stmt = $con->prepare($book_sql);
if ($filter !== 'all') {
    $book_stmt->bind_param("isii", $userid, $filter, $per_page, $offset);
} else {
    $book_stmt->bind_param("iii", $userid, $per_page, $offset);
}
$book_stmt->execute();
$booking_result = $book_stmt->get_result();

// ── Helpers ──
function format_seats(string $seats_str): array {
    return array_filter(array_map('trim', explode(',', $seats_str)));
}

function status_label(string $status): string {
    return match($status) {
        'confirmed' => 'Confirmed',
        'pending'   => 'Pending',
        'failed'    => 'Failed',
        default     => ucfirst($status),
    };
}

$avatar_initials = strtoupper(mb_substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineXpress - My Dashboard</title>
    <link rel="stylesheet" href="../css/users_dashboard.css">
    <link rel="icon" type="image/png" href="../images/icon.ico">
    <link rel="stylesheet" href="../css/header.css">
    <!-- Tabler icons CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/icons@latest/icons-react/dist/index.umd.min.js" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>

<?php include("users_header.php"); ?>

<main class="dashboard-wrapper">

    <!-- ── Stats ── -->
    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon">
                <i class="ti ti-currency-rupee" style="font-size:22px;"></i>
            </div>
            <p class="stat-label">Total Spent</p>
            <p class="stat-value gold">Rs. <?= number_format($stats['total_spent'], 0) ?></p>
            <p class="stat-sub">From confirmed booking only</p>
            <div class="stat-bg-icon">Rs</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="ti ti-ticket" style="font-size:22px;"></i>
            </div>
            <p class="stat-label">Total booking</p>
            <p class="stat-value"><?= (int)$stats['total_booking'] ?></p>
            <p class="stat-sub"><?= (int)$stats['confirmed_count'] ?> confirmed &middot; <?= (int)$stats['pending_count'] ?> pending</p>
            <div class="stat-bg-icon">TIX</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="ti ti-armchair" style="font-size:22px;"></i>
            </div>
            <p class="stat-label">Seats Booked</p>
            <p class="stat-value"><?= (int)$stats['total_seats'] ?></p>
            <p class="stat-sub">Across all confirmed shows</p>
            <div class="stat-bg-icon">SEATS</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="ti ti-circle-x" style="font-size:22px;"></i>
            </div>
            <p class="stat-label">Failed / Unpaid</p>
            <p class="stat-value" style="color:var(--danger);"><?= (int)$stats['failed_count'] ?></p>
            <p class="stat-sub">booking that didn't go through</p>
            <div class="stat-bg-icon">!</div>
        </div>

    </div>

    <!-- ── Booking History ── -->
    <div class="section-header">
        <h2 class="section-title">Booking History</h2>

        <div class="filter-bar">
            <?php
            $filter_labels = ['all' => 'All', 'confirmed' => 'Confirmed', 'pending' => 'Pending', 'failed' => 'Failed'];
            foreach ($filter_labels as $val => $label):
                $active_class = ($filter === $val) ? 'active' : '';
                $url = "?status=$val&page=1";
            ?>
            <a href="<?= $url ?>">
                <button class="filter-btn <?= $active_class ?>"><?= $label ?></button>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="table-container">
        <?php if ($booking_result->num_rows === 0): ?>
            <div class="empty-state">
                <i class="ti ti-mood-empty" style="font-size:56px; color:var(--accent); opacity:0.25; display:block; margin-bottom:16px;"></i>
                <p>No booking found<?= $filter !== 'all' ? " with status <strong>$filter</strong>" : '' ?>.</p>
                <p style="margin-top:8px;"><a href="../movies.php">Browse movies &rarr;</a></p>
            </div>
        <?php else: ?>
        <table class="booking-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Movie</th>
                    <th>Date &amp; Time</th>
                    <th>Seats</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $row_num = $offset + 1;
            while ($row = $booking_result->fetch_assoc()):
                $seats     = format_seats($row['seats']);
                $img_path  = "../admin/" . $row['image'];
                $has_img   = !empty($row['image']) && file_exists($img_path);
            ?>
            <tr>
                <td data-label="No."><?= $row_num++ ?></td>

                <td data-label="Movie">
                    <div class="movie-cell">
                        <?php if ($has_img): ?>
                            <img src="<?= htmlspecialchars($img_path) ?>"
                                 alt="<?= htmlspecialchars($row['title']) ?>"
                                 class="movie-thumb">
                        <?php else: ?>
                            <div class="movie-thumb-placeholder">
                                <i class="ti ti-movie"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <div class="movie-title-text"><?= htmlspecialchars($row['title']) ?></div>
                            <?php if (!empty($row['movie_type'])): ?>
                                <span class="movie-type-badge"><?= htmlspecialchars($row['movie_type']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>

                <td data-label="Date &amp; Time">
                    <?= date('d M Y', strtotime($row['booking_date'])) ?>
                    <?php if (!empty($row['timing'])): ?>
                        <br><span style="color:var(--muted); font-size:0.78rem;"><?= htmlspecialchars($row['timing']) ?></span>
                    <?php endif; ?>
                </td>

                <td data-label="Seats">
                    <div class="seats-list">
                        <?php foreach ($seats as $seat): ?>
                            <span class="seat-tag"><?= htmlspecialchars($seat) ?></span>
                        <?php endforeach; ?>
                    </div>
                </td>

                <td data-label="Amount">
                    <span class="amount-cell">
                        <?php if ($row['status'] === 'confirmed'): ?>
                            Rs. <?= number_format($row['booking_total'], 0) ?>
                        <?php else: ?>
                            <span style="color:var(--muted); font-family:'DM Sans',sans-serif; font-size:0.8rem;">—</span>
                        <?php endif; ?>
                    </span>
                </td>

                <td data-label="Status">
                    <span class="status-badge <?= htmlspecialchars($row['status']) ?>">
                        <span class="status-dot"></span>
                        <?= status_label($row['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

        <!-- ── Pagination ── -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <!-- Prev -->
            <a href="?status=<?= $filter ?>&page=<?= max(1, $current_page - 1) ?>">
                <button class="page-btn" <?= $current_page <= 1 ? 'disabled' : '' ?>>
                    <i class="ti ti-chevron-left"></i>
                </button>
            </a>

            <?php
            $range = 2;
            $start = max(1, $current_page - $range);
            $end   = min($total_pages, $current_page + $range);
            if ($start > 1): ?>
                <a href="?status=<?= $filter ?>&page=1"><button class="page-btn">1</button></a>
                <?php if ($start > 2): ?><span style="color:var(--muted); padding:0 4px;">…</span><?php endif; ?>
            <?php endif;
            for ($p = $start; $p <= $end; $p++): ?>
                <a href="?status=<?= $filter ?>&page=<?= $p ?>">
                    <button class="page-btn <?= $p === $current_page ? 'active' : '' ?>"><?= $p ?></button>
                </a>
            <?php endfor;
            if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?><span style="color:var(--muted); padding:0 4px;">…</span><?php endif; ?>
                <a href="?status=<?= $filter ?>&page=<?= $total_pages ?>"><button class="page-btn"><?= $total_pages ?></button></a>
            <?php endif; ?>

            <!-- Next -->
            <a href="?status=<?= $filter ?>&page=<?= min($total_pages, $current_page + 1) ?>">
                <button class="page-btn" <?= $current_page >= $total_pages ? 'disabled' : '' ?>>
                    <i class="ti ti-chevron-right"></i>
                </button>
            </a>
        </div>
        <?php endif; ?>

        <?php endif; // end booking check ?>
    </div><!-- /table-container -->

</main>
<?php include("footer.php"); ?>

</body>
</html>
<?php
$book_stmt->close();
$con->close();
?>