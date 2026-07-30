<?php
session_start();
include('config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = strtolower(trim($_POST['username']));
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
		header("Location: dashboard.php");
		exit;
	}else{
			$error = "Incorrect Password.";
		}
	}else{
		$error = "User does not exist!";
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
  <link rel="stylesheet" href="css/Document.css">
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
            <div class="brand-subtitle text-[16 	px] uppercase tracking-[0.18em] text-[var(--muted)] font-semibold">DOST-PSTO Daet</div>
            <div class="brand-title text-sm font-bold tracking-tight">Communication Management System</div>
          </div>
        </div>
		</div>
		<form method="POST" action="index.php">
			<div>
			<label for="username">Username: </label>
			<input type="text" name="username" required placeholder="Username"> <br>
			</div>
			<div>
			<label for="username">Password: </label>
			<input type="password" id="password" name="password" required placeholder="Password">
			</div>
			<button class="showPass" type="button" onclick="togglePassword()">👁 Show Password</button>
			<button class="submit" type="submit">Login</button><br>
		</form>
	</div>
	</main>
<script src="js/login.js"></script>
</body>
    

</html>