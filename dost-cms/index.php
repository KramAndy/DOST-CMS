<?php
require_once __DIR__ . '/inc/session.php';
secure_session_start();
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

include('inc/guest.php');
include('config/db.php');
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/rbac.php';
rbac_migrate_roles($conn);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_verify()) {
        $error = "Invalid request. Please try again.";
    } else {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

	if (password_verify($password, $user['password'])) {
        if (strtolower($user['status']) !== 'active') {
            $_SESSION['login_error'] = 'inactive';
            header("Location: index.php");
            exit;
        }
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['employee_name'] = $user['employee_name'];
    $_SESSION['role'] = rbac_is_valid_role($user['role'] ?? '') ? $user['role'] : 'user';
		header("Location: dashboard.php");
		exit;
	}else{
			$error = "Invalid username or password.";
		}
	}else{
		$error = "Invalid username or password.";
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
	<main>
	<?php if (isset($error)): ?>
    <div class="error-message">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>
			<div class="loginForm">
			<div class="top-top-head">
        <div class="brand-mark flex items-center gap-3">
          <div class="brand-logo w-11 h-11 flex-shrink-0">
            <img class="logo" src="images/logo.png" alt="logo">
			
          </div>
          <div class="brand-text">
            <div class="brand-subtitle text-xs uppercase tracking-[0.18em] text-[var(--muted)] font-semibold">DOST-PSTO Daet</div>
            <div class="brand-title text-sm font-bold tracking-tight">Communication Management System</div>
          </div>
        </div>
		</div>
		<form method="POST" action="index.php">
			<?= csrf_field() ?>
			<div>
			<label for="username">Username</label>
			<input type="text" name="username" required placeholder="Username">
			</div>
			<div>
			<label for="password">Password</label>
			<div class="input-wrapper">
				<input type="password" id="password" name="password" required placeholder="Password" class="js-password-field">
				<i class="fa-solid fa-eye js-password-toggle" onclick="toggleVisibility(this)"></i>
			</div>
			</div>
			<button class="submit" type="submit">Login</button>
		</form>
	</div>
	</main>
<?php if (isset($_SESSION['login_error'])): ?>
<?php if ($_SESSION['login_error'] === 'inactive'): ?>
<script>
	window.addEventListener('DOMContentLoaded', function () {
		showToast('Account Inactive: Your account is currently inactive. Please contact your administrator or the appropriate person in the company to reactivate your account.', 'warn');
	});
</script>
<?php endif; ?>
<?php unset($_SESSION['login_error']); ?>
<?php endif; ?>
	<!-- Toast Notification Area -->
	<div id="notification-toast-area" class="fixed bottom-5 right-5 z-50 space-y-2"></div>
	<script src="js/utils/dom.js"></script>
	<script src="js/ui/toast.js"></script>
	<script src="js/login.js"></script>
</body>
    

</html>