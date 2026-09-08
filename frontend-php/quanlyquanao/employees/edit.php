<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('user.update');

$pageTitle = 'Sửa nhân viên';
$currentPage = 'employees';

$id =
    (int)(
        $_GET['id']
        ?? 0
    );

if ($id <= 0) {
    die(
        'Nhân viên không hợp lệ.'
    );
}

$error = '';

$apiBaseUrl =
    'http://localhost:5162/api';


// =========================================
// HÀM GỌI API
// =========================================

function callEmployeeEditApi(
    string $url,
    string $method = 'GET',
    ?array $payload = null
): array {

    $ch =
        curl_init();

    $headers =
        apiAuthHeaders();

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
// LOAD NHÂN VIÊN
// =========================================

$employeeResult =
    callEmployeeEditApi(
        $apiBaseUrl
        . '/employees/'
        . $id
    );


if (
    $employeeResult['response']
    === false
) {
    die(
        'Không thể kết nối tới ASP.NET Core API. '
        . $employeeResult['curlError']
    );
}


$employeeData =
    json_decode(
        $employeeResult['response'],
        true
    );


if ($employeeResult['httpCode'] === 404) {
    die(
        $employeeData['message']
        ?? 'Không tìm thấy nhân viên.'
    );
}


if (
    $employeeResult['httpCode']
    !== 200
    || !is_array($employeeData)
) {
    die(
        $employeeData['message']
        ?? (
            'Không thể tải thông tin nhân viên. HTTP Status: '
            . $employeeResult['httpCode']
        )
    );
}


$employee = [
    'id' =>
        $employeeData['id']
        ?? 0,

    'username' =>
        $employeeData['username']
        ?? '',

    'email' =>
        $employeeData['email']
        ?? '',

    'full_name' =>
        $employeeData['fullName']
        ?? '',

    'phone' =>
        $employeeData['phone']
        ?? '',

    'is_active' =>
        $employeeData['isActive']
        ?? false,

    'role_id' =>
        $employeeData['roleId']
        ?? 0,

    'role_name' =>
        $employeeData['roleName']
        ?? ''
];

$currentRoleId =
    (int)(
        $employee['role_id']
        ?? 0
    );


// =========================================
// LOAD ROLE
// =========================================

$optionsResult =
    callEmployeeEditApi(
        $apiBaseUrl
        . '/employees/edit-options'
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
// UPDATE QUA ASP.NET API
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

    $roleId =
        (int)(
            $_POST['role_id']
            ?? 0
        );

    $newPassword =
        $_POST['new_password']
        ?? '';

    $confirmPassword =
        $_POST['confirm_password']
        ?? '';


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
            'Tên đăng nhập không hợp lệ.';

    } elseif (
        $email !== ''
        && !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Email không hợp lệ.';

    } elseif ($roleId <= 0) {

        $error =
            'Vui lòng chọn vai trò.';

    } elseif (
        $newPassword !== ''
        && strlen($newPassword) < 6
    ) {

        $error =
            'Mật khẩu mới phải có ít nhất 6 ký tự.';

    } elseif (
        $newPassword !== ''
        && $newPassword !== $confirmPassword
    ) {

        $error =
            'Xác nhận mật khẩu không khớp.';

    } else {

        $payload = [
            'fullName' =>
                $fullName,

            'username' =>
                $username,

            'email' =>
                $email,

            'phone' =>
                $phone,

            'roleId' =>
                $roleId,

            'newPassword' =>
                $newPassword !== ''
                    ? $newPassword
                    : null
        ];


        $result =
            callEmployeeEditApi(
                $apiBaseUrl
                . '/employees/'
                . $id,
                'PUT',
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
                === 200
            ) {

                header(
                    'Location: /quanlyquanao/employees/index.php?updated=1'
                );

                exit;

            } else {

                $error =
                    $data['message']
                    ?? (
                        'Không thể cập nhật nhân viên. HTTP Status: '
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

                <h1>Sửa nhân viên</h1>

                <p>
                    Nhân viên /
                    <?= htmlspecialchars(
                        $employee['username']
                    ) ?>
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
                                $_POST['full_name']
                                ?? $employee['full_name']
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
                                $_POST['username']
                                ?? $employee['username']
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>Email</label>

                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars(
                                $_POST['email']
                                ?? $employee['email']
                                ?? ''
                            ) ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>Số điện thoại</label>

                        <input
                            type="text"
                            name="phone"
                            value="<?= htmlspecialchars(
                                $_POST['phone']
                                ?? $employee['phone']
                                ?? ''
                            ) ?>"
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

                            <?php foreach (
                                $roles as $role
                            ): ?>

                                <option
                                    value="<?= $role['id'] ?>"
                                    <?= (
                                        (int)(
                                            $_POST['role_id']
                                            ?? $currentRoleId
                                        )
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
                                        !empty(
                                            $role['description']
                                        )
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


                    <div class="form-group">

                        <label>Mật khẩu mới</label>

                        <input
                            type="password"
                            name="new_password"
                            minlength="6"
                            placeholder="Để trống nếu không đổi"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Xác nhận mật khẩu mới
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            minlength="6"
                        >

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