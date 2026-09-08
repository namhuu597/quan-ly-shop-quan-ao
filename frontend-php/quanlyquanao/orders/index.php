<?php

require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$pageTitle = 'Quản lý đơn hàng';
$currentPage = 'orders';

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
// HÀM GỌI ASP.NET API
// =========================================

function getApi(string $url): array
{
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
                15,

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
// TẠO QUERY CHO API
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
    . '/orders';

if (!empty($queryParams)) {
    $apiUrl .=
        '?'
        . http_build_query(
            $queryParams
        );
}


// =========================================
// LẤY DANH SÁCH ĐƠN HÀNG
// =========================================

$orders = [];
$apiError = '';

$result =
    getApi(
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

        $orders =
            $data;

    } else {

        $apiError =
            $data['message']
            ?? (
                'Không thể tải danh sách đơn hàng. HTTP Status: '
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
                <?= htmlspecialchars(
                    $apiError
                ) ?>
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['created'])
            && $_GET['created'] === '1'
        ): ?>

            <div class="alert alert-success">
                Tạo đơn hàng thành công!
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['completed'])
            && $_GET['completed'] === '1'
        ): ?>

            <div class="alert alert-success">
                Hoàn thành đơn hàng thành công!
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['cancelled'])
            && $_GET['cancelled'] === '1'
        ): ?>

            <div class="alert alert-success">
                Chuyển đơn sang Không hoàn thành thành công!
            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>
                    Danh sách đơn hàng
                </h1>

                <p>
                    Đơn hàng / Danh sách
                </p>

            </div>


            <div class="detail-header-actions">

                <a
                    href="/quanlyquanao/orders/history.php"
                    class="btn btn-light"
                >
                    📜 Lịch sử đơn hàng
                </a>

                <?php if (
                    hasPermission(
                        'order.create'
                    )
                ): ?>

                    <a
                        href="/quanlyquanao/orders/create.php"
                        class="btn btn-primary"
                    >
                        + Tạo đơn hàng
                    </a>

                <?php endif; ?>

            </div>

        </div>


        <!-- FILTER -->

        <div class="card product-filter">

            <form
                method="GET"
                class="order-filter-form"
            >

                <input
                    type="text"
                    name="keyword"
                    placeholder="Tìm mã đơn, khách hàng, nhân viên..."
                    value="<?= htmlspecialchars(
                        $keyword
                    ) ?>"
                >


                <select name="status">

                    <option value="">
                        Tất cả trạng thái
                    </option>

                    <option
                        value="PENDING"
                        <?= $status === 'PENDING'
                            ? 'selected'
                            : '' ?>
                    >
                        Chưa hoàn thành
                    </option>

                    <option
                        value="COMPLETED"
                        <?= $status === 'COMPLETED'
                            ? 'selected'
                            : '' ?>
                    >
                        Hoàn thành
                    </option>

                    <option
                        value="CANCELLED"
                        <?= $status === 'CANCELLED'
                            ? 'selected'
                            : '' ?>
                    >
                        Không hoàn thành
                    </option>

                </select>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tìm kiếm
                </button>


                <a
                    href="/quanlyquanao/orders/index.php"
                    class="btn btn-light"
                >
                    Đặt lại
                </a>

            </form>

        </div>


        <!-- TABLE -->

        <div class="card">

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Nhân viên</th>
                            <th>Ngày tạo</th>
                            <th>Tổng tiền</th>
                            <th>Tiền cọc</th>
                            <th>Còn lại</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>

                    </thead>


                    <tbody>

                    <?php if (
                        $apiError === ''
                        && empty($orders)
                    ): ?>

                        <tr>

                            <td
                                colspan="10"
                                class="empty-data"
                            >
                                Chưa có đơn hàng nào.
                            </td>

                        </tr>


                    <?php elseif (
                        $apiError === ''
                    ): ?>


                        <?php foreach (
                            $orders
                            as $index => $order
                        ): ?>

                            <?php

                            $orderStatus =
                                $order['status']
                                ?? '';

                            $totalAmount =
                                (float)(
                                    $order['totalAmount']
                                    ?? 0
                                );

                            $depositAmount =
                                (float)(
                                    $order['depositAmount']
                                    ?? 0
                                );

                            $remaining =
                                $orderStatus === 'PENDING'
                                    ? max(
                                        0,
                                        $totalAmount
                                        - $depositAmount
                                    )
                                    : 0;

                            ?>

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
                                        !empty(
                                            $order['customerName']
                                        )
                                            ? $order['customerName']
                                            : 'Khách vãng lai'
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $order['employeeName']
                                        ?? ''
                                    ) ?>

                                </td>


                                <td>

                                    <?php

                                    $createdAt =
                                        $order['createdAt']
                                        ?? '';

                                    ?>

                                    <?= $createdAt !== ''
                                        ? date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $createdAt
                                            )
                                        )
                                        : '—' ?>

                                </td>


                                <td>

                                    <strong>
                                        <?= number_format(
                                            $totalAmount,
                                            0,
                                            ',',
                                            '.'
                                        ) ?> đ
                                    </strong>

                                </td>


                                <td>

                                    <?= number_format(
                                        $depositAmount,
                                        0,
                                        ',',
                                        '.'
                                    ) ?> đ

                                </td>


                                <td>

                                    <strong>
                                        <?= number_format(
                                            $remaining,
                                            0,
                                            ',',
                                            '.'
                                        ) ?> đ
                                    </strong>

                                </td>


                                <td>

                                    <?php if (
                                        $orderStatus
                                        === 'COMPLETED'
                                    ): ?>

                                        <span class="status status-success">
                                            Hoàn thành
                                        </span>


                                    <?php elseif (
                                        $orderStatus
                                        === 'CANCELLED'
                                    ): ?>

                                        <span class="status status-danger">
                                            Không hoàn thành
                                        </span>


                                    <?php else: ?>

                                        <span class="status status-warning">
                                            Chưa hoàn thành
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <div class="table-actions">

                                        <a
                                            href="/quanlyquanao/orders/view.php?id=<?= (int)(
                                                $order['id']
                                                ?? 0
                                            ) ?>"
                                            class="action-btn"
                                            title="Xem đơn hàng"
                                        >
                                            👁
                                        </a>

                                        <a
                                            href="/quanlyquanao/orders/print.php?id=<?= (int)(
                                                $order['id']
                                                ?? 0
                                            ) ?>"
                                            class="action-btn"
                                            title="In hóa đơn"
                                            target="_blank"
                                        >
                                            🖨
                                        </a>

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
