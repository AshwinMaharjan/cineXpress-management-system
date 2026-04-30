<?php
session_start();
include("connect.php");

// ── Auth guard ───────────────────────────────────────────────
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$uid = (int)$_SESSION['userid'];

// ── eSewa credentials (must match booking_esewa_payment.php) ──
$merchant_code = "EPAYTEST";
$secret_key    = "8gBm/:&EnhH.1/q";

$error_message = '';
$esewa_data    = [];
$bookingid     = null;

/* ══════════════════════════════════════════════════════
   1. DECODE eSewa BASE64 RESPONSE
   ══════════════════════════════════════════════════════ */
if (empty($_GET['data'])) {
    $error_message = "No payment response received from eSewa.";
} else {
    $decoded = base64_decode($_GET['data'], true);
    if ($decoded === false) {
        $error_message = "Invalid payment response (decode failed).";
    } else {
        $esewa_data = json_decode($decoded, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($esewa_data)) {
            $error_message = "Invalid payment response (JSON parse failed).";
        }
    }
}

/* ══════════════════════════════════════════════════════
   2. VERIFY HMAC-SHA256 SIGNATURE
   ══════════════════════════════════════════════════════ */
if (empty($error_message)) {
    $signed_fields = isset($esewa_data['signed_field_names'])
        ? explode(',', $esewa_data['signed_field_names'])
        : [];

    $message_parts = [];
    foreach ($signed_fields as $field) {
        $field = trim($field);
        if (!isset($esewa_data[$field])) {
            $error_message = "Signature field '{$field}' missing from eSewa response.";
            break;
        }
        $message_parts[] = "{$field}={$esewa_data[$field]}";
    }

    if (empty($error_message)) {
        $message            = implode(',', $message_parts);
        $expected_signature = base64_encode(hash_hmac('sha256', $message, $secret_key, true));
        $received_signature = $esewa_data['signature'] ?? '';

        if (!hash_equals($expected_signature, $received_signature)) {
            $error_message = "Payment signature verification failed. Please contact support.";
        }
    }
}

/* ══════════════════════════════════════════════════════
   3. CONFIRM STATUS = COMPLETE
   ══════════════════════════════════════════════════════ */
if (empty($error_message)) {
    $status = strtoupper($esewa_data['status'] ?? '');
    if ($status !== 'COMPLETE') {
        $error_message = "Payment status is '{$status}'. Only COMPLETE payments are accepted.";
    }
}

/* ══════════════════════════════════════════════════════
   4. VALIDATE SESSION PENDING BOOKING
   ══════════════════════════════════════════════════════ */
if (empty($error_message)) {
    if (empty($_SESSION['pending_booking'])) {
        $error_message = "Session expired or booking data missing. Please check your booking history.";
    } else {
        $pending          = $_SESSION['pending_booking'];
        $bookingid        = (int)($pending['bookingid']        ?? 0);
        $session_amount   = (float)($pending['total_amount']   ?? 0);
        $transaction_uuid = $pending['transaction_uuid']        ?? '';
        $uid_session      = (int)($pending['userid']           ?? 0);

        if ($bookingid <= 0 || $uid_session !== $uid) {
            $error_message = "Booking data mismatch. Please contact support.";
        }

        // Cross-check amount
        $esewa_amount = (float)($esewa_data['total_amount'] ?? 0);
        if (empty($error_message) && abs($session_amount - $esewa_amount) > 0.01) {
            $error_message = "Amount mismatch: expected Rs {$session_amount}, eSewa returned Rs {$esewa_amount}.";
        }
    }
}

/* ══════════════════════════════════════════════════════
   5. CHECK FOR DUPLICATE (refresh protection)
   ══════════════════════════════════════════════════════ */
if (empty($error_message)) {
    $esewa_ref_id     = $esewa_data['transaction_code'] ?? '';
    $transaction_uuid = $esewa_data['transaction_uuid'] ?? $transaction_uuid;

    // Check if this booking is already confirmed
    $dup_stmt = $con->prepare(
        "SELECT bookingid FROM booking WHERE bookingid = ? AND status = 'confirmed' LIMIT 1"
    );
    $dup_stmt->bind_param('i', $bookingid);
    $dup_stmt->execute();
    $dup_stmt->store_result();

    if ($dup_stmt->num_rows > 0) {
        $dup_stmt->close();
        // Already confirmed — just show success page
        goto show_success;
    }
    $dup_stmt->close();
}

/* ══════════════════════════════════════════════════════
   6. CONFIRM BOOKING IN DATABASE
   ══════════════════════════════════════════════════════ */
if (empty($error_message)) {
    $esewa_ref_id     = $esewa_data['transaction_code'] ?? '';
    $transaction_uuid = $esewa_data['transaction_uuid'] ?? ($pending['transaction_uuid'] ?? '');

    // Update booking status to 'confirmed'
    // Assumes your booking table has esewa_transaction_uuid and esewa_ref_id columns.
    // If not, remove those two lines and just update status.
    $stmt = $con->prepare(
        "UPDATE booking
         SET status = 'confirmed',
             esewa_transaction_uuid = ?,
             esewa_ref_id = ?
         WHERE bookingid = ? AND userid = ? AND status = 'pending'"
    );
    $stmt->bind_param("ssii", $transaction_uuid, $esewa_ref_id, $bookingid, $uid);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        // Booking may already be confirmed or not found
        $stmt->close();
        // Verify it exists and is confirmed
        $chk = $con->prepare("SELECT bookingid FROM booking WHERE bookingid = ? AND userid = ? AND status = 'confirmed'");
        $chk->bind_param("ii", $bookingid, $uid);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) {
            $error_message = "Could not confirm booking. Please contact support with your transaction ID.";
        }
        $chk->close();
    } else {
        $stmt->close();
    }

    if (empty($error_message)) {
        // Clear session
        unset($_SESSION['pending_booking']);
    }
}

/* ══════════════════════════════════════════════════════
   7. FETCH BOOKING SUMMARY FOR DISPLAY
   ══════════════════════════════════════════════════════ */
show_success:

$booking_summary = null;
if (empty($error_message) && $bookingid) {
    $sum_stmt = $con->prepare(
        "SELECT b.bookingid, b.seats, b.booking_date, b.timing, b.status,
                m.title AS movie_title, m.price AS price_per_seat
         FROM booking b
         JOIN movies m ON b.movieid = m.movieid
         WHERE b.bookingid = ? AND b.userid = ?
         LIMIT 1"
    );
    $sum_stmt->bind_param("ii", $bookingid, $uid);
    $sum_stmt->execute();
    $booking_summary = $sum_stmt->get_result()->fetch_assoc();
    $sum_stmt->close();

    if ($booking_summary) {
        $seat_list  = array_filter(array_map('trim', explode(',', $booking_summary['seats'])));
        $seat_count = count($seat_list);
        $total_paid = $seat_count * floatval($booking_summary['price_per_seat']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= empty($error_message) ? 'Booking Confirmed!' : 'Payment Issue' ?></title>
    <link rel="icon" type="image/png" href="images/icon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f5f5f7;
            color: #1a1a1a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .success-main {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 60px 20px 80px;
        }

        .container { width: 100%; max-width: 600px; }

        /* ── Status header ── */
        .status-header { text-align: center; margin-bottom: 36px; }

        .status-icon {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .status-icon.success { background: #d1fae5; }
        .status-icon.error   { background: #fee2e2; }

        .status-title {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .status-title.success { color: #065f46; }
        .status-title.error   { color: #991b1b; }

        .status-sub { font-size: 15px; color: #6b7280; line-height: 1.6; }

        /* ── Cards ── */
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 20px rgba(0,0,0,.07);
            padding: 28px 32px;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            margin-bottom: 18px;
        }

        /* ── Detail rows ── */
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }
        .detail-row:last-child { border-bottom: none; padding-bottom: 0; }
        .detail-row:first-of-type { padding-top: 0; }
        .dr-label { color: #6b7280; flex-shrink: 0; }
        .dr-val   { font-weight: 500; color: #111827; text-align: right; }
        .dr-val.amount {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            color: #065f46;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
        }
        .badge.confirmed { background: #d1fae5; color: #065f46; }

        /* Seats chip */
        .seats-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .seat-tag {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: 600;
            color: #15803d;
            font-family: monospace;
        }

        /* Ref chip */
        .ref-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 500;
            color: #15803d;
            font-family: monospace;
        }

        /* ── Error card ── */
        .error-card {
            background: #fef2f2;
            border: 1.5px solid #fecaca;
            border-radius: 12px;
            padding: 20px 24px;
            font-size: 14px;
            color: #7f1d1d;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .error-card strong { display: block; margin-bottom: 4px; font-size: 15px; }

        /* ── Action buttons ── */
        .actions { display: flex; gap: 12px; flex-wrap: wrap; }

        .btn-primary {
            flex: 1;
            min-width: 160px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #111827;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            padding: 13px 24px;
            border-radius: 10px;
            text-decoration: none;
            transition: background .2s;
        }
        .btn-primary:hover { background: #1f2937; }

        .btn-secondary {
            flex: 1;
            min-width: 160px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #f3f4f6;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            padding: 13px 24px;
            border-radius: 10px;
            text-decoration: none;
            transition: background .2s;
        }
        .btn-secondary:hover { background: #e5e7eb; }

        @media (max-width: 480px) {
            .card { padding: 22px 18px; }
            .actions { flex-direction: column; }
        }
    </style>
</head>
<body>
<?php include("header.php"); ?>

<main class="success-main">
<div class="container">

<?php if (!empty($error_message)): ?>

    <!-- ════════ ERROR STATE ════════ -->
    <div class="status-header">
        <div class="status-icon error">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <h1 class="status-title error">Something went wrong</h1>
        <p class="status-sub">Your payment may have been processed, but we could not confirm your booking.</p>
    </div>

    <div class="error-card">
        <strong>Error Details</strong>
        <?= htmlspecialchars($error_message) ?>
    </div>

    <div class="card">
        <p class="card-title">What to do next</p>
        <p style="font-size:14px;color:#374151;line-height:1.7;margin-bottom:18px;">
            If money was deducted from your eSewa wallet, please
            <strong>do not pay again</strong>. Contact us with your transaction ID and we will resolve it.
        </p>
        <div class="actions">
            <a href="my_bookings.php" class="btn-primary">
                <i class="fa-solid fa-ticket"></i>
                My Bookings
            </a>
            <a href="index.php" class="btn-secondary">
                <i class="fa-solid fa-house"></i>
                Go Home
            </a>
        </div>
    </div>

<?php else: ?>

    <!-- ════════ SUCCESS STATE ════════ -->
    <div class="status-header">
        <div class="status-icon success">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <h1 class="status-title success">Booking Confirmed!</h1>
        <p class="status-sub">
            Your payment was successful.<br>
            Enjoy your movie!
        </p>
    </div>

    <!-- Booking details -->
    <?php if ($booking_summary): ?>
    <div class="card">
        <p class="card-title">Booking Details</p>

        <div class="detail-row">
            <span class="dr-label">Booking ID</span>
            <span class="dr-val">#<?= str_pad($bookingid, 6, '0', STR_PAD_LEFT) ?></span>
        </div>

        <div class="detail-row">
            <span class="dr-label">Movie</span>
            <span class="dr-val"><?= htmlspecialchars($booking_summary['movie_title']) ?></span>
        </div>

        <div class="detail-row">
            <span class="dr-label">Show Date</span>
            <span class="dr-val"><?= date('d M Y', strtotime($booking_summary['booking_date'])) ?></span>
        </div>

        <div class="detail-row">
            <span class="dr-label">Timing</span>
            <span class="dr-val"><?= htmlspecialchars($booking_summary['timing']) ?></span>
        </div>

        <div class="detail-row">
            <span class="dr-label">Seats</span>
            <span class="dr-val">
                <span class="seats-chip">
                    <?php foreach ($seat_list as $s): ?>
                        <span class="seat-tag"><?= htmlspecialchars($s) ?></span>
                    <?php endforeach; ?>
                </span>
            </span>
        </div>

        <div class="detail-row">
            <span class="dr-label">Total Paid</span>
            <span class="dr-val amount">Rs <?= number_format($total_paid, 2) ?></span>
        </div>

        <div class="detail-row">
            <span class="dr-label">Status</span>
            <span class="dr-val">
                <span class="badge confirmed">
                    <i class="fa-solid fa-circle-check" style="font-size:10px;"></i>
                    Confirmed
                </span>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Payment details -->
    <div class="card">
        <p class="card-title">Payment Details</p>

        <div class="detail-row">
            <span class="dr-label">Payment Method</span>
            <span class="dr-val">
                <span style="color:#60bb46;font-weight:700;">e</span>Sewa Wallet
            </span>
        </div>

        <?php if (!empty($esewa_data['transaction_code'])): ?>
        <div class="detail-row">
            <span class="dr-label">eSewa Ref</span>
            <span class="dr-val">
                <span class="ref-chip">
                    <i class="fa-solid fa-hashtag" style="font-size:10px;"></i>
                    <?= htmlspecialchars($esewa_data['transaction_code']) ?>
                </span>
            </span>
        </div>
        <?php endif; ?>

        <div class="detail-row">
            <span class="dr-label">Transaction ID</span>
            <span class="dr-val" style="font-family:monospace;font-size:12px;">
                <?= htmlspecialchars($esewa_data['transaction_uuid'] ?? '') ?>
            </span>
        </div>
    </div>

    <!-- Actions -->
    <div class="card" style="background:transparent;box-shadow:none;padding:0;">
        <div class="actions">
            <a href="my_bookings.php" class="btn-primary">
                <i class="fa-solid fa-ticket"></i>
                My Bookings
            </a>
            <a href="index.php" class="btn-secondary">
                <i class="fa-solid fa-film"></i>
                Browse More Movies
            </a>
        </div>
    </div>

<?php endif; ?>

</div>
</main>

<?php include("footer.php"); ?>
</body>
</html>