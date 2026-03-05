<?php
/**
 * WAISTORE Audit Logger
 * Include this file and call logAudit() to record any action.
 * 
 * Usage:
 *   require_once 'audit_logger.php';
 *   logAudit($pdo, 'login', 'user', $user_id, 'User logged in successfully');
 *   logAudit($pdo, 'create_product', 'product', $product_id, 'Added "Rice 5kg" to inventory');
 *   logAudit($pdo, 'record_payment', 'debt', $debt_id, 'Payment of ₱500 received from Juan');
 */

function logAudit($pdo, $action, $entity_type, $entity_id = null, $details = null, $user_id = null, $admin_id = null)
{
    try {
        // Auto-detect user/admin from session if not provided
        if ($user_id === null && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        }
        if ($admin_id === null && isset($_SESSION['admin_id'])) {
            $admin_id = $_SESSION['admin_id'];
        }

        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // Create table if not exists (safe to call multiple times)
        $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            admin_id INT NULL,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id INT NULL,
            details TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_action (action),
            INDEX idx_entity (entity_type, entity_id),
            INDEX idx_created (created_at),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, admin_id, action, entity_type, entity_id, details, ip_address, user_agent) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $admin_id, $action, $entity_type, $entity_id, $details, $ip_address, $user_agent]);

        return true;
    } catch (PDOException $e) {
        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log audit using MySQLi connection (for non-PDO files like auth.php)
 */
function logAuditMysqli($conn, $action, $entity_type, $entity_id = null, $details = null, $user_id = null)
{
    try {
        // Create table if not exists
        $conn->query("CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            admin_id INT NULL,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id INT NULL,
            details TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_action (action),
            INDEX idx_entity (entity_type, entity_id),
            INDEX idx_created (created_at),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $admin_id = $_SESSION['admin_id'] ?? null;

        if ($user_id === null && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        }

        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, admin_id, action, entity_type, entity_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iississs", $user_id, $admin_id, $action, $entity_type, $entity_id, $details, $ip, $ua);
        $stmt->execute();
        $stmt->close();

        return true;
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
}
?>