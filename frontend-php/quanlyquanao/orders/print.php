<?php

require_once __DIR__ . '/../includes/auth.php';

requireLogin();

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


// =========================================
// HÀM GỌI API
// =========================================

function getPrintOrderApi(
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


$apiUrl =
    $apiBaseUrl
    . '/orders/'
    . $id;


// =========================================
// LOAD ĐƠN HÀNG + CHI TIẾT
// =========================================

$result =
    getPrintOrderApi(
        $apiUrl
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
            'Không thể tải hóa đơn. HTTP Status: '
            . $result['httpCode']
        )
    );
}


if (!is_array($data)) {
    die(
        'Dữ liệu hóa đơn từ API không hợp lệ.'
    );
}


$order =
    $data['order']
    ?? [];

$orderDetails =
    $data['details']
    ?? [];


$createdMessage =
    isset($_GET['created'])
    && $_GET['created'] === '1';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Hóa đơn <?= htmlspecialchars($order['orderCode']) ?>
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            background: #f3f4f6;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
        }

        .invoice-wrapper {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }

        .invoice-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .action-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 10px 16px;
            border: 0;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            font-family: Arial, Helvetica, sans-serif;
        }

        .btn-light {
            background: #ffffff;
            color: #111827;
            border: 1px solid #d1d5db;
        }

        .btn-primary {
            background: #6d5dfc;
            color: #ffffff;
        }

        .success-message {
            margin-bottom: 18px;
            padding: 12px 16px;
            border-radius: 8px;
            background: #dcfce7;
            color: #166534;
            font-weight: 700;
        }

        .invoice {
            background: #ffffff;
            border-radius: 12px;
            padding: 36px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 24px;
            border-bottom: 2px solid #111827;
        }

        .shop-name {
            margin: 0 0 6px;
            font-size: 26px;
            font-weight: 800;
        }

        .shop-subtitle {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .invoice-title strong {
            font-size: 15px;
        }

        .invoice-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            margin-top: 26px;
        }

        .info-box h3 {
            margin: 0 0 12px;
            font-size: 16px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 6px 0;
            font-size: 14px;
        }

        .info-row span {
            color: #6b7280;
        }

        .info-row strong {
            text-align: right;
        }

        .invoice-table-wrap {
            margin-top: 28px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            font-size: 14px;
            vertical-align: top;
        }

        th {
            background: #f9fafb;
            font-size: 13px;
        }

        .text-right {
            text-align: right;
        }

        .total-box {
            display: flex;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .total-row {
            width: min(100%, 360px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            padding: 16px 0;
            border-top: 2px solid #111827;
            font-size: 18px;
        }

        .total-row strong {
            font-size: 22px;
        }

        .note-box {
            margin-top: 26px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .note-box h3 {
            margin: 0 0 8px;
            font-size: 15px;
        }

        .note-box p {
            margin: 0;
            color: #4b5563;
            line-height: 1.6;
            font-size: 14px;
        }

        .invoice-footer {
            margin-top: 34px;
            padding-top: 20px;
            border-top: 1px dashed #d1d5db;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
        }

        @media (max-width: 700px) {

            body {
                padding: 14px;
            }

            .invoice {
                padding: 22px 16px;
            }

            .invoice-header {
                flex-direction: column;
            }

            .invoice-title {
                text-align: left;
            }

            .invoice-info-grid {
                grid-template-columns: 1fr;
            }

            .invoice-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .action-group {
                width: 100%;
            }

            .action-group .btn {
                flex: 1;
            }
        }

        @media print {

            @page {
                size: A4;
                margin: 14mm;
            }

            body {
                padding: 0;
                background: #ffffff;
            }

            .invoice-wrapper {
                max-width: none;
            }

            .invoice-actions,
            .success-message {
                display: none !important;
            }

            .invoice {
                padding: 0;
                border-radius: 0;
                box-shadow: none;
            }

            th {
                background: #ffffff;
            }
        }
    </style>
</head>

<body>

<div class="invoice-wrapper">

    <div class="invoice-actions">

        <div class="action-group">

            <a
                href="/quanlyquanao/orders/index.php"
                class="btn btn-light"
            >
                ← Danh sách đơn
            </a>

            <a
                href="/quanlyquanao/orders/view.php?id=<?= $order['id'] ?>"
                class="btn btn-light"
            >
                👁 Xem chi tiết
            </a>

        </div>

        <button
            type="button"
            class="btn btn-primary"
            onclick="window.print()"
        >
            🖨 In hóa đơn
        </button>

    </div>


    <?php if ($createdMessage): ?>

        <div class="success-message">
            Tạo đơn hàng thành công. Bạn có thể in hóa đơn ngay.
        </div>

    <?php endif; ?>


    <div class="invoice">

        <div class="invoice-header">

            <div>

                <h2 class="shop-name">
                    GIA BẢO BOUTIQUE
                </h2>

                <p class="shop-subtitle">
                    Hệ thống quản lý shop quần áo Gia Bảo Boutique
                </p>

            </div>


            <div class="invoice-title">

                <h1>
                    HÓA ĐƠN BÁN HÀNG
                </h1>

                <strong>
                    <?= htmlspecialchars($order['orderCode']) ?>
                </strong>

            </div>

        </div>


        <div class="invoice-info-grid">

            <div class="info-box">

                <h3>Thông tin đơn hàng</h3>

                <div class="info-row">
                    <span>Mã đơn</span>
                    <strong>
                        <?= htmlspecialchars($order['orderCode']) ?>
                    </strong>
                </div>

                <div class="info-row">
                    <span>Ngày tạo</span>
                    <strong>
                        <?= date(
                            'd/m/Y H:i',
                            strtotime($order['createdAt'])
                        ) ?>
                    </strong>
                </div>

                <div class="info-row">
                    <span>Nhân viên</span>
                    <strong>
                        <?= htmlspecialchars($order['employeeName']) ?>
                    </strong>
                </div>

                <div class="info-row">
                    <span>Trạng thái</span>
                    <strong>
                        <?= $order['status'] === 'COMPLETED'
                            ? 'Hoàn thành'
                            : (
                                $order['status'] === 'CANCELLED'
                                    ? 'Đã hủy'
                                    : 'Đang tạo'
                            )
                        ?>
                    </strong>
                </div>

            </div>


            <div class="info-box">

                <h3>Thông tin khách hàng</h3>

                <?php if (empty($order['customerId'])): ?>

                    <div class="info-row">
                        <span>Khách hàng</span>
                        <strong>Khách vãng lai</strong>
                    </div>

                <?php else: ?>

                    <div class="info-row">
                        <span>Mã khách</span>
                        <strong>
                            <?= htmlspecialchars(
                                ($order['customerCode'] ?? '') ?: '—'
                            ) ?>
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>Họ tên</span>
                        <strong>
                            <?= htmlspecialchars(
                                ($order['customerName'] ?? '') ?: '—'
                            ) ?>
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>SĐT</span>
                        <strong>
                            <?= htmlspecialchars(
                                ($order['customerPhone'] ?? '') ?: '—'
                            ) ?>
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>Địa chỉ</span>
                        <strong>
                            <?= htmlspecialchars(
                                ($order['customerAddress'] ?? '') ?: '—'
                            ) ?>
                        </strong>
                    </div>

                <?php endif; ?>

            </div>

        </div>


        <div class="invoice-table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sản phẩm</th>
                        <th>Size</th>
                        <th>Màu</th>
                        <th>SKU</th>
                        <th class="text-right">SL</th>
                        <th class="text-right">Đơn giá</th>
                        <th class="text-right">Thành tiền</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($orderDetails)): ?>

                    <tr>
                        <td colspan="8">
                            Đơn hàng chưa có sản phẩm.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($orderDetails as $index => $item): ?>

                        <tr>

                            <td>
                                <?= $index + 1 ?>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars(
                                        $item['productName']
                                    ) ?>
                                </strong>

                                <br>

                                <small>
                                    <?= htmlspecialchars(
                                        $item['productCode']
                                    ) ?>
                                </small>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $item['sizeName']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $item['colorName']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $item['sku']
                                ) ?>
                            </td>

                            <td class="text-right">
                                <?= (int)$item['quantity'] ?>
                            </td>

                            <td class="text-right">
                                <?= number_format(
                                    $item['unitPrice'],
                                    0,
                                    ',',
                                    '.'
                                ) ?> đ
                            </td>

                            <td class="text-right">
                                <strong>
                                    <?= number_format(
                                        $item['subtotal'],
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


        <div class="total-box">

            <div class="total-row">

                <span>
                    Tổng thanh toán
                </span>

                <strong>
                    <?= number_format(
                        $order['totalAmount'],
                        0,
                        ',',
                        '.'
                    ) ?> đ
                </strong>

            </div>

        </div>


        <?php if (!empty($order['note'])): ?>

            <div class="note-box">

                <h3>Ghi chú</h3>

                <p>
                    <?= nl2br(
                        htmlspecialchars($order['note'])
                    ) ?>
                </p>

            </div>

        <?php endif; ?>


        <div class="invoice-footer">
            Cảm ơn quý khách đã mua hàng tại GIA BẢO BOUTIQUE!
        </div>

    </div>

</div>

</body>
</html>
