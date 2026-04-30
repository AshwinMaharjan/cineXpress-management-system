<?php
session_start();
include("connect.php");

if (!isset($_SESSION['userid'])) {
    http_response_code(403);
    echo '<p>Unauthorized</p>';
    exit();
}

$userid    = $_SESSION['userid'];
$bookingid = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$bookingid) {
    echo '<p>Invalid booking.</p>';
    exit();
}

$stmt = $con->prepare("
    SELECT b.bookingid, b.booking_date, b.timing, b.seats, b.status,
           m.title, m.image, m.price, m.movie_type, m.rating, m.description
    FROM booking b
    JOIN movies m ON b.movieid = m.movieid
    WHERE b.bookingid = ? AND b.userid = ?
");
$stmt->bind_param("ii", $bookingid, $userid);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();

if (!$b) {
    echo '<p>Booking not found.</p>';
    exit();
}

$seats     = array_filter(array_map('trim', explode(',', $b['seats'])));
$seatCnt   = count($seats);
$total     = number_format($b['price'] * $seatCnt, 2);
$bookingNo = str_pad($b['bookingid'], 5, '0', STR_PAD_LEFT);
$showDate  = date('l, d F Y', strtotime($b['booking_date']));
$showTime  = date('g:i A', strtotime($b['timing']));
?>
<div class="ticket-wrap">

    <!-- Left: Poster -->
    <div class="ticket-poster">
        <?php if ($b['image']): ?>
            <img src="../admin/<?= htmlspecialchars($b['image']) ?>" alt="<?= htmlspecialchars($b['title']) ?>">
        <?php else: ?>
            <div class="ticket-poster__placeholder"><i class="fa fa-film"></i></div>
        <?php endif; ?>
        <div class="ticket-poster__gradient"></div>

        <div class="ticket-poster__meta">
            <?php if ($b['rating']): ?>
            <span class="ticket-meta-pill"><i class="fa fa-star"></i> <?= htmlspecialchars($b['rating']) ?></span>
            <?php endif; ?>
            <?php if ($b['movie_type']): ?>
            <span class="ticket-meta-pill"><?= htmlspecialchars($b['movie_type']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Details -->
    <div class="ticket-details">

        <div class="ticket-header">
            <p class="ticket-ref">Booking Ref &nbsp;·&nbsp; <strong>#<?= $bookingNo ?></strong></p>
            <h2 class="ticket-title"><?= htmlspecialchars($b['title']) ?></h2>
        </div>

        <div class="ticket-divider">
            <span class="ticket-divider__notch ticket-divider__notch--left"></span>
            <span class="ticket-divider__line"></span>
            <span class="ticket-divider__notch ticket-divider__notch--right"></span>
        </div>

        <div class="ticket-info-grid">
            <div class="ticket-info-item">
                <span class="info-label"><i class="fa fa-calendar-days"></i> Date</span>
                <span class="info-value"><?= $showDate ?></span>
            </div>
            <div class="ticket-info-item">
                <span class="info-label"><i class="fa fa-clock"></i> Showtime</span>
                <span class="info-value"><?= $showTime ?></span>
            </div>
            <div class="ticket-info-item ticket-info-item--full">
                <span class="info-label"><i class="fa fa-couch"></i> Seats &nbsp;<em>(<?= $seatCnt ?>)</em></span>
                <span class="info-value seats-chips">
                    <?php foreach ($seats as $seat): ?>
                    <span class="seat-chip"><?= htmlspecialchars($seat) ?></span>
                    <?php endforeach; ?>
                </span>
            </div>
        </div>

        <div class="ticket-divider">
            <span class="ticket-divider__notch ticket-divider__notch--left"></span>
            <span class="ticket-divider__line"></span>
            <span class="ticket-divider__notch ticket-divider__notch--right"></span>
        </div>

        <!-- Price Breakdown -->
        <div class="ticket-pricing">
            <div class="pricing-row">
                <span>Price per seat</span>
                <span>Rs. <?= number_format($b['price'], 2) ?></span>
            </div>
            <div class="pricing-row">
                <span>Seats</span>
                <span>× <?= $seatCnt ?></span>
            </div>
            <div class="pricing-row pricing-row--total">
                <span>Total</span>
                <span>Rs. <?= $total ?></span>
            </div>
        </div>

        <div class="ticket-status-bar status--<?= $b['status'] ?>">
            <i class="fa fa-circle-check"></i>
            <?= strtoupper($b['status']) ?>
        </div>

    </div><!-- /ticket-details -->
</div><!-- /ticket-wrap -->