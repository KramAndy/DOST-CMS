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
$currentPage = 'offices.php';
$isOfficesPage = true;

$breadcrumb = [
    'Offices' => 'offices.php',
    'Add Office' => null
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = "Invalid request. Please try again.";
    }

    if (empty($error)) {
        $office_code = strtoupper(trim($_POST['office_code'] ?? ''));
        $office_name = trim($_POST['office_name'] ?? '');
        $preferred_prefix = trim($_POST['preferred_prefix'] ?? '');

        if (empty($office_code) || empty($office_name) || empty($preferred_prefix)) {
            $error = "All fields are required.";
        }
        if (empty($error) && strlen($office_code) > 20) {
            $error = "Office code must be 20 characters or fewer.";
        }
        if (empty($error) && strlen($office_name) > 100) {
            $error = "Office name must be 100 characters or fewer.";
        }
        if (empty($error) && strlen($preferred_prefix) > 50) {
            $error = "Preferred prefix must be 50 characters or fewer.";
        }

        // Check for duplicate office code
        if (empty($error)) {
            $stmt = $conn->prepare("SELECT office_id FROM offices WHERE office_code = ?");
            $stmt->bind_param("s", $office_code);
            $stmt->execute();
            $existing = $stmt->get_result();
            $stmt->close();
            if ($existing->num_rows > 0) {
                $error = "Office code already exists.";
            }
        }

        // Check for duplicate office name
        if (empty($error)) {
            $stmt = $conn->prepare("SELECT office_id FROM offices WHERE office_name = ?");
            $stmt->bind_param("s", $office_name);
            $stmt->execute();
            $existing = $stmt->get_result();
            $stmt->close();
            if ($existing->num_rows > 0) {
                $error = "Office name already exists.";
            }
        }

        // Insert office if no errors
        if (empty($error)) {
            $stmt = $conn->prepare("
                INSERT INTO offices (office_code, office_name, preferred_prefix, status)
                VALUES (?, ?, ?, 'active')
            ");
            if (!$stmt) {
                error_log('[add-office] insert prepare failed: ' . $conn->error);
                $error = "Failed to add office. Please try again.";
            } else {
                $stmt->bind_param("sss", $office_code, $office_name, $preferred_prefix);
                if ($stmt->execute()) {
                    header("Location: offices.php?success=1");
                    exit();
                } else {
                    if ($stmt->errno === 1062) {
                        $error = "Office code or name already exists.";
                    } else {
                        error_log('[add-office] insert failed: ' . $stmt->error);
                        $error = "Failed to add office. Please try again.";
                    }
                    $stmt->close();
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
	<form method="POST" class="user-form">
		<?= csrf_field() ?>

		<div class="form-group">
			<label for="office_code">Office Code</label>
			<input
				type="text"
				name="office_code"
				value="<?= htmlspecialchars($_POST['office_code'] ?? '') ?>"
				class="flat-field w-full px-3 py-2.5 text-sm"
				placeholder="e.g. DOST-R5"
				required>
		</div>

		<div class="form-group">
			<label for="office_name">Office Name</label>
			<input
				type="text"
				name="office_name"
				value="<?= htmlspecialchars($_POST['office_name'] ?? '') ?>"
				class="flat-field w-full px-3 py-2.5 text-sm"
				placeholder="e.g. DOST Regional Office V"
				required>
		</div>

		<div class="form-group">
			<label for="preferred_prefix">Preferred Prefix</label>
			<input
				type="text"
				name="preferred_prefix"
				value="<?= htmlspecialchars($_POST['preferred_prefix'] ?? '') ?>"
				class="flat-field w-full px-3 py-2.5 text-sm"
				placeholder="e.g. RO5-CMS"
				required>
		</div>

		<button class="button-primary px-5 py-2.5 text-sm" type="submit">Create Office</button>
	</form>

	<!-- Toast Notification Area -->
	<div id="notification-toast-area" class="fixed bottom-5 right-5 z-50 space-y-2"></div>

	<script src="js/utils/dom.js"></script>
	<script src="js/utils/dates.js"></script>
	<script src="js/ui/toast.js"></script>
	<script src="js/services/documentService.js"></script>
	<script src="js/services/notificationService.js"></script>
	<script src="js/ui/notifications.js"></script>
</body>
</html>
