<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('category.create');

$pageTitle = 'Thêm danh mục';
$currentPage = 'categories';

$error = '';

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

        $apiUrl =
            'http://localhost:5162/api/categories';


        $payload = [
            'name' => $name,
            'description' =>
                $description !== ''
                    ? $description
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


        if ($response === false) {

            $error =
                'Không thể kết nối tới ASP.NET Core API. '
                . 'Hãy kiểm tra API localhost:5162 đang chạy.';

        } else {

            $data =
                json_decode(
                    $response,
                    true
                );


            if ($httpCode === 201) {

                curl_close($ch);

                header(
                    'Location: /quanlyquanao/categories/index.php?created=1'
                );

                exit;


            } elseif ($httpCode === 409) {

                $error =
                    $data['message']
                    ?? 'Tên danh mục đã tồn tại.';


            } elseif ($httpCode === 400) {

                $error =
                    $data['message']
                    ?? 'Dữ liệu không hợp lệ.';


            } else {

                $error =
                    $data['message']
                    ?? (
                        'Không thể thêm danh mục. '
                        . 'HTTP Status: '
                        . $httpCode
                    );
            }
        }


        curl_close($ch);
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

                <h1>Thêm danh mục</h1>

                <p>
                    Danh mục / Thêm mới
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
                        placeholder="Nhập tên danh mục"
                        value="<?= htmlspecialchars(
                            $_POST['name'] ?? ''
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Mô tả</label>

                    <textarea
                        name="description"
                        rows="5"
                        placeholder="Nhập mô tả danh mục..."
                    ><?= htmlspecialchars(
                        $_POST['description'] ?? ''
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
                        Lưu danh mục
                    </button>

                </div>

            </form>

        </div>

    </section>

</main>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>