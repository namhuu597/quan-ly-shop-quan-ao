<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('customer.read');

$pageTitle = 'Quản lý khách hàng';
$currentPage = 'customers';

$keyword = trim($_GET['keyword'] ?? '');

$apiUrl =
    'http://localhost:5162/api/customers';

if ($keyword !== '') {

    $apiUrl .=
        '?keyword='
        . urlencode($keyword);
}

$customers = [];
$apiError = '';

$ch = curl_init();

curl_setopt_array(
    $ch,
    [
        CURLOPT_URL =>
            $apiUrl,

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_CONNECTTIMEOUT =>
            5,

        CURLOPT_TIMEOUT =>
            10,

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


if ($response === false) {

    $apiError =
        'Không thể kết nối tới ASP.NET Core API. '
        . $curlError;

} elseif ($httpCode !== 200) {

    $apiError =
        'Không thể tải danh sách khách hàng. '
        . 'HTTP Status: '
        . $httpCode;

} else {

    $data =
        json_decode(
            $response,
            true
        );

    if (is_array($data)) {

        $customers =
            $data;

    } else {

        $apiError =
            'Dữ liệu API trả về không hợp lệ.';
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
                Thêm khách hàng thành công!
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['updated'])
            && $_GET['updated'] === '1'
        ): ?>

            <div class="alert alert-success">
                Cập nhật khách hàng thành công!
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['status_changed'])
            && $_GET['status_changed'] === '1'
        ): ?>

            <div class="alert alert-success">
                Cập nhật trạng thái khách hàng thành công!
            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>Danh sách khách hàng</h1>

                <p>
                    Khách hàng / Danh sách
                </p>

            </div>


            <?php if (
                hasPermission(
                    'customer.create'
                )
            ): ?>

                <a
                    href="/quanlyquanao/customers/create.php"
                    class="btn btn-primary"
                >
                    + Thêm khách hàng
                </a>

            <?php endif; ?>

        </div>


        <div class="card product-filter">

            <form
                method="GET"
                class="category-filter-form"
            >

                <input
                    type="text"
                    name="keyword"
                    placeholder="Tìm mã, tên, SĐT hoặc email..."
                    value="<?= htmlspecialchars(
                        $keyword
                    ) ?>"
                >

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tìm kiếm
                </button>

                <a
                    href="/quanlyquanao/customers/index.php"
                    class="btn btn-light"
                >
                    Đặt lại
                </a>

            </form>

        </div>


        <div class="card">

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Mã KH</th>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Email</th>
                            <th>Số đơn</th>
                            <th>Tổng chi tiêu</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>

                    </thead>


                    <tbody>

                    <?php if (
                        $apiError === ''
                        && empty($customers)
                    ): ?>

                        <tr>
                            <td
                                colspan="9"
                                class="empty-data"
                            >
                                Chưa có khách hàng nào.
                            </td>
                        </tr>

                    <?php elseif (
                        $apiError === ''
                    ): ?>

                        <?php foreach (
                            $customers
                            as $index => $customer
                        ): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $customer[
                                                'customerCode'
                                            ]
                                            ?? ''
                                        ) ?>
                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $customer[
                                            'fullName'
                                        ]
                                        ?? ''
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        !empty(
                                            $customer['phone']
                                        )
                                            ? $customer['phone']
                                            : '—'
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        !empty(
                                            $customer['email']
                                        )
                                            ? $customer['email']
                                            : '—'
                                    ) ?>

                                </td>


                                <td>

                                    <?= (int)(
                                        $customer[
                                            'orderCount'
                                        ]
                                        ?? 0
                                    ) ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= number_format(
                                            (float)(
                                                $customer[
                                                    'totalSpent'
                                                ]
                                                ?? 0
                                            ),
                                            0,
                                            ',',
                                            '.'
                                        ) ?> đ

                                    </strong>

                                </td>


                                <td>

                                    <?php if (
                                        !empty(
                                            $customer[
                                                'isActive'
                                            ]
                                        )
                                    ): ?>

                                        <span class="status status-success">
                                            Hoạt động
                                        </span>

                                    <?php else: ?>

                                        <span class="status status-danger">
                                            Ngừng hoạt động
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <div class="table-actions">

                                        <a
                                            href="/quanlyquanao/customers/view.php?id=<?= (int)($customer['id'] ?? 0) ?>"
                                            class="action-btn"
                                            title="Xem"
                                        >
                                            👁
                                        </a>


                                        <?php if (
                                            hasPermission(
                                                'customer.update'
                                            )
                                        ): ?>

                                            <a
                                                href="/quanlyquanao/customers/edit.php?id=<?= (int)($customer['id'] ?? 0) ?>"
                                                class="action-btn"
                                                title="Sửa"
                                            >
                                                ✏️
                                            </a>

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