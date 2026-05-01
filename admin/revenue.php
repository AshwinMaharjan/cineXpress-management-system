<?php
/**
 * revenue.php — Admin Revenue Dashboard
 * Shows: total revenue KPIs, revenue per movie, daily chart,
 *        and a paginated bookings table with user info.
 *
 * Revenue = COUNT(seats in booking.seats) × movies.price
 * seats column stores comma-separated labels e.g. "A2,A3,D4"
 */
include("connect.php");
include("admin_header.php"); // uncomment if you have one

/* ═══════════════════════════════════════════════════════════
   FILTERS — date range from GET params
═══════════════════════════════════════════════════════════ */
$date_from = isset($_GET['from']) && $_GET['from'] !== '' ? $_GET['from'] : null;
$date_to   = isset($_GET['to'])   && $_GET['to']   !== '' ? $_GET['to']   : null;

// Build a reusable date WHERE fragment for bookings
$date_conditions = [];
$date_types      = '';
$date_params     = [];

if ($date_from) {
    $date_conditions[] = "b.booking_date >= ?";
    $date_types  .= 's';
    $date_params[] = $date_from;
}
if ($date_to) {
    $date_conditions[] = "b.booking_date <= ?";
    $date_types  .= 's';
    $date_params[] = $date_to;
}

$date_where = $date_conditions ? ('WHERE ' . implode(' AND ', $date_conditions)) : '';

/* ═══════════════════════════════════════════════════════════
   HELPER: count seats from comma-separated string
   "A2,A3,D4" → 3
═══════════════════════════════════════════════════════════ */
// We do this in SQL using (LENGTH(seats) - LENGTH(REPLACE(seats,',','')) + 1)
// which correctly handles single seats too.
$seat_count_expr = "(LENGTH(b.seats) - LENGTH(REPLACE(b.seats, ',', '')) + 1)";

/* ═══════════════════════════════════════════════════════════
   KPI 1 — Total revenue (all time, no date filter)
═══════════════════════════════════════════════════════════ */
$kpi_total_res = mysqli_query($con,
    "SELECT
        SUM((LENGTH(b.seats) - LENGTH(REPLACE(b.seats,',','')) + 1) * m.price) AS total_revenue,
        COUNT(b.bookingid)  AS total_bookings,
        SUM(LENGTH(b.seats) - LENGTH(REPLACE(b.seats,',','')) + 1) AS total_seats
     FROM booking b
     INNER JOIN movies m ON m.movieid = b.movieid"
);
$kpi_total = mysqli_fetch_assoc($kpi_total_res);
$all_time_revenue  = (float)($kpi_total['total_revenue']  ?? 0);
$all_time_bookings = (int)  ($kpi_total['total_bookings'] ?? 0);
$all_time_seats    = (int)  ($kpi_total['total_seats']    ?? 0);

/* ═══════════════════════════════════════════════════════════
   KPI 2 — Filtered period revenue (respects date range)
═══════════════════════════════════════════════════════════ */
if ($date_conditions) {
    $kpi_period_sql = "SELECT
        SUM((LENGTH(b.seats) - LENGTH(REPLACE(b.seats,',','')) + 1) * m.price) AS period_revenue,
        COUNT(b.bookingid) AS period_bookings
     FROM booking b
     INNER JOIN movies m ON m.movieid = b.movieid
     " . $date_where;
    $kpi_stmt = mysqli_prepare($con, $kpi_period_sql);
    mysqli_stmt_bind_param($kpi_stmt, $date_types, ...$date_params);
    mysqli_stmt_execute($kpi_stmt);
    $kpi_period = mysqli_fetch_assoc(mysqli_stmt_get_result($kpi_stmt));
    mysqli_stmt_close($kpi_stmt);
} else {
    $kpi_period = ['period_revenue' => $all_time_revenue, 'period_bookings' => $all_time_bookings];
}
$period_revenue  = (float)($kpi_period['period_revenue']  ?? 0);
$period_bookings = (int)  ($kpi_period['period_bookings'] ?? 0);

/* ═══════════════════════════════════════════════════════════
   KPI 3 — Most booked movie
═══════════════════════════════════════════════════════════ */
$top_movie_res = mysqli_query($con,
    "SELECT m.title, COUNT(b.bookingid) AS cnt
     FROM booking b
     INNER JOIN movies m ON m.movieid = b.movieid
     GROUP BY b.movieid ORDER BY cnt DESC LIMIT 1"
);
$top_movie = mysqli_fetch_assoc($top_movie_res);

/* ═══════════════════════════════════════════════════════════
   REVENUE PER MOVIE (filtered by date)
═══════════════════════════════════════════════════════════ */
if ($date_conditions) {
    $movie_rev_sql =
        "SELECT m.movieid, m.title, m.image, m.price,
                COUNT(b.bookingid) AS bookings,
                SUM(LENGTH(b.seats) - LENGTH(REPLACE(b.seats,',','')) + 1) AS seats_sold,
                SUM((LENGTH(b.seats) - LENGTH(REPLACE(b.seats,',','')) + 1) * m.price) AS revenue
         FROM booking b
         INNER JOIN movies m ON m.movieid = b.movieid
         " . $date_where . "
         GROUP BY b.movieid
         ORDER BY revenue DESC";
    $mr_stmt = mysqli_prepare($con, $movie_rev_sql);
    mysqli_stmt_bind_param($mr_stmt, $date_types, ...$date_params);
    mysqli_stmt_execute($mr_stmt);
    $movie_revenues = mysqli_fetch_all(mysqli_stmt_get_result($mr_stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($mr_stmt);
} else {
    $mr_res = mysqli_query($con,
        "SELECT m.movieid, m.title, m.image, m.price,
                COUNT(b.bookingid) AS bookings,
                SUM(LENGTH(b.seats) - LENGTH(REPLACE(b.seats,',','')) + 1) AS seats_sold,
                SUM((LENGTH(b.seats) - LENGTH(REPLACE(b.seats,',','')) + 1) * m.price) AS revenue
         FROM booking b
         INNER JOIN movies m ON m.movieid = b.movieid
         GROUP BY b.movieid
         ORDER BY revenue DESC"
    );
    $movie_revenues = mysqli_fetch_all($mr_res, MYSQLI_ASSOC);
}
$max_movie_revenue = !empty($movie_revenues) ? (float)$movie_revenues[0]['revenue'] : 1;

/* ═══════════════════════════════════════════════════════════
   DAILY REVENUE — last 14 days (or within filter range)
═══════════════════════════════════════════════════════════ */
if ($date_from || $date_to) {
    $daily_where   = $date_where;
    $daily_types   = $date_types;
    $daily_params  = $date_params;
} else {
    $daily_where   = "WHERE b.booking_date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)";
    $daily_types   = '';
    $daily_params  = [];
}

$daily_sql =
    "SELECT DATE(b.booking_date) AS day,
            SUM((LENGTH(b.seats) - LENGTH(REPLACE(b.seats,',','')) + 1) * m.price) AS rev
     FROM booking b
     INNER JOIN movies m ON m.movieid = b.movieid
     " . $daily_where . "
     GROUP BY DATE(b.booking_date)
     ORDER BY day ASC";

if ($daily_params) {
    $daily_stmt = mysqli_prepare($con, $daily_sql);
    mysqli_stmt_bind_param($daily_stmt, $daily_types, ...$daily_params);
    mysqli_stmt_execute($daily_stmt);
    $daily_rows = mysqli_fetch_all(mysqli_stmt_get_result($daily_stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($daily_stmt);
} else {
    $daily_rows = mysqli_fetch_all(mysqli_query($con, $daily_sql), MYSQLI_ASSOC);
}
$max_daily = 1;
foreach ($daily_rows as $dr) {
    if ((float)$dr['rev'] > $max_daily) $max_daily = (float)$dr['rev'];
}

/* ═══════════════════════════════════════════════════════════
   BOOKINGS TABLE — paginated, with user info
═══════════════════════════════════════════════════════════ */
$per_page    = 10   ;
$page        = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($page - 1) * $per_page;

// Count total rows for pagination
if ($date_conditions) {
    $count_sql  = "SELECT COUNT(*) FROM booking b INNER JOIN movies m ON m.movieid = b.movieid " . $date_where;
    $count_stmt = mysqli_prepare($con, $count_sql);
    mysqli_stmt_bind_param($count_stmt, $date_types, ...$date_params);
    mysqli_stmt_execute($count_stmt);
    $total_rows = (int) mysqli_fetch_row(mysqli_stmt_get_result($count_stmt))[0];
    mysqli_stmt_close($count_stmt);
} else {
    $total_rows = (int) mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM booking"))[0];
}
$total_pages = max(1, (int) ceil($total_rows / $per_page));

// Fetch paginated bookings
$bookings_select =
    "SELECT b.bookingid, b.booking_date, b.timing, b.seats, b.status,
            m.title AS movie_title, m.image AS movie_image, m.price,
            (LENGTH(b.seats) - LENGTH(REPLACE(b.seats,',','')) + 1) AS seat_count,
            (LENGTH(b.seats) - LENGTH(REPLACE(b.seats,',','')) + 1) * m.price AS booking_revenue,
            u.userid, u.name AS user_name, u.email AS user_email, u.profile_pic
     FROM booking b
     INNER JOIN movies m ON m.movieid = b.movieid
     LEFT JOIN users u ON u.userid = b.userid
     " . $date_where . "
     ORDER BY b.booking_date DESC, b.bookingid DESC
     LIMIT ? OFFSET ?";

// Merge params: date params + limit/offset
$tbl_types  = $date_types . 'ii';
$tbl_params = array_merge($date_params, [$per_page, $offset]);

$tbl_stmt = mysqli_prepare($con, $bookings_select);
mysqli_stmt_bind_param($tbl_stmt, $tbl_types, ...$tbl_params);
mysqli_stmt_execute($tbl_stmt);
$bookings = mysqli_fetch_all(mysqli_stmt_get_result($tbl_stmt), MYSQLI_ASSOC);
mysqli_stmt_close($tbl_stmt);

/* ═══════════════════════════════════════════════════════════
   HELPERS for output
═══════════════════════════════════════════════════════════ */
function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function fmt_money($n) {
    return 'Rs. ' . number_format((float)$n, 2);
}

function status_pill($status) {
    $s = strtolower(trim($status ?? ''));
    $map = [
        'confirmed' => ['confirmed', 'confirmed'],
        'pending'   => ['pending',   'pending'],
        'cancelled' => ['cancelled', 'cancelled'],
        'cancel'    => ['cancelled', 'cancelled'],
    ];
    [$cls, $label] = $map[$s] ?? ['pending', $s];
    $dot = ['confirmed' => '●', 'pending' => '◐', 'cancelled' => '○'][$cls] ?? '●';
    return '<span class="status-pill status-pill--' . $cls . '">' . $dot . ' ' . h($label) . '</span>';
}

// Build pagination query string (preserve filters)
function pagination_qs($page, $from, $to) {
    $q = ['page' => $page];
    if ($from) $q['from'] = $from;
    if ($to)   $q['to']   = $to;
    return '?' . http_build_query($q);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineXpress - Revenue Dashboard — Admin</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Revenue CSS -->
    <link rel="stylesheet" href="../css/revenue.css">
    <link rel="icon" type="image/png" href="../images/icon.ico">
</head>
<body>

<div class="rev-page">

    <!-- ══════════════════════════════════════════════════
         PAGE HEADER
    ══════════════════════════════════════════════════ -->
    <div class="rev-header">
        <div>
            <h1 class="rev-header__title">
                Revenue <span>Dashboard</span>
            </h1>
            <p class="rev-header__meta">
                <?= $date_from || $date_to
                    ? 'Filtered: ' . h($date_from ?: '∞') . ' → ' . h($date_to ?: 'today')
                    : 'All-time data · ' . date('d M Y') ?>
            </p>
        </div>

        <!-- Filter form -->
        <form class="rev-filters" method="get" action="">
            <span class="rev-filters__label"><i class="fa-solid fa-filter"></i> Filter</span>
            <div class="rev-filters__group">
                <input
                    type="date"
                    name="from"
                    class="rev-input"
                    value="<?= h($date_from ?? '') ?>"
                    title="From date"
                >
                <span style="color:var(--muted);font-size:.8rem;">→</span>
                <input
                    type="date"
                    name="to"
                    class="rev-input"
                    value="<?= h($date_to ?? '') ?>"
                    title="To date"
                >
                <button type="submit" class="rev-btn rev-btn--primary">
                    <i class="fa-solid fa-magnifying-glass"></i> Apply
                </button>
                <?php if ($date_from || $date_to): ?>
                <a href="revenue.php" class="rev-btn rev-btn--ghost">
                    <i class="fa-solid fa-xmark"></i> Clear
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>


    <!-- ══════════════════════════════════════════════════
         KPI CARDS
    ══════════════════════════════════════════════════ -->
    <div class="rev-kpis">

        <!-- Total all-time revenue -->
        <div class="kpi-card">
            <div class="kpi-card__icon"><i class="fa-solid fa-sack-dollar"></i></div>
            <p class="kpi-card__label">All-Time Revenue</p>
            <p class="kpi-card__value kpi-card__value--accent"><?= fmt_money($all_time_revenue) ?></p>
            <p class="kpi-card__sub"><?= number_format($all_time_seats) ?> seats sold total</p>
        </div>

        <!-- Period revenue (respects filter) -->
        <div class="kpi-card">
            <div class="kpi-card__icon"><i class="fa-solid fa-chart-line"></i></div>
            <p class="kpi-card__label"><?= ($date_from || $date_to) ? 'Period Revenue' : 'Total Revenue' ?></p>
            <p class="kpi-card__value kpi-card__value--accent"><?= fmt_money($period_revenue) ?></p>
            <p class="kpi-card__sub"><?= number_format($period_bookings) ?> bookings</p>
        </div>

        <!-- Total bookings -->
        <div class="kpi-card">
            <div class="kpi-card__icon"><i class="fa-solid fa-ticket"></i></div>
            <p class="kpi-card__label">Total Bookings</p>
            <p class="kpi-card__value"><?= number_format($all_time_bookings) ?></p>
            <p class="kpi-card__sub">across all movies</p>
        </div>

        <!-- Top movie -->
        <div class="kpi-card">
            <div class="kpi-card__icon"><i class="fa-solid fa-trophy"></i></div>
            <p class="kpi-card__label">Top Movie</p>
            <p class="kpi-card__value" style="font-size:1.1rem;font-family:'Syne',sans-serif;font-weight:700;">
                <?= $top_movie ? h($top_movie['title']) : '—' ?>
            </p>
            <p class="kpi-card__sub"><?= $top_movie ? number_format($top_movie['cnt']) . ' bookings' : 'No data' ?></p>
        </div>

    </div><!-- /.rev-kpis -->


    <!-- ══════════════════════════════════════════════════
         TWO-COLUMN: Movie Revenue List + Daily Chart
    ══════════════════════════════════════════════════ -->
    <div class="rev-grid">

        <!-- Revenue per movie -->
        <div class="rev-panel">
            <div class="rev-panel__head">
                <h2 class="rev-panel__title">
                    <i class="fa-solid fa-film"></i> Revenue by Movie
                </h2>
                <span class="rev-panel__badge"><?= count($movie_revenues) ?> movies</span>
            </div>

            <?php if (empty($movie_revenues)): ?>
                <div class="rev-empty">
                    <i class="fa-solid fa-film-slash"></i>
                    <p>No booking data found for this period.</p>
                </div>
            <?php else: ?>
                <ul class="movie-rev-list">
                    <?php foreach ($movie_revenues as $rank => $mr): ?>
                    <li class="movie-rev-item">
                        <span class="movie-rev-item__rank"><?= $rank + 1 ?></span>

                        <?php if ($mr['image']): ?>
                            <img
                                src="../admin/<?= h($mr['image']) ?>"
                                alt="<?= h($mr['title']) ?>"
                                class="movie-rev-item__poster"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="movie-rev-item__poster-placeholder">
                                <i class="fa-solid fa-film"></i>
                            </div>
                        <?php endif; ?>

                        <div class="movie-rev-item__info">
                            <p class="movie-rev-item__title"><?= h($mr['title']) ?></p>
                            <div class="movie-rev-item__meta">
                                <span><?= number_format((int)$mr['bookings']) ?> bookings</span>
                                <span><?= number_format((int)$mr['seats_sold']) ?> seats</span>
                            </div>
                        </div>

                        <div class="movie-rev-item__bar-wrap">
                            <div
                                class="movie-rev-item__bar"
                                style="width:<?= round(((float)$mr['revenue'] / $max_movie_revenue) * 100) ?>%"
                            ></div>
                        </div>

                        <span class="movie-rev-item__revenue"><?= fmt_money($mr['revenue']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div><!-- revenue per movie panel -->


        <!-- Daily revenue bar chart -->
        <div class="rev-panel">
            <div class="rev-panel__head">
                <h2 class="rev-panel__title">
                    <i class="fa-solid fa-chart-bar"></i>
                    Daily Revenue
                </h2>
                <span class="rev-panel__badge">
                    <?= ($date_from || $date_to) ? 'filtered range' : 'last 14 days' ?>
                </span>
            </div>

            <?php if (empty($daily_rows)): ?>
                <div class="rev-empty">
                    <i class="fa-solid fa-chart-bar"></i>
                    <p>No daily data for this period.</p>
                </div>
            <?php else: ?>
                <div class="daily-chart">
                    <div class="daily-chart__y-labels">
                        <span><?= fmt_money(0) ?></span>
                        <span><?= fmt_money($max_daily / 2) ?></span>
                        <span><?= fmt_money($max_daily) ?></span>
                    </div>
                    <div class="daily-chart__divider"></div>
                    <div class="daily-chart__bars">
                        <?php foreach ($daily_rows as $dr):
                            $pct  = round(((float)$dr['rev'] / $max_daily) * 100);
                            $lbl  = date('d M', strtotime($dr['day']));
                        ?>
                        <div class="daily-chart__col">
                            <div class="daily-chart__bar" style="height:<?= $pct ?>%">
                                <div class="daily-chart__tooltip"><?= fmt_money($dr['rev']) ?></div>
                            </div>
                            <span class="daily-chart__label"><?= h($lbl) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div><!-- daily chart panel -->

    </div><!-- /.rev-grid -->


    <!-- ══════════════════════════════════════════════════
         BOOKINGS TABLE (full width)
    ══════════════════════════════════════════════════ -->
    <div class="rev-panel rev-panel--full">
        <div class="rev-panel__head">
            <h2 class="rev-panel__title">
                <i class="fa-solid fa-list"></i> Booking Records
            </h2>
            <span class="rev-panel__badge"><?= number_format($total_rows) ?> total</span>
        </div>

        <?php if (empty($bookings)): ?>
            <div class="rev-empty">
                <i class="fa-solid fa-ticket"></i>
                <p>No bookings found for this period.</p>
            </div>
        <?php else: ?>

        <div class="rev-table-wrap">
            <table class="rev-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Movie</th>
                        <th>Date</th>
                        <th>Timing</th>
                        <th>Seats</th>
                        <th class="num">Seat Qty</th>
                        <th class="num">Price/Seat</th>
                        <th class="num">Revenue</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b):
                        $seats_arr = array_filter(array_map('trim', explode(',', $b['seats'] ?? '')));
                    ?>
                    <tr>
                        <!-- Booking ID -->
                        <td class="mono">#<?= h($b['bookingid']) ?></td>

                        <!-- User -->
                        <td>
                            <div class="tbl-user">
                                <?php if (!empty($b['profile_pic'])): ?>
                                    <img
                                        src="../uploads/avatars/<?= h($b['profile_pic']) ?>"
                                        alt="<?= h($b['user_name']) ?>"
                                        class="tbl-user__avatar"
                                    >
                                <?php else: ?>
                                    <div class="tbl-user__avatar-placeholder">
                                        <?= strtoupper(substr($b['user_name'] ?? 'U', 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="tbl-user__name"><?= h($b['user_name'] ?? 'Guest') ?></div>
                                    <div class="tbl-user__email"><?= h($b['user_email'] ?? '') ?></div>
                                </div>
                            </div>
                        </td>

                        <!-- Movie -->
                        <td>
                            <div class="tbl-movie">
                                <?php if (!empty($b['movie_image'])): ?>
                                    <img
                                        src="../admin/<?= h($b['movie_image']) ?>"
                                        alt="<?= h($b['movie_title']) ?>"
                                        class="tbl-movie__thumb"
                                    >
                                <?php else: ?>
                                    <div class="tbl-movie__thumb-placeholder">
                                        <i class="fa-solid fa-film"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="tbl-movie__title"><?= h($b['movie_title']) ?></span>
                            </div>
                        </td>

                        <!-- Date -->
                        <td class="mono"><?= h(date('d M Y', strtotime($b['booking_date']))) ?></td>

                        <!-- Timing -->
                        <td class="mono"><?= h($b['timing'] ?? '—') ?></td>

                        <!-- Seats chips -->
                        <td>
                            <div class="seats-wrap">
                                <?php foreach ($seats_arr as $seat): ?>
                                    <span class="seat-chip"><?= h(trim($seat)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>

                        <!-- Seat qty -->
                        <td class="num"><?= (int)$b['seat_count'] ?></td>

                        <!-- Price per seat -->
                        <td class="num"><?= fmt_money($b['price']) ?></td>

                        <!-- Revenue -->
                        <td class="num"><?= fmt_money($b['booking_revenue']) ?></td>

                        <!-- Status -->
                        <td><?= status_pill($b['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div><!-- /.rev-table-wrap -->

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="rev-pagination">
            <span class="rev-pagination__info">
                Showing <?= number_format(($page - 1) * $per_page + 1) ?>–<?= number_format(min($page * $per_page, $total_rows)) ?>
                of <?= number_format($total_rows) ?> bookings
            </span>
            <div class="rev-pagination__pages">
                <?php if ($page > 1): ?>
                    <a href="<?= pagination_qs($page - 1, $date_from, $date_to) ?>" class="rev-pagination__btn" aria-label="Previous">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                <?php endif; ?>

                <?php
                // Show up to 7 page buttons around current page
                $start_p = max(1, $page - 3);
                $end_p   = min($total_pages, $page + 3);
                for ($p = $start_p; $p <= $end_p; $p++):
                ?>
                    <a
                        href="<?= pagination_qs($p, $date_from, $date_to) ?>"
                        class="rev-pagination__btn <?= $p === $page ? 'is-active' : '' ?>"
                    ><?= $p ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="<?= pagination_qs($page + 1, $date_from, $date_to) ?>" class="rev-pagination__btn" aria-label="Next">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; // end bookings not empty ?>

    </div><!-- /.bookings panel -->

</div><!-- /.rev-page -->
<?php include("footer.php"); ?>

</body>
</html>