<?php
session_start();
include("connect.php");

// ── Auth guard ───────────────────────────────────────────────
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

// ── Validate session pending booking ─────────────────────────
if (empty($_SESSION['pending_booking'])) {
    header("Location: index.php");
    exit();
}

$pending      = $_SESSION['pending_booking'];
$bookingid    = (int)($pending['bookingid']    ?? 0);
$total_amount = (float)($pending['total_amount'] ?? 0);
$seat_count   = (int)($pending['seat_count']   ?? 0);
$seats        = $pending['seats']        ?? '';
$timing       = $pending['timing']       ?? '';
$userid       = (int)($pending['userid'] ?? 0);

if ($bookingid <= 0 || $total_amount <= 0) {
    header("Location: index.php");
    exit();
}

// ── Verify the booking row still belongs to this user ─────────
$uid = $_SESSION['userid'];
$stmt = $con->prepare("SELECT bookingid FROM booking WHERE bookingid = ? AND userid = ? AND status = 'pending'");
$stmt->bind_param("ii", $bookingid, $uid);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    unset($_SESSION['pending_booking']);
    header("Location: index.php");
    exit();
}
$stmt->close();

// ── eSewa sandbox credentials ─────────────────────────────────
// Replace with real credentials for production:
$merchant_code = "EPAYTEST";
$secret_key    = "8gBm/:&EnhH.1/q";

// ── Unique transaction UUID ───────────────────────────────────
$transaction_uuid = date('Ymd-His') . '-B' . $bookingid;

// ── Amounts ───────────────────────────────────────────────────
$amount                  = $total_amount;
$tax_amount              = "0";
$product_service_charge  = "0";
$product_delivery_charge = "0";

// ── URLs ──────────────────────────────────────────────────────
// Update $base_url to match your domain in production
$base_url    = "http://localhost/updated_cinema_hall";   // ← change this
$success_url = $base_url . "/booking_esewa_success.php";
$failure_url = $base_url . "/booking_esewa_failure.php";

// ── eSewa endpoint ────────────────────────────────────────────
// Sandbox:    https://rc-epay.esewa.com.np/api/epay/main/v2/form
// Production: https://epay.esewa.com.np/api/epay/main/v2/form
$esewa_url = "https://rc-epay.esewa.com.np/api/epay/main/v2/form";

// ── HMAC-SHA256 signature ─────────────────────────────────────
$message   = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$merchant_code}";
$signature = base64_encode(hash_hmac('sha256', $message, $secret_key, true));

// ── Store transaction UUID in session so success page can verify ──
$_SESSION['pending_booking']['transaction_uuid'] = $transaction_uuid;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay with eSewa — Movie Booking</title>
    <link rel="icon" type="image/png" href="images/icon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f5f5f7;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 40px rgba(0,0,0,.10);
            padding: 48px 40px 40px;
            max-width: 440px;
            width: 100%;
            text-align: center;
        }

        /* eSewa branding */
        .esewa-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
        }
        .esewa-circle {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #60bb46;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .esewa-wordmark {
            font-family: 'DM Sans', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: -.3px;
        }
        .esewa-wordmark span { color: #60bb46; }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 6px;
        }
        .sub {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 32px;
            line-height: 1.5;
        }

        /* Amount box */
        .amount-box {
            background: #f9fafb;
            border: 1.5px solid #e5e7eb;
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 20px;
        }
        .amount-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            margin-bottom: 6px;
        }
        .amount-value {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            color: #111827;
        }
        .amount-value .currency {
            font-size: 16px;
            font-weight: 600;
            color: #6b7280;
            margin-right: 3px;
            font-family: 'DM Sans', sans-serif;
        }

        /* Booking summary pill */
        .booking-summary-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 13px;
            color: #15803d;
            font-weight: 500;
            margin-bottom: 28px;
        }

        /* Redirect loading */
        .redirect-msg {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 14px;
            color: #374151;
            font-weight: 500;
            margin-bottom: 24px;
        }
        .spinner {
            width: 20px;
            height: 20px;
            border: 2.5px solid #d1fae5;
            border-top-color: #60bb46;
            border-radius: 50%;
            animation: spin .75s linear infinite;
            flex-shrink: 0;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .btn-cancel {
            display: inline-block;
            font-size: 13px;
            color: #9ca3af;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: color .2s, border-color .2s;
        }
        .btn-cancel:hover { color: #6b7280; border-color: #6b7280; }

        .secure-note {
            margin-top: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12px;
            color: #9ca3af;
        }

        /* Transaction ID row */
        .txn-row {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 20px;
        }
        .txn-row code {
            font-size: 11px;
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            color: #6b7280;
        }

        @media (max-width: 480px) {
            .card { padding: 32px 20px 28px; }
        }
    </style>
</head>
<body>

<div class="card">

    <!-- eSewa logo -->
    <div class="esewa-logo">
        <div class="esewa-circle">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                <path d="M14 6C9.58 6 6 9.58 6 14s3.58 8 8 8c2.1 0 4-.8 5.46-2.1l-2.12-2.12A4.97 4.97 0 0 1 14 19a5 5 0 0 1-4.9-4h12.8c.07-.32.1-.65.1-.99C22 9.58 18.42 6 14 6Zm-4.9 7a5 5 0 0 1 9.8 0H9.1Z" fill="#fff"/>
            </svg>
        </div>
        <span class="esewa-wordmark">e<span>Sewa</span></span>
    </div>

    <h1>Redirecting to eSewa</h1>
    <p class="sub">Please wait. You are being securely redirected<br>to complete your movie ticket payment.</p>

    <!-- Amount -->
    <div class="amount-box">
        <p class="amount-label">Total Amount</p>
        <p class="amount-value">
            <span class="currency">Rs</span><?= number_format($total_amount, 2) ?>
        </p>
    </div>

    <!-- Booking chip -->
    <div class="booking-summary-chip">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>
        </svg>
        <?= $seat_count ?> seat<?= $seat_count > 1 ? 's' : '' ?> · <?= htmlspecialchars($timing) ?>
    </div>

    <p class="txn-row">
        Transaction ID: <code><?= htmlspecialchars($transaction_uuid) ?></code>
    </p>

    <div class="redirect-msg">
        <div class="spinner"></div>
        Connecting to eSewa…
    </div>

    <a href="booking.php?movieid=<?= (int)$pending['movieid'] ?>" class="btn-cancel">← Cancel &amp; go back</a>

    <div class="secure-note">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        256-bit encrypted &amp; secured by eSewa
    </div>
</div>

<!-- eSewa hidden form — auto-submitted -->
<form id="esewaForm" action="<?= htmlspecialchars($esewa_url) ?>" method="POST" style="display:none;">
    <input type="hidden" name="amount"                   value="<?= htmlspecialchars($amount) ?>">
    <input type="hidden" name="tax_amount"               value="<?= htmlspecialchars($tax_amount) ?>">
    <input type="hidden" name="product_service_charge"   value="<?= htmlspecialchars($product_service_charge) ?>">
    <input type="hidden" name="product_delivery_charge"  value="<?= htmlspecialchars($product_delivery_charge) ?>">
    <input type="hidden" name="total_amount"             value="<?= htmlspecialchars($total_amount) ?>">
    <input type="hidden" name="transaction_uuid"         value="<?= htmlspecialchars($transaction_uuid) ?>">
    <input type="hidden" name="product_code"             value="<?= htmlspecialchars($merchant_code) ?>">
    <input type="hidden" name="success_url"              value="<?= htmlspecialchars($success_url) ?>">
    <input type="hidden" name="failure_url"              value="<?= htmlspecialchars($failure_url) ?>">
    <input type="hidden" name="signed_field_names"       value="total_amount,transaction_uuid,product_code">
    <input type="hidden" name="signature"                value="<?= htmlspecialchars($signature) ?>">
</form>

<script>
    setTimeout(function () {
        document.getElementById('esewaForm').submit();
    }, 1200);
</script>

</body>
</html>