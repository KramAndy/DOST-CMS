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
rbac_require_any($conn, ['super_admin', 'office_admin']);
ensure_db_integrity($conn);
$currentPage = basename($_SERVER['PHP_SELF']);
$isUsersPage = true;
$currentUserRole = rbac_current_role();

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($user_id <= 0) {
    header("Location: users.php");
    exit();
}
$breadcrumb = [
    'Users' => 'users.php',
    'Edit User' => null
];


$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
if (!$item) {
    header("Location: users.php");
    exit();
}

// Office Administrators may only manage regular user accounts. They cannot
// modify Super Administrators, Office Administrators, or their own account.
if ($currentUserRole === 'office_admin' && ($item['role'] ?? 'user') !== 'user') {
    header("Location: users.php?denied=1");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF guard before any state change.
    if (!csrf_verify()) {
        $error = "Invalid request. Please try again.";
    }

    if (empty($error)) {
    // Sanitize inputs
    $username = strtolower(trim($_POST['username'] ?? ''));
    $employee_name = trim($_POST['employee_name'] ?? '');
    $password      = trim($_POST['password'] ?? '');
    $confirm       = trim($_POST['confirm'] ?? '');
	$status = $_POST['status'] ?? '';

   if (!empty($password) && strlen($password) < 4) {
    $error = "Password must be at least 4 characters.";
	}
    if (empty($error) && strlen($username) > 20) {
        $error = "Username must be 20 characters or fewer.";
    }
    if (empty($error) && strlen($employee_name) > 50) {
        $error = "Full name must be 50 characters or fewer.";
    }
    if (empty($error) && strlen($password) > 72) {
        $error = "Password must be 72 characters or fewer.";
    }
    if (empty($error) && !in_array($status, ['active', 'inactive'], true)) {
        $error = "Invalid status selected.";
	}
    // Role assignment: only Super Administrators may change roles.
    // Office Administrators can only edit regular users and cannot assign
    // administrative roles, so the target stays a regular user.
    $role = 'user';
    if ($currentUserRole === 'super_admin') {
        $postedRole = $_POST['role'] ?? 'user';
        $role = rbac_is_valid_role($postedRole) ? $postedRole : 'user';
    }


    // Check if username already exists
    if (empty($error)) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    $existing = $stmt->get_result();
	$stmt->close();
    if ($existing->num_rows > 0) {
        $error = "Username already taken.";
    }

	} 


   if (empty($error) && (empty($username) || empty($employee_name) || empty($status))) {
        $error = "All fields except password are required.";
    } elseif (empty($error) && !empty($password) && $password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (empty($error)) {
        // Optimistic lock: only save if the record was not modified by
        // another session since this page was loaded. Incrementing version
        // guarantees the row always changes when the lock matches, so a
        // zero affected-rows result reliably means a stale lock.
        $update_query = "UPDATE users 
                 SET username=?, employee_name=?, status=?, role=?, version=version+1";
        
		$types = "ssss";
		$params = [$username, $employee_name, $status, $role];


        // Add password if provided
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update_query .= ", password=?";
            $types .= "s";
            $params[] = $hashed;
        }

        $update_query .= " WHERE user_id=? AND version=?";
        $types .= "ii";
        $params[] = $user_id;
        $params[] = (int)($item['version'] ?? 0);

        $stmt = $conn->prepare($update_query);
        if (!$stmt) {
            error_log('[edit-users] update prepare failed: ' . $conn->error);
            $error = "Failed to update user.";
        } else {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
            if ($conn->affected_rows === 0) {
                $error = "This user was modified by another session. Please reload the page and try again.";
            } else {
                $item = array_merge($item, [
                    'username' => $username,
                    'employee_name' => $employee_name,
                    'status' => $status,
                    'role' => $role,
                    'version' => ((int)($item['version'] ?? 0)) + 1
                ]);
                $stmt->close();
                header("Location: users.php?updated=1");
                exit();
            }
        } else {
            $stmt->close();
            // Duplicate username despite the pre-check (concurrent request).
            if ($stmt->errno === 1062) {
                $error = "Username already taken.";
            } else {
                error_log('[edit-users] update failed: ' . $stmt->error);
                $error = "Failed to update user.";
            }
        }
    }
    }
    }
}
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
	<nav class="breadcrumb">
    <?php
    $last = array_key_last($breadcrumb);

    foreach ($breadcrumb as $label => $link) {
        if ($link && $label !== $last) {
            echo "<a href=\"$link\">$label</a>";
        } else {
            echo "<span>$label</span>";
        }

        if ($label !== $last) {
            echo '<i class="fa-solid fa-chevron-right separator"></i>';
        }
    }
    ?>
	</nav>
	<?php if (isset($error)): ?>
	<div class="error-message user-form-error">
		<i class="fa-solid fa-circle-exclamation"></i>
		<span><?= htmlspecialchars($error) ?></span>
	</div>
	<?php endif; ?>
	<form method="POST" onsubmit="return validatePasswords();" class="user-form">
		<?= csrf_field() ?>

		<div class="form-group">
			<label for="username">Username</label>
			<input
				type="text"
				name="username"
				value="<?= htmlspecialchars($_POST['username'] ?? $item['username']) ?>"
				class="flat-field w-full px-3 py-2.5 text-sm"
				required>
		</div>

		<div class="form-group">
			<label for="employee_name">Full Name</label>
			<input
				type="text"
				name="employee_name"
				value="<?= htmlspecialchars($_POST['employee_name'] ?? $item['employee_name']) ?>"
				class="flat-field w-full px-3 py-2.5 text-sm"
				required>
		</div>

		<div class="form-group">
			<label for="password">Password</label>
			<div class="input-wrapper">
				<input type="password" name="password" id="password" class="flat-field w-full px-3 py-2.5 text-sm js-password-field">
				<i class="fa-solid fa-eye js-password-toggle" onclick="toggleVisibility(this)"></i>
			</div>
		</div>

		<div class="form-group">
			<label for="confirm">Confirm Password</label>
			<div class="input-wrapper">
				<input type="password" name="confirm" id="confirm" class="flat-field w-full px-3 py-2.5 text-sm js-password-field">
				<i class="fa-solid fa-eye js-password-toggle" onclick="toggleVisibility(this)"></i>
			</div>
		</div>
		
		<div class="form-group">
			<label for="status" class="text-sm font-medium block mb-1">Status</label>
			<select name="status" id="status" class="flat-field w-full px-3 py-2.5 text-sm" required>
			<option value="">-- Select Status --</option>
			<option value="active" <?= ($item['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
			<option value="inactive" <?= ($item['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
			</select>
		</div>

		<?php if ($currentUserRole === 'super_admin'): ?>
		<div class="form-group">
			<label for="role" class="text-sm font-medium block mb-1">Role</label>
			<select name="role" id="role" class="flat-field w-full px-3 py-2.5 text-sm" required>
				<option value="user" <?= ($_POST['role'] ?? $item['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option>
				<option value="office_admin" <?= ($_POST['role'] ?? $item['role'] ?? '') === 'office_admin' ? 'selected' : '' ?>>Office Administrator</option>
				<option value="super_admin" <?= ($_POST['role'] ?? $item['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Administrator</option>
			</select>
		</div>
		<?php else: ?>
		<div class="form-group">
			<label class="text-sm font-medium block mb-1">Role</label>
			<div class="flat-field w-full px-3 py-2.5 text-sm text-[var(--muted)]">User</div>
		</div>
		<?php endif; ?>
		<button class="button-primary px-5 py-2.5 text-sm" type="submit">Save Changes</button>
	</form>

	<!-- Toast Notification Area -->
	<div id="notification-toast-area" class="fixed bottom-5 right-5 z-50 space-y-2"></div>

	<script src="js/utils/dom.js"></script>
	<script src="js/utils/dates.js"></script>
	<script src="js/ui/toast.js"></script>
	<script src="js/services/documentService.js"></script>
	<script src="js/services/notificationService.js"></script>
	<script src="js/ui/notifications.js"></script>
	<script src="js/login.js"></script>
</body>
</html>