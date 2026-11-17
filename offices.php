<?php
require_once __DIR__ . '/inc/session.php';
secure_session_start();
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

include('inc/auth.php');
include('config/db.php');
require_once __DIR__ . '/inc/rbac.php';
require_once __DIR__ . '/inc/db_integrity.php';
require_once __DIR__ . '/inc/csrf.php';
rbac_migrate_roles($conn);
rbac_sync_session_role($conn);
rbac_require_any($conn, ['super_admin']);
ensure_db_integrity($conn);
$currentPage = basename($_SERVER['PHP_SELF']);
$isOfficesPage = true;

// Status toggle handling. Only Super Administrators may change office status.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $toggleId = (int)($_POST['toggle_office_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    if ($toggleId > 0 && in_array($newStatus, ['active', 'inactive'], true)) {
        $stmt = $conn->prepare("UPDATE offices SET status = ? WHERE office_id = ?");
        $stmt->bind_param('si', $newStatus, $toggleId);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: offices.php?updated=1");
    exit;
}

$result = $conn->query("SELECT office_id, office_code, office_name, preferred_prefix, status, created_at FROM offices ORDER BY office_code ASC");
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
		<h2 class="text-base font-bold">Manage Offices</h2>
		<a href="add-office.php" class="button-link"><i class="fa-solid fa-plus"></i> Add Office</a>
	  </div>
	  <div class="flat-card overflow-hidden">
		<div class="overflow-x-auto">
		<table class="user-table">
		<thead>
			<tr>
				<th>ID</th>
				<th>Office Code</th>
				<th>Office Name</th>
				<th>Preferred Prefix</th>
				<th>Status</th>
				<th>Date Added</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody>
			<?php while ($row = $result->fetch_assoc()): ?>
			<tr>
				<td><?= htmlspecialchars($row['office_id']) ?></td>
				<td><?= htmlspecialchars($row['office_code']) ?></td>
				<td><?= htmlspecialchars($row['office_name']) ?></td>
				<td><?= htmlspecialchars($row['preferred_prefix']) ?></td>
				<td><?php if ($row['status'] === 'active'): ?>
				<span class="status-active">Active</span>
				<?php else: ?>
				<span class="status-inactive">Inactive</span>
				<?php endif; ?>
				</td>
				<td><?= date("M d, Y", strtotime($row['created_at'])) ?></td>
				<td>
					<a href="edit-office.php?office_id=<?= $row['office_id'] ?>" class="btn-edit">
						<i class="fa-solid fa-pen-to-square"></i> Edit
					</a>
					<?php if ($row['status'] === 'active'): ?>
					<form method="POST" action="offices.php" class="btn-delete-form" onsubmit="return confirm('Deactivate this office? It cannot be assigned to new users.');">
						<?= csrf_field() ?>
						<input type="hidden" name="toggle_office_id" value="<?= (int)$row['office_id'] ?>">
						<input type="hidden" name="new_status" value="inactive">
						<button type="submit" class="btn-delete" style="background:#FEF9EE;color:#B45309;border-color:#FCD34D;">
							<i class="fa-solid fa-ban"></i> Deactivate
						</button>
					</form>
					<?php else: ?>
					<form method="POST" action="offices.php" class="btn-delete-form" onsubmit="return confirm('Activate this office?');">
						<?= csrf_field() ?>
						<input type="hidden" name="toggle_office_id" value="<?= (int)$row['office_id'] ?>">
						<input type="hidden" name="new_status" value="active">
						<button type="submit" class="btn-edit" style="background:#ECFDF5;color:#15803D;border-color:#86EFAC;">
							<i class="fa-solid fa-check-circle"></i> Activate
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
      showToast('Office created successfully', 'success');
    }
    if (params.get('updated') === '1') {
      showToast('Office updated successfully', 'success');
    }
  });
  </script>
</body>
</html>
