<?php

require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$pageTitle = 'Lịch sử đơn hàng';
$currentPage = 'orders';

$keyword =
    trim(
        $_GET['keyword']
        ?? ''
    );

$status =
    trim(
        $_GET['status']
        ?? ''
    );

$apiBaseUrl =
    'http://localhost:5162/api';


// =========================================
// HÀM GỌI API
// =========================================

function getOrderHistoryApi(
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
// TẠO QUERY API
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
    . '/orders/history';

if (!empty($queryParams)) {
    $apiUrl .=
        '?'
        . http_build_query(
            $queryParams
        );
}


// =========================================
// LOAD LỊCH SỬ
// =========================================

$histories = [];
$apiError = '';

$result =
    getOrderHistoryApi(
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

        foreach ($data as $history) {

            $histories[] = [
                'id' =>
                    $history['id']
                    ?? 0,

                'order_id' =>
                    $history['orderId']
                    ?? 0,

                'from_status' =>
                    $history['fromStatus']
                    ?? null,

                'to_status' =>
                    $history['toStatus']
                    ?? '',

                'reason' =>
                    $history['reason']
                    ?? '',

                'created_at' =>
                    $history['createdAt']
                    ?? '',

                'order_code' =>
                    $history['orderCode']
                    ?? '',

                'customer_name' =>
                    $history['customerName']
                    ?? null,

                'order_creator_name' =>
                    $history['orderCreatorName']
                    ?? '',

                'changed_by_name' =>
                    $history['changedByName']
                    ?? ''
            ];
        }

    } else {

        $apiError =
            $data['message']
            ?? (
                'Không thể tải lịch sử đơn hàng. HTTP Status: '
                . $result['httpCode']
            );
    }
}


function orderStatusName(?string $status): string
{
    return match ($status) {
        'PENDING' => 'Chưa hoàn thành',
        'COMPLETED' => 'Hoàn thành',
        'CANCELLED' => 'Không hoàn thành',
        null, '' => 'Tạo mới',
        default => $status
    };
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

        <div class="product-page-header">

            <div>

                <h1>Lịch sử tất cả đơn hàng</h1>

                <p>
                    Đơn hàng / Lịch sử
                </p>

            </div>

            <div class="detail-header-actions">

                <a
                    href="/quanlyquanao/orders/index.php"
                    class="btn btn-light"
                >
                    ← Quay lại danh sách
                </a>

            </div>

        </div>


        <div class="card product-filter">

            <form
                method="GET"
                class="order-filter-form"
            >

                <input
                    type="text"
                    name="keyword"
                    placeholder="Tìm mã đơn, khách hàng, nhân viên, lý do..."
                    value="<?= htmlspecialchars($keyword) ?>"
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
                    href="/quanlyquanao/orders/history.php"
                    class="btn btn-light"
                >
                    Đặt lại
                </a>

            </form>

        </div>


        <div
            class="card"
            style="margin-top:20px;"
        >

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Nhân viên tạo</th>
                            <th>Thời gian</th>
                            <th>Trạng thái trước</th>
                            <th>Trạng thái sau</th>
                            <th>Người thực hiện</th>
                            <th>Lý do / Ghi chú</th>
                            <th>Thao tác</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($histories)): ?>

                        <tr>

                            <td
                                colspan="10"
                                class="empty-data"
                            >
                                Chưa có lịch sử đơn hàng.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($histories as $index => $history): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $history['order_code']
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $history['customer_name']
                                        ?: 'Khách vãng lai'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $history['order_creator_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            $history['created_at']
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        orderStatusName(
                                            $history['from_status']
                                        )
                                    ) ?>
                                </td>

                                <td>

                                    <?php if (
                                        $history['to_status']
                                        === 'COMPLETED'
                                    ): ?>

                                        <span class="status status-success">
                                            Hoàn thành
                                        </span>

                                    <?php elseif (
                                        $history['to_status']
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
                                    <?= htmlspecialchars(
                                        $history['changed_by_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $history['reason']
                                        ?: '—'
                                    ) ?>
                                </td>

                                <td>

                                    <a
                                        href="/quanlyquanao/orders/view.php?id=<?= $history['order_id'] ?>"
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
