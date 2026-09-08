<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('color.delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);
    exit('Method Not Allowed');
}

verifyCsrfToken();

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    die('Màu sắc không hợp lệ.');
}

$apiUrl =
    'http://localhost:5162/api/colors/'
    . $id
    . '/status';

$ch = curl_init();

curl_setopt_array(
    $ch,
    [
        CURLOPT_URL => $apiUrl,

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_CUSTOMREQUEST => 'PATCH',

        CURLOPT_HTTPHEADER =>
            apiAuthHeaders(),

        CURLOPT_CONNECTTIMEOUT => 5,

        CURLOPT_TIMEOUT => 10
    ]
);

$response = curl_exec($ch);

$httpCode =
    (int)curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

$curlError =
    curl_error($ch);

curl_close($ch);


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
        'Location: /quanlyquanao/colors/index.php?status_changed=1'
    );

    exit;
}


if ($httpCode === 404) {

    die(
        $data['message']
        ?? 'Không tìm thấy màu sắc.'
    );
}


die(
    $data['message']
    ?? (
        'Không thể cập nhật trạng thái màu sắc. '
        . 'HTTP Status: '
        . $httpCode
    )
);