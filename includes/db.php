<?php
/**
 * Database Connection Using PDO
 * 
 * This file establishes a secure database connection using PDO (PHP Data Objects)
 * with prepared statements to prevent SQL injection attacks.
 * 
 * Developer: Benjamin NIYOMURINZI
 */

// Prevent direct access
if (!defined('DB_HOST')) {
    die('Direct access not permitted');
}

// Global database connection variable
$conn = null;

try {
    // Create DSN (Data Source Name)
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    // PDO options for security and performance
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Fetch associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                   // Use real prepared statements
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET // Set charset
    ];
    
    // Create PDO instance (database connection)
    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // Log error in production, display in development
    if (ENVIRONMENT === 'development') {
        die("Database Connection Failed: " . $e->getMessage());
    } else {
        // Log to file in production
        error_log("Database Connection Error: " . $e->getMessage());
        die("Sorry, we're experiencing technical difficulties. Please try again later.");
    }
}

/**
 * Execute a prepared SQL query
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return PDOStatement
 */
function db_query($sql, $params = []) {
    global $conn;
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        if (ENVIRONMENT === 'development') {
            die("Query Error: " . $e->getMessage() . "<br>SQL: " . $sql);
        } else {
            error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }
}

/**
 * Fetch single row from database
 * 
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return array|false
 */
function db_fetch($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Fetch all rows from database
 * 
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return array
 */
function db_fetch_all($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

/**
 * Get last inserted ID
 * 
 * @return string
 */
function db_last_id() {
    global $conn;
    return $conn->lastInsertId();
}

/**
 * Begin database transaction
 */
function db_begin_transaction() {
    global $conn;
    $conn->beginTransaction();
}

/**
 * Commit database transaction
 */
function db_commit() {
    global $conn;
    $conn->commit();
}

/**
 * Rollback database transaction
 */
function db_rollback() {
    global $conn;
    $conn->rollBack();
}

/**
 * Count rows in result
 * 
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return int
 */
function db_count($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt ? $stmt->rowCount() : 0;
}

?>