<?php
// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /ERC-POS/views/auth/login.php");
        exit();
    }
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function format_money($amount) {
    return number_format($amount, 2);
}

function log_audit($user_id, $action, $table_name, $record_id, $old_values = null, $new_values = null) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $action,
        $table_name,
        $record_id,
        $old_values ? json_encode($old_values) : null,
        $new_values ? json_encode($new_values) : null
    ]);
}

function get_menu_items($category_id = null, $include_inactive = false) {
    global $conn;
    
    $sql = "SELECT * FROM menu_items WHERE 1=1";
    if ($category_id) {
        $sql .= " AND category_id = ?";
    }
    if (!$include_inactive) {
        $sql .= " AND is_active = 1";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($category_id) {
        $stmt->execute([$category_id]);
    } else {
        $stmt->execute();
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_categories() {
    global $conn;
    $stmt = $conn->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_inventory_items() {
    global $conn;
    $stmt = $conn->query("
        SELECT * FROM menu_items 
        WHERE is_inventory_item = 1 
        ORDER BY name
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function update_stock($menu_item_id, $quantity, $type, $user_id, $notes = '') {
    global $conn;
    $stmt = $conn->prepare("CALL sp_update_inventory_stock(?, ?, ?, ?, ?)");
    return $stmt->execute([$menu_item_id, $quantity, $type, $user_id, $notes]);
}

function get_sales_report($start_date, $end_date) {
    global $conn;
    $stmt = $conn->prepare("CALL sp_get_sales_report(?, ?)");
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// This function has been commented out as we now track inventory expenses through inventory_transactions
// function get_expenses_report($start_date, $end_date) {
//     global $conn;
//     $stmt = $conn->prepare("CALL sp_get_expenses_report(?, ?)");
//     $stmt->bindParam(1, $start_date);
//     $stmt->bindParam(2, $end_date);
//     $stmt->execute();
//     return $stmt->fetchAll();
// }

function get_inventory_transactions($start_date, $end_date, $menu_item_id = null) {
    global $conn;
    $stmt = $conn->prepare("CALL sp_get_inventory_transactions(?, ?, ?)");
    $stmt->execute([$start_date, $end_date, $menu_item_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Start a new transaction with an optional savepoint
 * @param string|null $savepoint_name Optional savepoint name
 */
function begin_transaction($savepoint_name = null) {
    global $conn;
    $stmt = $conn->prepare("CALL sp_begin_transaction(?)");
    $stmt->execute([$savepoint_name]);
}

/**
 * Rollback to a specific savepoint or perform a full rollback
 * @param string|null $savepoint_name Optional savepoint name
 */
function rollback_transaction($savepoint_name = null) {
    global $conn;
    $stmt = $conn->prepare("CALL sp_rollback_to_savepoint(?)");
    $stmt->execute([$savepoint_name]);
}

/**
 * Commit the current transaction
 */
function commit_transaction() {
    global $conn;
    $stmt = $conn->prepare("CALL sp_commit_transaction()");
    $stmt->execute();
} 