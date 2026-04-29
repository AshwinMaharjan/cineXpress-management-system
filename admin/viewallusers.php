<?php
include("connect.php");
include("admin_header.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Users</title>
    <link rel="stylesheet" href="../css/view_all_users.css">
</head>
<body>
    <div class="users-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1 class="page-title">User Management</h1>
                <p class="page-subtitle">View and manage all registered users</p>
            </div>
            <div class="header-actions">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search users..." class="search-input">
                    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </div>
                <select id="roleFilter" class="role-filter">
                    <option value="">All Roles</option>
                    <option value="1">Admin</option>
                    <option value="2">User</option>
                </select>
            </div>
        </div>

        <?php
        // Fetch all users for stats calculation
        $statsQuery = "SELECT roletype FROM users";
        $statsResult = mysqli_query($con, $statsQuery);
        
        $totalUsers = mysqli_num_rows($statsResult);
        $adminCount = 0;
        $userCount = 0;
        
        while ($user = mysqli_fetch_assoc($statsResult)) {
            if ($user['roletype'] == 1) {
                $adminCount++;
            } else {
                $userCount++;
            }
        }
        ?>

        <!-- User Stats -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon stat-icon-total">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <h4 class="stat-value"><?php echo $totalUsers; ?></h4>
                    <p class="stat-label">Total Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon stat-icon-admin">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5"></path>
                        <path d="M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <h4 class="stat-value"><?php echo $adminCount; ?></h4>
                    <p class="stat-label">Admins</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon stat-icon-user">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="stat-content">
                    <h4 class="stat-value"><?php echo $userCount; ?></h4>
                    <p class="stat-label">Regular Users</p>
                </div>
            </div>
        </div>

        <!-- Users Grid -->
        <div class="users-grid" id="usersGrid">
            <?php
            // Fetch all users from database
            $query = "SELECT userid, name, email, roletype, phone_number, profile_pic FROM users ORDER BY userid DESC";
            $result = mysqli_query($con, $query);

            if (mysqli_num_rows($result) > 0) {
                while ($user = mysqli_fetch_assoc($result)) {
                    $userid = htmlspecialchars($user['userid']);
                    $name = htmlspecialchars($user['name']);
                    $email = htmlspecialchars($user['email']);
                    $roletype = $user['roletype'];
                    $phone = htmlspecialchars($user['phone_number']);
                    
                    // Fix profile picture path - remove ../ if it's already in the path
$profilePic = !empty($user['profile_pic']) 
    ? '../uploads/avatars/' . $user['profile_pic'] 
    : '../uploads/avatars/default-avatar.png';
                        // Determine role display text and badge class
                    if ($roletype == 1) {
                        $roleDisplay = 'Admin';
                        $roleBadgeClass = 'role-badge role-admin';
                    } else {
                        $roleDisplay = 'User';
                        $roleBadgeClass = 'role-badge role-user';
                    }
                    
                    echo "
                    <div class='user-card' data-role='{$roletype}' data-name='{$name}' data-email='{$email}'>
                        <div class='user-card-header'>
                            <div class='user-avatar'>
                                <img src='{$profilePic}' alt='{$name}' onerror=\"this.src='../uploads/avatars/default-avatar.png'\">
                            </div>
                            <span class='{$roleBadgeClass}'>{$roleDisplay}</span>
                        </div>
                        
                        <div class='user-info'>
                            <h3 class='user-name'>{$name}</h3>
                            <p class='user-id'>ID: {$userid}</p>
                        </div>
                        
                        <div class='user-details'>
                            <div class='detail-item'>
                                <svg class='detail-icon' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                    <path d='M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z'></path>
                                    <polyline points='22,6 12,13 2,6'></polyline>
                                </svg>
                                <span class='detail-text'>{$email}</span>
                            </div>
                            <div class='detail-item'>
                                <svg class='detail-icon' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                    <path d='M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z'></path>
                                </svg>
                                <span class='detail-text'>{$phone}</span>
                            </div>
                        </div>
                        
                        <div class='user-actions'>
                            <button class='action-btn btn-view' onclick='viewUser({$userid})'>
                                <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                    <path d='M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z'></path>
                                    <circle cx='12' cy='12' r='3'></circle>
                                </svg>
                                View
                            </button>
                            <button class='action-btn btn-edit' onclick='editUser({$userid})'>
                                <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                    <path d='M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7'></path>
                                    <path d='M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z'></path>
                                </svg>
                                Edit
                            </button>
                            <button class='action-btn btn-delete' onclick='deleteUser({$userid})'>
                                <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                    <polyline points='3 6 5 6 21 6'></polyline>
                                    <path d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2'></path>
                                </svg>
                                Delete
                            </button>
                        </div>
                    </div>
                    ";
                }
            } else {
                echo "
                <div class='no-users'>
                    <svg class='no-users-icon' width='80' height='80' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.5'>
                        <path d='M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2'></path>
                        <circle cx='9' cy='7' r='4'></circle>
                        <path d='M23 21v-2a4 4 0 0 0-3-3.87'></path>
                        <path d='M16 3.13a4 4 0 0 1 0 7.75'></path>
                    </svg>
                    <h3>No Users Found</h3>
                    <p>There are no users in the system yet.</p>
                </div>
                ";
            }
            ?>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            filterUsers();
        });

        // Role filter functionality
        document.getElementById('roleFilter').addEventListener('change', function(e) {
            filterUsers();
        });

        function filterUsers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value.toLowerCase();
            const userCards = document.querySelectorAll('.user-card');

            userCards.forEach(card => {
                const name = card.dataset.name.toLowerCase();
                const email = card.dataset.email.toLowerCase();
                const role = card.dataset.role.toLowerCase();

                const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
                const matchesRole = !roleFilter || role === roleFilter;

                if (matchesSearch && matchesRole) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Action functions (you can implement these)
        function viewUser(userId) {
            window.location.href = `viewuser.php?id=${userId}`;
        }

        function editUser(userId) {
            window.location.href = `edituser.php?id=${userId}`;
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                window.location.href = `deleteuser.php?id=${userId}`;
            }
        }
    </script>
</body>
<?php include("footer.php") ?>
</html>
