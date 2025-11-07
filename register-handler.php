<?php
// register-handler.php
// Robust registration handler that inserts into janitors table and
// generates sequential employee IDs JAN-001, JAN-002, ...
// Put this in project root. Requires includes/config.php (which sets $pdo).

require_once 'includes/config.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'errors' => [],
    'debug' => null,
];

$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['errors']['general'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Read inputs (keep phone formatting if user provided)
$firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
$lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

if ($firstName === '') {
    $response['errors']['firstName'] = 'First name is required';
} elseif (!preg_match('/^[a-zA-Z\s]+$/', $firstName)) {
    $response['errors']['firstName'] = 'First name can only contain letters and spaces';
}

if ($lastName === '') {
    $response['errors']['lastName'] = 'Last name is required';
} elseif (!preg_match('/^[a-zA-Z\s]+$/', $lastName)) {
    $response['errors']['lastName'] = 'Last name can only contain letters and spaces';
}

if ($email === '') {
    $response['errors']['email'] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['errors']['email'] = 'Please enter a valid email address';
}

// Validate phone digits but preserve formatting when storing
$cleanPhoneDigits = preg_replace('/\D+/', '', $phone);
if ($phone === '') {
    $response['errors']['phone'] = 'Phone number is required';
} elseif (strlen($cleanPhoneDigits) !== 11) {
    $response['errors']['phone'] = 'Phone number must contain exactly 11 digits (formatting allowed)';
}

if ($password === '') {
    $response['errors']['password'] = 'Password is required';
} elseif (strlen($password) < 8) {
    $response['errors']['password'] = 'Password must be at least 8 characters';
} elseif (!preg_match('/[A-Z]/', $password)) {
    $response['errors']['password'] = 'Password must contain an uppercase letter';
} elseif (!preg_match('/[a-z]/', $password)) {
    $response['errors']['password'] = 'Password must contain a lowercase letter';
} elseif (!preg_match('/[0-9]/', $password)) {
    $response['errors']['password'] = 'Password must contain a number';
}

if ($confirmPassword === '') {
    $response['errors']['confirmPassword'] = 'Please confirm your password';
} elseif ($confirmPassword !== $password) {
    $response['errors']['confirmPassword'] = 'Passwords do not match';
}

if (!empty($response['errors'])) {
    echo json_encode($response);
    exit;
}

try {
    if (!isset($pdo) || $pdo === null) {
        throw new Exception('Database connection not available.');
    }

    // Ensure email is unique in janitors
    $check = $pdo->prepare("SELECT janitor_id FROM janitors WHERE email = :email LIMIT 1");
    $check->execute([':email' => $email]);
    if ($check->rowCount() > 0) {
        $response['errors']['email'] = 'This email is already registered';
        echo json_encode($response);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // We will attempt to generate sequential employee_id inside a transaction
    // and retry if a duplicate happens (race condition).
    $maxAttempts = 5;
    $attempt = 0;
    $inserted = false;
    $lastException = null;

    while ($attempt < $maxAttempts && !$inserted) {
        $attempt++;
        try {
            // Begin transaction
            $pdo->beginTransaction();

            // Lock janitors rows and compute max suffix
            // Using FOR UPDATE to serialize concurrent generators (requires InnoDB)
            $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(employee_id, '-', -1) AS UNSIGNED)) AS maxnum FROM janitors FOR UPDATE";
            $stmt = $pdo->query($sql);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $maxnum = ($row && $row['maxnum'] !== null) ? intval($row['maxnum']) : 0;
            $nextNum = $maxnum + 1;
            // Format with 3 digits; if you expect more digits later, change padding
            $employeeId = 'JAN-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

            // Prepare insert
            $insert = $pdo->prepare("
                INSERT INTO janitors
                  (first_name, last_name, email, phone, password, status, employee_id, created_at, updated_at)
                VALUES
                  (:fn, :ln, :email, :phone, :pwd, 'active', :emp, NOW(), NOW())
            ");

            $insert->execute([
                ':fn' => $firstName,
                ':ln' => $lastName,
                ':email' => $email,
                ':phone' => $phone,           // store formatted phone like '+1 (555) 123-4567'
                ':pwd' => $hashedPassword,    // bcrypt $2y$... format
                ':emp' => $employeeId
            ]);

            $janitorId = $pdo->lastInsertId();

            // commit
            $pdo->commit();
            $inserted = true;

            // Log activity (best-effort; failure shouldn't undo registration)
            try {
                $log = $pdo->prepare("
                    INSERT INTO activity_logs (janitor_id, action, entity_type, entity_id, description, ip_address, user_agent, created_at)
                    VALUES (:jid, 'register', 'janitor', :entity_id, :desc, :ip, :ua, NOW())
                ");
                $log->execute([
                    ':jid' => $janitorId,
                    ':entity_id' => $janitorId,
                    ':desc' => 'New janitor registered',
                    ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                    ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
            } catch (Exception $e) {
                error_log("[register] activity_logs insert failed: " . $e->getMessage());
            }

            $response['success'] = true;
            $response['message'] = 'Registration successful! Please log in.';
            $response['redirect'] = 'user-login.php';
            $response['janitor_id'] = $janitorId;
            if ($debug) $response['debug'] = ['employee_id' => $employeeId, 'attempt' => $attempt];

            echo json_encode($response);
            exit;

        } catch (PDOException $e) {
            // Rollback on error
            try { $pdo->rollBack(); } catch (Exception $_) {}

            $lastException = $e;

            // SQLSTATE 23000 = integrity constraint violation (duplicate key)
            // If duplicate on employee_id arose, try again to compute next number
            $sqlstate = $e->getCode();
            error_log("[register] PDOException attempt {$attempt}: " . $e->getMessage() . " SQLSTATE=" . $sqlstate);

            // If last attempt, return error to client
            if ($attempt >= $maxAttempts) {
                $response['errors']['general'] = 'Registration failed due to a database error. Please try again later.';
                if ($debug) $response['debug'] = $e->getMessage();
                echo json_encode($response);
                exit;
            }

            // Otherwise, small sleep to reduce contention and retry
            usleep(100000); // 100ms
            continue;
        }
    }

    // If we exit loop without inserted (shouldn't happen)
    $response['errors']['general'] = 'Registration could not be completed. Please try again.';
    if ($debug && $lastException) $response['debug'] = $lastException->getMessage();
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    error_log("[register] Exception: " . $e->getMessage());
    $response['errors']['general'] = 'Error: ' . $e->getMessage();
    if ($debug) $response['debug'] = $e->getMessage();
    echo json_encode($response);
    exit;
}
?>