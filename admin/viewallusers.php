<?php
include("connect.php");
include("admin_header.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineXpress - View All Users</title>
    <link rel="stylesheet" href="../css/view_all_users.css">
        <link rel="icon" type="image/png" href="../images/icon.ico">

    <style>
        /* ── Modal Overlay ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: #1a1a2e;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 2rem;
            width: 100%;
            max-width: 480px;
            animation: slideUp 0.25s ease;
            position: relative;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.1);
            color: #ccc;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            line-height: 1;
            transition: background 0.2s;
        }

        .modal-close:hover { background: rgba(255,255,255,0.15); }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1.5rem;
            padding-right: 2rem;
        }

        /* ── View Modal ── */
        .view-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent, #D4AF37);
            display: block;
            margin: 0 auto 1.25rem;
        }

        .view-name {
            text-align: center;
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.25rem;
        }

        .view-role-badge {
            display: block;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .view-rows {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .view-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
        }

        .view-row svg { color: #D4AF37; flex-shrink: 0; }

        .view-row-label {
            font-size: 0.75rem;
            color: rgba(245,245,245,0.5);
            margin-bottom: 2px;
        }

        .view-row-value {
            font-size: 0.9rem;
            color: #f5f5f5;
            word-break: break-all;
        }

        /* ── Edit Modal ── */
        .edit-form .form-group {
            margin-bottom: 1rem;
        }

        .edit-form label {
            display: block;
            font-size: 0.8rem;
            color: rgba(245,245,245,0.6);
            margin-bottom: 6px;
        }

        .edit-form input,
        .edit-form select {
            width: 100%;
            padding: 10px 14px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #f5f5f5;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }

        .edit-form input:focus,
        .edit-form select:focus {
            outline: none;
            border-color: #D4AF37;
        }

        .edit-form select option { background: #1a1a2e; }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 1.5rem;
        }

        .btn-modal {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: opacity 0.2s;
        }

        .btn-modal:hover { opacity: 0.85; }

        .btn-save   { background: #D4AF37; color: #0d0d0d; }
        .btn-cancel { background: rgba(255,255,255,0.07); color: #ccc; border: 1px solid rgba(255,255,255,0.1); }
        .btn-confirm-delete { background: #E63946; color: #fff; }

        /* ── Delete Modal ── */
        .delete-icon {
            width: 64px;
            height: 64px;
            background: rgba(230,57,70,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
        }

        .delete-icon svg { stroke: #E63946; }

        .delete-text {
            text-align: center;
            color: rgba(245,245,245,0.65);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 0.25rem;
        }

        .delete-name-highlight {
            color: #fff;
            font-weight: 700;
        }

        /* ── Toast Notification ── */
        #toast-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .toast {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            min-width: 260px;
            max-width: 360px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.35);
            animation: toastIn 0.3s ease forwards;
            pointer-events: all;
        }

        .toast.hiding {
            animation: toastOut 0.3s ease forwards;
        }

        @keyframes toastIn {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes toastOut {
            from { opacity: 1; transform: translateY(0); }
            to   { opacity: 0; transform: translateY(16px); }
        }

        .toast-success {
            background: #1a2e1e;
            border: 1px solid rgba(52,199,89,0.35);
            color: #4ade80;
        }

        .toast-error {
            background: #2e1a1a;
            border: 1px solid rgba(230,57,70,0.35);
            color: #f87171;
        }

        .toast-icon-wrap {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .toast-success .toast-icon-wrap { background: rgba(52,199,89,0.15); }
        .toast-error   .toast-icon-wrap { background: rgba(230,57,70,0.15); }

        .toast-body { display: flex; flex-direction: column; gap: 2px; }
        .toast-label { font-size: 0.75rem; opacity: 0.65; font-weight: 500; }
        .toast-message { font-size: 0.9rem; font-weight: 700; color: #fff; }
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div id="toast-container"></div>

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
        $statsQuery  = "SELECT roletype FROM users";
        $statsResult = mysqli_query($con, $statsQuery);
        $totalUsers  = mysqli_num_rows($statsResult);
        $adminCount  = 0;
        $userCount   = 0;

        while ($u = mysqli_fetch_assoc($statsResult)) {
            if ($u['roletype'] == 1) $adminCount++; else $userCount++;
        }
        ?>

        <!-- Stats -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon stat-icon-total">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="stat-content">
                    <h4 class="stat-value"><?= $totalUsers ?></h4>
                    <p class="stat-label">Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-admin">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                </div>
                <div class="stat-content">
                    <h4 class="stat-value"><?= $adminCount ?></h4>
                    <p class="stat-label">Admins</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-user">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="stat-content">
                    <h4 class="stat-value"><?= $userCount ?></h4>
                    <p class="stat-label">Regular Users</p>
                </div>
            </div>
        </div>

        <!-- Users Grid -->
        <div class="users-grid" id="usersGrid">
            <?php
            $query  = "SELECT userid, name, email, roletype, phone_number, profile_pic FROM users ORDER BY userid DESC";
            $result = mysqli_query($con, $query);

            if (mysqli_num_rows($result) > 0) {
                while ($user = mysqli_fetch_assoc($result)) {
                    $userid      = (int) $user['userid'];
                    $name        = htmlspecialchars($user['name']);
                    $email       = htmlspecialchars($user['email']);
                    $roletype    = (int) $user['roletype'];
                    $phone       = htmlspecialchars($user['phone_number']);
                    $profilePic  = !empty($user['profile_pic'])
                        ? '../uploads/avatars/' . $user['profile_pic']
                        : '../uploads/avatars/default-avatar.png';

                    $roleDisplay    = $roletype == 1 ? 'Admin' : 'User';
                    $roleBadgeClass = $roletype == 1 ? 'role-badge role-admin' : 'role-badge role-user';

                    $dataJson = htmlspecialchars(json_encode([
                        'userid'  => $userid,
                        'name'    => $user['name'],
                        'email'   => $user['email'],
                        'phone'   => $user['phone_number'],
                        'role'    => $roletype,
                        'pic'     => $profilePic,
                    ]), ENT_QUOTES);

                    echo "
                    <div class='user-card' data-role='{$roletype}' data-name='{$name}' data-email='{$email}' data-user='{$dataJson}'>
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
                                <svg class='detail-icon' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z'/><polyline points='22,6 12,13 2,6'/></svg>
                                <span class='detail-text'>{$email}</span>
                            </div>
                            <div class='detail-item'>
                                <svg class='detail-icon' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z'/></svg>
                                <span class='detail-text'>{$phone}</span>
                            </div>
                        </div>
                        <div class='user-actions'>
                            <button class='action-btn btn-view' onclick='openViewModal(this)'>
                                <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z'/><circle cx='12' cy='12' r='3'/></svg>
                                View
                            </button>
                            <button class='action-btn btn-edit' onclick='openEditModal(this)'>
                                <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7'/><path d='M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z'/></svg>
                                Edit
                            </button>
                            <button class='action-btn btn-delete' onclick='openDeleteModal(this)'>
                                <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><polyline points='3 6 5 6 21 6'/><path d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2'/></svg>
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
                        <path d='M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2'/><circle cx='9' cy='7' r='4'/>
                        <path d='M23 21v-2a4 4 0 0 0-3-3.87'/><path d='M16 3.13a4 4 0 0 1 0 7.75'/>
                    </svg>
                    <h3>No Users Found</h3>
                    <p>There are no users in the system yet.</p>
                </div>
                ";
            }
            ?>
        </div>
    </div>

    <!-- ░░ VIEW MODAL ░░ -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('viewModal')">✕</button>
            <p class="modal-title">User Details</p>
            <img id="view-pic" src="" alt="" class="view-avatar">
            <p class="view-name" id="view-name"></p>
            <span class="view-role-badge" id="view-role-badge"></span>
            <div class="view-rows">
                <div class="view-row">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <div>
                        <p class="view-row-label">User ID</p>
                        <p class="view-row-value" id="view-id"></p>
                    </div>
                </div>
                <div class="view-row">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <div>
                        <p class="view-row-label">Email</p>
                        <p class="view-row-value" id="view-email"></p>
                    </div>
                </div>
                <div class="view-row">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <div>
                        <p class="view-row-label">Phone</p>
                        <p class="view-row-value" id="view-phone"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ░░ EDIT MODAL ░░ -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('editModal')">✕</button>
            <p class="modal-title">Edit User</p>
            <form class="edit-form" id="editForm">
                <input type="hidden" name="userid" id="edit-userid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="edit-name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="edit-email" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone_number" id="edit-phone" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="roletype" id="edit-role">
                        <option value="1">Admin</option>
                        <option value="2">User</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ░░ DELETE MODAL ░░ -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('deleteModal')">✕</button>
            <div class="delete-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </div>
            <p class="modal-title" style="text-align:center;">Delete User?</p>
            <p class="delete-text">
                You are about to permanently delete<br>
                <span class="delete-name-highlight" id="delete-name"></span>.<br>
                This action cannot be undone.
            </p>
            <div class="modal-actions">
                <button type="button" class="btn-modal btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="button" class="btn-modal btn-confirm-delete" id="delete-confirm-btn">Yes, Delete</button>
            </div>
        </div>
    </div>

    <script>
        /* ─────────────────────────────────────────
           Helpers
        ───────────────────────────────────────── */
        function getUser(btn) {
            return JSON.parse(btn.closest('.user-card').dataset.user);
        }

        function openModal(id)  { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        /* Close on backdrop click */
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) closeModal(this.id);
            });
        });

        /* ─────────────────────────────────────────
           Toast Notification
           type: 'success' | 'error'
        ───────────────────────────────────────── */
        function showToast(label, message, type = 'success') {
            const container = document.getElementById('toast-container');

            const iconSuccess = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>`;
            const iconError   = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>`;

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="toast-icon-wrap">${type === 'success' ? iconSuccess : iconError}</div>
                <div class="toast-body">
                    <span class="toast-label">${label}</span>
                    <span class="toast-message">${message}</span>
                </div>
            `;

            container.appendChild(toast);

            /* Auto-dismiss after 3.5s */
            setTimeout(() => {
                toast.classList.add('hiding');
                toast.addEventListener('animationend', () => toast.remove());
            }, 3500);
        }

        /* ─────────────────────────────────────────
           View Modal
        ───────────────────────────────────────── */
        function openViewModal(btn) {
            const u = getUser(btn);
            document.getElementById('view-pic').src              = u.pic;
            document.getElementById('view-name').textContent     = u.name;
            document.getElementById('view-id').textContent       = '#' + u.userid;
            document.getElementById('view-email').textContent    = u.email;
            document.getElementById('view-phone').textContent    = u.phone;

            const badge = document.getElementById('view-role-badge');
            badge.textContent = u.role == 1 ? 'Admin' : 'User';
            badge.className   = 'view-role-badge ' + (u.role == 1 ? 'role-badge role-admin' : 'role-badge role-user');

            openModal('viewModal');
        }

        /* ─────────────────────────────────────────
           Edit Modal
        ───────────────────────────────────────── */
        function openEditModal(btn) {
            const u = getUser(btn);
            document.getElementById('edit-userid').value = u.userid;
            document.getElementById('edit-name').value   = u.name;
            document.getElementById('edit-email').value  = u.email;
            document.getElementById('edit-phone').value  = u.phone;
            document.getElementById('edit-role').value   = u.role;
            openModal('editModal');
        }

        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const res  = await fetch('update_user.php', { method: 'POST', body: new FormData(this) });
            const data = await res.json();

            if (data.success) {
                closeModal('editModal');
                showToast('Success', 'User updated successfully', 'success');
                setTimeout(() => location.reload(), 1800);
            } else {
                showToast('Error', data.error || 'Update failed', 'error');
            }
        });

        /* ─────────────────────────────────────────
           Delete Modal
        ───────────────────────────────────────── */
        let pendingDeleteId = null;

        function openDeleteModal(btn) {
            const u = getUser(btn);
            pendingDeleteId = u.userid;
            document.getElementById('delete-name').textContent = u.name;
            openModal('deleteModal');
        }

        document.getElementById('delete-confirm-btn').addEventListener('click', async function() {
            if (!pendingDeleteId) return;

            const res  = await fetch('delete_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + pendingDeleteId
            });
            const data = await res.json();

            if (data.success) {
                closeModal('deleteModal');
                showToast('Deleted', 'User deleted successfully', 'error');
                setTimeout(() => location.reload(), 1800);
            } else {
                showToast('Error', 'Delete failed. Please try again.', 'error');
            }

            pendingDeleteId = null;
        });

        /* ─────────────────────────────────────────
           Search & Filter
        ───────────────────────────────────────── */
        document.getElementById('searchInput').addEventListener('input', filterUsers);
        document.getElementById('roleFilter').addEventListener('change', filterUsers);

        function filterUsers() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const role   = document.getElementById('roleFilter').value;

            document.querySelectorAll('.user-card').forEach(card => {
                const matchSearch = card.dataset.name.toLowerCase().includes(search) ||
                                    card.dataset.email.toLowerCase().includes(search);
                const matchRole   = !role || card.dataset.role === role;
                card.style.display = (matchSearch && matchRole) ? 'flex' : 'none';
            });
        }
    </script>
</body>
<?php include("footer.php") ?>
</html>