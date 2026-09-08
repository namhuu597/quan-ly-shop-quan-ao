<?php

require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$pageTitle = 'Chi tiết đơn hàng';
$currentPage = 'orders';

$id =
    (int)(
        $_GET['id']
        ?? 0
    );

if ($id <= 0) {
    die('Đơn hàng không hợp lệ.');
}

$apiBaseUrl =
    'http://localhost:5162/api';

$actionError =
    '';


// =========================================
// HÀM API
// =========================================

function callOrderApi(
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
// LOAD CHI TIẾT ĐƠN
// =========================================

function loadOrderDetail(
    string $apiBaseUrl,
    int $id
): array {
    $url =
        $apiBaseUrl
        . '/orders/'
        . $id;


    $result =
        callOrderApi(
            $url
        );


    if (
        $result['response']
        === false
    ) {
        die(
            'Không thể kết nối tới ASP.NET Core API. '
            . $result['curlError']
        );
    }


    $data =
        json_decode(
            $result['response'],
            true
        );


    if ($result['httpCode'] === 404) {
        die(
            $data['message']
            ?? 'Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn này.'
        );
    }


    if ($result['httpCode'] !== 200) {
        die(
            $data['message']
            ?? (
                'Không thể tải chi tiết đơn hàng. HTTP Status: '
                . $result['httpCode']
            )
        );
    }


    if (!is_array($data)) {
        die(
            'Dữ liệu đơn hàng từ API không hợp lệ.'
        );
    }


    return $data;
}


$data =
    loadOrderDetail(
        $apiBaseUrl,
        $id
    );

$order =
    $data['order']
    ?? [];

$orderDetails =
    $data['details']
    ?? [];


// =========================================
// XỬ LÝ TRẠNG THÁI
// =========================================

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
    && ($order['status'] ?? '')
        === 'PENDING'
) {

    verifyCsrfToken();

    $action =
        $_POST['action']
        ?? '';

    $reason =
        trim(
            $_POST['reason']
            ?? ''
        );


    if ($action === 'complete') {

        $payload = [
            'reason' =>
                $reason !== ''
                    ? $reason
                    : null
        ];


        $result =
            callOrderApi(
                $apiBaseUrl
                . '/orders/'
                . $id
                . '/complete',
                'PATCH',
                $payload
            );


        $responseData =
            json_decode(
                $result['response']
                ?: '',
                true
            );


        if (
            $result['response']
            === false
        ) {

            $actionError =
                'Không thể kết nối tới ASP.NET Core API. '
                . $result['curlError'];

        } elseif (
            $result['httpCode']
            === 200
        ) {

            header(
                'Location: /quanlyquanao/orders/view.php?id='
                . $id
                . '&completed=1'
            );

            exit;

        } else {

            $actionError =
                $responseData['message']
                ?? (
                    'Không thể hoàn thành đơn hàng. HTTP Status: '
                    . $result['httpCode']
                );
        }


    } elseif ($action === 'cancel') {

        $cancelledBy =
            $_POST['cancelled_by']
            ?? '';


        $payload = [
            'cancelledBy' =>
                $cancelledBy,

            'reason' =>
                $reason
        ];


        $result =
            callOrderApi(
                $apiBaseUrl
                . '/orders/'
                . $id
                . '/cancel',
                'PATCH',
                $payload
            );


        $responseData =
            json_decode(
                $result['response']
                ?: '',
                true
            );


        if (
            $result['response']
            === false
        ) {

            $actionError =
                'Không thể kết nối tới ASP.NET Core API. '
                . $result['curlError'];

        } elseif (
            $result['httpCode']
            === 200
        ) {

            header(
                'Location: /quanlyquanao/orders/view.php?id='
                . $id
                . '&cancelled=1'
            );

            exit;

        } else {

            $actionError =
                $responseData['message']
                ?? (
                    'Không thể chuyển đơn sang Không hoàn thành. HTTP Status: '
                    . $result['httpCode']
                );
        }


    } else {

        $actionError =
            'Thao tác không hợp lệ.';
    }


    // Reload khi có lỗi.
    if ($actionError !== '') {
        $data =
            loadOrderDetail(
                $apiBaseUrl,
                $id
            );

        $order =
            $data['order']
            ?? [];

        $orderDetails =
            $data['details']
            ?? [];
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


        <?php if (
            $actionError !== ''
        ): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars(
                    $actionError
                ) ?>
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['completed'])
            && $_GET['completed'] === '1'
        ): ?>

            <div class="alert alert-success">
                Đơn hàng đã được hoàn thành và ghi nhận doanh thu.
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['cancelled'])
            && $_GET['cancelled'] === '1'
        ): ?>

            <div class="alert alert-success">
                Đơn hàng đã chuyển sang Không hoàn thành và tồn kho đã được hoàn lại.
            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>
                    Chi tiết đơn hàng
                </h1>

                <p>
                    Đơn hàng /
                    <?= htmlspecialchars(
                        $order['orderCode']
                        ?? ''
                    ) ?>
                </p>

            </div>


            <div class="detail-header-actions">

                <a
                    href="/quanlyquanao/orders/index.php"
                    class="btn btn-light"
                >
                    ← Quay lại
                </a>


                <a
                    href="/quanlyquanao/orders/history.php"
                    class="btn btn-light"
                >
                    📜 Lịch sử
                </a>


                <?php if (
                    ($order['status'] ?? '')
                    === 'COMPLETED'
                ): ?>

                    <a
                        href="/quanlyquanao/orders/print.php?id=<?= $id ?>"
                        class="btn btn-primary"
                        target="_blank"
                    >
                        🖨 In hóa đơn
                    </a>

                <?php endif; ?>

            </div>

        </div>


        <!-- =========================
             THÔNG TIN ĐƠN
        ========================== -->

        <div class="order-detail-grid">


            <div class="card">

                <h3>
                    Thông tin đơn hàng
                </h3>

                <div class="detail-list">


                    <div class="detail-item">

                        <span>
                            Mã đơn hàng
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $order['orderCode']
                                ?? ''
                            ) ?>
                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Nhân viên tạo
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $order['employeeName']
                                ?? ''
                            ) ?>
                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Ngày tạo
                        </span>

                        <strong>

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

                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Trạng thái
                        </span>

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

                    </div>


                    <?php if (
                        !empty(
                            $order['completedAt']
                        )
                    ): ?>

                        <div class="detail-item">

                            <span>
                                Hoàn thành lúc
                            </span>

                            <strong>
                                <?= date(
                                    'd/m/Y H:i',
                                    strtotime(
                                        $order['completedAt']
                                    )
                                ) ?>
                            </strong>

                        </div>

                    <?php endif; ?>


                    <div class="detail-item">

                        <span>
                            Tổng giá trị đơn
                        </span>

                        <strong class="order-total-highlight">
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

                    </div>


                    <div class="detail-item">

                        <span>
                            Tiền cọc
                        </span>

                        <strong>
                            <?= number_format(
                                (float)(
                                    $order['depositAmount']
                                    ?? 0
                                ),
                                0,
                                ',',
                                '.'
                            ) ?> đ
                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Còn phải thanh toán
                        </span>

                        <strong>

                            <?php

                            $remaining =
                                ($order['status'] ?? '')
                                === 'PENDING'
                                    ? max(
                                        0,
                                        (float)(
                                            $order['totalAmount']
                                            ?? 0
                                        )
                                        - (float)(
                                            $order['depositAmount']
                                            ?? 0
                                        )
                                    )
                                    : 0;

                            ?>

                            <?= number_format(
                                $remaining,
                                0,
                                ',',
                                '.'
                            ) ?> đ

                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Trạng thái tiền cọc
                        </span>

                        <strong>

                            <?php

                            $depositStatusText =
                                match (
                                    $order['depositStatus']
                                    ?? 'PENDING'
                                ) {
                                    'APPLIED' =>
                                        'Đã áp dụng vào thanh toán',

                                    'FORFEITED' =>
                                        'Khách mất cọc',

                                    'REFUNDED' =>
                                        'Đã hoàn cọc',

                                    default =>
                                        'Đang treo'
                                };

                            echo htmlspecialchars(
                                $depositStatusText
                            );

                            ?>

                        </strong>

                    </div>


                    <?php if (
                        ($order['status'] ?? '')
                        === 'CANCELLED'
                    ): ?>

                        <div class="detail-item">

                            <span>
                                Bên không hoàn thành
                            </span>

                            <strong>

                                <?= (
                                    ($order['cancelledBy'] ?? '')
                                    === 'CUSTOMER'
                                )
                                    ? 'Khách hàng'
                                    : 'Shop' ?>

                            </strong>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =========================
                 KHÁCH HÀNG
            ========================== -->

            <div class="card">

                <h3>
                    Thông tin khách hàng
                </h3>

                <div class="detail-list">


                    <?php if (
                        empty(
                            $order['customerId']
                        )
                    ): ?>

                        <div class="detail-item">

                            <span>
                                Khách hàng
                            </span>

                            <strong>
                                Khách vãng lai
                            </strong>

                        </div>


                    <?php else: ?>


                        <div class="detail-item">

                            <span>
                                Mã khách hàng
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $order['customerCode']
                                    ?? ''
                                ) ?>
                            </strong>

                        </div>


                        <div class="detail-item">

                            <span>
                                Họ tên
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $order['customerName']
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
                                        $order['customerPhone']
                                    )
                                        ? $order['customerPhone']
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
                                        $order['customerEmail']
                                    )
                                        ? $order['customerEmail']
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
                                        $order['customerAddress']
                                    )
                                        ? $order['customerAddress']
                                        : '—'
                                ) ?>
                            </strong>

                        </div>


                    <?php endif; ?>

                </div>

            </div>

        </div>


        <!-- =========================
             DANH SÁCH SẢN PHẨM
        ========================== -->

        <div
            class="card"
            style="margin-top:20px;"
        >

            <h3>
                Sản phẩm trong đơn
            </h3>

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Mã SP</th>
                            <th>Sản phẩm</th>
                            <th>SKU</th>
                            <th>Size</th>
                            <th>Màu</th>
                            <th>Số lượng</th>
                            <th>Đơn giá</th>
                            <th>Thành tiền</th>
                        </tr>

                    </thead>


                    <tbody>

                    <?php if (
                        empty($orderDetails)
                    ): ?>

                        <tr>

                            <td
                                colspan="9"
                                class="empty-data"
                            >
                                Đơn hàng chưa có sản phẩm.
                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $orderDetails
                            as $index => $item
                        ): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $item['productCode']
                                            ?? ''
                                        ) ?>
                                    </strong>

                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $item['productName']
                                        ?? ''
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $item['sku']
                                        ?? ''
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $item['sizeName']
                                        ?? ''
                                    ) ?>
                                </td>


                                <td>

                                    <div class="order-color-cell">

                                        <span
                                            class="order-color-dot"
                                            style="background:
                                                <?= htmlspecialchars(
                                                    !empty(
                                                        $item['colorCode']
                                                    )
                                                        ? $item['colorCode']
                                                        : '#cccccc'
                                                ) ?>;"
                                        ></span>

                                        <?= htmlspecialchars(
                                            $item['colorName']
                                            ?? ''
                                        ) ?>

                                    </div>

                                </td>


                                <td>
                                    <?= (int)(
                                        $item['quantity']
                                        ?? 0
                                    ) ?>
                                </td>


                                <td>
                                    <?= number_format(
                                        (float)(
                                            $item['unitPrice']
                                            ?? 0
                                        ),
                                        0,
                                        ',',
                                        '.'
                                    ) ?> đ
                                </td>


                                <td>

                                    <strong>
                                        <?= number_format(
                                            (float)(
                                                $item['subtotal']
                                                ?? 0
                                            ),
                                            0,
                                            ',',
                                            '.'
                                        ) ?> đ
                                    </strong>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                    <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <div class="order-detail-total">

                <span>
                    <?= (
                        ($order['status'] ?? '')
                        === 'PENDING'
                    )
                        ? 'Còn phải thanh toán'
                        : 'Tổng giá trị đơn'
                    ?>
                </span>

                <strong>

                    <?php

                    $bottomTotal =
                        (
                            ($order['status'] ?? '')
                            === 'PENDING'
                        )
                            ? max(
                                0,
                                (float)(
                                    $order['totalAmount']
                                    ?? 0
                                )
                                - (float)(
                                    $order['depositAmount']
                                    ?? 0
                                )
                            )
                            : (float)(
                                $order['totalAmount']
                                ?? 0
                            );

                    ?>

                    <?= number_format(
                        $bottomTotal,
                        0,
                        ',',
                        '.'
                    ) ?> đ

                </strong>

            </div>

        </div>


        <?php if (
            ($order['status'] ?? '')
            === 'PENDING'
        ): ?>

            <div
                class="card"
                style="margin-top:20px;"
            >

                <h3>
                    Xử lý đơn hàng
                </h3>


                <form
                    method="POST"
                    style="margin-top:18px;"
                >

                    <?= csrfField() ?>

                    <h4>
                        ✓ Hoàn thành đơn
                    </h4>

                    <input
                        type="hidden"
                        name="action"
                        value="complete"
                    >

                    <div class="form-group">

                        <label>
                            Ghi chú hoàn thành
                            <span class="text-muted">
                                (không bắt buộc)
                            </span>
                        </label>

                        <textarea
                            name="reason"
                            rows="3"
                            placeholder="Ví dụ: Khách đã thanh toán phần còn lại khi nhận hàng."
                        ></textarea>

                    </div>

                    <div class="form-actions">

                        <button
                            type="submit"
                            class="btn btn-primary"
                            onclick="return confirm('Xác nhận hoàn thành đơn hàng?');"
                        >
                            Xác nhận hoàn thành
                        </button>

                    </div>

                </form>


                <form
                    method="POST"
                    style="margin-top:24px; padding-top:20px; border-top:1px solid #e5e7eb;"
                >

                    <?= csrfField() ?>

                    <h4>
                        ✕ Không hoàn thành
                    </h4>

                    <input
                        type="hidden"
                        name="action"
                        value="cancel"
                    >

                    <div class="form-group">

                        <label>
                            Bên không hoàn thành đơn
                            <span class="required">*</span>
                        </label>

                        <select
                            name="cancelled_by"
                            required
                        >

                            <option value="">
                                -- Chọn nguyên nhân --
                            </option>

                            <option value="CUSTOMER">
                                Khách hàng không lấy / hủy đơn
                            </option>

                            <option value="SHOP">
                                Shop không thể hoàn thành đơn
                            </option>

                        </select>

                    </div>


                    <div
                        class="form-group"
                        style="margin-top:14px;"
                    >

                        <label>
                            Lý do
                            <span class="required">*</span>
                        </label>

                        <textarea
                            name="reason"
                            rows="3"
                            required
                            placeholder="Nhập lý do không hoàn thành đơn..."
                        ></textarea>

                    </div>


                    <div
                        class="alert alert-warning"
                        style="margin-top:12px;"
                    >
                        <strong>Khách hàng:</strong>
                        hoàn tồn kho, khách mất cọc và tiền cọc được tính vào doanh thu.
                        <br>
                        <strong>Shop:</strong>
                        hoàn tồn kho, hoàn lại tiền cọc và không tính khoản cọc vào doanh thu.
                    </div>


                    <div class="form-actions">

                        <button
                            type="submit"
                            class="btn btn-primary"
                            onclick="return confirm('Xác nhận chuyển đơn sang Không hoàn thành?');"
                        >
                            Xác nhận
                        </button>

                    </div>

                </form>

            </div>

        <?php endif; ?>


        <!-- =========================
             GHI CHÚ
        ========================== -->

        <div
            class="card"
            style="margin-top:20px;"
        >

            <h3>
                Ghi chú
            </h3>

            <div class="order-note">

                <?php if (
                    !empty(
                        $order['note']
                    )
                ): ?>

                    <?= nl2br(
                        htmlspecialchars(
                            $order['note']
                        )
                    ) ?>

                <?php else: ?>

                    <span class="text-muted">
                        Không có ghi chú.
                    </span>

                <?php endif; ?>

            </div>

        </div>

    </section>

</main>


<?php
require_once __DIR__ . '/../includes/footer.php';
?>
