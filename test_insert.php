<?php
// test_insert.php
// Simple test script that uses includes/config.php PDO connection
// to attempt a direct insert into janitors. Run once for debug,
// then remove from server.

require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/plain');

try {
    if (!isset($pdo) || $pdo === null) {
        throw new Exception('PDO $pdo is not set. Check includes/config.php');
    }

    $sql = "INSERT INTO janitors (first_name, last_name, email, phone, password, status, employee_id, created_at, updated_at)
            VALUES (:fn, :ln, :email, :phone, :pwd, 'active', :emp, NOW(), NOW())";

    $stmt = $pdo->prepare($sql);

    // Example values from your sample (password is already bcrypt)
    $params = [
        ':fn' => 'John',
        ':ln' => 'Doe',
        ':email' => 'john+test_insert@example.com', // use a test email not present in DB
        ':phone' => '+1 (555) 123-4567',
        ':pwd' => '$2y$10$zrso/wR/n/AIPhvxa1oReOLFVS0aLAUAD/6wNbUbYJwdpBgjvzb62',
        ':emp' => 'JAN-001' // you can change to a unique value per run
    ];

    $ok = $stmt->execute($params);
    if ($ok) {
        echo "Insert succeeded. lastInsertId: " . $pdo->lastInsertId() . PHP_EOL;
    } else {
        $err = $stmt->errorInfo();
        echo "Insert failed. PDOStatement errorInfo: " . print_r($err, true) . PHP_EOL;
    }
} catch (PDOException $e) {
    echo "PDOException: " . $e->getMessage() . PHP_EOL;
    echo "SQLSTATE: " . $e->getCode() . PHP_EOL;
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}