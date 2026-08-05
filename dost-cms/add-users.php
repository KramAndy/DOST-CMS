<?php
require_once __DIR__ . '/inc/session.php';
secure_session_start();
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

include('inc/auth.php');
include('config/db.php');
require_once __DIR__ . '/inc/csrf.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$isUsersPage = true;

$breadcrumb = [
    'Users' => 'users.php',
    'Add User' => null
];

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

    if (empty($username) || empty($employee_name) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    }
    if (empty($error) && strlen($password) < 4) {
		$error = "Password must be at least 4 characters.";
	}
    if (empty($error) && !in_array($status, ['active', 'inactive'], true)) {
        $status = 'active';
    }
   // Validate password match
    if (empty($error) && $password !== $confirm) {
        $error = "Passwords do not match.";
    }

    // Check if username already exists
    if (empty($error)) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $existing = $stmt->get_result();
	$stmt->close();
    if ($existing->num_rows > 0) {
        $error = "Username already taken.";
    }

}

    // Insert user if no errors
    if (empty($error)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
			INSERT INTO users (username, employee_name, password, status) 
			VALUES (?, ?, ?, ?)
		");
		$stmt->bind_param(
			"ssss", 
			$username, $employee_name, $hashed, $status	);


        if ($stmt->execute()) {
		header("Location: users.php?success=1");
		exit();
	} else {
		error_log('[add-users] insert failed: ' . $stmt->error);
		$error = "Failed to add user. Please try again.";
		$stmt->close();
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
				value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
				class="flat-field w-full px-3 py-2.5 text-sm"
				required>
		</div>

		<div class="form-group">
			<label for="employee_name">Full Name</label>
			<input
				type="text"
				name="employee_name"
				value="<?= htmlspecialchars($_POST['employee_name'] ?? '') ?>"
				class="flat-field w-full px-3 py-2.5 text-sm"
				required>
		</div>
		
		<div class="form-group">
			<label for="password">Password</label>
			<div class="input-wrapper">
				<input type="password" name="password" id="password" class="flat-field w-full px-3 py-2.5 text-sm js-password-field" required>
				<i class="fa-solid fa-eye js-password-toggle" onclick="toggleVisibility(this)"></i>
			</div>
		</div>

		<div class="form-group">
			<label for="confirm">Confirm Password</label>
			<div class="input-wrapper">
				<input type="password" name="confirm" id="confirm" class="flat-field w-full px-3 py-2.5 text-sm js-password-field" required>
				<i class="fa-solid fa-eye js-password-toggle" onclick="toggleVisibility(this)"></i>
			</div>
		</div>
		<div class="form-group">
			<label class="text-sm font-medium block mb-2">Status</label>
			<label class="inline-flex items-center gap-2 mr-4 text-sm">
<input type="radio" name="status" value="active" checked>
            Active
          </label>
          <label class="inline-flex items-center gap-2 text-sm">
            <input type="radio" name="status" value="inactive">
            Inactive
			</label>
		</div>
		<button class="button-primary px-5 py-2.5 text-sm" type="submit">Create User</button>
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