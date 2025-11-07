<?php
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ==================================================
// DATABASE CONFIGURATION
// ==================================================
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'trashbin_management';

// --- MySQLi Connection ---
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}
$conn->set_charset("utf8mb4");

// --- PDO Connection ---
try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

// ==================================================
// SESSION START
// ==================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========== AUTHENTICATION FUNCTIONS ==========

// Check if any user (admin or janitor) is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']) || isset($_SESSION['janitor_id']);
}

// Specifically check for admin login
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

// Specifically check for janitor login
function isJanitor() {
    return isset($_SESSION['janitor_id']);
}

// Get current logged-in user's ID
function getCurrentUserId() {
    if (isset($_SESSION['admin_id'])) {
        return $_SESSION['admin_id'];
    } elseif (isset($_SESSION['janitor_id'])) {
        return $_SESSION['janitor_id'];
    }
    return null;
}

// Get current user type as a string
function getCurrentUserType() {
    if (isset($_SESSION['admin_id'])) {
        return 'admin';
    } elseif (isset($_SESSION['janitor_id'])) {
        return 'janitor';
    }
    return null;
}


// ==================================================
// USER MANAGEMENT FUNCTIONS
// ==================================================

// 🔧 Generate Employee ID for janitors only
function generateEmployeeId() {
    global $conn;
    do {
        $randomNum = rand(10000, 99999);
        $employee_id = 'JAN-' . $randomNum;
        $result = $conn->query("SELECT employee_id FROM janitors WHERE employee_id = '$employee_id'");
    } while ($result->num_rows > 0);
    return $employee_id;
}

// ==================================================
// BIN MANAGEMENT FUNCTIONS
// ==================================================

// Return JSON response
function sendJSON($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// 🔧 Get all bins and assigned janitor info
function getAllBins() {
    global $conn;
    $query = "SELECT b.bin_id, b.bin_code, b.location, b.type, b.capacity, b.status,
                     b.assigned_to, 
                     CONCAT(j.first_name, ' ', j.last_name) AS janitor_name,
                     b.latitude, b.longitude, b.installation_date, b.notes,
                     b.created_at, b.updated_at
              FROM bins b
              LEFT JOIN janitors j ON b.assigned_to = j.janitor_id
              ORDER BY b.created_at DESC";
    
    $result = $conn->query($query);
    $bins = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bins[] = $row;
        }
    }
    return $bins;
}

// 🔧 Get a specific bin by ID
function getBinById($bin_id) {
    global $conn;
    $bin_id = intval($bin_id);
    $query = "SELECT b.bin_id, b.bin_code, b.location, b.type, b.capacity, b.status,
                     b.assigned_to, CONCAT(j.first_name, ' ', j.last_name) AS janitor_name,
                     b.latitude, b.longitude, b.installation_date, b.notes,
                     b.created_at, b.updated_at
              FROM bins b
              LEFT JOIN janitors j ON b.assigned_to = j.janitor_id
              WHERE b.bin_id = $bin_id";
    
    $result = $conn->query($query);
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}

// 🔧 Get all active janitors
function getActiveJanitors() {
    global $conn;
    $query = "SELECT j.janitor_id, 
                     CONCAT(j.first_name, ' ', j.last_name) AS full_name,
                     j.email, j.phone, j.employee_id, j.status,
                     COUNT(b.bin_id) AS assigned_bins
              FROM janitors j
              LEFT JOIN bins b ON j.janitor_id = b.assigned_to
              WHERE j.status = 'active'
              GROUP BY j.janitor_id
              ORDER BY j.first_name ASC";
    
    $result = $conn->query($query);
    $janitors = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $janitors[] = $row;
        }
    }
    return $janitors;
}

// 🔧 Get all janitors (with optional filter)
function getAllJanitors($filter = 'all') {
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
        return [];
    }
}
?>
