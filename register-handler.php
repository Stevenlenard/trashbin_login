<?php
session_start();

header('Content-Type: application/json');

require_once 'includes/config.php';

error_log("[v0] Registration handler called");
error_log("[v0] POST data: " . print_r($_POST, true));

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => [],
    'redirect' => ''
];

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['errors']['general'] = 'Invalid request method';
    error_log("[v0] Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode($response);
    exit;
}

// Get form data
$firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
$lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

error_log("[v0] Form data received - Email: $email, Phone: $phone");

// Validate first name
if (empty($firstName)) {
    $response['errors']['firstName'] = 'First name is required';
} elseif (!preg_match('/^[a-zA-Z\s]+$/', $firstName)) {
    $response['errors']['firstName'] = 'First name can only contain letters';
}

// Validate last name
if (empty($lastName)) {
    $response['errors']['lastName'] = 'Last name is required';
} elseif (!preg_match('/^[a-zA-Z\s]+$/', $lastName)) {
    $response['errors']['lastName'] = 'Last name can only contain letters';
}

// Validate email
if (empty($email)) {
    $response['errors']['regEmail'] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['errors']['regEmail'] = 'Please enter a valid email address';
}

// Validate phone (11 digits)
$cleanPhone = preg_replace('/\D/', '', $phone);
if (empty($phone)) {
    $response['errors']['phone'] = 'Phone number is required';
} elseif (strlen($cleanPhone) !== 11) {
    $response['errors']['phone'] = 'Phone number must be exactly 11 digits';
}

// Validate password
if (empty($password)) {
    $response['errors']['regPassword'] = 'Password is required';
} elseif (strlen($password) < 8) {
    $response['errors']['regPassword'] = 'Password must be at least 8 characters';
} elseif (!preg_match('/[A-Z]/', $password)) {
    $response['errors']['regPassword'] = 'Password must contain an uppercase letter';
} elseif (!preg_match('/[a-z]/', $password)) {
    $response['errors']['regPassword'] = 'Password must contain a lowercase letter';
} elseif (!preg_match('/[0-9]/', $password)) {
    $response['errors']['regPassword'] = 'Password must contain a number';
}

// Validate confirm password
if (empty($confirmPassword)) {
    $response['errors']['confirmPassword'] = 'Please confirm your password';
} elseif ($confirmPassword !== $password) {
    $response['errors']['confirmPassword'] = 'Passwords do not match';
}

// If validation errors exist, return them
if (!empty($response['errors'])) {
    error_log("[v0] Validation errors: " . print_r($response['errors'], true));
    echo json_encode($response);
    exit;
}

try {
    if (!isset($pdo) || $pdo === null) {
        error_log("[v0] Database connection is null");
        throw new Exception('Database connection failed. Please check your database configuration.');
    }

    error_log("[v0] Database connection successful");

    // Check if email already exists
    $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    
    if ($checkStmt->rowCount() > 0) {
        $response['errors']['regEmail'] = 'This email is already registered';
        error_log("[v0] Email already exists: $email");
        echo json_encode($response);
        exit;
    }

    error_log("[v0] Email is unique, proceeding with registration");

    // Hash the password using bcrypt
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Generate employee ID for janitor (JAN-XXXXX format)
    $employeeId = 'JAN-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

    error_log("[v0] Generated employee ID: $employeeId");

    // Insert new user into database
    $insertStmt = $pdo->prepare("
        INSERT INTO users (first_name, last_name, email, phone, password, role, status, employee_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $insertStmt->execute([
        $firstName,
        $lastName,
        $email,
        $cleanPhone,
        $hashedPassword,
        'janitor',
        'active',
        $employeeId
    ]);

    $userId = $pdo->lastInsertId();
    error_log("[v0] User inserted successfully with ID: $userId");

    // Log the registration activity
    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $logStmt->execute([
        $userId,
        'register',
        'user',
        $userId,
        'New user registered successfully',
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    error_log("[v0] Activity logged successfully");

    $response['success'] = true;
    $response['message'] = 'Registration successful! Please log in with your new account.';
    $response['redirect'] = 'login.php';

    error_log("[v0] Registration completed successfully for email: $email");

} catch (PDOException $e) {
    error_log("[v0] PDOException: " . $e->getMessage());
    $response['errors']['general'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    error_log("[v0] Exception: " . $e->getMessage());
    $response['errors']['general'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
