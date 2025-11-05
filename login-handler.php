<?php
require_once 'includes/config.php';

// Set response header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'errors' => [],
    'redirect' => ''
];

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['errors']['general'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get form data
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validate inputs
if (empty($email)) {
    $response['errors']['email'] = 'Email is required';
}

if (empty($password)) {
    $response['errors']['password'] = 'Password is required';
}

// If validation errors exist, return them
if (!empty($response['errors'])) {
    echo json_encode($response);
    exit;
}

try {
    error_log("[v0] Attempting login for email: " . $email);
    
    $stmt = $pdo->prepare('SELECT user_id, first_name, last_name, email, password, role, status FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        $response['errors']['general'] = 'Invalid email or password';
        error_log("[v0] Login failed for email: " . $email . " - Invalid credentials");
        echo json_encode($response);
        exit;
    }

    if ($user['status'] !== 'active') {
        $response['errors']['general'] = 'Your account is inactive. Please contact an administrator.';
        error_log("[v0] Login failed for email: " . $email . " - Account inactive");
        echo json_encode($response);
        exit;
    }

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['role'] = $user['role'];

    error_log("[Login successful for email: " . $email . " with role: " . $user['role']);

    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $logStmt->execute([
        $user['user_id'],
        'login',
        'user',
        $user['user_id'],
        'User logged in successfully',
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    if ($user['role'] === 'admin') {
        $response['redirect'] = 'admin-dashboard.php';
    } elseif ($user['role'] === 'janitor') {
        $response['redirect'] = 'janitor-dashboard.php';
    } else {
        $response['errors']['general'] = 'Unknown user role';
        echo json_encode($response);
        exit;
    }

    $response['success'] = true;
    $response['message'] = 'Login successful! Redirecting...';

} catch (PDOException $e) {
    error_log("[Database error during login: " . $e->getMessage());
    $response['errors']['general'] = 'Database error. Please try again later.';
}

echo json_encode($response);
exit;
?>
