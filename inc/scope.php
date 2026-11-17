<?php
// Office-based data scope helpers.
// Determines which records a user may access based on their role and
// assigned office. Super Administrators see everything; all other roles
// are scoped to their assigned office_id.

if ((basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__))) {
    http_response_code(404);
    exit;
}

if (!function_exists('scope_office_id')) {

    // Returns the office_id the current user is scoped to, or null if
    // the user is a Super Administrator (no filtering).
    function scope_office_id() {
        $role = rbac_current_role();
        if ($role === 'super_admin') return null;
        return (int)($_SESSION['office_id'] ?? 0) ?: null;
    }

    // Returns a WHERE clause fragment and bind parameters to filter a
    // query by office_id. For Super Administrators returns empty strings
    // (no filtering).
    //
    // Usage:
    //   $scope = scope_where($table_alias);
    //   $query = "SELECT ... FROM documents d {$scope['where']}";
    //   $stmt->bind_param($scope['types'], ...$scope['params']);
    function scope_where($table_alias = '') {
        $officeId = scope_office_id();
        if ($officeId === null) {
            return ['where' => '', 'types' => '', 'params' => []];
        }
        $col = $table_alias ? "{$table_alias}.office_id" : 'office_id';
        return [
            'where'  => " AND {$col} = ?",
            'types'  => 'i',
            'params' => [$officeId],
        ];
    }

    // Returns true if the given record's office_id matches the current
    // user's scope. Super Administrators always return true.
    function scope_allows($record_office_id) {
        $officeId = scope_office_id();
        if ($officeId === null) return true;
        return (int)$record_office_id === $officeId;
    }
}
