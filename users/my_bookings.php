<?php
session_start();
include("connect.php");
include("users_header.php");

// Auth guard
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userid = $_SESSION['userid'];
$msg = '';
$msgType = '';

// ── Cancel booking ──────────────────────────────────────────────────────────
if (isset($_POST['cancel_booking']) && isset($_POST['bookingid'])) {
    $bookingid = intval($_POST['bookingid']);

    // Only allow cancelling own confirmed/pending bookings
    $check = $con->prepare("SELECT bookingid, status, booking_date, timing FROM booking WHERE bookingid = ? AND userid = ?");
    $check->bind_param("ii", $bookingid, $userid);
    $check->execute();
    $result = $check->get_result();

    if ($row = $result->fetch_assoc()) {
        if (in_array($row['status'], ['confirmed', 'pending'])) {
            // Optional: block cancellation if show time has passed
            $showDateTime = $row['booking_date'] . ' ' . $row['timing'];
            if (strtotime($showDateTime) > time()) {
                $upd = $con->prepare("UPDATE booking SET status = 'cancelled' WHERE bookingid = ?");
                $upd->bind_param("i", $bookingid);
                $upd->execute();
                $msg = "Booking #" . str_pad($bookingid, 5, '0', STR_PAD_LEFT) . " has been cancelled successfully.";
                $msgType = 'success';
            } else {
                $msg = "This show has already started. Cancellation is no longer allowed.";
                $msgType = 'danger';
            }
        } else {
            $msg = "This booking cannot be cancelled (Status: " . ucfirst($row['status']) . ").";
            $msgType = 'danger';
        }
    } else {
        $msg = "Booking not found or access denied.";
        $msgType = 'danger';
    }
}

// ── Fetch bookings ──────────────────────────────────────────────────────────
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$allowed = ['all', 'confirmed', 'pending', 'cancelled', 'failed'];
if (!in_array($filter, $allowed)) $filter = 'all';

$query = "
    SELECT b.bookingid, b.booking_date, b.timing, b.seats, b.status,
           m.movieid, m.title, m.image, m.price, m.movie_type, m.rating
    FROM booking b
    JOIN movies m ON b.movieid = m.movieid
    WHERE b.userid = ?
";
$params = [$userid];
$types  = "i";

if ($filter !== 'all') {
    $query .= " AND b.status = ?";
    $params[] = $filter;
    $types   .= "s";
}

$query .= " ORDER BY b.booking_date DESC, b.timing DESC";

$stmt = $con->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Summary counts ──────────────────────────────────────────────────────────
$countStmt = $con->prepare("
    SELECT status, COUNT(*) as cnt 
    FROM booking 
    WHERE userid = ? 
    GROUP BY status
");
$countStmt->bind_param("i", $userid);
$countStmt->execute();
$countRes = $countStmt->get_result();
$counts = ['all' => 0, 'confirmed' => 0, 'pending' => 0, 'cancelled' => 0, 'failed' => 0];
while ($c = $countRes->fetch_assoc()) {
    $counts[$c['status']] = (int)$c['cnt'];
    $counts['all'] += (int)$c['cnt'];
}

// Helper
function seatList($seats) {
    return array_filter(array_map('trim', explode(',', $seats)));
}
function seatCount($seats) {
    return count(seatList($seats));
}
function totalPrice($price, $seats) {
    return number_format($price * seatCount($seats), 2);
}
function statusBadgeClass($status) {
    return match($status) {
        'confirmed' => 'badge-confirmed',
        'pending'   => 'badge-pending',
        'cancelled' => 'badge-cancelled',
        'failed'    => 'badge-failed',
        default     => ''
    };
}
function canCancel($status, $date, $time) {
    return in_array($status, ['confirmed', 'pending'])
        && strtotime($date . ' ' . $time) > time();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings — CineVerse</title>
    <link rel="stylesheet" href="../css/my_bookings.css">
        <link rel="icon" type="image/png" href="../images/icon.ico">

    <link rel="stylesheet" href="../css/ticket.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- ── Toast Notification ────────────────────────────────────────────────── -->
<?php if ($msg): ?>
<div class="toast toast--<?= $msgType ?>" id="toast">
    <i class="fa <?= $msgType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
    <span><?= htmlspecialchars($msg) ?></span>
    <button class="toast__close" onclick="this.parentElement.remove()"><i class="fa fa-xmark"></i></button>
</div>
<?php endif; ?>

<!-- ── Page Hero ─────────────────────────────────────────────────────────── -->
<section class="bookings-hero">
    <div class="hero-bg-text">TICKETS</div>
    <div class="container">
        <div class="hero-content">
            <p class="hero-eyebrow"><i class="fa fa-ticket"></i> Your Cinema Journey</p>
            <h1 class="hero-title">My Bookings</h1>
            <p class="hero-sub">All your reservations, past and present, in one place.</p>
        </div>

        <!-- Stats Strip -->
        <div class="stats-strip">
            <div class="stat-card">
                <span class="stat-num"><?= $counts['all'] ?></span>
                <span class="stat-label">Total</span>
            </div>
            <div class="stat-card stat-card--confirmed">
                <span class="stat-num"><?= $counts['confirmed'] ?></span>
                <span class="stat-label">Confirmed</span>
            </div>
            <div class="stat-card stat-card--pending">
                <span class="stat-num"><?= $counts['pending'] ?></span>
                <span class="stat-label">Pending</span>
            </div>
            <div class="stat-card stat-card--cancelled">
                <span class="stat-num"><?= $counts['cancelled'] ?></span>
                <span class="stat-label">Cancelled</span>
            </div>
        </div>
    </div>
</section>

<!-- ── Main Content ──────────────────────────────────────────────────────── -->
<main class="bookings-main">
    <div class="container">

        <!-- Filter Tabs -->
        <div class="filter-bar">
            <?php foreach (['all' => 'All', 'confirmed' => 'Confirmed', 'pending' => 'Pending', 'cancelled' => 'Cancelled', 'failed' => 'Failed'] as $key => $label): ?>
            <a href="?filter=<?= $key ?>" class="filter-tab <?= $filter === $key ? 'active' : '' ?>">
                <?= $label ?>
                <span class="filter-count"><?= $counts[$key] ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Bookings Grid -->
        <?php if (empty($bookings)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa fa-film"></i></div>
            <h2>No bookings found</h2>
            <p>Looks like you haven't booked any <?= $filter !== 'all' ? $filter : '' ?> tickets yet.</p>
            <a href="movies.php" class="btn-primary">
                <i class="fa fa-clapperboard"></i> Browse Movies
            </a>
        </div>
        <?php else: ?>
        <div class="bookings-grid" id="bookingsGrid">
            <?php foreach ($bookings as $i => $b):
                $seats     = seatList($b['seats']);
                $seatCnt   = count($seats);
                $total     = totalPrice($b['price'], $b['seats']);
                $badgeCls  = statusBadgeClass($b['status']);
                $showDate  = date('D, d M Y', strtotime($b['booking_date']));
                $showTime  = date('g:i A', strtotime($b['timing']));
                $isPast    = strtotime($b['booking_date'] . ' ' . $b['timing']) < time();
                $bookingNo = str_pad($b['bookingid'], 5, '0', STR_PAD_LEFT);
            ?>
            <article class="booking-card <?= $isPast ? 'booking-card--past' : '' ?>" 
                     style="animation-delay: <?= $i * 0.06 ?>s"
                     data-id="<?= $b['bookingid'] ?>">

                <!-- Poster Strip -->
                <div class="card-poster">
                    <?php if ($b['image']): ?>
                        <img src="../admin/<?= htmlspecialchars($b['image']) ?>" 
                             alt="<?= htmlspecialchars($b['title']) ?>">
                    <?php else: ?>
                        <div class="poster-placeholder"><i class="fa fa-film"></i></div>
                    <?php endif; ?>
                    <div class="card-poster__overlay"></div>
                    <span class="badge <?= $badgeCls ?>">
                        <?= ucfirst($b['status']) ?>
                    </span>
                    <?php if ($isPast && $b['status'] === 'confirmed'): ?>
                    <span class="badge-past">Watched</span>
                    <?php endif; ?>
                </div>

                <!-- Card Body -->
                <div class="card-body">
                    <div class="card-top">
                        <div>
                            <p class="booking-id">#<?= $bookingNo ?></p>
                            <h2 class="movie-title"><?= htmlspecialchars($b['title']) ?></h2>
                            <div class="movie-meta">
                                <?php if ($b['rating']): ?>
                                <span class="meta-tag"><i class="fa fa-star"></i> <?= htmlspecialchars($b['rating']) ?></span>
                                <?php endif; ?>
                                <?php if ($b['movie_type']): ?>
                                <span class="meta-tag"><i class="fa fa-film"></i> <?= htmlspecialchars($b['movie_type']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="detail-grid">
                        <div class="detail-item">
                            <i class="fa fa-calendar-days"></i>
                            <div>
                                <span class="detail-label">Date</span>
                                <span class="detail-value"><?= $showDate ?></span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fa fa-clock"></i>
                            <div>
                                <span class="detail-label">Showtime</span>
                                <span class="detail-value"><?= $showTime ?></span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fa fa-couch"></i>
                            <div>
                                <span class="detail-label">Seats (<?= $seatCnt ?>)</span>
                                <span class="detail-value seats-value"><?= implode(', ', $seats) ?></span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fa fa-tag"></i>
                            <div>
                                <span class="detail-label">Total</span>
                                <span class="detail-value detail-value--gold">Rs. <?= $total ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card-actions">
                        <!-- Print / Download Ticket -->
                        <?php if ($b['status'] === 'confirmed'): ?>
                        <button class="btn-action btn-ticket" 
        onclick="openTicketModal(<?= $b['bookingid'] ?>)">
    <i class="fa fa-print"></i> Print Ticket
</button>
                            
                        </a>
                        <?php endif; ?>

                        <!-- View Details toggle -->
                        <button class="btn-action btn-details" onclick="toggleDetails(this, <?= $b['bookingid'] ?>)">
                            <i class="fa fa-eye"></i> Details
                        </button>

                        <!-- Cancel -->
                        <?php if (canCancel($b['status'], $b['booking_date'], $b['timing'])): ?>
                        <button class="btn-action btn-cancel" 
                                onclick="openCancelModal(<?= $b['bookingid'] ?>, '<?= addslashes($b['title']) ?>', '<?= $bookingNo ?>')">
                            <i class="fa fa-ban"></i> Cancel
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Expandable Detail Panel -->
                    <div class="detail-panel" id="panel-<?= $b['bookingid'] ?>">
                        <div class="detail-panel__inner">
                            <div class="qr-mock">
                                <div class="qr-icon"><i class="fa fa-qrcode"></i></div>
                                <p>Booking Ref</p>
                                <strong>#<?= $bookingNo ?></strong>
                            </div>
                            <div class="panel-info">
                                <div class="panel-row">
                                    <span>Movie</span>
                                    <strong><?= htmlspecialchars($b['title']) ?></strong>
                                </div>
                                <div class="panel-row">
                                    <span>Date &amp; Time</span>
                                    <strong><?= $showDate ?>, <?= $showTime ?></strong>
                                </div>
                                <div class="panel-row">
                                    <span>Seats</span>
                                    <strong><?= implode(', ', $seats) ?></strong>
                                </div>
                                <div class="panel-row">
                                    <span>Price per Seat</span>
                                    <strong>Rs. <?= number_format($b['price'], 2) ?></strong>
                                </div>
                                <div class="panel-row panel-row--total">
                                    <span>Grand Total</span>
                                    <strong>Rs. <?= $total ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /card-body -->
            </article>
            <?php endforeach; ?>
        </div><!-- /bookings-grid -->
        <?php endif; ?>

    </div><!-- /container -->
</main>

<!-- ── Cancel Modal ──────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="cancelModal" onclick="closeCancelModal(event)">
    <div class="modal">
        <div class="modal-icon"><i class="fa fa-triangle-exclamation"></i></div>
        <h2 class="modal-title">Cancel Booking?</h2>
        <p class="modal-body" id="modalBody">Are you sure you want to cancel this booking?</p>
        <form method="POST" class="modal-actions">
            <input type="hidden" name="bookingid" id="modalBookingId">
            <button type="button" class="btn-action btn-details" onclick="closeCancelModal()">
                Keep It
            </button>
            <button type="submit" name="cancel_booking" class="btn-action btn-cancel">
                <i class="fa fa-ban"></i> Yes, Cancel
            </button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="ticketModal" onclick="closeTicketModal(event)">
    <div class="modal modal--large">
        <div id="ticketContent">
            <!-- Ticket will load here -->
        </div>

        <div class="modal-actions">
            <button onclick="printTicket()" class="btn-action btn-ticket">
                <i class="fa fa-print"></i> Print
            </button>
            <button onclick="closeTicketModal()" class="btn-action btn-details">
                Close
            </button>
        </div>
    </div>
</div>


<script>
    function openTicketModal(id) {
    fetch('ticket.php?id=' + id)
        .then(res => res.text())
        .then(data => {
            document.getElementById('ticketContent').innerHTML = data;
            document.getElementById('ticketModal').classList.add('active');
        });
}

function closeTicketModal(e) {
    if (!e || e.target.id === 'ticketModal') {
        document.getElementById('ticketModal').classList.remove('active');
    }
}
function printTicket() {
    const content = document.getElementById('ticketContent').innerHTML;
    const printWindow = window.open('', '', 'width=900,height=700');

    printWindow.document.write(`
        <html>
        <head>
            <title>Print Ticket</title>
            <link rel="stylesheet" href="../css/ticket.css">
        </head>
        <body>
            ${content}
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.print();
}

// Toast auto-dismiss
const toast = document.getElementById('toast');
if (toast) setTimeout(() => toast.classList.add('toast--hide'), 4000);

// Cancel modal
function openCancelModal(id, title, ref) {
    document.getElementById('modalBookingId').value = id;
    document.getElementById('modalBody').textContent =
        `Cancel booking #${ref} for "${title}"? This action cannot be undone.`;
    document.getElementById('cancelModal').classList.add('active');
}
function closeCancelModal(e) {
    if (!e || e.target === document.getElementById('cancelModal')) {
        document.getElementById('cancelModal').classList.remove('active');
    }
}

// Expand / collapse detail panel
function toggleDetails(btn, id) {
    const panel = document.getElementById('panel-' + id);
    const isOpen = panel.classList.toggle('open');
    btn.innerHTML = isOpen
        ? '<i class="fa fa-eye-slash"></i> Hide'
        : '<i class="fa fa-eye"></i> Details';
}

// Stagger card animations on load
document.querySelectorAll('.booking-card').forEach((card, i) => {
    card.style.opacity = 0;
    card.style.transform = 'translateY(24px)';
    setTimeout(() => {
        card.style.transition = 'opacity 0.45s ease, transform 0.45s ease';
        card.style.opacity = 1;
        card.style.transform = 'translateY(0)';
    }, 80 + i * 70);
});
</script>
<?php include("footer.php") ?>
</body>
</html>