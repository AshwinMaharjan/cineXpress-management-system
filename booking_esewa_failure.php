<?php
session_start();
include("connect.php");

// ── Auth guard ───────────────────────────────────────────────
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$uid = (int)$_SESSION['userid'];

/*
   eSewa redirects here on payment failure, cancellation,
   or session timeout. The pending_booking session is kept
   intact so the user can retry from booking.php.

   eSewa does NOT send a signed response to the failure URL,
   so we only use query params for display — never for logic.
*/

$transaction_uuid = htmlspecialchars($_GET['transaction_uuid'] ?? '');
$status           = strtoupper(htmlspecialchars($_GET['status'] ?? 'FAILED'));

// ── Mark booking as 'failed' if it's still pending ───────────
if (!empty($_SESSION['pending_booking'])) {
    $bookingid = (int)($_SESSION['pending_booking']['bookingid'] ?? 0);
    if ($bookingid > 0) {
        $stmt = $con->prepare(
            "UPDATE booking SET status = 'failed' WHERE bookingid = ? AND userid = ? AND status = 'pending'"
        );
        $stmt->bind_param("ii", $bookingid, $uid);
        $stmt->execute();
        $stmt->close();
    }
    // Clear session — user must start over from booking.php
    $movieid_for_retry = (int)($_SESSION['pending_booking']['movieid'] ?? 0);
    unset($_SESSION['pending_booking']);
} else {
    $movieid_for_retry = 0;
}

// ── Status messages ───────────────────────────────────────────
$status_messages = [
    'FAILED'    => 'Your payment could not be processed.',
    'CANCELED'  => 'You cancelled the payment.',
    'PENDING'   => 'Your payment is still pending. Please check back shortly.',
    'NOT_FOUND' => 'The payment session expired before completion.',
    'AMBIGUOUS' => 'The payment is in an uncertain state. Please contact support.',
];
$friendly_message = $status_messages[$status] ?? 'Your payment was not completed.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed — Movie Booking</title>
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

        .failure-main {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 60px 20px 80px;
        }

        .container { width: 100%; max-width: 560px; }

        /* ── Status header ── */
        .status-header { text-align: center; margin-bottom: 36px; }

        .status-icon {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: #fee2e2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .status-title {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: #991b1b;
            margin-bottom: 8px;
        }
        .status-sub { font-size: 15px; color: #6b7280; line-height: 1.6; }

        /* ── Card ── */
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

        /* ── Reason banner ── */
        .reason-banner {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: #fef2f2;
            border: 1.5px solid #fecaca;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 20px;
        }
        .reason-icon {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #fee2e2;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ef4444;
            font-size: 15px;
        }
        .reason-text strong {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #7f1d1d;
            margin-bottom: 3px;
        }
        .reason-text p { font-size: 13px; color: #991b1b; line-height: 1.5; }

        /* ── Info rows ── */
        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }
        .info-row:last-child { border-bottom: none; padding-bottom: 0; }
        .info-row:first-child { padding-top: 0; }
        .ir-label { color: #6b7280; }
        .ir-val   { font-weight: 500; color: #374151; text-align: right; }

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
        .badge.failed   { background: #fee2e2; color: #991b1b; }
        .badge.canceled { background: #fef3c7; color: #92400e; }
        .badge.pending  { background: #dbeafe; color: #1e40af; }

        /* ── Tips ── */
        .tips-list { list-style: none; display: flex; flex-direction: column; gap: 10px; }
        .tips-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
            color: #374151;
            line-height: 1.5;
        }
        .tip-bullet {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #6b7280;
        }

        /* ── Buttons ── */
        .actions { display: flex; gap: 12px; flex-wrap: wrap; }

        .btn-retry {
            flex: 1;
            min-width: 150px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #60bb46;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            padding: 13px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: background .2s;
        }
        .btn-retry:hover { background: #4fa336; }

        .btn-secondary {
            flex: 1;
            min-width: 150px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #f3f4f6;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            padding: 13px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: background .2s;
        }
        .btn-secondary:hover { background: #e5e7eb; }

        .support-note {
            text-align: center;
            font-size: 13px;
            color: #9ca3af;
            margin-top: 8px;
            line-height: 1.6;
        }
        .support-note a {
            color: #374151;
            font-weight: 500;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        @media (max-width: 480px) {
            .card { padding: 22px 18px; }
            .actions { flex-direction: column; }
        }
    </style>
</head>
<body>
<?php include("header.php"); ?>

<main class="failure-main">
<div class="container">

    <!-- ── Header ── -->
    <div class="status-header">
        <div class="status-icon">
            <?php if ($status === 'CANCELED'): ?>
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6"  x2="6"  y2="18"/>
                    <line x1="6"  y1="6"  x2="18" y2="18"/>
                </svg>
            <?php elseif ($status === 'PENDING'): ?>
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            <?php else: ?>
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8"  x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            <?php endif; ?>
        </div>

        <h1 class="status-title">
            <?php
                if ($status === 'CANCELED') echo 'Payment Cancelled';
                elseif ($status === 'PENDING') echo 'Payment Pending';
                else echo 'Payment Failed';
            ?>
        </h1>
        <p class="status-sub"><?= $friendly_message ?></p>
    </div>

    <!-- ── Reason banner ── -->
    <div class="reason-banner">
        <div class="reason-icon">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <div class="reason-text">
            <strong>
                <?php
                    if ($status === 'CANCELED') echo 'Payment was cancelled';
                    elseif ($status === 'PENDING') echo 'Payment is still pending';
                    else echo 'Payment was not successful';
                ?>
            </strong>
            <p>No money has been deducted. Your seats have been released — please try booking again.</p>
        </div>
    </div>

    <!-- ── Transaction info ── -->
    <?php if (!empty($transaction_uuid)): ?>
    <div class="card">
        <p class="card-title">Transaction Info</p>

        <div class="info-row">
            <span class="ir-label">Transaction ID</span>
            <span class="ir-val" style="font-family:monospace;font-size:12px;"><?= $transaction_uuid ?></span>
        </div>

        <div class="info-row">
            <span class="ir-label">Status</span>
            <span class="ir-val">
                <?php
                    $badge_class = match($status) {
                        'CANCELED' => 'canceled',
                        'PENDING'  => 'pending',
                        default    => 'failed'
                    };
                ?>
                <span class="badge <?= $badge_class ?>">
                    <i class="fa-solid <?= $status === 'CANCELED' ? 'fa-ban' : ($status === 'PENDING' ? 'fa-clock' : 'fa-xmark') ?>" style="font-size:10px;"></i>
                    <?= ucfirst(strtolower($status)) ?>
                </span>
            </span>
        </div>

        <div class="info-row">
            <span class="ir-label">Payment Method</span>
            <span class="ir-val">
                <span style="color:#60bb46;font-weight:700;">e</span>Sewa Wallet
            </span>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Tips ── -->
    <div class="card">
        <p class="card-title">Common Reasons &amp; Tips</p>
        <ul class="tips-list">
            <li>
                <div class="tip-bullet"><i class="fa-solid fa-wallet"></i></div>
                <span>Make sure your eSewa wallet has sufficient balance before paying.</span>
            </li>
            <li>
                <div class="tip-bullet"><i class="fa-solid fa-key"></i></div>
                <span>Double-check your eSewa ID and password. Test IDs: 9806800001–9806800005.</span>
            </li>
            <li>
                <div class="tip-bullet"><i class="fa-solid fa-clock"></i></div>
                <span>Complete the payment within 5 minutes, or the session will expire.</span>
            </li>
            <li>
                <div class="tip-bullet"><i class="fa-solid fa-rotate-right"></i></div>
                <span>Your seat selection has been cleared — simply pick your seats again and retry.</span>
            </li>
        </ul>
    </div>

    <!-- ── Actions ── -->
    <div class="card" style="background:transparent;box-shadow:none;padding:0;">
        <div class="actions">
            <?php if ($movieid_for_retry > 0): ?>
                <a href="booking.php?movieid=<?= $movieid_for_retry ?>" class="btn-retry">
                    <i class="fa-solid fa-rotate-right"></i>
                    Try Again
                </a>
            <?php else: ?>
                <a href="index.php" class="btn-retry">
                    <i class="fa-solid fa-film"></i>
                    Browse Movies
                </a>
            <?php endif; ?>
            <a href="index.php" class="btn-secondary">
                <i class="fa-solid fa-house"></i>
                Go Home
            </a>
        </div>
    </div>

    <p class="support-note">
        Money deducted but booking not confirmed?
        <a href="mailto:support@yourcinema.com">Contact support</a>
        with your Transaction ID.
    </p>

</div>
</main>

<?php include("footer.php"); ?>
</body>
</html>