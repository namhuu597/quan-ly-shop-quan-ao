<?php

if (
    session_status()
    === PHP_SESSION_NONE
) {
    session_start();
}

require_once __DIR__ . '/csrf.php';


// =========================================
// ĐĂNG NHẬP
// =========================================

function requireLogin(): void
{
    if (
        !isset($_SESSION['user'])
        || empty($_SESSION['access_token'])
    ) {
        header(
            'Location: /quanlyquanao/login.php'
        );

        exit;
    }
}


// =========================================
// TOKEN
// =========================================

function getAccessToken(): string
{
    return (string)(
        $_SESSION['access_token']
        ?? ''
    );
}


// Header chuẩn khi PHP gọi ASP.NET API.
function apiAuthHeaders(
    array $extraHeaders = []
): array {

    requireLogin();

    $token =
        getAccessToken();

    $headers = [
        'Accept: application/json',
        'Authorization: Bearer '
            . $token
    ];


    foreach (
        $extraHeaders
        as $header
    ) {
        $headers[] =
            $header;
    }


    return $headers;
}


// =========================================
// PERMISSION PHÍA GIAO DIỆN PHP
//
// Chỉ dùng để ẩn/hiện menu, nút.
// ASP.NET API mới là lớp bảo vệ cuối cùng.
// =========================================

function hasPermission(
    string $permission
): bool {

    if (
        !isset(
            $_SESSION['user']['permissions']
        )
    ) {
        return false;
    }


    return in_array(
        $permission,
        $_SESSION['user']['permissions'],
        true
    );
}


function requirePermission(
    string $permission
): void {

    requireLogin();


    if (!hasPermission($permission))
    {
        http_response_code(403);

        echo '<h1>403 - Forbidden</h1>';

        echo '<p>Bạn không có quyền thực hiện chức năng này.</p>';

        exit;
    }
}


// =========================================
// ROLE PHÍA GIAO DIỆN PHP
// =========================================

function hasRole(
    string $role
): bool {

    if (
        !isset(
            $_SESSION['user']['roles']
        )
    ) {
        return false;
    }


    return in_array(
        $role,
        $_SESSION['user']['roles'],
        true
    );
}


// =========================================
// USER HIỆN TẠI
// =========================================

function currentUserId(): int
{
    return (int)(
        $_SESSION['user']['id']
        ?? 0
    );
}
