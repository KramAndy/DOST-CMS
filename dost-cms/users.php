<?php
session_start();
include('config/db.php');
include('auth.php');
$currentPage = basename($_SERVER['PHP_SELF']);
$isUsersPage = true;
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
<?php include('inc/header.php'); ?>
	<div class="table-section">
	  <div class="table-header">
		<h2 class="text-base font-bold">Manage Users</h2>
		<a href="add-users.php" class="button-link"><i class="fa-solid fa-plus"></i> Add User</a>
	  </div>
	  <div class="flat-card overflow-hidden">
		<div class="overflow-x-auto">
		<table class="user-table">
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
				<td><?php if ($row['status'] === 'active'): ?>
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
	  </div>
	</div>

</body>
</html>
