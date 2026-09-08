<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('user.create');

$pageTitle = 'Thêm nhân viên';
$currentPage = 'employees';

$error = '';

$apiBaseUrl =
    'http://localhost:5162/api';


// =========================================
// HÀM GỌI API
// =========================================

function callEmployeesApi(
    string $url,
    string $method = 'GET',
    ?array $payload = null
): array {

    $ch =
        curl_init();

    $headers = [
        'Accept: application/json'
    ];

    $options = [
        CURLOPT_URL =>
            $url,

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_CONNECTTIMEOUT =>
            5,

        CURLOPT_TIMEOUT =>
            30
    ];


    if ($method !== 'GET') {
        $options[
            CURLOPT_CUSTOMREQUEST
        ] = $method;
    }


    if ($payload !== null) {

        $headers[] =
            'Content-Type: application/json';

        $options[
            CURLOPT_POSTFIELDS
        ] =
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
            );
    }


    $options[
        CURLOPT_HTTPHEADER
    ] = $headers;


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
// LOAD ROLE
// =========================================

$optionsResult =
    callEmployeesApi(
        $apiBaseUrl
        . '/employees/form-options'
    );


if (
    $optionsResult['response']
    === false
) {
    die(
        'Không thể kết nối tới ASP.NET Core API. '
        . $optionsResult['curlError']
    );
}


$optionsData =
    json_decode(
        $optionsResult['response'],
        true
    );


if (
    $optionsResult['httpCode']
    !== 200
    || !is_array($optionsData)
) {
    die(
        $optionsData['message']
        ?? (
            'Không thể tải danh sách vai trò. HTTP Status: '
            . $optionsResult['httpCode']
        )
    );
}


$roles = [];

foreach (
    $optionsData['roles']
        ?? []
    as $role
) {

    $roles[] = [
        'id' =>
            $role['id']
            ?? 0,

        'name' =>
            $role['name']
            ?? '',

        'description' =>
            $role['description']
            ?? ''
    ];
}


// =========================================
// XỬ LÝ THÊM NHÂN VIÊN
// =========================================

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    verifyCsrfToken();

    $fullName =
        trim(
            $_POST['full_name']
            ?? ''
        );

    $username =
        trim(
            $_POST['username']
            ?? ''
        );

    $email =
        trim(
            $_POST['email']
            ?? ''
        );

    $phone =
        trim(
            $_POST['phone']
            ?? ''
        );

    $password =
        $_POST['password']
        ?? '';

    $confirmPassword =
        $_POST['confirm_password']
        ?? '';

    $roleId =
        (int)(
            $_POST['role_id']
            ?? 0
        );


    if ($fullName === '') {

        $error =
            'Vui lòng nhập họ tên nhân viên.';

    } elseif ($username === '') {

        $error =
            'Vui lòng nhập tên đăng nhập.';

    } elseif (
        !preg_match(
            '/^[a-zA-Z0-9_.]{4,30}$/',
            $username
        )
    ) {

        $error =
            'Tên đăng nhập phải từ 4-30 ký tự và chỉ gồm chữ, số, dấu chấm hoặc gạch dưới.';

    } elseif (
        $email !== ''
        && !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Email không hợp lệ.';

    } elseif (
        strlen($password) < 6
    ) {

        $error =
            'Mật khẩu phải có ít nhất 6 ký tự.';

    } elseif (
        $password
        !== $confirmPassword
    ) {

        $error =
            'Xác nhận mật khẩu không khớp.';

    } elseif ($roleId <= 0) {

        $error =
            'Vui lòng chọn vai trò.';

    } else {

        /*
         * Tạm thời PHP chỉ tạo password_hash để giữ
         * tương thích với login hiện tại đang dùng
         * password_verify().
         *
         * Việc INSERT user + gán role đã chuyển hoàn toàn
         * sang ASP.NET API.
         */
        $passwordHash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        $payload = [
            'fullName' =>
                $fullName,

            'username' =>
                $username,

            'email' =>
                $email,

            'phone' =>
                $phone,

            'passwordHash' =>
                $passwordHash,

            'roleId' =>
                $roleId
        ];


        $result =
            callEmployeesApi(
                $apiBaseUrl
                . '/employees',
                'POST',
                $payload
            );


        if (
            $result['response']
            === false
        ) {

            $error =
                'Không thể kết nối tới ASP.NET Core API. '
                . $result['curlError'];

        } else {

            $data =
                json_decode(
                    $result['response'],
                    true
                );


            if (
                $result['httpCode']
                === 201
            ) {

                header(
                    'Location: /quanlyquanao/employees/index.php?created=1'
                );

                exit;

            } else {

                $error =
                    $data['message']
                    ?? (
                        'Không thể thêm nhân viên. HTTP Status: '
                        . $result['httpCode']
                    );


                if (
                    !empty(
                        $data['detail']
                        ?? ''
                    )
                ) {
                    $error .=
                        ' - '
                        . $data['detail'];
                }
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
            <strong>Quản lý nhân viên</strong>
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

                <h1>Thêm nhân viên</h1>

                <p>
                    Nhân viên / Thêm mới
                </p>

            </div>


            <a
                href="/quanlyquanao/employees/index.php"
                class="btn btn-light"
            >
                ← Quay lại
            </a>

        </div>


        <div class="card employee-form-card">

            <form method="POST">

<?= csrfField() ?>
                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Họ và tên
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            value="<?= htmlspecialchars(
                                $_POST['full_name'] ?? ''
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Tên đăng nhập
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="username"
                            value="<?= htmlspecialchars(
                                $_POST['username'] ?? ''
                            ) ?>"
                            placeholder="Ví dụ: nhanvien01"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>Email</label>

                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars(
                                $_POST['email'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>Số điện thoại</label>

                        <input
                            type="text"
                            name="phone"
                            value="<?= htmlspecialchars(
                                $_POST['phone'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Mật khẩu
                            <span class="required">*</span>
                        </label>

                        <input
                            type="password"
                            name="password"
                            minlength="6"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Xác nhận mật khẩu
                            <span class="required">*</span>
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            minlength="6"
                            required
                        >

                    </div>


                    <div class="form-group full-width">

                        <label>
                            Vai trò
                            <span class="required">*</span>
                        </label>

                        <select
                            name="role_id"
                            required
                        >

                            <option value="">
                                -- Chọn vai trò --
                            </option>


                            <?php foreach (
                                $roles as $role
                            ): ?>

                                <option
                                    value="<?= $role['id'] ?>"
                                    <?= (
                                        (int)($_POST['role_id'] ?? 0)
                                        ===
                                        (int)$role['id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $role['name']
                                    ) ?>

                                    <?php if (
                                        !empty($role['description'])
                                    ): ?>

                                        -
                                        <?= htmlspecialchars(
                                            $role['description']
                                        ) ?>

                                    <?php endif; ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


                <div class="employee-role-help">

                    <div>

                        <strong>MANAGER</strong>

                        <p>
                            Quản lý sản phẩm, kho hàng,
                            đơn hàng, khách hàng và báo cáo.
                        </p>

                    </div>


                    <div>

                        <strong>EMPLOYEE</strong>

                        <p>
                            Bán hàng, xem sản phẩm,
                            tồn kho và quản lý khách hàng.
                        </p>

                    </div>

                </div>


                <div class="form-actions">

                    <a
                        href="/quanlyquanao/employees/index.php"
                        class="btn btn-light"
                    >
                        Hủy
                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Thêm nhân viên
                    </button>

                </div>

            </form>

        </div>

    </section>

</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>