<?php
require_once 'config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'errors' => []];

// Ensure it’s a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['errors']['general'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$role = trim($_POST['role'] ?? '');

// Validate inputs
if (empty($email)) {
    $response['errors']['email'] = 'Email is required.';
}
if (empty($password)) {
    $response['errors']['password'] = 'Password is required.';
}
if (empty($role) || !in_array($role, ['admin', 'janitor'])) {
    $response['errors']['general'] = 'Invalid login source.';
}

if (!empty($response['errors'])) {
    echo json_encode($response);
    exit;
}

// Choose table
$table = $role === 'admin' ? 'admins' : 'janitors';
$idColumn = $role === 'admin' ? 'admin_id' : 'janitor_id';

// Fetch account
$stmt = $pdo->prepare("SELECT * FROM $table WHERE email = ? AND status = 'active' LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $response['errors']['email'] = 'No account found with that email.';
    echo json_encode($response);
    exit;
}

// Verify password
if (!password_verify($password, $user['password'])) {
    $response['errors']['password'] = 'Incorrect password.';
    echo json_encode($response);
    exit;
}

// Success
session_regenerate_id(true);

if ($role === 'admin') {
    $_SESSION['admin_id'] = $user['admin_id'];
    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
    $response['redirect'] = 'admin-dashboard.php';
    $response['message'] = 'Welcome back, Admin!';
} else {
    $_SESSION['janitor_id'] = $user['janitor_id'];
    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
    $response['redirect'] = 'janitor-dashboard.php';
    $response['message'] = 'Welcome back!';
}

$response['success'] = true;
echo json_encode($response);
exit;
?>