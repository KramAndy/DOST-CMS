<?php
session_start();
include('auth.php');
include('config/db.php');
$currentPage = basename($_SERVER['PHP_SELF']);
$isUsersPage = true;

$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = strtolower(trim($_POST['username']));
    $employee_name = trim($_POST['employee_name']);
    $password      = trim($_POST['password']);
    $confirm       = trim($_POST['confirm']);
	$status = $_POST['status'] ?? '';
   
   if (!empty($password) && strlen($password) < 4) {
    $error = "Password must be at least 4 characters.";
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


   if (empty($username)|| empty($employee_name) || empty($status)) {
        $error = "All fields except password are required.";
    } elseif (!empty($password) && $password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Base query
        $update_query = "UPDATE users 
                 SET username=?, employee_name=?, status=?";
        
		$types = "sss";
		$params = [$username, $employee_name, $status];


        // Add password if provided
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update_query .= ", password=?";
            $types .= "s";
            $params[] = $hashed;
        }

        $update_query .= " WHERE user_id=?";
        $types .= "i";
        $params[] = $user_id;

        $stmt = $conn->prepare($update_query);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
        $item = array_merge($item, [
            'username' => $username,
            'employee_name' => $employee_name,
			'status' => $status
        ]);
		$stmt->close();
		header("Location: users.php?updated=1");
		exit();
		} else {
			$stmt->close();
			$error = "Failed to update user.";
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
				<input type="password" name="password" id="password" class="flat-field w-full px-3 py-2.5 text-sm">
				<i class="fa-solid fa-eye" onclick="toggleVisibility('password', this)"></i>
			</div>
		</div>

		<div class="form-group">
			<label for="confirm">Confirm Password</label>
			<div class="input-wrapper">
				<input type="password" name="confirm" id="confirm" class="flat-field w-full px-3 py-2.5 text-sm">
				<i class="fa-solid fa-eye" onclick="toggleVisibility('confirm', this)"></i>
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
		
		<button class="button-primary px-5 py-2.5 text-sm" type="submit">Edit User</button>
	</form>
<script src="js/login.js"></script>
</body>
</html>