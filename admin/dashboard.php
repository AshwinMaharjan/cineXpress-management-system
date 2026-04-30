<?php
include("connect.php");
include("admin_header.php");

// ── STAT CARDS ─────────────────────────────────────────────────────────────

// Total Movies
$total_movies = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS cnt FROM movies"))['cnt'];

// Total Users
$total_users = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS cnt FROM users"))['cnt'];

// Total Bookings
$total_bookings = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS cnt FROM booking"))['cnt'];

// Total Categories
$total_categories = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS cnt FROM category"))['cnt'];

// Total Revenue — count seats per booking (comma-separated like "A2,A3") × movie price
// We count seats by counting commas + 1 per booking row
$rev_result = mysqli_query($con, "
    SELECT b.seats, m.price
    FROM booking b
    LEFT JOIN movies m ON b.movieid = m.movieid
    WHERE b.status != 'cancelled'
");
$total_revenue = 0;
while ($r = mysqli_fetch_assoc($rev_result)) {
    if (!empty($r['seats']) && $r['price'] > 0) {
        $seat_count = count(array_filter(array_map('trim', explode(',', $r['seats']))));
        $total_revenue += $seat_count * $r['price'];
    }
}

// ── MONTHLY REVENUE (last 6 months) ────────────────────────────────────────
$monthly_query = mysqli_query($con, "
    SELECT
        DATE_FORMAT(b.booking_date, '%b %Y') AS month_label,
        DATE_FORMAT(b.booking_date, '%Y-%m') AS month_sort,
        b.seats, m.price
    FROM booking b
    LEFT JOIN movies m ON b.movieid = m.movieid
    WHERE b.status != 'cancelled'
      AND b.booking_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    ORDER BY month_sort ASC
");

$monthly_data = [];
while ($r = mysqli_fetch_assoc($monthly_query)) {
    $key = $r['month_label'];
    if (!isset($monthly_data[$key])) $monthly_data[$key] = 0;
    if (!empty($r['seats']) && $r['price'] > 0) {
        $seat_count = count(array_filter(array_map('trim', explode(',', $r['seats']))));
        $monthly_data[$key] += $seat_count * $r['price'];
    }
}
$chart_labels  = json_encode(array_keys($monthly_data));
$chart_values  = json_encode(array_values($monthly_data));

// ── RECENT BOOKINGS (5 rows) ────────────────────────────────────────────────
$recent_bookings = mysqli_query($con, "
    SELECT b.bookingid, b.booking_date, b.timing, b.seats, b.status,
           m.title AS movie_title, m.price,
           u.name  AS user_name
    FROM booking b
    LEFT JOIN movies m ON b.movieid = m.movieid
    LEFT JOIN users  u ON b.userid  = u.userid
    ORDER BY b.bookingid DESC
    LIMIT 5
");

// ── TOP 5 MOVIES BY BOOKINGS ────────────────────────────────────────────────
$top_movies = mysqli_query($con, "
    SELECT m.title, m.image, COUNT(b.bookingid) AS booking_count,
           SUM(
               (LENGTH(b.seats) - LENGTH(REPLACE(b.seats, ',', '')) + 1) * m.price
           ) AS movie_revenue
    FROM booking b
    LEFT JOIN movies m ON b.movieid = m.movieid
    WHERE b.status != 'cancelled'
    GROUP BY b.movieid
    ORDER BY booking_count DESC
    LIMIT 5
");

// Max bookings for bar scaling
$top_movies_arr = [];
while ($r = mysqli_fetch_assoc($top_movies)) $top_movies_arr[] = $r;
$max_bookings = !empty($top_movies_arr) ? max(array_column($top_movies_arr, 'booking_count')) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/admin_dashboard.css">
    <link rel="icon" type="image/png" href="images/icon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>

<div class="dash-wrap">

    <!-- ── PAGE HEADER ── -->
    <div class="dash-header">
        <div>
            <span class="dash-header__eyebrow">Overview</span>
            <h1 class="dash-header__title">Admin <span class="gold">Dashboard</span></h1>
            <p class="dash-header__sub">Everything at a glance — <?= date('l, d F Y') ?></p>
        </div>
        <a href="viewallbooking.php" class="dash-header__cta">
            <i class="fa fa-ticket"></i> View All Bookings
        </a>
    </div>

    <!-- ── STAT CARDS ── -->
    <div class="stat-grid">

        <div class="stat-card stat-card--gold" style="--delay:0s">
            <div class="stat-card__icon"><i class="fa fa-coins"></i></div>
            <div class="stat-card__body">
                <span class="stat-card__label">Total Revenue</span>
                <span class="stat-card__value">Rs. <?= number_format($total_revenue) ?></span>
            </div>
            <div class="stat-card__glow"></div>
        </div>

        <div class="stat-card" style="--delay:0.08s">
            <div class="stat-card__icon"><i class="fa fa-ticket"></i></div>
            <div class="stat-card__body">
                <span class="stat-card__label">Total Bookings</span>
                <span class="stat-card__value"><?= number_format($total_bookings) ?></span>
            </div>
        </div>

        <div class="stat-card" style="--delay:0.16s">
            <div class="stat-card__icon"><i class="fa fa-film"></i></div>
            <div class="stat-card__body">
                <span class="stat-card__label">Total Movies</span>
                <span class="stat-card__value"><?= number_format($total_movies) ?></span>
            </div>
        </div>

        <div class="stat-card" style="--delay:0.24s">
            <div class="stat-card__icon"><i class="fa fa-users"></i></div>
            <div class="stat-card__body">
                <span class="stat-card__label">Registered Users</span>
                <span class="stat-card__value"><?= number_format($total_users) ?></span>
            </div>
        </div>

        <div class="stat-card" style="--delay:0.32s">
            <div class="stat-card__icon"><i class="fa fa-layer-group"></i></div>
            <div class="stat-card__body">
                <span class="stat-card__label">Categories</span>
                <span class="stat-card__value"><?= number_format($total_categories) ?></span>
            </div>
        </div>

    </div><!-- /.stat-grid -->

    <!-- ── MAIN GRID: Chart + Top Movies ── -->
    <div class="main-grid">

        <!-- Revenue Chart -->
        <div class="panel panel--chart">
            <div class="panel__head">
                <div>
                    <h2 class="panel__title">Monthly Revenue</h2>
                    <p class="panel__sub">Last 6 months — confirmed bookings only</p>
                </div>
                <span class="panel__badge"><i class="fa fa-chart-line"></i> Trend</span>
            </div>
            <div class="chart-wrap">
                <?php if (empty($monthly_data)): ?>
                    <div class="empty-state"><i class="fa fa-chart-line"></i><p>No revenue data yet.</p></div>
                <?php else: ?>
                    <canvas id="revenueChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Movies -->
        <div class="panel panel--top">
            <div class="panel__head">
                <div>
                    <h2 class="panel__title">Top Movies</h2>
                    <p class="panel__sub">By number of bookings</p>
                </div>
                <span class="panel__badge"><i class="fa fa-fire"></i> Hot</span>
            </div>

            <?php if (empty($top_movies_arr)): ?>
                <div class="empty-state"><i class="fa fa-film"></i><p>No data yet.</p></div>
            <?php else: ?>
            <div class="top-movies">
                <?php foreach ($top_movies_arr as $i => $movie): ?>
                <?php $pct = $max_bookings > 0 ? round(($movie['booking_count'] / $max_bookings) * 100) : 0; ?>
                <div class="top-movie-row" style="--bar-pct: <?= $pct ?>%; --bar-delay: <?= $i * 0.1 ?>s">
                    <div class="top-movie-row__rank"><?= $i + 1 ?></div>
                    <div class="top-movie-row__thumb">
                        <?php if ($movie['image']): ?>
                            <img src="<?= htmlspecialchars($movie['image']) ?>" alt="">
                        <?php else: ?>
                            <div class="thumb-placeholder"><i class="fa fa-film"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="top-movie-row__info">
                        <span class="top-movie-row__title"><?= htmlspecialchars($movie['title']) ?></span>
                        <div class="top-movie-row__bar-wrap">
                            <div class="top-movie-row__bar"></div>
                        </div>
                    </div>
                    <div class="top-movie-row__count"><?= $movie['booking_count'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /.main-grid -->

    <!-- ── RECENT BOOKINGS ── -->
    <div class="panel panel--table">
        <div class="panel__head">
            <div>
                <h2 class="panel__title">Recent Bookings</h2>
                <p class="panel__sub">Latest 5 reservations</p>
            </div>
            <a href="viewallbooking.php" class="panel__link">View all <i class="fa fa-arrow-right"></i></a>
        </div>

        <?php
        $rows = [];
        while ($r = mysqli_fetch_assoc($recent_bookings)) $rows[] = $r;
        ?>

        <?php if (empty($rows)): ?>
            <div class="empty-state"><i class="fa fa-ticket"></i><p>No bookings yet.</p></div>
        <?php else: ?>
        <div class="rb-table-wrap">
            <table class="rb-table">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Movie</th>
                        <th>User</th>
                        <th>Date</th>
                        <th>Timing</th>
                        <th>Seats</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row):
                    $seat_arr   = array_filter(array_map('trim', explode(',', $row['seats'])));
                    $seat_count = count($seat_arr);
                    $amount     = $seat_count * (int)($row['price'] ?? 0);
                ?>
                <tr>
                    <td class="td-id">#<?= $row['bookingid'] ?></td>
                    <td><?= htmlspecialchars($row['movie_title'] ?? 'N/A') ?></td>
                    <td>
                        <div class="user-mini">
                            <div class="user-mini__av"><?= strtoupper(substr($row['user_name'] ?? 'U', 0, 1)) ?></div>
                            <?= htmlspecialchars($row['user_name'] ?? 'N/A') ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($row['booking_date']) ?></td>
                    <td><span class="timing-pill"><i class="fa fa-clock"></i> <?= htmlspecialchars($row['timing']) ?></span></td>
                    <td><span class="seats-pill"><?= htmlspecialchars($row['seats']) ?></span></td>
                    <td class="td-amount">Rs. <?= number_format($amount) ?></td>
                    <td><span class="status-badge status-badge--<?= strtolower($row['status']) ?>"><?= ucfirst($row['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /.dash-wrap -->

<script>
<?php if (!empty($monthly_data)): ?>
const ctx = document.getElementById('revenueChart').getContext('2d');

const gradient = ctx.createLinearGradient(0, 0, 0, 320);
gradient.addColorStop(0,   'rgba(212, 175, 55, 0.35)');
gradient.addColorStop(1,   'rgba(212, 175, 55, 0.0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= $chart_labels ?>,
        datasets: [{
            label: 'Revenue (Rs.)',
            data: <?= $chart_values ?>,
            borderColor: '#D4AF37',
            borderWidth: 2.5,
            pointBackgroundColor: '#D4AF37',
            pointBorderColor: '#0B0B0F',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            fill: true,
            backgroundColor: gradient,
            tension: 0.45,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1C1C24',
                borderColor: 'rgba(255,255,255,0.08)',
                borderWidth: 1,
                titleColor: '#F5F5F5',
                bodyColor: '#D4AF37',
                titleFont: { family: "'DM Sans', sans-serif", size: 12 },
                bodyFont:  { family: "'DM Sans', sans-serif", size: 14, weight: '500' },
                callbacks: {
                    label: ctx => ' Rs. ' + ctx.parsed.y.toLocaleString()
                }
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { color: 'rgba(245,245,245,0.5)', font: { family: "'DM Sans'", size: 12 } },
                border: { color: 'rgba(255,255,255,0.08)' }
            },
            y: {
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: {
                    color: 'rgba(245,245,245,0.5)',
                    font: { family: "'DM Sans'", size: 12 },
                    callback: v => 'Rs. ' + v.toLocaleString()
                },
                border: { color: 'rgba(255,255,255,0.08)' }
            }
        }
    }
});
<?php endif; ?>
</script>
<?php include("footer.php")?>
</body>
</html>