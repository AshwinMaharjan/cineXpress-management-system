<?php
include("connect.php");

// ── Auth guard ───────────────────────────────────────────────
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$uid       = $_SESSION['userid'];
$bookingid = isset($_GET['bookingid']) ? intval($_GET['bookingid']) : 0;

if (!$bookingid) {
    header("Location: index.php");
    exit();
}

// ── Fetch booking (must belong to this user & be pending) ────
$booking = null;
$stmt = $con->prepare(
    "SELECT b.*, m.title, m.image, m.rating, m.movie_type
     FROM booking b
     JOIN movies m ON m.movieid = b.movieid
     WHERE b.bookingid = ? AND b.userid = ? AND b.status = 'pending'"
);
$stmt->bind_param("ii", $bookingid, $uid);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    header("Location: index.php");
    exit();
}

// ── Seats & Pricing ──────────────────────────────────────────
$seats_arr      = array_filter(array_map('trim', explode(',', $booking['seats'])));
$seat_count     = count($seats_arr);
$price_per_seat = 350; // Rs. — adjust as needed
$total_amount   = $seat_count * $price_per_seat;

// ── Handle form submission ────────────────────────────────────
$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $method = $_POST['payment_method'] ?? '';

    if (!in_array($method, ['esewa', 'cod'])) {
        $error_msg = "Please select a valid payment method.";
    } else {

        // ── 1. Insert row into payments ──────────────────────
        $transaction_id = null;
        $pay_status     = ($method === 'cod') ? 'completed' : 'pending';

        $stmt = $con->prepare(
            "INSERT INTO payments (bookingid, userid, amount, payment_method, transaction_id, status)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iidsss", $bookingid, $uid, $total_amount, $method, $transaction_id, $pay_status);
        $pay_inserted = $stmt->execute();
        $stmt->close();

        if (!$pay_inserted) {
            $error_msg = "Payment processing failed. Please try again.";
        } else {

            if ($method === 'cod') {
                // ── 2a. COD: confirm booking immediately ─────
                $stmt = $con->prepare(
                    "UPDATE booking SET status = 'confirmed' WHERE bookingid = ? AND userid = ?"
                );
                $stmt->bind_param("ii", $bookingid, $uid);
                $stmt->execute();
                $stmt->close();
                $success_msg = "confirmed";

            } else {
                // ── 2b. eSewa: build UUID, store it, redirect ─
                // TODO: Replace EPAYTEST with your real merchant code for production
                $esewa_product_code = "EPAYTEST";
                $esewa_url          = "https://rc-epay.esewa.com.np/api/epay/main/v2/form";
                // Production URL: https://epay.esewa.com.np/api/epay/main/v2/form

                $transaction_uuid = 'BK-' . $bookingid . '-' . time();
                $base             = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                $success_url      = $base . '/esewa_callback.php?bookingid=' . $bookingid;
                $failure_url      = $base . '/payment.php?bookingid=' . $bookingid . '&esewa_failed=1';

                // Store UUID so callback can verify & match
                $stmt = $con->prepare(
                    "UPDATE payments SET transaction_id = ?
                     WHERE bookingid = ? AND userid = ?
                     ORDER BY payment_id DESC LIMIT 1"
                );
                $stmt->bind_param("sii", $transaction_uuid, $bookingid, $uid);
                $stmt->execute();
                $stmt->close();
                ?>
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Redirecting to eSewa…</title>
                    <style>
                        body { background:#0B0B0F; color:#888; font-family:sans-serif;
                               display:flex; align-items:center; justify-content:center;
                               min-height:100vh; margin:0; }
                        p { font-size:1rem; }
                    </style>
                </head>
                <body>
                    <p>Redirecting to eSewa gateway…</p>
                    <form id="esewaForm" action="<?= htmlspecialchars($esewa_url) ?>" method="POST">
                        <input type="hidden" name="amount"                       value="<?= $total_amount ?>">
                        <input type="hidden" name="tax_amount"                   value="0">
                        <input type="hidden" name="total_amount"                 value="<?= $total_amount ?>">
                        <input type="hidden" name="transaction_uuid"             value="<?= htmlspecialchars($transaction_uuid) ?>">
                        <input type="hidden" name="product_code"                 value="<?= htmlspecialchars($esewa_product_code) ?>">
                        <input type="hidden" name="product_service_charge"       value="0">
                        <input type="hidden" name="product_delivery_charge"      value="0">
                        <input type="hidden" name="success_url"                  value="<?= htmlspecialchars($success_url) ?>">
                        <input type="hidden" name="failure_url"                  value="<?= htmlspecialchars($failure_url) ?>">
                        <input type="hidden" name="signed_field_names"           value="total_amount,transaction_uuid,product_code">
                        <!-- Generate a real HMAC-SHA256 signature for production -->
                        <input type="hidden" name="signature"                    value="">
                    </form>
                    <script>document.getElementById('esewaForm').submit();</script>
                </body>
                </html>
                <?php
                exit();
            }
        }
    }
}

// ── eSewa failure param ───────────────────────────────────────
if (isset($_GET['esewa_failed']) && empty($error_msg)) {
    $error_msg = "eSewa payment was cancelled or failed. Please try again.";
}

// ── Safe variables ────────────────────────────────────────────
$movie_title  = htmlspecialchars($booking['title']        ?? '', ENT_QUOTES, 'UTF-8');
$movie_image  = htmlspecialchars($booking['image']        ?? '', ENT_QUOTES, 'UTF-8');
$movie_rating = htmlspecialchars($booking['rating']       ?? '', ENT_QUOTES, 'UTF-8');
$movie_type   = htmlspecialchars($booking['movie_type']   ?? '', ENT_QUOTES, 'UTF-8');
$timing       = htmlspecialchars($booking['timing']       ?? '', ENT_QUOTES, 'UTF-8');
$book_date    = htmlspecialchars($booking['booking_date'] ?? '', ENT_QUOTES, 'UTF-8');
$booking_ref  = '#' . str_pad($bookingid, 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment — <?= $movie_title ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/icon.ico">

    <link rel="stylesheet" href="css/payment.css">
</head>
<body>
<?php include("header.php"); ?>

<main class="payment-page">

    <div class="ambient-orb ambient-orb--1"></div>
    <div class="ambient-orb ambient-orb--2"></div>

    <?php if ($success_msg === 'confirmed'): ?>
    <!-- ══════════════════════════════════════════════════════ -->
    <!--  SUCCESS STATE                                         -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div class="success-wrapper">
        <div class="success-card">

            <div class="success-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <h1 class="success-title">Booking Confirmed!</h1>
            <p class="success-sub">Your seats are locked in. See you at the cinema!</p>

            <!-- Ticket Stub -->
            <div class="ticket-stub">

                <div class="stub-film">
                    <?php if ($movie_image): ?>
                    <img src="admin/<?= $movie_image ?>" alt="<?= $movie_title ?>">
                    <?php endif; ?>
                    <div class="stub-film__info">
                        <div class="stub-movie-title"><?= $movie_title ?></div>
                        <?php if ($movie_type): ?>
                        <span class="stub-badge"><?= $movie_type ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="stub-divider">
                    <span></span>
                    <i class="fa-solid fa-scissors"></i>
                    <span></span>
                </div>

                <div class="stub-details">
                    <div class="stub-row">
                        <span><i class="fa-regular fa-calendar"></i> Date</span>
                        <strong><?= $book_date ?></strong>
                    </div>
                    <div class="stub-row">
                        <span><i class="fa-regular fa-clock"></i> Show</span>
                        <strong><?= $timing ?></strong>
                    </div>
                    <div class="stub-row">
                        <span><i class="fa-solid fa-couch"></i> Seats</span>
                        <strong class="seats-list"><?= implode(', ', $seats_arr) ?></strong>
                    </div>
                    <div class="stub-row">
                        <span><i class="fa-solid fa-money-bill-wave"></i> Payment</span>
                        <strong>Cash on Delivery</strong>
                    </div>
                    <div class="stub-row">
                        <span><i class="fa-solid fa-receipt"></i> Ref</span>
                        <strong class="mono"><?= $booking_ref ?></strong>
                    </div>
                    <div class="stub-row stub-row--total">
                        <span>Total</span>
                        <strong class="gold">Rs. <?= number_format($total_amount) ?></strong>
                    </div>
                </div>

            </div><!-- /.ticket-stub -->

            <div class="success-actions">
                <a href="my_bookings.php" class="btn-secondary">
                    <i class="fa-solid fa-ticket"></i> My Bookings
                </a>
                <a href="index.php" class="btn-home">
                    <i class="fa-solid fa-house"></i> Home
                </a>
            </div>

        </div>
    </div>

    <?php else: ?>
    <!-- ══════════════════════════════════════════════════════ -->
    <!--  PAYMENT FORM                                          -->
    <!-- ══════════════════════════════════════════════════════ -->

    <div class="payment-topbar">
        <a href="javascript:history.back()" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <div class="payment-topbar__title">
            <i class="fa-solid fa-lock"></i>
            Secure Checkout
        </div>
    </div>

    <?php if (!empty($error_msg)): ?>
    <div class="alert alert--error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= htmlspecialchars($error_msg) ?>
    </div>
    <?php endif; ?>

    <div class="payment-layout">

        <!-- ── LEFT: ORDER SUMMARY ──────────────────────── -->
        <aside class="order-panel">

            <div class="order-panel__header">
                <i class="fa-solid fa-film"></i>
                Order Summary
            </div>

            <?php if ($movie_image): ?>
            <div class="order-poster">
                <img src="admin/<?= $movie_image ?>" alt="<?= $movie_title ?>">
                <div class="order-poster__overlay">
                    <?php if ($movie_rating): ?>
                    <div class="order-rating">
                        <i class="fa-solid fa-star"></i> <?= $movie_rating ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="order-details">
                <h2 class="order-movie-title"><?= $movie_title ?></h2>
                <?php if ($movie_type): ?>
                <span class="order-badge"><?= $movie_type ?></span>
                <?php endif; ?>

                <div class="order-rows">
                    <div class="order-row">
                        <span><i class="fa-regular fa-calendar"></i> Date</span>
                        <strong><?= $book_date ?></strong>
                    </div>
                    <div class="order-row">
                        <span><i class="fa-regular fa-clock"></i> Show</span>
                        <strong><?= $timing ?></strong>
                    </div>
                    <div class="order-row">
                        <span><i class="fa-solid fa-couch"></i> Seats</span>
                        <strong class="seats-wrap"><?= implode(', ', $seats_arr) ?></strong>
                    </div>
                    <div class="order-row">
                        <span><i class="fa-solid fa-ticket"></i> Count</span>
                        <strong><?= $seat_count ?> seat<?= $seat_count > 1 ? 's' : '' ?></strong>
                    </div>
                    <div class="order-row">
                        <span>Price / seat</span>
                        <strong>Rs. <?= number_format($price_per_seat) ?></strong>
                    </div>
                </div>

                <div class="order-total">
                    <span>Total Amount</span>
                    <span class="order-total__amount">Rs. <?= number_format($total_amount) ?></span>
                </div>

                <div class="booking-ref">
                    <i class="fa-solid fa-hashtag"></i>
                    Booking ref: <span class="mono"><?= $booking_ref ?></span>
                </div>
            </div>

        </aside>

        <!-- ── RIGHT: PAYMENT PANEL ─────────────────────── -->
        <section class="payment-panel">

            <h2 class="payment-panel__heading">Payment Method</h2>
            <p class="payment-panel__sub">Choose how you'd like to complete your booking.</p>

            <form class="payment-form" method="post" action="">
                <input type="hidden" name="bookingid" value="<?= $bookingid ?>">

                <!-- Method Cards -->
                <div class="method-grid">

                    <!-- eSewa -->
                    <label class="method-card" for="esewa">
                        <input type="radio" name="payment_method" id="esewa" value="esewa" required>
                        <div class="method-card__inner">
                            <div class="method-icon method-icon--esewa">
                                <svg width="28" height="28" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="30" cy="30" r="30" fill="#60BB46"/>
                                    <text x="50%" y="57%" dominant-baseline="middle" text-anchor="middle"
                                          font-family="Arial Black,Arial" font-weight="900"
                                          font-size="14" fill="white">eS</text>
                                </svg>
                            </div>
                            <div class="method-info">
                                <div class="method-name">eSewa</div>
                                <div class="method-desc">Digital wallet · Instant</div>
                            </div>
                            <div class="method-check">
                                <i class="fa-solid fa-check"></i>
                            </div>
                        </div>
                    </label>

                    <!-- COD -->
                    <label class="method-card" for="cod">
                        <input type="radio" name="payment_method" id="cod" value="cod" required>
                        <div class="method-card__inner">
                            <div class="method-icon method-icon--cod">
                                <i class="fa-solid fa-money-bill-wave"></i>
                            </div>
                            <div class="method-info">
                                <div class="method-name">Cash on Delivery</div>
                                <div class="method-desc">Pay at the counter</div>
                            </div>
                            <div class="method-check">
                                <i class="fa-solid fa-check"></i>
                            </div>
                        </div>
                    </label>

                </div><!-- /.method-grid -->

                <!-- eSewa Info Notice -->
                <div class="method-notice esewa-notice" id="esewaNotice">
                    <i class="fa-solid fa-circle-info"></i>
                    <div>
                        <strong>You'll be redirected to eSewa</strong>
                        <p>Complete payment on eSewa's secure gateway. Don't close the tab until done.</p>
                    </div>
                </div>

                <!-- COD Info Notice -->
                <div class="method-notice cod-notice" id="codNotice">
                    <i class="fa-solid fa-circle-info"></i>
                    <div>
                        <strong>Pay at the cinema counter</strong>
                        <p>Arrive 15 minutes early and show booking ref
                            <span class="mono"><?= $booking_ref ?></span> at the desk.</p>
                    </div>
                </div>

                <!-- Security Note -->
                <div class="security-note">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>All transactions are encrypted with 256-bit SSL.</span>
                </div>

                <!-- Amount Bar -->
                <div class="amount-bar">
                    <div class="amount-bar__label">
                        <i class="fa-solid fa-ticket"></i>
                        <?= $seat_count ?> seat<?= $seat_count > 1 ? 's' : '' ?> ×
                        Rs. <?= number_format($price_per_seat) ?>
                    </div>
                    <div class="amount-bar__total">
                        Rs. <?= number_format($total_amount) ?>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="confirm_payment" class="pay-btn" id="payBtn">
                    <span class="pay-btn__label">
                        <i class="fa-solid fa-lock" id="payIcon"></i>
                        <span id="payText">Confirm &amp; Pay · Rs. <?= number_format($total_amount) ?></span>
                    </span>
                    <span class="pay-btn__shine"></span>
                </button>

            </form>

        </section>

    </div><!-- /.payment-layout -->
    <?php endif; ?>

</main>

<?php include("footer.php"); ?>

<script>
const radios      = document.querySelectorAll('input[name="payment_method"]');
const codNotice   = document.getElementById('codNotice');
const esewaNotice = document.getElementById('esewaNotice');
const payText     = document.getElementById('payText');
const payIcon     = document.getElementById('payIcon');
const totalStr    = 'Rs. <?= number_format($total_amount) ?>';

function updateUI(val) {
    codNotice.classList.remove('visible');
    esewaNotice.classList.remove('visible');

    if (val === 'cod') {
        codNotice.classList.add('visible');
        payText.textContent = 'Confirm Booking · Pay at Counter';
        payIcon.className   = 'fa-solid fa-circle-check';
    } else if (val === 'esewa') {
        esewaNotice.classList.add('visible');
        payText.textContent = 'Pay via eSewa · ' + totalStr;
        payIcon.className   = 'fa-solid fa-arrow-right';
    }
}

radios.forEach(r => r.addEventListener('change', () => updateUI(r.value)));

// Prevent double-submit
document.querySelector('.payment-form').addEventListener('submit', function () {
    const btn = document.getElementById('payBtn');
    btn.disabled           = true;
    btn.style.opacity      = '0.7';
    payText.textContent    = 'Processing…';
    payIcon.className      = 'fa-solid fa-spinner fa-spin';
});
</script>

</body>
</html>