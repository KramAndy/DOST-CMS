<?php
require_once __DIR__ . '/inc/session.php';
secure_session_start();
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

include('inc/auth.php');
include('config/db.php');
require_once __DIR__ . '/inc/rbac.php';
require_once __DIR__ . '/inc/csrf.php';
rbac_migrate_roles($conn);
rbac_sync_session_role($conn);
rbac_require_any($conn, ['super_admin', 'office_admin']);
$currentPage = basename($_SERVER['PHP_SELF']);
$isUsersPage = true;
$currentUserRole = rbac_current_role();

// Delete handling. Only Super Administrators may delete accounts, and they
// can never delete their own active account (prevents self-lockout).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($currentUserRole === 'super_admin' && csrf_verify()) {
        $deleteId = (int)($_POST['delete_user_id'] ?? 0);
        if ($deleteId > 0 && $deleteId !== (int)$_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param('i', $deleteId);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: users.php?deleted=1");
    exit;
}

// Office Administrators may manage only regular user accounts.
$query = "
    SELECT
        user_id,
        employee_name,
        username,
        role,
        status,
        created_at
    FROM users";
if ($currentUserRole === 'office_admin') {
    $query .= " WHERE role = 'user'";
}
$query .= "
    ORDER BY employee_name ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Communication Management System · DOST Region V</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/document.css">
</head>
<body>

  <!-- Top Accent Stripe -->
  <div class="h-[3px] bg-[var(--accent)]"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

    <!-- ======================== PAGE HEADER ======================== -->
<?php include('inc/header.php'); ?>
	<div class="table-section">
	  <div class="table-header">
		<h2 class="text-base font-bold">Manage Users</h2>
		<a href="add-users.php" class="button-link"><i class="fa-solid fa-plus"></i> Add User</a>
	  </div>
	  <div class="flat-card overflow-hidden">
		<div class="overflow-x-auto">
		<table class="user-table">
		<thead>
			<tr>
				<th>ID</th>
				<th>Name</th>
				<th>Username</th>
				<th>Role</th>
				<th>Status</th>
				<th>Date Added</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody>
			<?php while ($row = $result->fetch_assoc()): ?>
			<tr>
				<td><?= htmlspecialchars($row['user_id']) ?></td>
				<td><?= htmlspecialchars($row['employee_name']) ?></td>
				<td><?= htmlspecialchars($row['username']) ?></td>
				<td><?= htmlspecialchars($row['role'] ?? 'user') ?></td>
				<td><?php if ($row['status'] === 'active'): ?>
				<span class="status-active">Active</span>
				<?php else: ?>
				<span class="status-inactive">Inactive</span>
				<?php endif; ?>
				</td>
				<td><?= date("M d, Y", strtotime($row['created_at'])) ?></td>
				<td>
					<a href="edit-users.php?user_id=<?= $row['user_id'] ?>" class="btn-edit">
						<i class="fa-solid fa-pen-to-square"></i> Edit
					</a>
					<?php if ($currentUserRole === 'super_admin' && (int)$row['user_id'] !== (int)$_SESSION['user_id']): ?>
					<form method="POST" action="users.php" class="btn-delete-form" onsubmit="return confirm('Delete this user account? This cannot be undone.');">
						<?= csrf_field() ?>
						<input type="hidden" name="delete_user_id" value="<?= (int)$row['user_id'] ?>">
						<button type="submit" class="btn-delete">
							<i class="fa-solid fa-trash-can"></i> Delete
						</button>
					</form>
					<?php endif; ?>
				</td>
			</tr>
			<?php endwhile; ?>
			</tbody>
		</table>
		</div>
	  </div>
	</div>

	<!-- Toast Notification Area -->
	<div id="notification-toast-area" class="fixed bottom-5 right-5 z-50 space-y-2"></div>

  <script src="js/utils/dom.js"></script>
  <script src="js/utils/dates.js"></script>
  <script src="js/ui/toast.js"></script>
  <script src="js/services/documentService.js"></script>
  <script src="js/services/notificationService.js"></script>
  <script src="js/ui/notifications.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    var params = new URLSearchParams(window.location.search);
    if (params.get('success') === '1') {
      showToast('User added successfully', 'success');
    }
    if (params.get('updated') === '1') {
      showToast('User updated successfully', 'success');
    }
    if (params.get('deleted') === '1') {
      showToast('User deleted successfully', 'success');
    }
    if (params.get('denied') === '1') {
      showToast('You do not have permission to manage this account.', 'warn');
    }
  });
  </script>
</body>
</html>
