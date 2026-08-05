<?php
require_once __DIR__ . '/csrf.php';
$isUsersPage = $isUsersPage ?? false;
?>
    <header class="mb-8 sm:mb-12">
      <script>window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;</script>
      <div class="header-top-bar flex items-center justify-between mb-8 gap-4 flex-wrap">
        <div class="brand-mark flex items-center gap-3">
          <div class="brand-logo logo-tile w-11 h-11 flex-shrink-0">
            <img class="logo" src="images/logo.png" alt="logo">
          </div>
          <div class="brand-text">
            <div class="brand-subtitle text-[10px] uppercase tracking-[0.18em] text-[var(--muted)] font-semibold">DOST Regional Office No. V</div>
            <div class="brand-title font-display text-sm font-bold tracking-tight">Communication Management System</div>
          </div>
        </div>
        <div class="header-actions flex items-center gap-3">
          <div class="relative" id="notification-container">
            <button id="notification-bell-button" class="button-secondary text-xs font-medium px-4 py-2 flex items-center gap-2 relative" title="Notifications">
              <i class="fa-solid fa-bell text-[11px]"></i>
              <span class="hidden sm:inline">Notifications</span>
              <span id="notification-badge" class="notification-badge hidden">0</span>
            </button>
            <div id="notification-dropdown" class="notification-dropdown hidden">
              <div class="notification-dropdown-header flex items-center justify-between px-4 py-3 border-b border-[var(--line)]">
                <span class="text-sm font-bold">Notifications</span>
                <button id="mark-all-notifications-read" class="notification-action-trigger text-[11px] font-medium">Mark all read</button>
              </div>
              <div id="notification-list" class="notification-list max-h-96 overflow-y-auto"></div>
            </div>
          </div>
          <div class="relative" id="avatar-container">
            <button id="avatar-btn" class="user-avatar w-9 h-9 rounded-full bg-[var(--accent)] flex items-center justify-center text-[11px] font-bold text-white flex-shrink-0 cursor-pointer">
            <?php
              $nameParts = explode(' ', $_SESSION['employee_name']);
              $initials = '';
              foreach ($nameParts as $part) {
                  $initials .= strtoupper($part[0]);
              }
              echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');
            ?>
            </button>
            <div id="avatar-dropdown" class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-[var(--line)] py-1 z-50 hidden">
              <a href="logout.php" class="block px-4 py-2 text-sm text-[var(--ink)] hover:bg-[var(--accent-soft)] transition-colors">
                Logout
              </a>
            </div>
          </div>
          <script>
          document.addEventListener('DOMContentLoaded', function() {
            var container = document.getElementById('avatar-container');
            var btn = document.getElementById('avatar-btn');
            var dropdown = document.getElementById('avatar-dropdown');
            btn.addEventListener('click', function(e) {
              e.stopPropagation();
              dropdown.classList.toggle('hidden');
            });
            document.addEventListener('click', function(e) {
              if (!container.contains(e.target)) {
                dropdown.classList.add('hidden');
              }
            });
          });
          </script>
        </div>
      </div>

      <div class="header-intro border-b border-[var(--line)] pb-7">
        <div class="flex items-center gap-3 mb-4">
          <div class="section-accent-bar"></div>
          <span class="header-eyebrow text-[11px] uppercase tracking-[0.2em] text-[var(--accent)] font-bold">Document Tracking Dashboard</span>
        </div>
        <h1 class="page-title font-display text-3xl sm:text-4xl md:text-5xl font-bold leading-[1.08] tracking-tight">
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
       class="redirect-card <?= $isUsersPage ? 'active' : '' ?>">
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
