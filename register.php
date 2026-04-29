<?php
include("connect.php");
include("header.php");

$errors = [];
$success = "";
$old = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $old['name']    = trim($_POST['name']    ?? '');
    $old['email']   = trim($_POST['email']   ?? '');
    $old['phone_number']   = trim($_POST['phone_number']   ?? '');

    $name     = $old['name'];
    $email    = $old['email'];
    $phone_number    = $old['phone_number'];
    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // ── Validation ──────────────────────────────────────────────
    if (empty($name)) {
        $errors['name'] = "Full name is required.";
    } elseif (strlen($name) < 3) {
        $errors['name'] = "Name must be at least 3 characters.";
    }

    if (empty($email)) {
        $errors['email'] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }

    if (empty($phone_number)) {
        $errors['phone_number'] = "Phone number is required.";
    } elseif (!preg_match('/^\+?[0-9\s\-]{7,15}$/', $phone_number)) {
        $errors['phone_number'] = "Please enter a valid phone number.";
    }

    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors['password'] = "Password must contain at least one number.";
    }

    if (empty($confirm)) {
        $errors['confirm_password'] = "Please confirm your password.";
    } elseif ($password !== $confirm) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    // ── Profile Photo ────────────────────────────────────────────
    $photo_name = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file      = $_FILES['profile_pic'];
        $allowed   = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $max_size  = 2 * 1024 * 1024; // 2 MB

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['profile_pic'] = "File upload failed. Please try again.";
        } elseif (!in_array($file['type'], $allowed)) {
            $errors['profile_pic'] = "Only JPG, PNG, WEBP, or GIF images are allowed.";
        } elseif ($file['size'] > $max_size) {
            $errors['profile_pic'] = "Image must be smaller than 2 MB.";
        } else {
            $ext        = pathinfo($file['name'], PATHINFO_EXTENSION);
            $photo_name = uniqid('avatar_', true) . '.' . $ext;
            $upload_dir = __DIR__ . '/uploads/avatars/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            if (!move_uploaded_file($file['tmp_name'], $upload_dir . $photo_name)) {
                $errors['profile_pic'] = "Could not save the image. Please try again.";
                $photo_name = null;
            }
        }
    }

    // ── If no errors, proceed ────────────────────────────────────
if (empty($errors)) {
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $roletype = 2;
// ── Check if email already exists ─────────────────────────
if (empty($errors)) {
    $check = $con->prepare("SELECT userid FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $errors['email'] = "This email is already registered.";
    }

    $check->close();
}

    $stmt = $con->prepare("INSERT INTO users (name, email, phone_number, password, profile_pic, roletype) VALUES (?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        die("Prepare failed: " . $con->error);
    }

    $stmt->bind_param("sssssi", $name, $email, $phone_number, $hashed, $photo_name, $roletype);

    if ($stmt->execute()) {
        $success = "Your account has been created successfully! <a href='login.php'>Sign in now →</a>";
        $old = [];
    } else {
        $errors['db'] = "Database error: " . $stmt->error;
    }

    $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create Account — CineBook</title>
    <link rel="icon" type="image/png" href="images/icon.ico">
    <link rel="stylesheet" href="css/register.css" />

</head>
<body>

<!-- ░░ REGISTER SECTION ░░ -->
<section class="register-section">
    <div class="register-wrapper">

        <!-- Left Panel — Branding -->
        <div class="register-brand">
            <div class="brand-inner">
                <div class="brand-icon">🎬</div>
                <h1 class="brand-title">Join CineXpress</h1>
                <p class="brand-subtitle">
                    Your ultimate cinema booking experience.<br>
                    Reserve seats, catch premieres, and never miss a show.
                </p>
                <ul class="brand-perks">
                    <li><span class="perk-icon">🎟️</span> Instant seat reservation</li>
                    <li><span class="perk-icon">🍿</span> Exclusive member offers</li>
                    <li><span class="perk-icon">⭐</span> Early-access screenings</li>
                    <li><span class="perk-icon">📱</span> E-tickets on your phone</li>
                </ul>
                <div class="brand-divider"></div>
                <p class="brand-signin">
                    Already have an account?
                    <a href="login.php" class="brand-signin-link">Sign In</a>
                </p>
            </div>
        </div>

        <!-- Right Panel — Form -->
        <div class="register-card">
            <div class="card-header">
                <h2 class="card-title">Create Account</h2>
                <p class="card-subtitle">Fill in the details below to get started</p>
            </div>

            <!-- Success Banner -->
            <?php if ($success): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                <span><?= $success ?></span>
            </div>
            <?php endif; ?>

            <!-- Global Error Banner -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <span class="alert-icon">⚠️</span>
                <span>Please fix the highlighted errors below.</span>
            </div>
            <?php endif; ?>

            <form method="POST" action="process_register.php"
                  enctype="multipart/form-data"
                  class="register-form"
                  novalidate>

                <!-- ── Profile Photo ── -->
                <div class="photo-upload-group">
                    <div class="photo-preview-wrap">
                        <div class="photo-preview" id="photoPreview">
                            <span class="photo-placeholder">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.5"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </span>
                        </div>
                        <label for="profile_pic" class="photo-edit-btn" title="Upload Photo">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14
                                         a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </label>
                    </div>
                    <div class="photo-info">
                        <p class="photo-label">Profile Photo <span class="optional">(optional)</span></p>
                        <p class="photo-hint">JPG, PNG, WEBP or GIF · Max 2 MB</p>
                        <?php if (!empty($errors['profile_pic'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['profile_pic']) ?></p>
                        <?php endif; ?>
                    </div>
                    <input type="file" id="profile_pic" name="profile_pic"
                           accept="image/*" class="photo-input" />
                </div>

                <!-- ── Row: Full Name ── -->
                <div class="form-group <?= isset($errors['name']) ? 'has-error' : '' ?>">
                    <label for="name" class="form-label">
                        Full Name <span class="required">*</span>
                    </label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <input type="text" id="name" name="name"
                               class="form-input"
                               placeholder="Ashwin Maharjan"
                               value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                               autocomplete="name" />
                    </div>
                    <?php if (!empty($errors['name'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- ── Row: Email ── -->
                <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                    <label for="email" class="form-label">
                        Email Address <span class="required">*</span>
                    </label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2"/>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                            </svg>
                        </span>
                        <input type="email" id="email" name="email"
                               class="form-input"
                               placeholder="ashwin@example.com"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                               autocomplete="email" />
                    </div>
                    <?php if (!empty($errors['email'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['email']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- ── Row: Phone ── -->
                <div class="form-group <?= isset($errors['phone_number']) ? 'has-error' : '' ?>">
                    <label for="phone_number" class="form-label">
                        Phone Number <span class="required">*</span>
                    </label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2
                                         19.79 19.79 0 0 1-8.63-3.07
                                         19.5 19.5 0 0 1-6-6
                                         19.79 19.79 0 0 1-3.07-8.67
                                         A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72
                                         12.84 12.84 0 0 0 .7 2.81
                                         2 2 0 0 1-.45 2.11L8.09 9.91
                                         a16 16 0 0 0 6 6l1.27-1.27
                                         a2 2 0 0 1 2.11-.45
                                         12.84 12.84 0 0 0 2.81.7
                                         A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </span>
                        <input type="tel" id="phone_number" name="phone_number"
                               class="form-input"
                               placeholder="+977 9812345678"
                               value="<?= htmlspecialchars($old['phone_number'] ?? '') ?>"
                               autocomplete="tel" />
                    </div>
                    <?php if (!empty($errors['phone_number'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['phone_number']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- ── Row: Password ── -->
                <div class="form-group <?= isset($errors['password']) ? 'has-error' : '' ?>">
                    <label for="password" class="form-label">
                        Password <span class="required">*</span>
                    </label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="password" name="password"
                               class="form-input"
                               placeholder="Min. 8 characters"
                               autocomplete="new-password" />
                        <button type="button" class="toggle-password" data-target="password"
                                aria-label="Toggle password visibility">
                            <svg class="eye-icon" width="16" height="16" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Strength Meter -->
                    <div class="strength-meter" id="strengthMeter">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-label" id="strengthLabel"></span>
                    </div>
                    <?php if (!empty($errors['password'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['password']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- ── Row: Confirm Password ── -->
                <div class="form-group <?= isset($errors['confirm_password']) ? 'has-error' : '' ?>">
                    <label for="confirm_password" class="form-label">
                        Confirm Password <span class="required">*</span>
                    </label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </span>
                        <input type="password" id="confirm_password" name="confirm_password"
                               class="form-input"
                               placeholder="Re-enter your password"
                               autocomplete="new-password" />
                        <button type="button" class="toggle-password"
                                data-target="confirm_password"
                                aria-label="Toggle confirm password visibility">
                            <svg class="eye-icon" width="16" height="16" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <p class="match-hint" id="matchHint"></p>
                    <?php if (!empty($errors['confirm_password'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['confirm_password']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- ── Terms ── -->
                <div class="form-group terms-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" class="checkbox-input" required />
                        <span class="checkbox-custom"></span>
                        <span class="checkbox-text">
                            I agree to the
                            Terms of Service
                            and
                            Privacy Policy
                        </span>
                    </label>
                </div>

                <!-- ── Submit ── -->
                <button type="submit" class="btn-register">
                    <span class="btn-text">Create My Account</span>
                    <span class="btn-icon">→</span>
                </button>

                <p class="form-footer">
                    Already registered?
                    <a href="login.php" class="link">Sign in here</a>
                </p>

            </form>
        </div><!-- /register-card -->
    </div><!-- /register-wrapper -->
</section>

<script>
/* ── Photo Preview ──────────────────────────────────────── */
document.getElementById('profile_pic').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        const preview = document.getElementById('photoPreview');
        preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="photo-img" />`;
    };
    reader.readAsDataURL(file);
});

/* ── Password Toggle ────────────────────────────────────── */
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function () {
        const targetId = this.dataset.target;
        const input    = document.getElementById(targetId);
        const isHidden = input.type === 'password';
        input.type     = isHidden ? 'text' : 'password';
        this.classList.toggle('active', isHidden);
    });
});

/* ── Password Strength ──────────────────────────────────── */
const passwordInput  = document.getElementById('password');
const strengthFill   = document.getElementById('strengthFill');
const strengthLabel  = document.getElementById('strengthLabel');
const strengthMeter  = document.getElementById('strengthMeter');

passwordInput.addEventListener('input', function () {
    const val = this.value;
    if (!val) { strengthMeter.classList.remove('visible'); return; }

    strengthMeter.classList.add('visible');
    let score = 0;
    if (val.length >= 8)             score++;
    if (/[A-Z]/.test(val))           score++;
    if (/[0-9]/.test(val))           score++;
    if (/[^A-Za-z0-9]/.test(val))   score++;

    const levels = [
        { label: 'Weak',   color: '#E63946', width: '25%'  },
        { label: 'Fair',   color: '#F4A261', width: '50%'  },
        { label: 'Good',   color: '#D4AF37', width: '75%'  },
        { label: 'Strong', color: '#2ECC71', width: '100%' },
    ];

    const level        = levels[score - 1] || levels[0];
    strengthFill.style.width            = level.width;
    strengthFill.style.backgroundColor  = level.color;
    strengthLabel.textContent           = level.label;
    strengthLabel.style.color           = level.color;
});

/* ── Confirm Password Match ─────────────────────────────── */
const confirmInput = document.getElementById('confirm_password');
const matchHint    = document.getElementById('matchHint');

confirmInput.addEventListener('input', function () {
    const match = this.value === passwordInput.value;
    if (!this.value) { matchHint.textContent = ''; return; }
    matchHint.textContent = match ? '✅ Passwords match' : '❌ Passwords do not match';
    matchHint.className   = 'match-hint ' + (match ? 'match-ok' : 'match-fail');
});
</script>
<?php include("footer.php") ?>
</body>
</html>
