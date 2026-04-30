<?php
include("connect.php");
include("admin_header.php");

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bookingid = intval($_POST['bookingid']);

    if ($_POST['action'] === 'update_status') {
        $status = mysqli_real_escape_string($con, $_POST['status']);
        $sql = "UPDATE booking SET status='$status' WHERE bookingid=$bookingid";
        mysqli_query($con, $sql);
        header("Location: viewallbookings.php?msg=updated");
        exit();
    }

    if ($_POST['action'] === 'delete') {
        $sql = "DELETE FROM booking WHERE bookingid=$bookingid";
        mysqli_query($con, $sql);
        header("Location: viewallbookings.php?msg=deleted");
        exit();
    }
}

// Filters
// Filters
$search        = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($con, $_GET['status']) : '';

$where = "WHERE 1=1";
if ($search)        $where .= " AND (u.name LIKE '%$search%' OR m.title LIKE '%$search%' OR b.bookingid LIKE '%$search%')";
if ($filter_status) $where .= " AND b.status='$filter_status'";

// Pagination
$per_page    = 10;
$page        = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset      = ($page - 1) * $per_page;

// Total count for pagination
$count_result = mysqli_query($con, "SELECT COUNT(*) AS cnt FROM booking b LEFT JOIN movies m ON b.movieid = m.movieid LEFT JOIN users u ON b.userid = u.userid $where");
$total        = (int)mysqli_fetch_assoc($count_result)['cnt'];
$total_pages  = ceil($total / $per_page);

$sql = "
    SELECT b.bookingid, b.booking_date, b.timing, b.seats, b.status,
           m.title AS movie_title, m.image AS movie_image,
           u.name AS user_name, u.email AS user_email
    FROM booking b
    LEFT JOIN movies m ON b.movieid = m.movieid
    LEFT JOIN users  u ON b.userid  = u.userid
    $where
    ORDER BY b.bookingid DESC
    LIMIT $per_page OFFSET $offset
";
$result = mysqli_query($con, $sql);

$total  = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Bookings — Admin</title>
    <link rel="stylesheet" href="../css/view_all_booking.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- Toast Notification -->
<?php if (isset($_GET['msg'])): ?>
<div class="toast <?= $_GET['msg'] === 'deleted' ? 'toast--danger' : 'toast--success' ?>" id="toast">
    <i class="fa <?= $_GET['msg'] === 'deleted' ? 'fa-trash' : 'fa-check-circle' ?>"></i>
    <?= $_GET['msg'] === 'deleted' ? 'Booking deleted successfully.' : 'Booking status updated.' ?>
</div>
<?php endif; ?>

<div class="ab-wrap">

    <!-- Page Header -->
    <div class="ab-header">
        <div class="ab-header__left">
            <span class="ab-header__eyebrow">Admin Panel</span>
            <h1 class="ab-header__title">All <span class="gold">Bookings</span></h1>
            <p class="ab-header__sub">Manage, update and track every reservation in one place.</p>
        </div>
        <div class="ab-header__stat">
            <span class="stat-number"><?= $total ?></span>
            <span class="stat-label">Total Results</span>
        </div>
    </div>

    <!-- Filters Bar -->
    <form method="GET" class="ab-filters" id="filterForm">
        <div class="ab-filters__search">
            <i class="fa fa-search"></i>
            <input type="text" name="search" placeholder="Search by user, movie or ID…" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="ab-filters__select">
            <i class="fa fa-filter"></i>
            <select name="status" onchange="document.getElementById('filterForm').submit()">
                <option value="">All Statuses</option>
                <option value="confirmed"  <?= $filter_status==='confirmed'  ? 'selected':'' ?>>Confirmed</option>
                <option value="pending"    <?= $filter_status==='pending'    ? 'selected':'' ?>>Pending</option>
                <option value="cancelled"  <?= $filter_status==='cancelled'  ? 'selected':'' ?>>Cancelled</option>
            </select>
        </div>
        <button type="submit" class="btn-filter"><i class="fa fa-magnifying-glass"></i> Search</button>
        <?php if ($search || $filter_status): ?>
            <a href="viewallbookings.php" class="btn-clear"><i class="fa fa-xmark"></i> Clear</a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <?php if ($total === 0): ?>
    <div class="ab-empty">
        <i class="fa fa-ticket"></i>
        <p>No bookings found.</p>
    </div>
    <?php else: ?>
    <div class="ab-table-wrap">
        <table class="ab-table">
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>Movie</th>
                    <th>User</th>
                    <th>Date</th>
                    <th>Timing</th>
                    <th>Seats</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td class="td-id">#<?= $row['bookingid'] ?></td>

                <!-- Movie -->
                <td class="td-movie">
                    <div class="movie-cell">
                        <?php if ($row['movie_image']): ?>
                            <img src="<?= htmlspecialchars($row['movie_image']) ?>" alt="poster" class="movie-thumb">
                        <?php else: ?>
                            <div class="movie-thumb movie-thumb--placeholder"><i class="fa fa-film"></i></div>
                        <?php endif; ?>
                        <span><?= htmlspecialchars($row['movie_title'] ?? 'N/A') ?></span>
                    </div>
                </td>

                <!-- User -->
                <td class="td-user">
                    <div class="user-cell">
                        <div class="user-avatar"><?= strtoupper(substr($row['user_name'] ?? 'U', 0, 1)) ?></div>
                        <div>
                            <div class="user-name"><?= htmlspecialchars($row['user_name'] ?? 'N/A') ?></div>
                            <div class="user-email"><?= htmlspecialchars($row['user_email'] ?? '') ?></div>
                        </div>
                    </div>
                </td>

                <td><?= htmlspecialchars($row['booking_date']) ?></td>
                <td><span class="timing-badge"><i class="fa fa-clock"></i> <?= htmlspecialchars($row['timing']) ?></span></td>
                <td><span class="seats-badge"><?= htmlspecialchars($row['seats']) ?></span></td>

                <!-- Status -->
                <td>
                    <span class="status-badge status-badge--<?= strtolower($row['status']) ?>">
                        <?= ucfirst($row['status']) ?>
                    </span>
                </td>

                <!-- Actions -->
                <td class="td-actions">
                    <!-- Edit Status -->
                    <button class="btn-icon btn-icon--edit" title="Change Status"
                        onclick="openStatusModal(<?= $row['bookingid'] ?>, '<?= $row['status'] ?>')">
                        <i class="fa fa-pen-to-square"></i>
                    </button>
                    <!-- Delete -->
                    <button class="btn-icon btn-icon--delete" title="Delete Booking"
                        onclick="openDeleteModal(<?= $row['bookingid'] ?>)">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div><!-- /.ab-table-wrap -->

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="ab-pagination">
        <?php
        $query_params = [];
        if ($search)        $query_params['search'] = $search;
        if ($filter_status) $query_params['status'] = $filter_status;

        // Prev
        if ($page > 1):
            $query_params['page'] = $page - 1;
        ?>
            <a href="?<?= http_build_query($query_params) ?>" class="pg-btn pg-btn--arrow"><i class="fa fa-chevron-left"></i></a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++):
            $query_params['page'] = $i;
        ?>
            <a href="?<?= http_build_query($query_params) ?>"
               class="pg-btn <?= $i === $page ? 'pg-btn--active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php
        // Next
        if ($page < $total_pages):
            $query_params['page'] = $page + 1;
        ?>
            <a href="?<?= http_build_query($query_params) ?>" class="pg-btn pg-btn--arrow"><i class="fa fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>


<!-- ── STATUS MODAL ── -->
<div class="modal-overlay" id="statusModal">
    <div class="modal">
        <button class="modal__close" onclick="closeModals()"><i class="fa fa-xmark"></i></button>
        <div class="modal__icon modal__icon--edit"><i class="fa fa-pen-to-square"></i></div>
        <h3 class="modal__title">Update Status</h3>
        <p class="modal__sub">Change the booking status for <strong id="statusBookingLabel">this booking</strong>.</p>
        <form method="POST" class="modal__form">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="bookingid" id="statusBookingId">
            <div class="modal__select-wrap">
                <select name="status" id="statusSelect">
                    <option value="confirmed">Confirmed</option>
                    <option value="pending">Pending</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="modal__actions">
                <button type="button" class="btn-modal btn-modal--ghost" onclick="closeModals()">Cancel</button>
                <button type="submit" class="btn-modal btn-modal--primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ── DELETE MODAL ── -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <button class="modal__close" onclick="closeModals()"><i class="fa fa-xmark"></i></button>
        <div class="modal__icon modal__icon--danger"><i class="fa fa-trash"></i></div>
        <h3 class="modal__title">Delete Booking</h3>
        <p class="modal__sub">This action is <strong>permanent</strong>. Are you sure you want to delete booking <strong id="deleteBookingLabel"></strong>?</p>
        <form method="POST" class="modal__form">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="bookingid" id="deleteBookingId">
            <div class="modal__actions">
                <button type="button" class="btn-modal btn-modal--ghost" onclick="closeModals()">Cancel</button>
                <button type="submit" class="btn-modal btn-modal--danger">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
// Toast auto-dismiss
const toast = document.getElementById('toast');
if (toast) setTimeout(() => toast.classList.add('toast--hide'), 3500);

function openStatusModal(id, currentStatus) {
    document.getElementById('statusBookingId').value    = id;
    document.getElementById('statusBookingLabel').textContent = '#' + id;
    document.getElementById('statusSelect').value       = currentStatus;
    document.getElementById('statusModal').classList.add('modal-overlay--active');
}

function openDeleteModal(id) {
    document.getElementById('deleteBookingId').value        = id;
    document.getElementById('deleteBookingLabel').textContent = '#' + id;
    document.getElementById('deleteModal').classList.add('modal-overlay--active');
}

function closeModals() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('modal-overlay--active'));
}

// Close on backdrop click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => { if (e.target === overlay) closeModals(); });
});
</script>


<?php include("footer.php")?>
</body>
</html>