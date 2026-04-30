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

// ── Fetch booking ────────────────────────────────────────────
$stmt = $con->prepare(
    "SELECT * FROM booking WHERE bookingid = ? AND userid = ? AND status = 'confirmed'"
);
$stmt->bind_param("ii", $bookingid, $uid);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    header("Location: index.php");
    exit();
}

// ── Fetch movie ──────────────────────────────────────────────
$stmt = $con->prepare("SELECT * FROM movies WHERE movieid = ?");
$stmt->bind_param("i", $booking['movieid']);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$movie) {
    header("Location: index.php");
    exit();
}

// ── Fetch transaction ────────────────────────────────────────
$stmt = $con->prepare(
    "SELECT * FROM transactions WHERE bookingid = ? AND userid = ? ORDER BY created_at DESC LIMIT 1"
);
$stmt->bind_param("ii", $bookingid, $uid);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();
$stmt->close();

$txn_ref   = $transaction['transaction_ref'] ?? ('TXN-BK-' . $bookingid);
$txn_time  = isset($transaction['created_at'])
    ? date('d M Y, h:i A', strtotime($transaction['created_at']))
    : date('d M Y, h:i A');

// ── Fetch user info ──────────────────────────────────────────
$stmt = $con->prepare("SELECT * FROM users WHERE userid = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$user_name  = htmlspecialchars(($user['name']  ?? $user['username'] ?? 'Guest'), ENT_QUOTES, 'UTF-8');
$user_email = htmlspecialchars(($user['email'] ?? ''), ENT_QUOTES, 'UTF-8');

// ── Compute total ────────────────────────────────────────────
$seats      = array_filter(array_map('trim', explode(',', $booking['seats'])));
$seat_count = count($seats);
$price      = floatval($movie['price'] ?? 0);
$total      = round($seat_count * $price, 2);

// ── Safe variables ───────────────────────────────────────────
$movie_title  = htmlspecialchars($movie['title']        ?? 'Movie', ENT_QUOTES, 'UTF-8');
$movie_image  = htmlspecialchars($movie['image']        ?? '',       ENT_QUOTES, 'UTF-8');
$movie_rating = htmlspecialchars($movie['rating']       ?? '',       ENT_QUOTES, 'UTF-8');
$release_date = htmlspecialchars($movie['release_date'] ?? '',       ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed — <?= $movie_title ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/icon.ico">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f3f4f6;
            color: #111827;
        }

        .success-page {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .success-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }

        /* ── Banner ── */
        .success-banner {
            background: #2563eb;
            padding: 2rem 1.5rem;
            text-align: center;
            color: #fff;
        }
        .success-banner__icon {
            width: 64px;
            height: 64px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 28px;
        }
        .success-banner h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .success-banner p  { font-size: 14px; opacity: 0.85; }

        .txn-ref-pill {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.35);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 16px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.8px;
            margin-top: 12px;
            font-family: monospace;
        }

        /* ── Movie row ── */
        .movie-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f1f1;
            background: #fafafa;
        }
        .movie-row img {
            width: 60px;
            height: 86px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
            border: 1px solid #e5e7eb;
        }
        .movie-row__placeholder {
            width: 60px;
            height: 86px;
            background: #e5e7eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 22px;
            flex-shrink: 0;
        }
        .movie-row__title {
            font-size: 17px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 6px;
        }
        .movie-row__meta {
            font-size: 13px;
            color: #6b7280;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .movie-row__meta span { display: flex; align-items: center; gap: 6px; }

        /* ── Details ── */
        .details {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f1f1;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 0;
            font-size: 14px;
            border-bottom: 1px dashed #f1f1f1;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-row__label {
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .detail-row__label i { width: 16px; text-align: center; color: #9ca3af; font-size: 13px; }
        .detail-row__value {
            font-weight: 600;
            color: #111827;
            text-align: right;
            max-width: 60%;
            word-break: break-word;
        }
        .detail-row__value.mono {
            font-family: monospace;
            font-size: 13px;
            color: #2563eb;
        }

        /* ── Total ── */
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: #eff6ff;
            border-bottom: 1px solid #dbeafe;
        }
        .total-row__label  { font-size: 15px; font-weight: 600; color: #374151; }
        .total-row__amount { font-size: 22px; font-weight: 700; color: #2563eb; }

        /* ── Actions ── */
        .success-actions {
            padding: 1.25rem 1.5rem;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-print {
            flex: 1;
            min-width: 120px;
            padding: 12px;
            background: #fff;
            color: #374151;
            border: 1.5px solid #d1d5db;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: background .2s, border-color .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-print:hover { background: #f9fafb; border-color: #9ca3af; }

        .btn-home {
            flex: 1;
            min-width: 120px;
            padding: 12px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: background .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-home:hover { background: #1d4ed8; }

        .btn-mybookings {
            flex: 1;
            min-width: 120px;
            padding: 12px;
            background: #fff;
            color: #2563eb;
            border: 1.5px solid #2563eb;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: background .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-mybookings:hover { background: #eff6ff; }

        /* ════════════════════════════════════════
           PRINT STYLES — ticket layout
        ════════════════════════════════════════ */
        @media print {
            /* Hide everything except the ticket */
            body > *:not(.print-ticket-wrapper) { display: none !important; }
            .print-ticket-wrapper { display: block !important; }

            @page { margin: 10mm; size: A5 portrait; }
        }

        .print-ticket-wrapper {
            display: none; /* hidden on screen, shown on print */
        }

        /* ── Ticket design ── */
        .ticket {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            font-family: 'DM Sans', sans-serif;
            border: 2px solid #1e3a8a;
            border-radius: 12px;
            overflow: hidden;
        }
        .ticket__header {
            background: #1e3a8a;
            color: #fff;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ticket__header-brand {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .ticket__header-label {
            font-size: 11px;
            opacity: 0.75;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .ticket__movie {
            background: #eff6ff;
            padding: 14px 20px;
            border-bottom: 2px dashed #bfdbfe;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .ticket__movie img {
            width: 50px;
            height: 72px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #bfdbfe;
        }
        .ticket__movie-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e3a8a;
            line-height: 1.3;
        }
        .ticket__movie-meta {
            font-size: 12px;
            color: #3b82f6;
            margin-top: 4px;
        }

        .ticket__body {
            padding: 14px 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 20px;
            border-bottom: 2px dashed #e5e7eb;
        }
        .ticket__field label {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #9ca3af;
            margin-bottom: 2px;
        }
        .ticket__field span {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
        }
        .ticket__field.full { grid-column: 1 / -1; }
        .ticket__field.seats span {
            font-size: 12px;
            font-family: monospace;
            word-break: break-word;
        }

        .ticket__footer {
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f9fafb;
        }
        .ticket__txn {
            font-size: 10px;
            color: #6b7280;
        }
        .ticket__txn strong {
            display: block;
            font-size: 11px;
            font-family: monospace;
            color: #1e3a8a;
            letter-spacing: 0.5px;
        }
        .ticket__total {
            text-align: right;
        }
        .ticket__total label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #9ca3af;
            display: block;
        }
        .ticket__total span {
            font-size: 18px;
            font-weight: 700;
            color: #2563eb;
        }

        .ticket__barcode {
            text-align: center;
            padding: 10px 20px 14px;
            background: #fff;
            border-top: 1px solid #e5e7eb;
        }
        .ticket__barcode .barcode-lines {
            display: inline-flex;
            gap: 2px;
            height: 36px;
            align-items: flex-end;
            margin-bottom: 4px;
        }
        .ticket__barcode .barcode-lines span {
            background: #111827;
            border-radius: 1px;
        }
        .ticket__barcode p {
            font-size: 10px;
            font-family: monospace;
            color: #6b7280;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
<?php include("header.php"); ?>

<!-- ════════════════════════════════════════
     SCREEN VIEW
════════════════════════════════════════ -->
<main class="success-page">
    <div class="success-card">

        <!-- Banner -->
        <div class="success-banner">
            <div class="success-banner__icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h1>Booking Confirmed!</h1>
            <p>Your seats have been reserved successfully.</p>
            <div class="txn-ref-pill">
                <i class="fa-solid fa-receipt" style="font-size:11px;"></i>
                <?= htmlspecialchars($txn_ref) ?>
            </div>
        </div>

        <!-- Movie -->
        <div class="movie-row">
            <?php if ($movie_image): ?>
                <img src="admin/<?= $movie_image ?>" alt="<?= $movie_title ?>">
            <?php else: ?>
                <div class="movie-row__placeholder"><i class="fa-solid fa-film"></i></div>
            <?php endif; ?>
            <div>
                <div class="movie-row__title"><?= $movie_title ?></div>
                <div class="movie-row__meta">
                    <?php if ($movie_rating): ?>
                    <span>
                        <i class="fa-solid fa-star" style="color:#f59e0b; width:14px;"></i>
                        <?= $movie_rating ?> / 10
                    </span>
                    <?php endif; ?>
                    <?php if ($release_date): ?>
                    <span>
                        <i class="fa-regular fa-calendar" style="width:14px;"></i>
                        Released: <?= $release_date ?>
                    </span>
                    <?php endif; ?>
                    <span>
                        <i class="fa-solid fa-couch" style="width:14px;"></i>
                        <?= $seat_count ?> seat<?= $seat_count > 1 ? 's' : '' ?> booked
                    </span>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="details">
            <div class="detail-row">
                <span class="detail-row__label"><i class="fa-solid fa-receipt"></i> Transaction Ref</span>
                <span class="detail-row__value mono"><?= htmlspecialchars($txn_ref) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-row__label"><i class="fa-regular fa-clock"></i> Paid At</span>
                <span class="detail-row__value"><?= $txn_time ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-row__label"><i class="fa-regular fa-calendar"></i> Booking Date</span>
                <span class="detail-row__value"><?= htmlspecialchars($booking['booking_date']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-row__label"><i class="fa-regular fa-clock"></i> Show Time</span>
                <span class="detail-row__value"><?= htmlspecialchars($booking['timing']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-row__label"><i class="fa-solid fa-couch"></i> Seats</span>
                <span class="detail-row__value"><?= htmlspecialchars($booking['seats']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-row__label"><i class="fa-solid fa-tag"></i> Price / Seat</span>
                <span class="detail-row__value">Rs <?= number_format($price, 2) ?></span>
            </div>
            <?php if ($user_name): ?>
            <div class="detail-row">
                <span class="detail-row__label"><i class="fa-solid fa-user"></i> Booked By</span>
                <span class="detail-row__value"><?= $user_name ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Total -->
        <div class="total-row">
            <span class="total-row__label">
                <i class="fa-solid fa-receipt" style="color:#2563eb; margin-right:6px;"></i>
                Total Paid
            </span>
            <span class="total-row__amount">Rs <?= number_format($total, 2) ?></span>
        </div>

        <!-- Actions -->
        <div class="success-actions">
            <button class="btn-print" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Print Ticket
            </button>
            <a href="index.php" class="btn-home">
                <i class="fa-solid fa-house"></i> Home
            </a>
            <a href="mybookings.php" class="btn-mybookings">
                <i class="fa-solid fa-ticket"></i> My Bookings
            </a>
        </div>

    </div>
</main>

<!-- ════════════════════════════════════════
     PRINT-ONLY TICKET
════════════════════════════════════════ -->
<div class="print-ticket-wrapper">
    <div class="ticket">

        <!-- Header -->
        <div class="ticket__header">
            <div>
                <div class="ticket__header-brand">🎬 CineBook</div>
                <div class="ticket__header-label">Movie Ticket</div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:12px; opacity:.8;">Booking #<?= $bookingid ?></div>
                <div style="font-size:11px; opacity:.65;"><?= $txn_time ?></div>
            </div>
        </div>

        <!-- Movie -->
        <div class="ticket__movie">
            <?php if ($movie_image): ?>
                <img src="admin/<?= $movie_image ?>" alt="<?= $movie_title ?>">
            <?php endif; ?>
            <div>
                <div class="ticket__movie-title"><?= $movie_title ?></div>
                <div class="ticket__movie-meta">
                    <?php if ($movie_rating): ?>⭐ <?= $movie_rating ?>/10 &nbsp;|&nbsp; <?php endif; ?>
                    <?php if ($release_date): ?><?= $release_date ?><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Body fields -->
        <div class="ticket__body">
            <div class="ticket__field">
                <label>Date</label>
                <span><?= htmlspecialchars($booking['booking_date']) ?></span>
            </div>
            <div class="ticket__field">
                <label>Show Time</label>
                <span><?= htmlspecialchars($booking['timing']) ?></span>
            </div>
            <div class="ticket__field">
                <label>Seat Count</label>
                <span><?= $seat_count ?> Seat<?= $seat_count > 1 ? 's' : '' ?></span>
            </div>
            <div class="ticket__field">
                <label>Price / Seat</label>
                <span>Rs <?= number_format($price, 2) ?></span>
            </div>
            <div class="ticket__field full seats">
                <label>Seat Numbers</label>
                <span><?= htmlspecialchars($booking['seats']) ?></span>
            </div>
            <div class="ticket__field full">
                <label>Passenger</label>
                <span><?= $user_name ?><?= $user_email ? ' &lt;' . $user_email . '&gt;' : '' ?></span>
            </div>
        </div>

        <!-- Footer -->
        <div class="ticket__footer">
            <div class="ticket__txn">
                Transaction Reference
                <strong><?= htmlspecialchars($txn_ref) ?></strong>
            </div>
            <div class="ticket__total">
                <label>Total Paid</label>
                <span>Rs <?= number_format($total, 2) ?></span>
            </div>
        </div>

        <!-- Barcode (decorative) -->
        <div class="ticket__barcode">
            <div class="barcode-lines" id="barcodeLines"></div>
            <p><?= htmlspecialchars($txn_ref) ?></p>
        </div>

    </div>
</div>

<script>
// Generate a deterministic-looking barcode from the txn ref
(function() {
    const ref  = "<?= addslashes($txn_ref) ?>";
    const wrap = document.getElementById('barcodeLines');
    if (!wrap) return;

    const widths  = [1, 2, 3, 1, 2, 1, 3, 2, 1, 2, 1, 2, 3, 1, 2, 1, 3, 2, 1, 2,
                     1, 1, 2, 3, 1, 2, 1, 2, 3, 1, 2, 1, 2, 1, 3, 2, 1, 2, 1, 3];
    const heights = [36, 28, 36, 20, 36, 28, 36, 20, 36, 28,
                     36, 28, 36, 20, 36, 28, 36, 36, 20, 28,
                     36, 20, 36, 28, 36, 28, 20, 36, 28, 36,
                     20, 36, 28, 36, 20, 36, 28, 36, 20, 28];

    for (let i = 0; i < 40; i++) {
        const bar    = document.createElement('span');
        const charCode = ref.charCodeAt(i % ref.length) || 65;
        bar.style.width  = (widths[i] + (charCode % 2)) + 'px';
        bar.style.height = heights[i] + 'px';
        wrap.appendChild(bar);
    }
})();
</script>

<?php include("footer.php"); ?>
</body>
</html>