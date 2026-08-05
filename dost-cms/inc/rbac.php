<?php
// Centralized Role-Based Access Control (RBAC).
// Roles: super_admin, office_admin, user.
// All role validation, migration and page guarding lives here so new roles
// can be introduced without scattering role logic across the application.

if ((basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__))) {
    http_response_code(404);
    exit;
}

if (!function_exists('rbac_roles')) {

    // The complete set of roles understood by the system.
    function rbac_roles() {
        return ['super_admin', 'office_admin', 'user'];
    }

    function rbac_is_valid_role($role) {
        return in_array($role, rbac_roles(), true);
    }

    // Ensures the users.role column exists and migrates legacy values.
    // Idempotent: runs its DB work once per session, then no-ops.
    function rbac_migrate_roles($conn) {
        if (!empty($_SESSION['rbac_roles_migrated'])) return;

        $col = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
        if ($col === false) return;

        $justAdded = $col->num_rows === 0;
        if ($justAdded) {
            $conn->query(
                "ALTER TABLE users
                 ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'
                 AFTER status"
            );
            // Only mark as migrated when the column actually exists.
            $check = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
            if ($check === false || $check->num_rows === 0) return;
        }

        // Legacy 'admin' role value -> super_admin.
        $conn->query("UPDATE users SET role = 'super_admin' WHERE role = 'admin'");

        // First-time migration: the legacy administrator account becomes
        // super_admin so existing administrators never lose access.
        if ($justAdded) {
            $conn->query("UPDATE users SET role = 'super_admin' WHERE LOWER(username) = 'admin'");
        }

        // Safety net: never leave the system without a super_admin.
        $sa = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'super_admin'");
        if ($sa && (int)$sa->fetch_assoc()['c'] === 0) {
            $conn->query("UPDATE users SET role = 'super_admin' WHERE LOWER(username) = 'admin' LIMIT 1");
        }

        // Normalize any unknown/legacy role values to 'user'.
        $conn->query("UPDATE users SET role = 'user' WHERE role NOT IN ('super_admin','office_admin','user')");

        $_SESSION['rbac_roles_migrated'] = 1;
    }

    // Refreshes the session role from the database (authoritative source),
    // so role changes take effect immediately and stale sessions cannot
    // retain privileges. Logs out sessions whose account no longer exists.
    function rbac_sync_session_role($conn) {
        $uid = $_SESSION['user_id'] ?? null;
        if (!$uid) return;

        $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
        if (!$stmt) return;
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            session_destroy();
            header("Location: index.php");
            exit;
        }

        $_SESSION['role'] = rbac_is_valid_role($user['role'] ?? '') ? $user['role'] : 'user';
    }

    // Returns the current user's role, defaulting to the least privileged.
    function rbac_current_role() {
        return rbac_is_valid_role($_SESSION['role'] ?? null) ? $_SESSION['role'] : 'user';
    }

    // Page-level guard. Redirects unauthorized users to an authorized page.
    function rbac_require_any($conn, $roles) {
        rbac_sync_session_role($conn);
        if (!in_array(rbac_current_role(), $roles, true)) {
            header("Location: dashboard.php");
            exit;
        }
    }
}
