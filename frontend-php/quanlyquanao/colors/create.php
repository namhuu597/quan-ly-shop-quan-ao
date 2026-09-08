<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('color.create');

$pageTitle = 'Thêm màu sắc';
$currentPage = 'colors';

$error = '';

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

        $apiUrl =
            'http://localhost:5162/api/colors';


        $payload = [
            'name' =>
                $name,

            'colorCode' =>
                $colorCode !== ''
                    ? $colorCode
                    : null
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
                    'Location: /quanlyquanao/colors/index.php?created=1'
                );

                exit;


            } elseif ($httpCode === 409) {

                $error =
                    $data['message']
                    ?? 'Tên màu này đã tồn tại.';


            } elseif ($httpCode === 400) {

                $error =
                    $data['message']
                    ?? 'Dữ liệu màu không hợp lệ.';


            } else {

                $error =
                    $data['message']
                    ?? (
                        'Không thể thêm màu sắc. HTTP Status: '
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

                <h1>Thêm màu sắc</h1>

                <p>
                    Màu sắc / Thêm mới
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
                        placeholder="Ví dụ: Đen"
                        value="<?= htmlspecialchars(
                            $_POST['name'] ?? ''
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
                                ?? '#000000'
                            ) ?>"
                        >

                        <input
                            type="text"
                            name="color_code"
                            id="colorCodeInput"
                            placeholder="#000000"
                            value="<?= htmlspecialchars(
                                $_POST['color_code']
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
                        Lưu màu
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