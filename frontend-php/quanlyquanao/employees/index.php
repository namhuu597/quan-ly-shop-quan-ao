<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('user.read');

$pageTitle = 'Quản lý nhân viên';
$currentPage = 'employees';

$keyword =
    trim(
        $_GET['keyword']
        ?? ''
    );

$status =
    $_GET['status']
    ?? '';

$apiBaseUrl =
    'http://localhost:5162/api';


// =========================================
// HÀM GỌI API
// =========================================

function getEmployeesApi(
    string $url
): array {

    $ch =
        curl_init();

    curl_setopt_array(
        $ch,
        [
            CURLOPT_URL =>
                $url,

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_CONNECTTIMEOUT =>
                5,

            CURLOPT_TIMEOUT =>
                20,

            CURLOPT_HTTPHEADER =>
                apiAuthHeaders()
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
// QUERY API
// =========================================

$queryParams = [];

if ($keyword !== '') {
    $queryParams['keyword'] =
        $keyword;
}

if ($status !== '') {
    $queryParams['status'] =
        $status;
}


$apiUrl =
    $apiBaseUrl
    . '/employees';

if (!empty($queryParams)) {
    $apiUrl .=
        '?'
        . http_build_query(
            $queryParams
        );
}


// =========================================
// LOAD DANH SÁCH + THỐNG KÊ
// =========================================

$users = [];

$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'inactive_users' => 0
];

$apiError = '';

$result =
    getEmployeesApi(
        $apiUrl
    );


if (
    $result['response']
    === false
) {

    $apiError =
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
        && is_array($data)
    ) {

        $apiStats =
            $data['stats']
            ?? [];

        $stats = [
            'total_users' =>
                (int)(
                    $apiStats['totalUsers']
                    ?? 0
                ),

            'active_users' =>
                (int)(
                    $apiStats['activeUsers']
                    ?? 0
                ),

            'inactive_users' =>
                (int)(
                    $apiStats['inactiveUsers']
                    ?? 0
                )
        ];


        foreach (
            $data['employees']
                ?? []
            as $user
        ) {

            $users[] = [
                'id' =>
                    $user['id']
                    ?? 0,

                'username' =>
                    $user['username']
                    ?? '',

                'email' =>
                    $user['email']
                    ?? '',

                'full_name' =>
                    $user['fullName']
                    ?? '',

                'phone' =>
                    $user['phone']
                    ?? '',

                'is_active' =>
                    $user['isActive']
                    ?? false,

                'created_at' =>
                    $user['createdAt']
                    ?? '',

                'roles' =>
                    $user['roles']
                    ?? null
            ];
        }

    } else {

        $apiError =
            $data['message']
            ?? (
                'Không thể tải danh sách nhân viên. HTTP Status: '
                . $result['httpCode']
            );
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

        <?php if ($apiError !== ''): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($apiError) ?>
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['created'])
            && $_GET['created'] === '1'
        ): ?>

            <div class="alert alert-success">
                Thêm nhân viên thành công!
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['updated'])
            && $_GET['updated'] === '1'
        ): ?>

            <div class="alert alert-success">
                Cập nhật nhân viên thành công!
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['status_changed'])
            && $_GET['status_changed'] === '1'
        ): ?>

            <div class="alert alert-success">
                Cập nhật trạng thái tài khoản thành công!
            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>Nhân viên</h1>

                <p>
                    Nhân viên / Danh sách
                </p>

            </div>


            <?php if (
                hasPermission('user.create')
            ): ?>

                <a
                    href="/quanlyquanao/employees/create.php"
                    class="btn btn-primary"
                >
                    + Thêm nhân viên
                </a>

            <?php endif; ?>

        </div>


        <!-- =========================
             THỐNG KÊ
        ========================== -->

        <div class="employee-stats">

            <div class="stat-card">

                <div class="stat-icon">
                    👥
                </div>

                <div class="stat-info">

                    <span>Tổng tài khoản</span>

                    <h3>
                        <?= (int)$stats['total_users'] ?>
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ✅
                </div>

                <div class="stat-info">

                    <span>Đang hoạt động</span>

                    <h3>
                        <?= (int)$stats['active_users'] ?>
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ⛔
                </div>

                <div class="stat-info">

                    <span>Đã khóa</span>

                    <h3>
                        <?= (int)$stats['inactive_users'] ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- =========================
             FILTER
        ========================== -->

        <div class="card employee-filter">

            <form
                method="GET"
                class="employee-filter-form"
            >

                <input
                    type="text"
                    name="keyword"
                    placeholder="Tìm tên, username, email hoặc SĐT..."
                    value="<?= htmlspecialchars($keyword) ?>"
                >


                <select name="status">

                    <option value="">
                        Tất cả trạng thái
                    </option>

                    <option
                        value="active"
                        <?= $status === 'active'
                            ? 'selected'
                            : '' ?>
                    >
                        Hoạt động
                    </option>

                    <option
                        value="inactive"
                        <?= $status === 'inactive'
                            ? 'selected'
                            : '' ?>
                    >
                        Đã khóa
                    </option>

                </select>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tìm kiếm
                </button>


                <a
                    href="/quanlyquanao/employees/index.php"
                    class="btn btn-light"
                >
                    Đặt lại
                </a>

            </form>

        </div>


        <!-- =========================
             TABLE
        ========================== -->

        <div class="card">

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Nhân viên</th>
                            <th>Tài khoản</th>
                            <th>Email</th>
                            <th>SĐT</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($users)): ?>

                        <tr>

                            <td
                                colspan="9"
                                class="empty-data"
                            >
                                Chưa có nhân viên nào.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach (
                            $users as $index => $user
                        ): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $user['full_name']
                                        ) ?>
                                    </strong>

                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $user['username']
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $user['email']
                                        ?: '—'
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $user['phone']
                                        ?: '—'
                                    ) ?>
                                </td>


                                <td>

                                    <?php if (
                                        !empty($user['roles'])
                                    ): ?>

                                        <span class="employee-role">
                                            <?= htmlspecialchars(
                                                $user['roles']
                                            ) ?>
                                        </span>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php if (
                                        $user['is_active']
                                    ): ?>

                                        <span class="status status-success">
                                            Hoạt động
                                        </span>

                                    <?php else: ?>

                                        <span class="status status-danger">
                                            Đã khóa
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime(
                                            $user['created_at']
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <div class="table-actions">

                                        <?php if (
                                            hasPermission(
                                                'user.update'
                                            )
                                        ): ?>

                                            <a
                                                href="/quanlyquanao/employees/edit.php?id=<?= $user['id'] ?>"
                                                class="action-btn"
                                                title="Sửa nhân viên"
                                            >
                                                ✏️
                                            </a>

                                        <?php endif; ?>


                                        <?php if (
                                            hasPermission(
                                                'user.disable'
                                            )
                                            &&
                                            (int)$user['id']
                                            !==
                                            (int)$_SESSION['user']['id']
                                        ): ?>

                                            <form
                                                method="POST"
                                                action="/quanlyquanao/employees/toggle-status.php"
                                                style="display:inline;"
                                            >

                                                <?= csrfField() ?>

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= $user['id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="action-btn"
                                                    title="<?= $user['is_active']
                                                        ? 'Khóa tài khoản'
                                                        : 'Kích hoạt tài khoản'
                                                    ?>"
                                                    onclick="return confirm('Bạn có chắc muốn thay đổi trạng thái tài khoản này?');"
                                                >

                                                    <?= $user['is_active']
                                                        ? '⛔'
                                                        : '✅'
                                                    ?>

                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </section>

</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>