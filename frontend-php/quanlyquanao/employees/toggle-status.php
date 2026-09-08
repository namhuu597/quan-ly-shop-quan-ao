<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('user.disable');

if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {
    http_response_code(405);

    exit(
        'Method Not Allowed'
    );
}

verifyCsrfToken();

$id =
    (int)(
        $_POST['id']
        ?? 0
    );

if ($id <= 0) {
    die(
        'Tài khoản không hợp lệ.'
    );
}


// Chặn ngay từ giao diện PHP
// không cho tự khóa tài khoản đang đăng nhập.
if (
    $id
    ===
    (int)(
        $_SESSION['user']['id']
        ?? 0
    )
) {
    die(
        'Bạn không thể khóa tài khoản đang đăng nhập.'
    );
}


$apiUrl =
    'http://localhost:5162/api/employees/'
    . $id
    . '/status';


$ch =
    curl_init();


curl_setopt_array(
    $ch,
    [
        CURLOPT_URL =>
            $apiUrl,

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_CUSTOMREQUEST =>
            'PATCH',

        CURLOPT_CONNECTTIMEOUT =>
            5,

        CURLOPT_TIMEOUT =>
            20,

        CURLOPT_HTTPHEADER =>
            apiAuthHeaders()
    ]
);


$response =
    curl_exec(
        $ch
    );


$httpCode =
    (int)curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


$curlError =
    curl_error(
        $ch
    );


curl_close(
    $ch
);


if ($response === false) {

    die(
        'Không thể kết nối tới ASP.NET Core API. '
        . $curlError
    );
}


$data =
    json_decode(
        $response,
        true
    );


if ($httpCode === 200) {

    header(
        'Location: /quanlyquanao/employees/index.php?status_changed=1'
    );

    exit;
}


if ($httpCode === 401) {

    die(
        'Phiên đăng nhập đã hết hạn hoặc JWT không hợp lệ. '
        . 'Vui lòng đăng xuất và đăng nhập lại.'
    );
}


if ($httpCode === 403) {

    die(
        $data['message']
        ?? 'Bạn không có quyền khóa hoặc kích hoạt tài khoản.'
    );
}


if ($httpCode === 404) {

    die(
        $data['message']
        ?? 'Không tìm thấy tài khoản.'
    );
}


die(
    $data['message']
    ?? (
        'Không thể cập nhật trạng thái tài khoản. '
        . 'HTTP Status: '
        . $httpCode
    )
);