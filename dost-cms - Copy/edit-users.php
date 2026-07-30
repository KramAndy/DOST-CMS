<?php
session_start();
include('auth.php');
include('config/db.php');
$currentPage = basename($_SERVER['PHP_SELF']);

$userPages = [
    'users.php',
    'add-users.php',
    'edit-users.php'
];

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
        $stmt->execute();
        $item = array_merge($item, [
            'username' => $username,
            'employee_name' => $employee_name,
			'status' => $status
        ]);
		
		if ($stmt->execute()) {
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
    <header class="mb-8">
      <div class="header-top-bar flex items-center justify-between mb-8 gap-4 flex-wrap">
        <div class="brand-mark flex items-center gap-3">
          <div class="brand-logo w-11 h-11 flex-shrink-0">
            <img class="logo" src="images/logo.png" alt="logo">
          </div>
          <div class="brand-text">
            <div class="brand-subtitle text-[10px] uppercase tracking-[0.18em] text-[var(--muted)] font-semibold">DOST Regional Office No. V</div>
            <div class="brand-title text-sm font-bold tracking-tight">Communication Management System</div>
          </div>
        </div>
        <div class="header-actions flex items-center gap-3">
          <button class="button-secondary text-xs font-medium px-4 py-2 flex items-center gap-2">
            <i class="fa-solid fa-bell text-[11px]"></i> Notifications
          </button>
          <div class="user-avatar w-9 h-9 rounded-full bg-[var(--accent)] flex items-center justify-center text-[11px] font-bold text-white flex-shrink-0">
		  <?php
			$nameParts = explode(' ', $_SESSION['employee_name']);
			$initials = '';

			foreach ($nameParts as $part) {
				$initials .= strtoupper($part[0]);
			}

			echo $initials;
			?>
			</div>
		  </div>
      </div>

      <div class="header-intro border-b border-[var(--line)] pb-7">
        <div class="flex items-center gap-3 mb-4">
          <div class="section-accent-bar"></div>
          <span class="text-[11px] uppercase tracking-[0.2em] text-[var(--accent)] font-bold">Document Tracking Dashboard</span>
        </div>
        <h1 class="page-title text-3xl sm:text-4xl md:text-5xl font-bold leading-[1.08] tracking-tight">
          Manage communications<br>with <span class="text-[var(--accent)]">clarity</span>.
        </h1>
        <p class="page-description mt-4 text-[var(--muted)] max-w-xl text-sm leading-relaxed">
          Generate automatic reference numbers, configure formats, and track the lifecycle of official documents in real-time.
        </p>
		<div class="nav">

    <a href="dashboard.php"
       class="redirect-card <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
        <div class="redirect-icon bg-[var(--accent-soft)] text-[var(--accent)]">
            <i class="fa-solid fa-gauge-high"></i>
        </div>

        <div class="card-label">
            Dashboard
        </div>
    </a>

    <a href="users.php"
   class="redirect-card <?= in_array($currentPage, $userPages) ? 'active' : '' ?>">
    <div class="redirect-icon bg-[var(--accent-soft)] text-[var(--accent)]">
        <i class="fa-solid fa-user"></i>
    </div>

    <div class="card-label">
        Users
    </div>
</a>

</div>
      </div>
    </header>
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
	<form method="POST" onsubmit="return validatePasswords();">

		<div class="form-group">
			<label for="username">Username:</label>
			<input
				type="text"
				name="username"
				value="<?= htmlspecialchars($_POST['username'] ?? $item['username']) ?>"
				required>
		</div>

		<div class="form-group">
			<label for="employee_name">Full Name:</label>
			<input
				type="text"
				name="employee_name"
				value="<?= htmlspecialchars($_POST['employee_name'] ?? $item['employee_name']) ?>"
				required>
		</div>

		<div class="form-group">
			<label for="password">Password:</label>
			<div class="input-wrapper">
				<input type="password" name="password" id="password" >
				<i class="fa-solid fa-eye" onclick="toggleVisibility('password', this)"></i>
			</div>
		</div>

		<div class="form-group">
			<label for="confirm">Confirm Password:</label>
			<div class="input-wrapper">
				<input type="password" name="confirm" id="confirm" >
				<i class="fa-solid fa-eye" onclick="toggleVisibility('confirm', this)"></i>
			</div>
		</div>
		
		<div class="form-group">
			<label for="status">Status:</label>
			<select name="status" id="status" required>
			<option value="">-- Select Status --</option>
			<option value="active">Active</option>
			<option value="inactive">Inactive</option>
			</select>
		</div>
		
		<button class="submit" type="submit">Edit User</button><br>
	</form>
<script src="js/login.js"></script>
</body>
</html>