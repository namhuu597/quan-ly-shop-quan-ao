<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('size.create');

$pageTitle = 'Thêm Size';
$currentPage = 'sizes';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrfToken();

    $name = strtoupper(
        trim($_POST['name'] ?? '')
    );

    if ($name === '') {

        $error = 'Vui lòng nhập tên Size.';

    } else {

        $apiUrl =
            'http://localhost:5162/api/sizes';

        $payload = [
            'name' => $name
        ];

        $ch = curl_init();

        curl_setopt_array(
            $ch,
            [
                CURLOPT_URL =>
                    $apiUrl,

                CURLOPT_RETURNTRANSFER =>
                    true,

                CURLOPT_POST =>
                    true,

                CURLOPT_POSTFIELDS =>
                    json_encode(
                        $payload,
                        JSON_UNESCAPED_UNICODE
                    ),

                CURLOPT_HTTPHEADER =>
                    apiAuthHeaders([
                        'Content-Type: application/json'
                    ]),

                CURLOPT_CONNECTTIMEOUT =>
                    5,

                CURLOPT_TIMEOUT =>
                    10
            ]
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


        if ($response === false) {

            $error =
                'Không thể kết nối tới ASP.NET Core API. '
                . $curlError;

        } else {

            $data =
                json_decode(
                    $response,
                    true
                );


            if ($httpCode === 201) {

                header(
                    'Location: /quanlyquanao/sizes/index.php?created=1'
                );

                exit;


            } elseif ($httpCode === 409) {

                $error =
                    $data['message']
                    ?? 'Size này đã tồn tại.';


            } elseif ($httpCode === 400) {

                $error =
                    $data['message']
                    ?? 'Dữ liệu không hợp lệ.';


            } else {

                $error =
                    $data['message']
                    ?? (
                        'Không thể thêm Size. '
                        . 'HTTP Status: '
                        . $httpCode
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
                <h1>Thêm Size</h1>
                <p>Size / Thêm mới</p>
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
                        placeholder="Ví dụ: S, M, L, XL..."
                        value="<?= htmlspecialchars(
                            $_POST['name'] ?? ''
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
                        Lưu Size
                    </button>

                </div>

            </form>

        </div>

    </section>

</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>