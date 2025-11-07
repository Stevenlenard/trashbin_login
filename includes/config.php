<?php
// includes/config.php
// Robust configuration for Trashbin project:
// - absolute autoload path
// - mysqli ($conn) and PDO ($pdo) connections
// - session start
// - auth helpers and janitor employee id generator
// - small data helpers
//
// Replace your existing includes/config.php with this file (backup first).

declare(strict_types=1);

// === Environment / error reporting ===
$APP_ENV = getenv('APP_ENV') ?: 'development';
if ($APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

// === Timezone ===
date_default_timezone_set('Asia/Manila');

// === Composer autoload (absolute path) ===
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    error_log("[config] vendor/autoload.php not found at: $vendorAutoload");
}

// === Database credentials (prefer env vars) ===
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'trashbin_management';
$db_charset = 'utf8mb4';

// === MySQLi connection (legacy/utility) ===
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = null;
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset($db_charset);
} catch (mysqli_sql_exception $e) {
    error_log("[config] MySQLi connection failed: " . $e->getMessage());
    // For web pages, exit with minimal message. For APIs, endpoints can detect $conn === null.
    http_response_code(500);
    exit('Database connection error (MySQLi). Check server logs.');
}

// === PDO connection (preferred for business logic) ===
$pdo = null;
try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => false,
    ]);
} catch (PDOException $e) {
    error_log("[config] PDO connection failed: " . $e->getMessage());
    http_response_code(500);
    exit('Database connection error (PDO). Check server logs.');
}

// === Session ===
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === Authentication / session helpers ===
function isLoggedIn(): bool {
    return isset($_SESSION['admin_id']) || isset($_SESSION['janitor_id']) || isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['admin_id']);
}

function isJanitor(): bool {
    return isset($_SESSION['janitor_id']);
}

function getCurrentUserId() {
    if (isset($_SESSION['admin_id'])) return $_SESSION['admin_id'];
    if (isset($_SESSION['janitor_id'])) return $_SESSION['janitor_id'];
    if (isset($_SESSION['user_id'])) return $_SESSION['user_id'];
    return null;
}

function getCurrentUserType() {
    if (isset($_SESSION['admin_id'])) return 'admin';
    if (isset($_SESSION['janitor_id'])) return 'janitor';
    if (isset($_SESSION['user_id'])) return 'user';
    return null;
}

// === Employee ID generator for janitors (DB-safe-ish) ===
// This tries a simple sequential approach using MAX(...) OR a random fallback.
// Requires janitors table and InnoDB for FOR UPDATE to be meaningful under concurrency.
function generateEmployeeId(): string {
    global $conn;

    if (!$conn) {
        throw new Exception('MySQLi connection not available for generateEmployeeId().');
    }

    // Attempt sequential generation using MAX substring with a short lock if possible
    try {
        // Try to use a SELECT MAX(...) FOR UPDATE inside a transaction (InnoDB)
        $conn->begin_transaction();

        $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(employee_id, '-', -1) AS UNSIGNED)) AS maxnum FROM janitors FOR UPDATE";
        $res = $conn->query($sql);
        $maxnum = 0;
        if ($res) {
            $row = $res->fetch_assoc();
            if ($row && isset($row['maxnum']) && $row['maxnum'] !== null) {
                $maxnum = intval($row['maxnum']);
            }
            $res->free();
        }

        $next = $maxnum + 1;
        // pad to 3 digits by default (JAN-001); increase if needed
        $employee_id = 'JAN-' . str_pad((string)$next, 3, '0', STR_PAD_LEFT);

        $conn->commit();

        // Final safety check: ensure not present (race unlikely due to lock)
        $safe = $conn->real_escape_string($employee_id);
        $check = $conn->query("SELECT employee_id FROM janitors WHERE employee_id = '{$safe}' LIMIT 1");
        if ($check && $check->num_rows === 0) {
            return $employee_id;
        }
        // fallback path if collision observed
    } catch (Exception $e) {
        // rollback and fall back
        try { $conn->rollback(); } catch (Exception $_) {}
        error_log("[config] generateEmployeeId sequential attempt failed: " . $e->getMessage());
    }

    // Fallback: try random generation with limited attempts
    $attempts = 0;
    while ($attempts < 10) {
        $attempts++;
        $randomNum = rand(10000, 99999);
        $employee_id = 'JAN-' . $randomNum;
        $safe = $conn->real_escape_string($employee_id);
        $res = $conn->query("SELECT employee_id FROM janitors WHERE employee_id = '{$safe}' LIMIT 1");
        if ($res && $res->num_rows === 0) {
            return $employee_id;
        }
    }

    // Last-resort deterministic fallback
    return 'JAN-' . str_pad((string)mt_rand(1000, 99999), 5, '0', STR_PAD_LEFT);
}

// === Utilities ===
function sendJSON(array $data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// === Small data helpers (kept for compatibility) ===
function getAllBins(): array {
    global $conn;
    $bins = [];
    $sql = "SELECT b.bin_id, b.bin_code, b.location, b.type, b.capacity, b.status,
                   b.assigned_to, CONCAT(j.first_name, ' ', j.last_name) AS janitor_name,
                   b.latitude, b.longitude, b.installation_date, b.notes,
                   b.created_at, b.updated_at
            FROM bins b
            LEFT JOIN janitors j ON b.assigned_to = j.janitor_id
            ORDER BY b.created_at DESC";
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            $bins[] = $row;
        }
        $res->free();
    }
    return $bins;
}

function getBinById(int $bin_id) {
    global $conn;
    $id = intval($bin_id);
    $sql = "SELECT b.bin_id, b.bin_code, b.location, b.type, b.capacity, b.status,
                   b.assigned_to, CONCAT(j.first_name, ' ', j.last_name) AS janitor_name,
                   b.latitude, b.longitude, b.installation_date, b.notes,
                   b.created_at, b.updated_at
            FROM bins b
            LEFT JOIN janitors j ON b.assigned_to = j.janitor_id
            WHERE b.bin_id = {$id} LIMIT 1";
    if ($res = $conn->query($sql)) {
        $row = $res->fetch_assoc();
        $res->free();
        return $row ?: null;
    }
    return null;
}

function getActiveJanitors(): array {
    global $conn;
    $janitors = [];
    $sql = "SELECT j.janitor_id, CONCAT(j.first_name,' ',j.last_name) AS full_name, j.email, j.phone, j.employee_id, j.status,
                   COUNT(b.bin_id) AS assigned_bins
            FROM janitors j
            LEFT JOIN bins b ON j.janitor_id = b.assigned_to
            WHERE j.status = 'active'
            GROUP BY j.janitor_id
            ORDER BY j.first_name ASC";
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            $janitors[] = $row;
        }
        $res->free();
    }
    return $janitors;
}

function getAllJanitors($filter = 'all'): array {
    global $pdo;
    try {
        $sql = "SELECT j.janitor_id, j.first_name, j.last_name, j.email, j.phone, j.status, j.employee_id,
                       COUNT(b.bin_id) AS assigned_bins
                FROM janitors j
                LEFT JOIN bins b ON j.janitor_id = b.assigned_to";
        $params = [];
        if ($filter !== 'all') {
            $sql .= " WHERE j.status = ?";
            $params[] = $filter;
        }
        $sql .= " GROUP BY j.janitor_id ORDER BY j.first_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("[config] getAllJanitors error: " . $e->getMessage());
        return [];
    }
}

// End of includes/config.php
?>