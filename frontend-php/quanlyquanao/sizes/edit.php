<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('size.update');

$pageTitle = 'Sửa Size';
$currentPage = 'sizes';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Size không hợp lệ.');
}

$error = '';

$apiBaseUrl =
    'http://localhost:5162/api/sizes';


// =========================================
// HÀM GỌI API
// =========================================

function callApi(
    string $url,
    string $method = 'GET',
    ?array $payload = null
): array {

    $ch = curl_init();

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER =>
            apiAuthHeaders([
                'Content-Type: application/json'
            ])
    ];

    if ($method !== 'GET') {
        $options[CURLOPT_CUSTOMREQUEST] = $method;
    }

    if ($payload !== null) {
        $options[CURLOPT_POSTFIELDS] =
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
            );
    }

    curl_setopt_array(
        $ch,
        $options
    );

    $response =
        curl_exec($ch);

    $httpCode =
        (int)curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

    $curlError =
        curl_error($ch);

    curl_close($ch);

    return [
        'response' => $response,
        'httpCode' => $httpCode,
        'curlError' => $curlError
    ];
}


// =========================================
// LẤY SIZE TỪ ASP.NET API
// =========================================

$result =
    callApi(
        $apiBaseUrl . '/' . $id
    );

if ($result['response'] === false) {

    die(
        'Không thể kết nối tới ASP.NET Core API.'
    );
}

if ($result['httpCode'] === 404) {

    die('Không tìm thấy Size.');
}

if ($result['httpCode'] !== 200) {

    die(
        'Không thể tải dữ liệu Size. HTTP Status: '
        . $result['httpCode']
    );
}

$size =
    json_decode(
        $result['response'],
        true
    );

if (!is_array($size)) {

    die(
        'Dữ liệu API trả về không hợp lệ.'
    );
}


// =========================================
// XỬ LÝ CẬP NHẬT
// =========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrfToken();

    $name = strtoupper(
        trim($_POST['name'] ?? '')
    );

    if ($name === '') {

        $error =
            'Vui lòng nhập tên Size.';

    } else {

        $payload = [
            'name' => $name
        ];

        $updateResult =
            callApi(
                $apiBaseUrl . '/' . $id,
                'PUT',
                $payload
            );

        if (
            $updateResult['response']
            === false
        ) {

            $error =
                'Không thể kết nối tới ASP.NET Core API.';

        } else {

            $data =
                json_decode(
                    $updateResult['response'],
                    true
                );

            if (
                $updateResult['httpCode']
                === 200
            ) {

                header(
                    'Location: /quanlyquanao/sizes/index.php?updated=1'
                );

                exit;

            } elseif (
                $updateResult['httpCode']
                === 409
            ) {

                $error =
                    $data['message']
                    ?? 'Size này đã tồn tại.';

            } elseif (
                $updateResult['httpCode']
                === 400
            ) {

                $error =
                    $data['message']
                    ?? 'Dữ liệu không hợp lệ.';

            } elseif (
                $updateResult['httpCode']
                === 404
            ) {

                $error =
                    'Không tìm thấy Size.';

            } else {

                $error =
                    $data['message']
                    ?? (
                        'Không thể cập nhật Size. HTTP Status: '
                        . $updateResult['httpCode']
                    );
            }
        }
    }
}


require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="main">

    <header class="topbar">
        <strong>Quản lý Size</strong>
    </header>


    <section class="content">

        <?php if ($error !== ''): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>Sửa Size</h1>

                <p>
                    Size /
                    <?= htmlspecialchars(
                        $size['name']
                        ?? ''
                    ) ?>
                    / Sửa
                </p>

            </div>

        </div>


        <div class="card category-form-card">

            <form method="POST">

                <?= csrfField() ?>

                <div class="form-group">

                    <label>
                        Tên Size
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="<?= htmlspecialchars(
                            $_POST['name']
                            ?? $size['name']
                            ?? ''
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-actions">

                    <a
                        href="/quanlyquanao/sizes/index.php"
                        class="btn btn-light"
                    >
                        Hủy
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Lưu thay đổi
                    </button>

                </div>

            </form>

        </div>

    </section>

</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>