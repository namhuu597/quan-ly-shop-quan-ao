<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('category.update');

$pageTitle = 'Sửa danh mục';
$currentPage = 'categories';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Danh mục không hợp lệ.');
}

$error = '';

$apiBaseUrl =
    'http://localhost:5162/api/categories';


// ======================================================
// HÀM GỌI API
// ======================================================

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

        $options[CURLOPT_CUSTOMREQUEST] =
            $method;
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


// ======================================================
// LẤY DANH MỤC TỪ ASP.NET API
// ======================================================

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

    die('Không tìm thấy danh mục.');
}


if ($result['httpCode'] !== 200) {

    die(
        'Không thể tải dữ liệu danh mục. HTTP Status: '
        . $result['httpCode']
    );
}


$category =
    json_decode(
        $result['response'],
        true
    );


if (!is_array($category)) {

    die(
        'Dữ liệu API trả về không hợp lệ.'
    );
}


// ======================================================
// XỬ LÝ CẬP NHẬT
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrfToken();

    $name =
        trim($_POST['name'] ?? '');

    $description =
        trim($_POST['description'] ?? '');


    if ($name === '') {

        $error =
            'Vui lòng nhập tên danh mục.';

    } else {

        $payload = [
            'name' => $name,
            'description' =>
                $description !== ''
                    ? $description
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
                    'Location: /quanlyquanao/categories/index.php?updated=1'
                );

                exit;


            } elseif (
                $updateResult['httpCode']
                === 409
            ) {

                $error =
                    $data['message']
                    ?? 'Tên danh mục đã tồn tại.';


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
                    'Không tìm thấy danh mục.';


            } else {

                $error =
                    $data['message']
                    ?? (
                        'Không thể cập nhật danh mục. HTTP Status: '
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

        <div>
            <strong>Quản lý danh mục</strong>
        </div>

        <div class="topbar-right">

            <div class="top-user">

                <div class="avatar">
                    <?= strtoupper(
                        substr(
                            $_SESSION['user']['full_name'],
                            0,
                            1
                        )
                    ) ?>
                </div>

                <strong>
                    <?= htmlspecialchars(
                        $_SESSION['user']['full_name']
                    ) ?>
                </strong>

            </div>

        </div>

    </header>


    <section class="content">

        <?php if ($error !== ''): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>Sửa danh mục</h1>

                <p>
                    Danh mục /
                    <?= htmlspecialchars(
                        $category['name']
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
                        Tên danh mục
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="<?= htmlspecialchars(
                            $_POST['name']
                            ?? $category['name']
                            ?? ''
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Mô tả</label>

                    <textarea
                        name="description"
                        rows="5"
                    ><?= htmlspecialchars(
                        $_POST['description']
                        ?? $category['description']
                        ?? ''
                    ) ?></textarea>

                </div>


                <div class="form-actions">

                    <a
                        href="/quanlyquanao/categories/index.php"
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