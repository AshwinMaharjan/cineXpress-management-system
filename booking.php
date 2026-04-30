<?php
include("connect.php");

// ── Auth guard ───────────────────────────────────────────────
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['userid'];

// ── Get movieid from URL ─────────────────────────────────────
$movieid = isset($_GET['movieid']) ? intval($_GET['movieid']) : 0;
if (!$movieid) {
    header("Location: index.php");
    exit();
}

// ── Selected timing ──────────────────────────────────────────
$selected_timing = isset($_GET['timing']) ? trim($_GET['timing']) : '';

// ── Fetch movie ──────────────────────────────────────────────
$stmt = $con->prepare("SELECT * FROM movies WHERE movieid = ?");
$stmt->bind_param("i", $movieid);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$movie) {
    header("Location: index.php");
    exit();
}

// ── Format movie_type for display ────────────────────────────
function formatMovieType(string $type): string {
    return match($type) {
        'now_showing' => 'Now Showing',
        'coming_soon' => 'Coming Soon',
        default       => ucwords(str_replace('_', ' ', $type)),
    };
}

// ── Available timings ─────────────────────────────────────────
$available_timings = ['10:00 AM', '1:00 PM', '4:00 PM', '7:00 PM', '10:00 PM'];

if (!$selected_timing) {
    $selected_timing = $available_timings[0];
}

// ── Today's date as booking date ──────────────────────────────
$booking_date = date('Y-m-d');

// ── Fetch seat statuses ───────────────────────────────────────
$taken_seats    = [];
$reserved_seats = [];

if ($booking_date && $selected_timing) {
    $stmt = $con->prepare(
        "SELECT seats, status FROM booking
         WHERE movieid = ? AND booking_date = ? AND timing = ?
           AND status IN ('confirmed', 'pending')"
    );
    $stmt->bind_param("iss", $movieid, $booking_date, $selected_timing);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $seats_in_row = array_filter(array_map('trim', explode(',', $row['seats'])));
        if ($row['status'] === 'confirmed') {
            $taken_seats = array_merge($taken_seats, $seats_in_row);
        } elseif ($row['status'] === 'pending') {
            $reserved_seats = array_merge($reserved_seats, $seats_in_row);
        }
    }
    $stmt->close();
}

$taken_seats    = array_values(array_unique($taken_seats));
$reserved_seats = array_values(array_unique(array_diff($reserved_seats, $taken_seats)));

// ── Handle booking form submission ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticketbook'])) {
    $movieid_post      = intval($_POST['movieid']);
    $booking_date_post = $_POST['booking_date'];
    $timing_post       = $_POST['timing'];
    $seats             = trim($_POST['seats']);

    $selected_seats = array_filter(array_map('trim', explode(',', $seats)));

    if (empty($selected_seats)) {
        $error_msg = "Please select at least one seat.";
    } else {
        // Check for conflicts
        $stmt = $con->prepare(
            "SELECT seats, status FROM booking
             WHERE movieid = ? AND booking_date = ? AND timing = ?
               AND status IN ('confirmed', 'pending')"
        );
        $stmt->bind_param("iss", $movieid_post, $booking_date_post, $timing_post);
        $stmt->execute();
        $res = $stmt->get_result();
        $existing_taken    = [];
        $existing_reserved = [];
        while ($row = $res->fetch_assoc()) {
            $s = array_map('trim', explode(',', $row['seats']));
            if ($row['status'] === 'confirmed') {
                $existing_taken = array_merge($existing_taken, $s);
            } else {
                $existing_reserved = array_merge($existing_reserved, $s);
            }
        }
        $stmt->close();

        $taken_conflicts    = array_intersect($selected_seats, $existing_taken);
        $reserved_conflicts = array_intersect($selected_seats, $existing_reserved);

        if (!empty($taken_conflicts)) {
            $error_msg = "Seats already taken: " . implode(', ', $taken_conflicts);
        } elseif (!empty($reserved_conflicts)) {
            $error_msg = "Seats currently reserved: " . implode(', ', $reserved_conflicts) . ". Please choose other seats.";
        } else {
            $seats_str  = implode(',', $selected_seats);
            $seat_count = count($selected_seats);
            $price_each = floatval($movie['price'] ?? 0);
            $total      = round($seat_count * $price_each, 2);

            // ── Insert booking as 'confirmed' ─────────────────
            $stmt = $con->prepare(
                "INSERT INTO booking (movieid, booking_date, timing, seats, userid, status)
                 VALUES (?, ?, ?, ?, ?, 'confirmed')"
            );
            $stmt->bind_param("isssi", $movieid_post, $booking_date_post, $timing_post, $seats_str, $uid);

            if ($stmt->execute()) {
                $new_booking_id = $stmt->insert_id;
                $stmt->close();

                // ── Generate unique transaction reference ──────
                // Format: TXN-YYYYMMDD-XXXXXXXX (8 random hex chars)
                $txn_ref = 'TXN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

                // ── Insert into transactions table ─────────────
                $stmt = $con->prepare(
                    "INSERT INTO transactions
                        (transaction_ref, bookingid, userid, movieid, seats, seat_count, timing, booking_date, amount, payment_method, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Direct', 'success')"
                );
                $stmt->bind_param(
    "siiisissd",
    $txn_ref,
    $new_booking_id,
    $uid,
    $movieid_post,
    $seats_str,
    $seat_count,
    $timing_post,
    $booking_date_post,
    $total
);

                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: booking_success.php?bookingid=" . $new_booking_id);
                    exit();
                } else {
                    // Transaction insert failed — still redirect, booking is confirmed
                    $stmt->close();
                    header("Location: booking_success.php?bookingid=" . $new_booking_id);
                    exit();
                }
            } else {
                $error_msg = "Booking failed. Please try again.";
                $stmt->close();
            }
        }
    }
}

// ── Safe variables ────────────────────────────────────────────
$movie_title      = htmlspecialchars($movie['title']       ?? 'Movie', ENT_QUOTES, 'UTF-8');
$movie_image      = htmlspecialchars($movie['image']       ?? '',       ENT_QUOTES, 'UTF-8');
$movie_rating     = htmlspecialchars($movie['rating']      ?? '',       ENT_QUOTES, 'UTF-8');
$movie_desc       = htmlspecialchars($movie['description'] ?? '',       ENT_QUOTES, 'UTF-8');
$movie_type_raw   = $movie['movie_type'] ?? '';
$movie_type_label = htmlspecialchars(formatMovieType($movie_type_raw),  ENT_QUOTES, 'UTF-8');
$movie_price      = isset($movie['price']) ? floatval($movie['price']) : 0.00;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Tickets — <?= $movie_title ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/icon.ico">
    <link rel="stylesheet" href="css/booking.css">

    <style>
        .seat--reserved {
            background: #f59e0b !important;
            color: #fff !important;
            cursor: not-allowed !important;
            opacity: 0.85;
        }
        .legend-box--reserved { background: #f59e0b; }

        .btn-pay {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px 24px;
            background: #2563eb;
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background .2s, transform .1s;
        }
        .btn-pay:hover:not(:disabled)  { background: #1d4ed8; }
        .btn-pay:active:not(:disabled) { transform: scale(.98); }
        .btn-pay:disabled {
            background: #93c5fd;
            cursor: not-allowed;
        }
        .pay-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12px;
            color: #6b7280;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<?php include("header.php"); ?>

<main class="booking-page">

    <div class="booking-topbar">
        <a href="javascript:history.back()" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <div class="booking-topbar__title">
            <i class="fa-solid fa-ticket"></i>
            Book Tickets
        </div>
    </div>

    <?php if (!empty($error_msg)): ?>
    <div class="alert alert--error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= htmlspecialchars($error_msg) ?>
    </div>
    <?php endif; ?>

    <div class="booking-layout">

        <!-- LEFT: MOVIE INFO -->
        <aside class="movie-panel">
            <?php if ($movie_image): ?>
            <div class="movie-panel__poster">
                <img src="admin/<?= $movie_image ?>" alt="<?= $movie_title ?>">
            </div>
            <?php endif; ?>

            <div class="movie-panel__body">
                <h2 class="movie-panel__title"><?= $movie_title ?></h2>

                <?php if ($movie_type_raw): ?>
                <div class="movie-panel__badge"><?= $movie_type_label ?></div>
                <?php endif; ?>

                <?php if ($movie_rating): ?>
                <div class="movie-panel__rating">
                    <i class="fa-solid fa-star"></i>
                    <?= $movie_rating ?> / 10
                </div>
                <?php endif; ?>

                <?php if ($movie_desc): ?>
                <p class="movie-panel__desc"><?= nl2br($movie_desc) ?></p>
                <?php endif; ?>

                <div class="movie-panel__meta">
                    <div class="meta-row">
                        <i class="fa-regular fa-calendar"></i>
                        <span><?= htmlspecialchars($booking_date) ?></span>
                    </div>
                    <?php if ($movie_price > 0): ?>
                    <div class="meta-row">
                        <i class="fa-solid fa-tag"></i>
                        <span>Rs <?= number_format($movie_price, 2) ?> / seat</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

        <!-- RIGHT: BOOKING FORM -->
        <section class="booking-panel">
            <form class="booking-form" method="post" action="">
                <input type="hidden" name="movieid"      value="<?= $movieid ?>">
                <input type="hidden" name="booking_date" value="<?= htmlspecialchars($booking_date) ?>">

                <!-- Timing -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-regular fa-clock"></i> Show Time
                    </label>
                    <div class="timing-pills" id="timingPills">
                        <?php foreach ($available_timings as $t):
                            $t_safe = htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
                            $active = ($t === $selected_timing) ? 'active' : '';
                        ?>
                        <button
                            type="button"
                            class="timing-pill <?= $active ?>"
                            data-timing="<?= $t_safe ?>"
                            onclick="selectTiming('<?= $t_safe ?>')"
                        ><?= $t_safe ?></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="timing" id="timingInput" value="<?= htmlspecialchars($selected_timing) ?>">
                </div>

                <!-- Seat map -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-couch"></i> Select Seats
                    </label>
                    <div class="screen-bar"><span>S C R E E N</span></div>
                    <div id="seatMap" class="seat-map"></div>

                    <div class="seat-legend">
                        <div class="legend-item">
                            <div class="legend-box legend-box--available"></div>
                            <span>Available</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box legend-box--selected"></div>
                            <span>Selected</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box legend-box--reserved"></div>
                            <span>Reserved</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box legend-box--booked"></div>
                            <span>Taken</span>
                        </div>
                    </div>

                    <input type="hidden" name="seats" id="selectedSeats" required>
                </div>

                <!-- Summary -->
                <div class="booking-summary" id="bookingSummary" style="display:none;">
                    <div class="summary-row">
                        <span>Selected Seats</span>
                        <strong id="summarySeats">—</strong>
                    </div>
                    <div class="summary-row">
                        <span>Price per Seat</span>
                        <strong>Rs <?= number_format($movie_price, 2) ?></strong>
                    </div>
                    <div class="summary-row summary-row--total">
                        <span>Total Price</span>
                        <strong id="summaryTotal">Rs 0.00</strong>
                    </div>
                </div>

                <button type="submit" name="ticketbook" class="btn-pay" id="payBtn" disabled>
                    <i class="fa-solid fa-circle-check"></i>
                    Confirm &amp; Pay
                </button>
                <p class="pay-note">
                    <i class="fa-solid fa-shield-halved"></i>
                    Your booking will be confirmed immediately
                </p>
            </form>
        </section>
    </div>
</main>

<?php include("footer.php"); ?>

<script>
const takenSeats    = <?= json_encode($taken_seats) ?>;
const reservedSeats = <?= json_encode($reserved_seats) ?>;
const pricePerSeat  = <?= json_encode($movie_price) ?>;

const selectedSeats = new Set();
const rows          = ['A','B','C','D','E','F','G','H'];
const cols          = 13;

function renderSeats() {
    const map = document.getElementById('seatMap');
    map.innerHTML = '';
    rows.forEach(row => {
        const rowDiv = document.createElement('div');
        rowDiv.className = 'seat-row';

        const label = document.createElement('span');
        label.className   = 'row-label';
        label.textContent = row;
        rowDiv.appendChild(label);

        for (let col = 1; col <= cols; col++) {
            const id   = row + col;
            const seat = document.createElement('div');
            seat.className   = 'seat';
            seat.textContent = id;
            seat.dataset.id  = id;

            if (takenSeats.includes(id)) {
                seat.classList.add('seat--booked');
                seat.title = 'Taken — confirmed booking';
            } else if (reservedSeats.includes(id)) {
                seat.classList.add('seat--reserved');
                seat.title = 'Reserved — pending payment';
            } else {
                seat.addEventListener('click', () => toggleSeat(seat, id));
            }
            rowDiv.appendChild(seat);
        }
        map.appendChild(rowDiv);
    });
}

function toggleSeat(el, id) {
    if (selectedSeats.has(id)) {
        selectedSeats.delete(id);
        el.classList.remove('seat--selected');
    } else {
        selectedSeats.add(id);
        el.classList.add('seat--selected');
    }
    updateSummary();
}

function updateSummary() {
    const arr   = Array.from(selectedSeats);
    const total = (arr.length * pricePerSeat).toFixed(2);

    document.getElementById('selectedSeats').value = arr.join(',');

    const summary = document.getElementById('bookingSummary');
    const payBtn  = document.getElementById('payBtn');

    if (arr.length > 0) {
        summary.style.display = 'block';
        document.getElementById('summarySeats').textContent = arr.join(', ');
        document.getElementById('summaryTotal').textContent = 'Rs ' + total;
        payBtn.disabled = false;
    } else {
        summary.style.display = 'none';
        payBtn.disabled = true;
    }
}

function selectTiming(timing) {
    const url = new URL(window.location.href);
    url.searchParams.set('timing',  timing);
    url.searchParams.set('movieid', <?= $movieid ?>);
    window.location.href = url.toString();
}

document.querySelector('.booking-form').addEventListener('submit', function(e) {
    if (selectedSeats.size === 0) {
        e.preventDefault();
        alert('Please select at least one seat before proceeding.');
    }
});

renderSeats();
</script>

</body>
</html>