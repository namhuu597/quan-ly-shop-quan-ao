<?php

require_once __DIR__ . '/config/database.php';

$username = 'admin';
$email = 'admin@gmail.com';
$fullName = 'Quản trị viên';
$password = 'Admin@123';

try {

    // Kiểm tra ADMIN đã tồn tại chưa
    $check = $pdo->prepare("
        SELECT id
        FROM users
        WHERE username = ? OR email = ?
        LIMIT 1
    ");

    $check->execute([$username, $email]);

    if ($check->fetch()) {
        die("Tài khoản Admin đã tồn tại.");
    }

    // Hash mật khẩu
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Bắt đầu transaction
    $pdo->beginTransaction();

    // Tạo user
    $stmt = $pdo->prepare("
        INSERT INTO users
        (
            username,
            email,
            password_hash,
            full_name,
            is_active
        )
        VALUES (?, ?, ?, ?, 1)
    ");

    $stmt->execute([
        $username,
        $email,
        $passwordHash,
        $fullName
    ]);

    $userId = $pdo->lastInsertId();

    // Lấy role ADMIN
    $roleStmt = $pdo->prepare("
        SELECT id
        FROM roles
        WHERE name = 'ADMIN'
        LIMIT 1
    ");

    $roleStmt->execute();

    $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        throw new Exception("Không tìm thấy role ADMIN.");
    }

    // Gán role ADMIN
    $assignRole = $pdo->prepare("
        INSERT INTO user_roles (user_id, role_id)
        VALUES (?, ?)
    ");

    $assignRole->execute([
        $userId,
        $role['id']
    ]);

    $pdo->commit();

    echo "<h2>Tạo Admin thành công!</h2>";
    echo "Username: admin<br>";
    echo "Password: Admin@123<br>";

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "Lỗi: " . $e->getMessage();
}