<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('inventory.read');

$pageTitle = 'Lịch sử kho';
$currentPage = 'inventory';

$keyword =
    trim(
        $_GET['keyword']
        ?? ''
    );

$type =
    $_GET['type']
    ?? '';

$variantId =
    (int)(
        $_GET['variant_id']
        ?? 0
    );

$apiBaseUrl =
    'http://localhost:5162/api';


// =========================================
// HÀM GỌI API
// =========================================

function getInventoryHistoryApi(
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

if ($type !== '') {
    $queryParams['type'] =
        $type;
}

if ($variantId > 0) {
    $queryParams['variantId'] =
        $variantId;
}


$apiUrl =
    $apiBaseUrl
    . '/inventory/history';

if (!empty($queryParams)) {
    $apiUrl .=
        '?'
        . http_build_query(
            $queryParams
        );
}


// =========================================
// LOAD LỊCH SỬ + THỐNG KÊ
// =========================================

$transactions = [];

$stats = [
    'total_transactions' => 0,
    'total_import' => 0,
    'total_sale' => 0,
    'total_adjustment' => 0
];

$apiError = '';

$result =
    getInventoryHistoryApi(
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
            'total_transactions' =>
                (int)(
                    $apiStats['totalTransactions']
                    ?? 0
                ),

            'total_import' =>
                (int)(
                    $apiStats['totalImport']
                    ?? 0
                ),

            'total_sale' =>
                (int)(
                    $apiStats['totalSale']
                    ?? 0
                ),

            'total_adjustment' =>
                (int)(
                    $apiStats['totalAdjustment']
                    ?? 0
                )
        ];


        foreach (
            $data['transactions']
                ?? []
            as $transaction
        ) {

            $transactions[] = [
                'id' =>
                    $transaction['id']
                    ?? 0,

                'type' =>
                    $transaction['type']
                    ?? '',

                'quantity' =>
                    $transaction['quantity']
                    ?? 0,

                'stock_before' =>
                    $transaction['stockBefore']
                    ?? 0,

                'stock_after' =>
                    $transaction['stockAfter']
                    ?? 0,

                'reason' =>
                    $transaction['reason']
                    ?? '',

                'created_at' =>
                    $transaction['createdAt']
                    ?? '',

                'variant_id' =>
                    $transaction['variantId']
                    ?? 0,

                'sku' =>
                    $transaction['sku']
                    ?? '',

                'product_code' =>
                    $transaction['productCode']
                    ?? '',

                'product_name' =>
                    $transaction['productName']
                    ?? '',

                'size_name' =>
                    $transaction['sizeName']
                    ?? '',

                'color_name' =>
                    $transaction['colorName']
                    ?? '',

                'color_code' =>
                    $transaction['colorCode']
                    ?? null,

                'employee_name' =>
                    $transaction['employeeName']
                    ?? '',

                'order_id' =>
                    $transaction['orderId']
                    ?? null,

                'order_code' =>
                    $transaction['orderCode']
                    ?? null
            ];
        }

    } else {

        $apiError =
            $data['message']
            ?? (
                'Không thể tải lịch sử kho. HTTP Status: '
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

        <div class="product-page-header">

            <div>

                <h1>Lịch sử kho</h1>

                <p>
                    Kho hàng / Lịch sử giao dịch
                </p>

            </div>


            <a
                href="/quanlyquanao/inventory/index.php"
                class="btn btn-light"
            >
                ← Quay lại kho
            </a>

        </div>


        <!-- =========================
             THỐNG KÊ
        ========================== -->

        <div class="inventory-stats">

            <div class="stat-card">

                <div class="stat-icon">
                    📋
                </div>

                <div class="stat-info">

                    <span>Tổng giao dịch</span>

                    <h3>
                        <?= (int)$stats[
                            'total_transactions'
                        ] ?>
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    📥
                </div>

                <div class="stat-info">

                    <span>Đã nhập</span>

                    <h3>
                        <?= (int)$stats[
                            'total_import'
                        ] ?>
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    🛒
                </div>

                <div class="stat-info">

                    <span>Đã bán</span>

                    <h3>
                        <?= (int)$stats[
                            'total_sale'
                        ] ?>
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    🔧
                </div>

                <div class="stat-info">

                    <span>Lần điều chỉnh</span>

                    <h3>
                        <?= (int)$stats[
                            'total_adjustment'
                        ] ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- =========================
             FILTER
        ========================== -->

        <div class="card inventory-filter">

            <form
                method="GET"
                class="inventory-filter-form"
            >

                <?php if ($variantId > 0): ?>

                    <input
                        type="hidden"
                        name="variant_id"
                        value="<?= $variantId ?>"
                    >

                <?php endif; ?>


                <input
                    type="text"
                    name="keyword"
                    placeholder="Tìm mã SP, tên, SKU, mã đơn, nhân viên..."
                    value="<?= htmlspecialchars(
                        $keyword
                    ) ?>"
                >


                <select name="type">

                    <option value="">
                        Tất cả loại
                    </option>


                    <option
                        value="SALE"
                        <?= $type === 'SALE'
                            ? 'selected'
                            : '' ?>
                    >
                        Bán hàng
                    </option>


                    <option
                        value="IMPORT"
                        <?= $type === 'IMPORT'
                            ? 'selected'
                            : '' ?>
                    >
                        Nhập kho
                    </option>


                    <option
                        value="ADJUSTMENT"
                        <?= $type === 'ADJUSTMENT'
                            ? 'selected'
                            : '' ?>
                    >
                        Điều chỉnh
                    </option>

                </select>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tìm kiếm
                </button>


                <a
                    href="<?= $variantId > 0
                        ? '/quanlyquanao/inventory/history.php?variant_id=' . $variantId
                        : '/quanlyquanao/inventory/history.php'
                    ?>"
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
                            <th>Thời gian</th>
                            <th>Sản phẩm</th>
                            <th>SKU</th>
                            <th>Loại</th>
                            <th>Số lượng</th>
                            <th>Tồn trước</th>
                            <th>Tồn sau</th>
                            <th>Người thực hiện</th>
                            <th>Đơn hàng</th>
                            <th>Lý do</th>
                        </tr>

                    </thead>


                    <tbody>

                    <?php if (
                        empty($transactions)
                    ): ?>

                        <tr>

                            <td
                                colspan="11"
                                class="empty-data"
                            >
                                Chưa có giao dịch kho.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach (
                            $transactions
                            as $index => $transaction
                        ): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>


                                <td>

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            $transaction[
                                                'created_at'
                                            ]
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $transaction[
                                                'product_code'
                                            ]
                                        ) ?>
                                    </strong>

                                    <br>

                                    <small>
                                        <?= htmlspecialchars(
                                            $transaction[
                                                'product_name'
                                            ]
                                        ) ?>
                                    </small>

                                    <br>

                                    <small class="text-muted">

                                        Size:
                                        <?= htmlspecialchars(
                                            $transaction[
                                                'size_name'
                                            ]
                                        ) ?>

                                        |

                                        Màu:
                                        <?= htmlspecialchars(
                                            $transaction[
                                                'color_name'
                                            ]
                                        ) ?>

                                    </small>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $transaction['sku']
                                    ) ?>

                                </td>


                                <td>

                                    <?php if (
                                        $transaction['type']
                                        === 'SALE'
                                    ): ?>

                                        <span
                                            class="inventory-type inventory-type-sale"
                                        >
                                            Bán hàng
                                        </span>


                                    <?php elseif (
                                        $transaction['type']
                                        === 'IMPORT'
                                    ): ?>

                                        <span
                                            class="inventory-type inventory-type-import"
                                        >
                                            Nhập kho
                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="inventory-type inventory-type-adjust"
                                        >
                                            Điều chỉnh
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php
                                    $quantity =
                                        (int)$transaction[
                                            'quantity'
                                        ];
                                    ?>


                                    <?php if (
                                        $transaction['type']
                                        === 'SALE'
                                    ): ?>

                                        <strong class="stock-minus">
                                            -<?= abs($quantity) ?>
                                        </strong>


                                    <?php elseif (
                                        $transaction['type']
                                        === 'IMPORT'
                                    ): ?>

                                        <strong class="stock-plus">
                                            +<?= abs($quantity) ?>
                                        </strong>


                                    <?php else: ?>

                                        <?php if (
                                            $quantity > 0
                                        ): ?>

                                            <strong class="stock-plus">
                                                +<?= $quantity ?>
                                            </strong>

                                        <?php else: ?>

                                            <strong class="stock-minus">
                                                <?= $quantity ?>
                                            </strong>

                                        <?php endif; ?>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?= (int)$transaction[
                                        'stock_before'
                                    ] ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= (int)$transaction[
                                            'stock_after'
                                        ] ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $transaction[
                                            'employee_name'
                                        ]
                                    ) ?>

                                </td>


                                <td>

                                    <?php if (
                                        !empty(
                                            $transaction[
                                                'order_id'
                                            ]
                                        )
                                    ): ?>

                                        <a
                                            href="/quanlyquanao/orders/view.php?id=<?= $transaction['order_id'] ?>"
                                            class="inventory-order-link"
                                        >

                                            <?= htmlspecialchars(
                                                $transaction[
                                                    'order_code'
                                                ]
                                            ) ?>

                                        </a>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $transaction[
                                            'reason'
                                        ]
                                        ?: '—'
                                    ) ?>

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