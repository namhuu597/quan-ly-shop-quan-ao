<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('customer.create');

$pageTitle = 'Thêm khách hàng';
$currentPage = 'customers';

$error = '';


// ==========================
// XỬ LÝ FORM
// ==========================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrfToken();

    $fullName =
        trim(
            $_POST['full_name']
            ?? ''
        );

    $phone =
        trim(
            $_POST['phone']
            ?? ''
        );

    $email =
        trim(
            $_POST['email']
            ?? ''
        );

    $address =
        trim(
            $_POST['address']
            ?? ''
        );


    if ($fullName === '') {

        $error =
            'Vui lòng nhập họ tên khách hàng.';

    } else {

        $apiUrl =
            'http://localhost:5162/api/customers';


        $payload = [
            'fullName' =>
                $fullName,

            'phone' =>
                $phone !== ''
                    ? $phone
                    : null,

            'email' =>
                $email !== ''
                    ? $email
                    : null,

            'address' =>
                $address !== ''
                    ? $address
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
                    'Location: /quanlyquanao/customers/index.php?created=1'
                );

                exit;


            } elseif ($httpCode === 401) {

                $error =
                    'Phiên đăng nhập đã hết hạn hoặc JWT không hợp lệ. '
                    . 'Vui lòng đăng xuất và đăng nhập lại.';

            } elseif ($httpCode === 403) {

                $error =
                    $data['message']
                    ?? 'Bạn không có quyền thêm khách hàng.';

            } elseif ($httpCode === 409) {

                $error =
                    $data['message']
                    ?? 'Dữ liệu khách hàng đã tồn tại.';


            } elseif ($httpCode === 400) {

                $error =
                    $data['message']
                    ?? 'Dữ liệu khách hàng không hợp lệ.';


            } else {

                $error =
                    $data['message']
                    ?? (
                        'Không thể thêm khách hàng. HTTP Status: '
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

    <?php
    require_once __DIR__ . '/../includes/topbar.php';
    ?>


    <section class="content">


        <?php if ($error !== ''): ?>

            <div class="alert alert-danger">

                <?= htmlspecialchars(
                    $error
                ) ?>

            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>
                    Thêm khách hàng
                </h1>

                <p>
                    Khách hàng / Thêm mới
                </p>

            </div>

        </div>


        <div class="card customer-form-card">

            <form method="POST">

                <?= csrfField() ?>


                <div class="form-grid">


                    <div class="form-group full-width">

                        <label>

                            Họ và tên

                            <span class="required">
                                *
                            </span>

                        </label>

                        <input
                            type="text"
                            name="full_name"
                            placeholder="Nhập họ tên khách hàng"
                            value="<?= htmlspecialchars(
                                $_POST['full_name'] ?? ''
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Số điện thoại
                        </label>

                        <input
                            type="text"
                            name="phone"
                            placeholder="Ví dụ: 0987654321"
                            value="<?= htmlspecialchars(
                                $_POST['phone'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            placeholder="Ví dụ: khachhang@gmail.com"
                            value="<?= htmlspecialchars(
                                $_POST['email'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <div class="form-group full-width">

                        <label>
                            Địa chỉ
                        </label>

                        <textarea
                            name="address"
                            rows="4"
                            placeholder="Nhập địa chỉ khách hàng..."
                        ><?= htmlspecialchars(
                            $_POST['address'] ?? ''
                        ) ?></textarea>

                    </div>


                </div>


                <div class="form-actions">

                    <a
                        href="/quanlyquanao/customers/index.php"
                        class="btn btn-light"
                    >
                        Hủy
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Lưu khách hàng
                    </button>

                </div>

            </form>

        </div>

    </section>

</main>


<?php
require_once __DIR__ . '/../includes/footer.php';
?>