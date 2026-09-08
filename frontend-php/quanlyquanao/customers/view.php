<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('customer.read');

$pageTitle = 'Chi tiết khách hàng';
$currentPage = 'customers';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Khách hàng không hợp lệ.');
}


$apiBaseUrl =
    'http://localhost:5162/api/customers';


// =========================================
// HÀM GỌI API
// =========================================

function callCustomerApi(
    string $url
): array {

    $ch = curl_init();

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
// LẤY THÔNG TIN KHÁCH HÀNG
// =========================================

$customerResult =
    callCustomerApi(
        $apiBaseUrl . '/' . $id
    );


if (
    $customerResult['response']
    === false
) {

    die(
        'Không thể kết nối tới ASP.NET Core API.'
    );
}


if (
    $customerResult['httpCode']
    === 404
) {

    die(
        'Không tìm thấy khách hàng.'
    );
}


if (
    $customerResult['httpCode']
    !== 200
) {

    die(
        'Không thể tải dữ liệu khách hàng. '
        . 'HTTP Status: '
        . $customerResult['httpCode']
    );
}


$customer =
    json_decode(
        $customerResult['response'],
        true
    );


if (!is_array($customer)) {

    die(
        'Dữ liệu khách hàng từ API không hợp lệ.'
    );
}


// =========================================
// LẤY LỊCH SỬ ĐƠN HÀNG
// =========================================

$orderResult =
    callCustomerApi(
        $apiBaseUrl
        . '/'
        . $id
        . '/orders'
    );


$orders = [];


if (
    $orderResult['response']
    !== false
    && $orderResult['httpCode']
    === 200
) {

    $orderData =
        json_decode(
            $orderResult['response'],
            true
        );


    if (is_array($orderData)) {

        $orders =
            $orderData;
    }
}


// =========================================
// THỐNG KÊ
// =========================================

$stats = [

    'total_orders' =>
        (int)(
            $customer['orderCount']
            ?? 0
        ),

    'completed_orders' =>
        (int)(
            $customer['completedOrders']
            ?? 0
        ),

    'total_spent' =>
        (float)(
            $customer['totalSpent']
            ?? 0
        )
];


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


        <?php if (
            isset($_GET['updated'])
            && $_GET['updated'] === '1'
        ): ?>

            <div class="alert alert-success">
                Cập nhật khách hàng thành công!
            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>
                    Chi tiết khách hàng
                </h1>

                <p>

                    Khách hàng /

                    <?= htmlspecialchars(
                        $customer['customerCode']
                        ?? ''
                    ) ?>

                </p>

            </div>


            <div class="detail-header-actions">

                <a
                    href="/quanlyquanao/customers/index.php"
                    class="btn btn-light"
                >
                    ← Quay lại
                </a>


                <?php if (
                    hasPermission(
                        'customer.update'
                    )
                ): ?>

                    <a
                        href="/quanlyquanao/customers/edit.php?id=<?= $id ?>"
                        class="btn btn-primary"
                    >
                        Sửa khách hàng
                    </a>

                <?php endif; ?>

            </div>

        </div>


        <!-- =========================
             THỐNG KÊ
        ========================== -->

        <div class="customer-stats">

            <div class="stat-card">

                <div class="stat-icon">
                    🧾
                </div>

                <div class="stat-info">

                    <span>
                        Tổng số đơn
                    </span>

                    <h3>
                        <?= $stats['total_orders'] ?>
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ✅
                </div>

                <div class="stat-info">

                    <span>
                        Đơn hoàn thành
                    </span>

                    <h3>
                        <?= $stats['completed_orders'] ?>
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    💰
                </div>

                <div class="stat-info">

                    <span>
                        Tổng chi tiêu
                    </span>

                    <h3>

                        <?= number_format(
                            $stats['total_spent'],
                            0,
                            ',',
                            '.'
                        ) ?> đ

                    </h3>

                </div>

            </div>

        </div>


        <!-- =========================
             THÔNG TIN KHÁCH
        ========================== -->

        <div class="card">

            <h3>
                Thông tin khách hàng
            </h3>

            <div class="detail-list">


                <div class="detail-item">

                    <span>
                        Mã khách hàng
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $customer['customerCode']
                            ?? ''
                        ) ?>

                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Họ và tên
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $customer['fullName']
                            ?? ''
                        ) ?>

                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Số điện thoại
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            !empty(
                                $customer['phone']
                            )
                                ? $customer['phone']
                                : '—'
                        ) ?>

                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Email
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            !empty(
                                $customer['email']
                            )
                                ? $customer['email']
                                : '—'
                        ) ?>

                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Địa chỉ
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            !empty(
                                $customer['address']
                            )
                                ? $customer['address']
                                : '—'
                        ) ?>

                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Trạng thái
                    </span>


                    <?php if (
                        !empty(
                            $customer['isActive']
                        )
                    ): ?>

                        <span
                            class="status status-success"
                        >
                            Hoạt động
                        </span>

                    <?php else: ?>

                        <span
                            class="status status-danger"
                        >
                            Ngừng hoạt động
                        </span>

                    <?php endif; ?>

                </div>


                <div class="detail-item">

                    <span>
                        Ngày tạo
                    </span>

                    <strong>

                        <?php if (
                            !empty(
                                $customer['createdAt']
                            )
                        ): ?>

                            <?= date(
                                'd/m/Y H:i',
                                strtotime(
                                    $customer['createdAt']
                                )
                            ) ?>

                        <?php else: ?>

                            —

                        <?php endif; ?>

                    </strong>

                </div>

            </div>

        </div>


        <!-- =========================
             LỊCH SỬ ĐƠN HÀNG
        ========================== -->

        <div
            class="card"
            style="margin-top:20px;"
        >

            <h3>
                Lịch sử mua hàng
            </h3>


            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Mã đơn</th>
                            <th>Nhân viên</th>
                            <th>Ngày tạo</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>

                    </thead>


                    <tbody>

                    <?php if (
                        empty($orders)
                    ): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="empty-data"
                            >
                                Khách hàng chưa có đơn hàng nào.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach (
                            $orders
                            as $index => $order
                        ): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $order['orderCode']
                                            ?? ''
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $order['employeeName']
                                        ?? '—'
                                    ) ?>

                                </td>


                                <td>

                                    <?php if (
                                        !empty(
                                            $order['createdAt']
                                        )
                                    ): ?>

                                        <?= date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $order['createdAt']
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= number_format(
                                            (float)(
                                                $order['totalAmount']
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
                                        ($order['status'] ?? '')
                                        === 'COMPLETED'
                                    ): ?>

                                        <span
                                            class="status status-success"
                                        >
                                            Hoàn thành
                                        </span>


                                    <?php elseif (
                                        ($order['status'] ?? '')
                                        === 'CANCELLED'
                                    ): ?>

                                        <span
                                            class="status status-danger"
                                        >
                                            Không hoàn thành
                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="status status-warning"
                                        >
                                            Chưa hoàn thành
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <a
                                        href="/quanlyquanao/orders/view.php?id=<?= (int)($order['id'] ?? 0) ?>"
                                        class="action-btn"
                                        title="Xem đơn hàng"
                                    >
                                        👁
                                    </a>

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