<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('customer.update');

$pageTitle = 'Sửa khách hàng';
$currentPage = 'customers';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Khách hàng không hợp lệ.');
}

$error = '';

$apiBaseUrl =
    'http://localhost:5162/api/customers';


// =========================================
// HÀM GỌI ASP.NET API
// =========================================

function callApi(
    string $url,
    string $method = 'GET',
    ?array $payload = null
): array {

    $ch = curl_init();

    $options = [
        CURLOPT_URL =>
            $url,

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_CONNECTTIMEOUT =>
            5,

        CURLOPT_TIMEOUT =>
            10,

        CURLOPT_HTTPHEADER =>
            apiAuthHeaders([
                'Content-Type: application/json'
            ])
    ];


    if ($method !== 'GET') {

        $options[
            CURLOPT_CUSTOMREQUEST
        ] = $method;
    }


    if ($payload !== null) {

        $options[
            CURLOPT_POSTFIELDS
        ] = json_encode(
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
        'response' =>
            $response,

        'httpCode' =>
            $httpCode,

        'curlError' =>
            $curlError
    ];
}


// =========================================
// LẤY KHÁCH HÀNG TỪ API
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
        'Không tìm thấy khách hàng.'
    );
}


if ($result['httpCode'] !== 200) {

    die(
        'Không thể tải dữ liệu khách hàng. '
        . 'HTTP Status: '
        . $result['httpCode']
    );
}


$customer =
    json_decode(
        $result['response'],
        true
    );


if (!is_array($customer)) {

    die(
        'Dữ liệu API trả về không hợp lệ.'
    );
}


// =========================================
// XỬ LÝ FORM CẬP NHẬT
// =========================================

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

    $isActive =
        isset($_POST['is_active']);


    if ($fullName === '') {

        $error =
            'Vui lòng nhập họ tên khách hàng.';

    } else {

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
                    : null,

            'isActive' =>
                $isActive
        ];


        $updateResult =
            callApi(
                $apiBaseUrl
                . '/'
                . $id,
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
                    'Location: /quanlyquanao/customers/view.php?id='
                    . $id
                    . '&updated=1'
                );

                exit;


            } elseif (
                $updateResult['httpCode']
                === 401
            ) {

                $error =
                    'Phiên đăng nhập đã hết hạn hoặc JWT không hợp lệ. '
                    . 'Vui lòng đăng xuất và đăng nhập lại.';

            } elseif (
                $updateResult['httpCode']
                === 403
            ) {

                $error =
                    $data['message']
                    ?? 'Bạn không có quyền cập nhật khách hàng.';

            } elseif (
                $updateResult['httpCode']
                === 409
            ) {

                $error =
                    $data['message']
                    ?? 'Số điện thoại đã tồn tại.';


            } elseif (
                $updateResult['httpCode']
                === 400
            ) {

                $error =
                    $data['message']
                    ?? 'Dữ liệu khách hàng không hợp lệ.';


            } elseif (
                $updateResult['httpCode']
                === 404
            ) {

                $error =
                    'Không tìm thấy khách hàng.';


            } else {

                $error =
                    $data['message']
                    ?? (
                        'Không thể cập nhật khách hàng. '
                        . 'HTTP Status: '
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
            <strong>
                Quản lý khách hàng
            </strong>
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

                <?= htmlspecialchars(
                    $error
                ) ?>

            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>
                    Sửa khách hàng
                </h1>

                <p>

                    Khách hàng /

                    <?= htmlspecialchars(
                        $customer[
                            'customerCode'
                        ]
                        ?? ''
                    ) ?>

                    / Sửa

                </p>

            </div>

        </div>


        <div class="card customer-form-card">

            <form method="POST">

                <?= csrfField() ?>


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Mã khách hàng
                        </label>

                        <input
                            type="text"
                            value="<?= htmlspecialchars(
                                $customer[
                                    'customerCode'
                                ]
                                ?? ''
                            ) ?>"
                            disabled
                        >

                    </div>


                    <div class="form-group">

                        <label>

                            Họ và tên

                            <span class="required">
                                *
                            </span>

                        </label>

                        <input
                            type="text"
                            name="full_name"
                            value="<?= htmlspecialchars(
                                $_POST['full_name']
                                ?? $customer['fullName']
                                ?? ''
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
                            value="<?= htmlspecialchars(
                                $_POST['phone']
                                ?? $customer['phone']
                                ?? ''
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
                            value="<?= htmlspecialchars(
                                $_POST['email']
                                ?? $customer['email']
                                ?? ''
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
                        ><?= htmlspecialchars(
                            $_POST['address']
                            ?? $customer['address']
                            ?? ''
                        ) ?></textarea>

                    </div>


                    <div class="form-group full-width">

                        <label class="customer-status-option">

                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"

                                <?= (
                                    $_SERVER['REQUEST_METHOD']
                                    === 'POST'

                                        ? isset(
                                            $_POST['is_active']
                                        )

                                        : !empty(
                                            $customer['isActive']
                                        )
                                )
                                    ? 'checked'
                                    : '' ?>
                            >

                            Khách hàng đang hoạt động

                        </label>


                        <small class="field-help">

                            Bỏ chọn nếu muốn ngừng hoạt động khách hàng.
                            Dữ liệu và lịch sử đơn hàng vẫn được giữ lại.

                        </small>

                    </div>

                </div>


                <div class="form-actions">

                    <a
                        href="/quanlyquanao/customers/view.php?id=<?= $id ?>"
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