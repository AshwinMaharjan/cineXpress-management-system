<?php 
include("connect.php");
include("admin_header.php");

if(!isset($_SESSION['uid'])){
    echo "<script>window.location.href='../login.php'</script>";
}

// Flash message helpers
function setFlash($type, $msg) { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }
function getFlash() { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }

// DELETE THEATER
if(isset($_GET['delid'])){
    $delid = intval($_GET['delid']);
    $check_res = mysqli_query($con, "SELECT * FROM `theater` WHERE theaterid='$delid'");
    if(mysqli_num_rows($check_res) > 0){
        if(mysqli_query($con, "DELETE FROM `theater` WHERE theaterid='$delid'")){
            setFlash('success', 'Theater deleted successfully.');
        } else {
            setFlash('error', 'Could not delete theater.');
        }
    } else {
        setFlash('error', 'Invalid Theater ID.');
    }
    header('Location: theater.php'); exit;
}

// ADD or UPDATE THEATER
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movieid      = mysqli_real_escape_string($con, $_POST['movieid']);
    $theater_name = mysqli_real_escape_string($con, trim($_POST['theater_name']));
    $timing       = mysqli_real_escape_string($con, $_POST['timing']);
    $timing2      = mysqli_real_escape_string($con, $_POST['timing2'] ?? '');
    $timing3      = mysqli_real_escape_string($con, $_POST['timing3'] ?? '');
    $timing4      = mysqli_real_escape_string($con, $_POST['timing4'] ?? '');
    $price        = floatval($_POST['price']);
    $date         = $_POST['date'];
    $location     = mysqli_real_escape_string($con, trim($_POST['location']));

    $errors = [];
    if (!preg_match('/^[A-Za-z\s]+$/', $theater_name)) $errors[] = 'Theater name should only contain alphabets and spaces.';
    if (!preg_match('/^[A-Za-z\s,]+$/', $location))    $errors[] = 'Location should only contain alphabets, spaces and commas.';
    if ($price < 0)                                     $errors[] = 'Price should not be negative.';
    if (strtotime($date) < strtotime(date('Y-m-d')))   $errors[] = 'Date should not be in the past.';

    if (!empty($errors)) {
        setFlash('error', implode(' ', $errors));
        header('Location: theater.php'); exit;
    }

    if (isset($_POST['add'])) {
        $sql = "INSERT INTO `theater`(`theater_name`,`timing`,`timing2`,`timing3`,`timing4`,`date`,`price`,`location`,`movieid`)
                VALUES ('$theater_name','$timing','$timing2','$timing3','$timing4','$date','$price','$location','$movieid')";
        setFlash(mysqli_query($con, $sql) ? 'success' : 'error',
                 mysqli_query($con, "SELECT 1") ? 'Theater added successfully!' : 'Could not add theater.');
        // Re-run properly
        if(mysqli_query($con, $sql)){
            setFlash('success', 'Theater added successfully!');
        } else {
            setFlash('error', 'Could not add theater.');
        }
        header('Location: theater.php'); exit;
    }

    if (isset($_POST['update'])) {
        $theaterid = intval($_POST['theaterid']);
        $sql = "UPDATE `theater` SET 
                `theater_name`='$theater_name',
                `timing`='$timing',`timing2`='$timing2',`timing3`='$timing3',`timing4`='$timing4',
                `date`='$date',`price`='$price',`location`='$location',`movieid`='$movieid'
                WHERE theaterid='$theaterid'";
        if(mysqli_query($con, $sql)){
            setFlash('success', 'Theater updated successfully!');
        } else {
            setFlash('error', 'Could not update theater.');
        }
        header('Location: theater.php'); exit;
    }
}

$flash    = getFlash();
$editData = null;
if(isset($_GET['editid'])){
    $editid = intval($_GET['editid']);
    $res = mysqli_query($con, "SELECT * FROM `theater` WHERE theaterid='$editid'");
    if(mysqli_num_rows($res) > 0) $editData = mysqli_fetch_array($res);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Theater Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_theater.css" />
</head>
<body>

<!-- ── TOAST NOTIFICATION ── -->
<?php if($flash): ?>
<div id="th-toast" class="th-toast th-toast-<?= $flash['type'] ?>">
    <span class="th-toast-icon"><?= $flash['type'] === 'success' ? '✓' : '✕' ?></span>
    <span class="th-toast-msg"><?= htmlspecialchars($flash['msg']) ?></span>
    <button class="th-toast-close" onclick="dismissToast()">✕</button>
</div>
<?php endif; ?>

<!-- ── DELETE CONFIRMATION MODAL ── -->
<div id="th-confirm-backdrop" class="th-confirm-backdrop">
    <div class="th-confirm-modal">
        <div class="th-confirm-icon">🗑️</div>
        <h3 class="th-confirm-title">Delete Theater?</h3>
        <p class="th-confirm-msg">This action cannot be undone. The theater and its showtimes will be permanently removed.</p>
        <div class="th-confirm-actions">
            <a id="th-confirm-yes" href="#" class="th-btn th-btn-danger">Yes, Delete</a>
            <button onclick="closeConfirm()" class="th-btn th-btn-ghost">Cancel</button>
        </div>
    </div>
</div>

<div class="th-wrapper">

    <div class="th-page-header">
        <div>
            <h1 class="th-page-title">Theater Management</h1>
            <p class="th-page-sub">Manage showtimes, pricing, and locations</p>
        </div>
    </div>

    <div class="th-layout">

        <!-- ── FORM PANEL ── -->
        <div class="th-form-panel">
            <div class="th-panel-header">
                <h2><?= $editData ? '✏️ Edit Theater' : '➕ Add New Theater' ?></h2>
            </div>

            <form name="theaterForm" action="theater.php<?= $editData ? '?editid='.$editData['theaterid'] : '' ?>" method="post" onsubmit="return validateForm()">

                <div class="th-form-group">
                    <label>🎬 Movie</label>
                    <select name="movieid" required>
                        <option value="">— Select a Movie —</option>
                        <?php
                        if ($editData) {
                            $sql = "SELECT * FROM `movies` WHERE movieid NOT IN (SELECT movieid FROM `theater` WHERE movieid != '{$editData['movieid']}') OR movieid = '{$editData['movieid']}'";
                        } else {
                            $sql = "SELECT * FROM `movies` WHERE movieid NOT IN (SELECT movieid FROM `theater`)";
                        }
                        $res = mysqli_query($con, $sql);
                        if(mysqli_num_rows($res) > 0){
                            while($d = mysqli_fetch_array($res)){
                                $sel = ($editData && $editData['movieid'] == $d['movieid']) ? 'selected' : '';
                                echo "<option value='{$d['movieid']}' $sel>".htmlspecialchars($d['title'])."</option>";
                            }
                        } else {
                            echo '<option value="">No unassigned movies available</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="th-form-group">
                    <label>🏛️ Theater Name</label>
                    <input type="text" name="theater_name" value="<?= htmlspecialchars($editData['theater_name'] ?? '') ?>" placeholder="e.g. Grand Cinema Hall" required>
                </div>

                <div class="th-form-group">
                    <label>🕐 Showtimes</label>
                    <div class="th-times-grid">
                        <div class="th-time-slot">
                            <span class="th-slot-label">Show 1 *</span>
                            <input type="time" name="timing" value="<?= $editData['timing'] ?? '' ?>" required>
                        </div>
                        <div class="th-time-slot">
                            <span class="th-slot-label">Show 2</span>
                            <input type="time" name="timing2" value="<?= $editData['timing2'] ?? '' ?>">
                        </div>
                        <div class="th-time-slot">
                            <span class="th-slot-label">Show 3</span>
                            <input type="time" name="timing3" value="<?= $editData['timing3'] ?? '' ?>">
                        </div>
                        <div class="th-time-slot">
                            <span class="th-slot-label">Show 4</span>
                            <input type="time" name="timing4" value="<?= $editData['timing4'] ?? '' ?>">
                        </div>
                    </div>
                    <small class="th-hint">Show 1 is required. Others are optional.</small>
                </div>

                <div class="th-two-col">
                    <div class="th-form-group">
                        <label>📅 Show Date</label>
                        <input type="date" name="date" value="<?= $editData['date'] ?? '' ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="th-form-group">
                        <label>🎟️ Ticket Price (Rs.)</label>
                        <input type="number" name="price" value="<?= $editData['price'] ?? '' ?>" placeholder="e.g. 450" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="th-form-group">
                    <label>📍 Location</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($editData['location'] ?? '') ?>" placeholder="e.g. Kathmandu, Thamel" required>
                </div>

                <div class="th-form-actions">
                    <?php if($editData): ?>
                        <input type="hidden" name="theaterid" value="<?= $editData['theaterid'] ?>">
                        <button type="submit" name="update" class="th-btn th-btn-primary">✓ Update Theater</button>
                        <a href="theater.php" class="th-btn th-btn-ghost">✕ Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add" class="th-btn th-btn-primary">＋ Add Theater</button>
                        <button type="reset" class="th-btn th-btn-ghost">Clear</button>
                    <?php endif; ?>
                </div>

            </form>
        </div>

        <!-- ── TABLE PANEL ── -->
        <div class="th-table-panel">
            <div class="th-panel-header">
                <h2>🎭 All Theaters</h2>
                <?php
                $count_res   = mysqli_query($con, "SELECT COUNT(*) as total FROM theater");
                $total       = mysqli_fetch_assoc($count_res)['total'];
                $limit       = 10;
                $page        = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                $offset      = ($page - 1) * $limit;
                $total_pages = max(1, ceil($total / $limit));
                $editid_param = isset($_GET['editid']) ? '&editid='.intval($_GET['editid']) : '';
                ?>
                <span class="th-count-badge"><?= $total ?> entries</span>
            </div>

            <div class="th-table-wrap">
                <table class="th-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Movie</th>
                            <th>Theater</th>
                            <th>Date</th>
                            <th>Showtimes</th>
                            <th>Price</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $sql = "SELECT theater.*, movies.title
                            FROM theater
                            INNER JOIN movies ON movies.movieid = theater.movieid
                            ORDER BY theaterid DESC
                            LIMIT $limit OFFSET $offset";
                    $res = mysqli_query($con, $sql);
                    if(mysqli_num_rows($res) > 0){
                        $i = $offset + 1;
                        while($d = mysqli_fetch_array($res)){
                            $times      = array_filter([$d['timing'], $d['timing2'], $d['timing3'], $d['timing4']]);
                            $times_html = implode('', array_map(fn($t) => "<span class='th-time-pill'>".date('h:i A', strtotime($t))."</span>", $times));
                    ?>
                    <tr>
                        <td class="th-id"><?= $i++ ?></td>
                        <td class="th-movie-name"><?= htmlspecialchars($d['title']) ?></td>
                        <td><?= htmlspecialchars($d['theater_name']) ?></td>
                        <td><?= date('M d, Y', strtotime($d['date'])) ?></td>
                        <td><div class="th-times-cell"><?= $times_html ?></div></td>
                        <td><span class="th-price">Rs. <?= number_format($d['price'], 0) ?></span></td>
                        <td><?= htmlspecialchars($d['location']) ?></td>
                        <td>
                            <div class="th-action-btns">
                                <a href="theater.php?editid=<?= $d['theaterid'] ?>" class="th-act-btn edit">Edit</a>
                                <button class="th-act-btn delete" onclick="openConfirm('theater.php?delid=<?= $d['theaterid'] ?>')">Del</button>
                            </div>
                        </td>
                    </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='8' class='th-empty'>No theaters found. Add one!</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>

            <?php if($total_pages > 1): ?>
            <div class="th-pagination">
                <?php if($page > 1): ?>
                    <a href="?page=1<?= $editid_param ?>" class="th-pg-btn" title="First">⏮</a>
                    <a href="?page=<?= $page - 1 . $editid_param ?>" class="th-pg-btn">‹ Prev</a>
                <?php endif; ?>
                <?php
                $pg_start = max(1, $page - 2);
                $pg_end   = min($total_pages, $page + 2);
                for($p = $pg_start; $p <= $pg_end; $p++): ?>
                    <a href="?page=<?= $p . $editid_param ?>" class="th-pg-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 . $editid_param ?>" class="th-pg-btn">Next ›</a>
                    <a href="?page=<?= $total_pages . $editid_param ?>" class="th-pg-btn" title="Last">⏭</a>
                <?php endif; ?>
                <span class="th-pg-info"><?= $offset + 1 ?>–<?= min($offset + $limit, $total) ?> of <?= $total ?></span>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// ── Toast ──
const toast = document.getElementById('th-toast');
if (toast) {
    setTimeout(() => toast.classList.add('th-toast-hide'), 3500);
}
function dismissToast() {
    if (toast) toast.classList.add('th-toast-hide');
}

// ── Delete Confirm Modal ──
function openConfirm(url) {
    document.getElementById('th-confirm-yes').href = url;
    document.getElementById('th-confirm-backdrop').classList.add('open');
}
function closeConfirm() {
    document.getElementById('th-confirm-backdrop').classList.remove('open');
}
document.getElementById('th-confirm-backdrop').addEventListener('click', function(e) {
    if (e.target === this) closeConfirm();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeConfirm(); });

// ── Form Validation ──
function validateForm() {
    const name       = document.forms["theaterForm"]["theater_name"].value.trim();
    const price      = document.forms["theaterForm"]["price"].value.trim();
    const location   = document.forms["theaterForm"]["location"].value.trim();
    const date       = document.forms["theaterForm"]["date"].value;
    const alpha      = /^[A-Za-z\s]+$/;
    const alphaComma = /^[A-Za-z\s,]+$/;

    if (!alpha.test(name))          { showValidationToast("Theater name should only contain alphabets and spaces."); return false; }
    if (!alphaComma.test(location)) { showValidationToast("Location should only contain alphabets, spaces and commas."); return false; }
    if (!price || isNaN(price) || Number(price) < 0) { showValidationToast("Price should be a valid non-negative number."); return false; }
    if (!date) { showValidationToast("Please select a date."); return false; }
    const sel = new Date(date), today = new Date(); today.setHours(0,0,0,0);
    if (sel < today) { showValidationToast("Date should not be in the past."); return false; }
    return true;
}

function showValidationToast(msg) {
    // Create a temporary error toast for client-side validation
    const t = document.createElement('div');
    t.className = 'th-toast th-toast-error';
    t.innerHTML = `<span class="th-toast-icon">✕</span><span class="th-toast-msg">${msg}</span><button class="th-toast-close" onclick="this.parentElement.remove()">✕</button>`;
    document.body.appendChild(t);
    setTimeout(() => t.classList.add('th-toast-hide'), 3500);
    setTimeout(() => t.remove(), 4000);
}
</script>

<?php include("footer.php"); ?>
</body>
</html>