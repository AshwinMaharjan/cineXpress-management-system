<?php
include("connect.php");

$error   = null;
$success = false;

/* ── Collect POST Data ─────────────────────────────────────── */
$name         = trim($_POST['name']             ?? '');
$email        = trim($_POST['email']            ?? '');
$phone_number = trim($_POST['phone_number']     ?? '');
$password     = $_POST['password']              ?? '';
$confirm      = $_POST['confirm_password']      ?? '';

/* ── Basic Validation ──────────────────────────────────────── */
if (empty($name) || empty($email) || empty($phone_number) || empty($password)) {
    $error = "All fields are required.";
}
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Invalid email address.";
}
elseif ($password !== $confirm) {
    $error = "Passwords do not match.";
}
elseif (strlen($password) < 8) {
    $error = "Password must be at least 8 characters.";
}

/* ── Duplicate Email Check ─────────────────────────────────── */
if (!$error) {
    $check = $con->prepare("SELECT userid FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "This email is already registered. Try logging in instead.";
    }
    $check->close();
}

/* ── Profile Photo Upload ──────────────────────────────────── */
$photo_name = null;

if (!$error && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file     = $_FILES['profile_pic'];
    $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $max_size = 2 * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "File upload error. Please try again.";
    } elseif (!in_array($file['type'], $allowed)) {
        $error = "Only JPG, PNG, WEBP, or GIF images are allowed.";
    } elseif ($file['size'] > $max_size) {
        $error = "Image must be smaller than 2 MB.";
    } else {
        $ext        = pathinfo($file['name'], PATHINFO_EXTENSION);
        $photo_name = uniqid('avatar_', true) . '.' . $ext;
        $upload_dir = __DIR__ . '/uploads/avatars/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $upload_dir . $photo_name)) {
            $error = "Could not save the image. Please try again.";
        }
    }
}

/* ── Insert Into Database ──────────────────────────────────── */
if (!$error) {
    $hashed   = password_hash($password, PASSWORD_BCRYPT);
    $roletype = 2;

    $stmt = $con->prepare("INSERT INTO users (name, email, phone_number, password, profile_pic, roletype) VALUES (?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        $error = "Something went wrong. Please try again.";
    } else {
        $stmt->bind_param("sssssi", $name, $email, $phone_number, $hashed, $photo_name, $roletype);

        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = "Could not create your account. Please try again.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= $success ? 'Welcome!' : 'Error' ?> — CineBook</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0d0d0d;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal {
            background: #1a1a2e;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 2.5rem 2rem;
            max-width: 420px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0);    }
        }

        /* ── Icon circle — red for error, green for success ── */
        .modal-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
        }

        .modal-icon.error   { background: rgba(230, 57, 70, 0.15); }
        .modal-icon.error svg { stroke: #e63946; }

        .modal-icon.success { background: rgba(46, 204, 113, 0.15); }
        .modal-icon.success svg { stroke: #2ecc71; }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .modal-message {
            font-size: 0.95rem;
            color: #a0a0b0;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .modal-message strong {
            color: #ffffff;
        }

        .modal-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.65rem 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: opacity 0.2s;
        }

        .btn:hover { opacity: 0.85; }

        .btn-danger {
            background: #e63946;
            color: #fff;
        }

        .btn-success {
            background: #2ecc71;
            color: #0d0d0d;
        }

        .btn-ghost {
            background: rgba(255,255,255,0.07);
            color: #ccc;
            border: 1px solid rgba(255,255,255,0.1);
        }

        /* ── Auto-redirect countdown ── */
        .redirect-note {
            margin-top: 1.25rem;
            font-size: 0.8rem;
            color: #606070;
        }

        #countdown {
            color: #2ecc71;
            font-weight: 600;
        }
    </style>

        <link rel="icon" type="image/png" href="images/icon.ico">

</head>
<body>

<div class="modal-backdrop">
    <div class="modal" role="alertdialog" aria-modal="true" aria-labelledby="modalTitle">

        <?php if ($success): ?>

            <!-- ── SUCCESS ── -->
            <div class="modal-icon success">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M8 12l3 3 5-5"/>
                </svg>
            </div>

            <h2 class="modal-title" id="modalTitle">You're all set, <?= htmlspecialchars($name) ?>! 🎬</h2>
            <p class="modal-message">
                Your CineXpress account has been created successfully.<br>
                Sign in to start booking seats and catching premieres.
            </p>

            <div class="modal-actions">
                <a href="login.php" class="btn btn-success">Sign In Now →</a>
                <a href="index.php" class="btn btn-ghost">Go to Home</a>
            </div>

            <p class="redirect-note">
                Redirecting to login in <span id="countdown">5</span>s…
            </p>

            <script>
                let seconds = 5;
                const counter = document.getElementById('countdown');
                const timer = setInterval(() => {
                    seconds--;
                    counter.textContent = seconds;
                    if (seconds <= 0) {
                        clearInterval(timer);
                        window.location.href = 'login.php';
                    }
                }, 1000);
            </script>

        <?php else: ?>

            <!-- ── ERROR ── -->
            <div class="modal-icon error">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>

            <h2 class="modal-title" id="modalTitle">Registration Failed</h2>
            <p class="modal-message"><?= htmlspecialchars($error ?? 'An unexpected error occurred.') ?></p>

            <div class="modal-actions">
                <a href="javascript:history.back()" class="btn btn-danger">← Go Back</a>
                <a href="register.php" class="btn btn-ghost">Start Over</a>
            </div>

        <?php endif; ?>

    </div>
</div>

</body>
</html>