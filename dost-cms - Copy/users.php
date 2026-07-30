<?php
session_start();
include('config/db.php');
include('auth.php');
$currentPage = basename($_SERVER['PHP_SELF']);
	$result = $conn->query("
    SELECT
        user_id,
        employee_name,
        username,
		status,
		created_at
    FROM users
    ORDER BY employee_name ASC
");



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
       class="redirect-card <?= $currentPage == 'users.php' ? 'active' : '' ?>">
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
	<a href="add-users.php">Add Users</a>
	<div class="table-section">
		<table>
		<thead>
			<tr>
				<th>ID</th>
				<th>Name</th>
				<th>Username</th>
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
				<td<?php if ($row['status'] === 'active'): ?>
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
				</td>
				
			</tr>
		
			<?php endwhile; ?>
			</tbody>
		</table>
		</div>
	
</body>


</html>