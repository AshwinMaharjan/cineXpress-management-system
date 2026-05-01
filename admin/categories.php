<?php
include("connect.php");
if(!isset($_SESSION['userid'])){
    echo "<script>window.location.href='../login.php'</script>";
}


/* ─────────────── EDIT DATA ─────────────── */
$editData = null;

if (isset($_GET['editid'])) {
    $stmt = $con->prepare("SELECT * FROM category WHERE catid = ?");
    $stmt->bind_param("i", $_GET['editid']);
    $stmt->execute();
    $result = $stmt->get_result();
    $editData = $result->fetch_assoc();
}

/* ─────────────── HANDLE ADD ─────────────── */
if (isset($_POST['add'])) {
    $name = trim($_POST['catname']);

    if ($name !== "") {
        $stmt = $con->prepare("SELECT catid FROM category WHERE LOWER(catname) = LOWER(?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $check = $stmt->get_result();

        if ($check->num_rows > 0) {
            $_SESSION['msg'] = "Category already exists!";
        } else {
            $stmt = $con->prepare("INSERT INTO category (catname) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();

            $_SESSION['msg'] = "Category added successfully!";
        }
    }

    header("Location: categories.php");
    exit;
}

/* ─────────────── HANDLE UPDATE ─────────────── */
if (isset($_POST['update'])) {
    $id = $_POST['catid'];
    $name = trim($_POST['catname']);

    $stmt = $con->prepare("SELECT catid FROM category WHERE LOWER(catname) = LOWER(?) AND catid != ?");
    $stmt->bind_param("si", $name, $id);
    $stmt->execute();
    $check = $stmt->get_result();

    if ($check->num_rows > 0) {
        $_SESSION['msg'] = "Duplicate category name not allowed!";
    } else {
        $stmt = $con->prepare("UPDATE category SET catname=? WHERE catid=?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();

        $_SESSION['msg'] = "Category updated successfully!";
    }

    header("Location: categories.php");
    exit;
}

/* ─────────────── HANDLE DELETE ─────────────── */
if (isset($_GET['delid'])) {
    $stmt = $con->prepare("DELETE FROM category WHERE catid=?");
    $stmt->bind_param("i", $_GET['delid']);
    $stmt->execute();

    $_SESSION['msg'] = "Category deleted successfully!";
    header("Location: categories.php");
    exit;
}

/* ─────────────── FETCH DATA ─────────────── */
$result = $con->query("SELECT * FROM category ORDER BY catid DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CineXpress - Categories</title>
    <link rel="stylesheet" href="../css/categories.css">
    <link rel="icon" type="image/png" href="../images/icon.ico">
</head>
<body>

<?php include("admin_header.php"); ?>

<div class="cat-page">

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert">
            <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
        </div>
    <?php endif; ?>

    <div class="cat-container">

        <!-- FORM -->
        <div class="cat-form-card">
            <h2><?= $editData ? "Edit Category" : "Add Category" ?></h2>

            <form method="post">
                <input type="hidden" name="catid" value="<?= $editData['catid'] ?? '' ?>">

                <input type="text"
                       name="catname"
                       value="<?= $editData['catname'] ?? '' ?>"
                       placeholder="Enter category"
                       required>

                <button type="submit" name="add">Add</button>
                <button type="submit" name="update">Update</button>
            </form>
        </div>

        <!-- TABLE -->
        <div class="cat-table-card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $i = 1; ?>
                <?php    $totalRows = mysqli_num_rows($result);
$i = $totalRows; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i-- ?></td>
                        <td><?= htmlspecialchars($row['catname']) ?></td>
                        <td>
                            <a class="btn edit" href="?editid=<?= $row['catid'] ?>">Edit</a>
                            <button class="btn delete deleteBtn"
        data-id="<?= $row['catid'] ?>"
        data-name="<?= htmlspecialchars($row['catname']) ?>">
    Delete
</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>

            </table>
        </div>

    </div>
</div>

</body>
<div class="modal" id="deleteModal">
    <div class="modal-box">

        <h2>Confirm Delete</h2>

        <p>
            Are you sure you want to delete
            <strong id="deleteName"></strong>?
        </p>

        <div class="modal-actions">
            <button id="cancelDelete">Cancel</button>

            <a id="confirmDelete" href="#" class="danger-btn">
                Yes, Delete
            </a>
        </div>

    </div>
</div>
<script>
const modal = document.getElementById("deleteModal");
const deleteName = document.getElementById("deleteName");
const confirmDelete = document.getElementById("confirmDelete");
const cancelDelete = document.getElementById("cancelDelete");

document.querySelectorAll(".deleteBtn").forEach(btn => {
    btn.addEventListener("click", () => {
        const id = btn.dataset.id;
        const name = btn.dataset.name;

        deleteName.textContent = name;
        confirmDelete.href = "?delid=" + id;

        modal.classList.add("active");
    });
});

cancelDelete.addEventListener("click", () => {
    modal.classList.remove("active");
});

// close when clicking outside box
modal.addEventListener("click", (e) => {
    if (e.target === modal) {
        modal.classList.remove("active");
    }
});
</script>
<?php include("footer.php"); ?>
</html>