<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('color.update');

$pageTitle = 'Sửa màu sắc';
$currentPage = 'colors';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Màu sắc không hợp lệ.');
}

$error = '';

$apiBaseUrl =
    'http://localhost:5162/api/colors';


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
// LẤY MÀU TỪ ASP.NET API
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

    die(
        'Không tìm thấy màu sắc.'
    );
}

if ($result['httpCode'] !== 200) {

    die(
        'Không thể tải dữ liệu màu sắc. HTTP Status: '
        . $result['httpCode']
    );
}

$color =
    json_decode(
        $result['response'],
        true
    );

if (!is_array($color)) {

    die(
        'Dữ liệu API trả về không hợp lệ.'
    );
}


// =========================================
// XỬ LÝ CẬP NHẬT
// =========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrfToken();

    $name =
        trim($_POST['name'] ?? '');

    $colorCode =
        strtoupper(
            trim($_POST['color_code'] ?? '')
        );


    if ($name === '') {

        $error =
            'Vui lòng nhập tên màu.';

    } else {

        $payload = [
            'name' => $name,
            'colorCode' =>
                $colorCode !== ''
                    ? $colorCode
                    : null
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
                    'Location: /quanlyquanao/colors/index.php?updated=1'
                );

                exit;


            } elseif (
                $updateResult['httpCode']
                === 409
            ) {

                $error =
                    $data['message']
                    ?? 'Tên màu này đã tồn tại.';


            } elseif (
                $updateResult['httpCode']
                === 400
            ) {

                $error =
                    $data['message']
                    ?? 'Dữ liệu màu không hợp lệ.';


            } elseif (
                $updateResult['httpCode']
                === 404
            ) {

                $error =
                    'Không tìm thấy màu sắc.';


            } else {

                $error =
                    $data['message']
                    ?? (
                        'Không thể cập nhật màu sắc. HTTP Status: '
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
        <strong>Quản lý Màu sắc</strong>
    </header>


    <section class="content">

        <?php if ($error !== ''): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>Sửa màu sắc</h1>

                <p>
                    Màu sắc /
                    <?= htmlspecialchars(
                        $color['name']
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
                        Tên màu
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="<?= htmlspecialchars(
                            $_POST['name']
                            ?? $color['name']
                            ?? ''
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Mã màu HEX
                    </label>

                    <div class="color-input-row">

                        <input
                            type="color"
                            id="colorPicker"
                            value="<?= htmlspecialchars(
                                $_POST['color_code']
                                ?? $color['colorCode']
                                ?? '#000000'
                            ) ?>"
                        >

                        <input
                            type="text"
                            name="color_code"
                            id="colorCodeInput"
                            value="<?= htmlspecialchars(
                                $_POST['color_code']
                                ?? $color['colorCode']
                                ?? '#000000'
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="form-actions">

                    <a
                        href="/quanlyquanao/colors/index.php"
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


<script>

const colorPicker =
    document.getElementById(
        'colorPicker'
    );

const colorCodeInput =
    document.getElementById(
        'colorCodeInput'
    );


if (
    colorPicker
    && colorCodeInput
) {

    colorPicker.addEventListener(
        'input',
        function () {

            colorCodeInput.value =
                colorPicker.value
                    .toUpperCase();
        }
    );


    colorCodeInput.addEventListener(
        'input',
        function () {

            const value =
                colorCodeInput.value
                    .trim();

            if (
                /^#[0-9A-Fa-f]{6}$/.test(
                    value
                )
            ) {

                colorPicker.value =
                    value;
            }
        }
    );

}

</script>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>