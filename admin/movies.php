<?php
include("connect.php");
include("admin_header.php");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Add new movie
            $title = $_POST['title'];
            $description = $_POST['description'];
            $release_date = $_POST['release_date'];
            $trailer = $_POST['trailer'];
            $movie = $_POST['movie'];
            $rating = $_POST['rating'];
            $catid = $_POST['catid'];
            
            // Handle image upload
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $target_dir = "uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $image = $target_dir . time() . '_' . basename($_FILES['image']['name']);
                move_uploaded_file($_FILES['image']['tmp_name'], $image);
            }
            
            $stmt = $con->prepare("INSERT INTO movies (title, description, release_date, image, trailer, movie, rating, catid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssdi", $title, $description, $release_date, $image, $trailer, $movie, $rating, $catid);
            $stmt->execute();
            $success_message = "Movie added successfully!";
        } elseif ($_POST['action'] === 'delete') {
            // Delete movie
            $movieid = $_POST['movieid'];
            $stmt = $con->prepare("DELETE FROM movies WHERE movieid = ?");
            $stmt->bind_param("i", $movieid);
            $stmt->execute();
            $success_message = "Movie deleted successfully!";
        } elseif ($_POST['action'] === 'update') {
            // Update movie
            $movieid = $_POST['movieid'];
            $title = $_POST['title'];
            $description = $_POST['description'];
            $release_date = $_POST['release_date'];
            $trailer = $_POST['trailer'];
            $movie = $_POST['movie'];
            $rating = $_POST['rating'];
            $catid = $_POST['catid'];
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $target_dir = "uploads/posters/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $filename = time() . '_' . basename($_FILES['image']['name']);
$path = "uploads/" . $filename;

move_uploaded_file($_FILES['image']['tmp_name'], $path);
$image = $filename;
                
                $stmt = $con->prepare("UPDATE movies SET title=?, description=?, release_date=?, image=?, trailer=?, movie=?, rating=?, catid=? WHERE movieid=?");
                $stmt->bind_param("ssssssdii", $title, $description, $release_date, $image, $trailer, $movie, $rating, $catid, $movieid);
            } else {
                $stmt = $con->prepare("UPDATE movies SET title=?, description=?, release_date=?, trailer=?, movie=?, rating=?, catid=? WHERE movieid=?");
                $stmt->bind_param("sssssdii", $title, $description, $release_date, $trailer, $movie, $rating, $catid, $movieid);
            }
            $stmt->execute();
            $success_message = "Movie updated successfully!";
        }
    }
}

// Fetch all categories for dropdown
$categories = $con->query("SELECT * FROM category ORDER BY catname");

// Fetch all movies
$movies = $con->query("SELECT m.*, c.catname as category_name FROM movies m LEFT JOIN category c ON m.catid = c.catid ORDER BY m.movieid DESC");
?>

<link rel="stylesheet" href="../css/movies.css">
<link rel="icon" type="image/png" href="../images/icon.ico">

<div class="movies-container">

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <span>✓</span> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <!-- Add Movie Form -->
    <div class="form-panel">
        <div class="panel-header">
            <h2>Add New Movie</h2>
        </div>
        <form method="POST" enctype="multipart/form-data" class="movie-form" id="addMovieForm">
            <input type="hidden" name="action" value="add">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="title">Movie Title *</label>
                    <input type="text" id="title" name="title" required placeholder="Enter movie title">
                </div>

                <div class="form-group">
                    <label for="catid">Category *</label>
                    <select id="catid" name="catid" required>
                        <option value="">Select a category</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['catid']; ?>"><?php echo htmlspecialchars($cat['catname']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="release_date">Release Date *</label>
                    <input type="date" id="release_date" name="release_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="rating">Rating (0-10) *</label>
                    <input type="number" id="rating" name="rating" step="0.1" min="0" max="10" required placeholder="8.5">
                </div>

                <div class="form-group full-width">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" rows="4" required placeholder="Enter movie description..."></textarea>
                </div>

                <div class="form-group">
                    <label for="image">Poster Image *</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                    <small class="form-hint">Upload movie poster (JPG, PNG, WebP)</small>
                </div>

                <div class="form-group">
                    <label for="trailer">Trailer URL</label>
                    <input type="url" id="trailer" name="trailer" placeholder="https://youtube.com/...">
                    <small class="form-hint">YouTube or video URL</small>
                </div>

                <div class="form-group full-width">
                    <label for="movie">Movie File URL</label>
                    <input type="text" id="movie" name="movie" placeholder="Path or URL to movie file">
                    <small class="form-hint">Link to the movie file or streaming URL</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span>✓</span> Add Movie
                </button>
                <button type="reset" class="btn btn-secondary">Clear Form</button>
                <a href="view_added_movies.php" class="btn btn-view">
                     View Added Movies
                </a>
            </div>
        </form>
    </div>

</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Movie</h2>
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data" id="editForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="movieid" id="edit_movieid">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="edit_title">Movie Title *</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="edit_catid">Category *</label>
                    <select id="edit_catid" name="catid" required>
                        <?php 
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $cat['catid']; ?>"><?php echo htmlspecialchars($cat['catname']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_release_date">Release Date *</label>
                    <input type="date" id="edit_release_date" name="release_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="edit_rating">Rating (0-10) *</label>
                    <input type="number" id="edit_rating" name="rating" step="0.1" min="0" max="10" required>
                </div>

                <div class="form-group full-width">
                    <label for="edit_description">Description *</label>
                    <textarea id="edit_description" name="description" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_image">Poster Image</label>
                    <input type="file" id="edit_image" name="image" accept="image/*">
                    <small class="form-hint">Leave empty to keep current poster</small>
                </div>

                <div class="form-group">
                    <label for="edit_trailer">Trailer URL</label>
                    <input type="url" id="edit_trailer" name="trailer">
                </div>

                <div class="form-group full-width">
                    <label for="edit_movie">Movie File URL</label>
                    <input type="text" id="edit_movie" name="movie">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span>✓</span> Update Movie
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function editMovie(movieId) {
    fetch('api/get_movie.php?id=' + movieId)
        .then(res => res.json())
        .then(movie => {
            document.getElementById('edit_movieid').value = movie.movieid;
            document.getElementById('edit_title').value = movie.title;
            document.getElementById('edit_description').value = movie.description;
            document.getElementById('edit_release_date').value = movie.release_date;
            document.getElementById('edit_rating').value = movie.rating;
            document.getElementById('edit_catid').value = movie.catid;
            document.getElementById('edit_trailer').value = movie.trailer || '';
            document.getElementById('edit_movie').value = movie.movie || '';
            document.getElementById('editModal').style.display = 'flex';
        })
        .catch(err => {
            alert('Error loading movie data');
            console.error(err);
        });
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function deleteMovie(movieId, title) {
    if (confirm(`Are you sure you want to delete "${title}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="movieid" value="${movieId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) closeEditModal();
}

// ✅ Auto-open edit modal if redirected from view page
const urlParams = new URLSearchParams(window.location.search);
const editId = urlParams.get('edit');
if (editId) {
    editMovie(editId);
}
</script>

<?php include("footer.php"); ?>
