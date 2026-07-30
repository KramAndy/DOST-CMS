<?php
session_start();
include('auth.php');
include('config/db.php');
$currentPage = basename($_SERVER['PHP_SELF']);
$isUsersPage = true;

$breadcrumb = [
    'Users' => 'users.php',
    'Add User' => null
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = strtolower(trim($_POST['username']));
    $employee_name = trim($_POST['employee_name']);
    $password      = trim($_POST['password']);
    $confirm       = trim($_POST['confirm']);
	$status = $_POST['status'] ?? '';
   
	if (strlen($password) < 4) {
		$error = "Password must be at least 4 characters.";
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
		$error = "Database Error: " . $stmt->error;
		$stmt->close();
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
	<form method="POST" onsubmit="return validatePasswords();" class="user-form">

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
				<input type="password" name="password" id="password" class="flat-field w-full px-3 py-2.5 text-sm" required>
				<i class="fa-solid fa-eye" onclick="toggleVisibility('password', this)"></i>
			</div>
		</div>

		<div class="form-group">
			<label for="confirm">Confirm Password</label>
			<div class="input-wrapper">
				<input type="password" name="confirm" id="confirm" class="flat-field w-full px-3 py-2.5 text-sm" required>
				<i class="fa-solid fa-eye" onclick="toggleVisibility('confirm', this)"></i>
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

<script src="js/login.js"></script>
</body>
</html>