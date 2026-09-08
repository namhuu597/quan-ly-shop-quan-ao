<?php

require_once __DIR__ . '/../includes/auth.php';

// Đồng bộ múi giờ với Việt Nam để bộ lọc "Hôm nay",
// "7 ngày", "30 ngày", "Tháng này" không bị lệch sang ngày trước.
date_default_timezone_set('Asia/Ho_Chi_Minh');

requirePermission('report.read');

$pageTitle = 'Báo cáo';
$currentPage = 'reports';


// ==========================
// BỘ LỌC THỜI GIAN
// ==========================

$quick = $_GET['quick'] ?? '';

$fromDate =
    trim(
        $_GET['from_date']
        ?? ''
    );

$toDate =
    trim(
        $_GET['to_date']
        ?? ''
    );

$today =
    date('Y-m-d');


if ($quick === 'today') {

    $fromDate =
        $today;

    $toDate =
        $today;

} elseif ($quick === '7days') {

    $fromDate =
        date(
            'Y-m-d',
            strtotime('-6 days')
        );

    $toDate =
        $today;

} elseif ($quick === '30days') {

    $fromDate =
        date(
            'Y-m-d',
            strtotime('-29 days')
        );

    $toDate =
        $today;

} elseif ($quick === 'month') {

    $fromDate =
        date('Y-m-01');

    $toDate =
        $today;
}


// ==========================
// VALIDATE DATE
// ==========================

function validDate(string $date): bool
{
    if ($date === '') {
        return false;
    }

    $d =
        DateTime::createFromFormat(
            'Y-m-d',
            $date
        );

    return
        $d
        && $d->format('Y-m-d')
            === $date;
}


if (!validDate($fromDate)) {
    $fromDate = '';
}


if (!validDate($toDate)) {
    $toDate = '';
}


if (
    $fromDate !== ''
    && $toDate !== ''
    && $fromDate > $toDate
) {
    [
        $fromDate,
        $toDate
    ] = [
        $toDate,
        $fromDate
    ];
}


// Mặc định 7 ngày gần nhất.
if (
    $quick === ''
    && $fromDate === ''
    && $toDate === ''
) {
    $fromDate =
        date(
            'Y-m-d',
            strtotime('-6 days')
        );

    $toDate =
        $today;
}


// ==========================
// GỌI ASP.NET API
// ==========================

$apiUrl =
    'http://localhost:5162/api/reports/overview';


$query = [];

if ($fromDate !== '') {
    $query['fromDate'] =
        $fromDate;
}

if ($toDate !== '') {
    $query['toDate'] =
        $toDate;
}

if (!empty($query)) {
    $apiUrl .=
        '?'
        . http_build_query($query);
}


$apiError = '';

$summary = [
    'total_revenue' => 0,
    'sales_revenue' => 0,
    'forfeited_deposit_revenue' => 0,
    'pending_deposit' => 0,
    'refunded_deposit' => 0,
    'completed_orders' => 0,
    'pending_orders' => 0,
    'cancelled_orders' => 0
];

$totalSold = 0;
$totalCustomers = 0;
$topProducts = [];
$topCustomers = [];
$chartRows = [];

$chartLabels = [];
$chartRevenue = [];
$chartOrders = [];


$ch =
    curl_init();

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


if ($response === false) {

    $apiError =
        'Không thể kết nối tới ASP.NET Core API. '
        . $curlError;

} else {

    $data =
        json_decode(
            $response,
            true
        );


    if ($httpCode === 200 && is_array($data)) {

        $apiSummary =
            $data['summary']
            ?? [];


        $summary = [
            'total_revenue' =>
                (float)(
                    $apiSummary['totalRevenue']
                    ?? 0
                ),

            'sales_revenue' =>
                (float)(
                    $apiSummary['salesRevenue']
                    ?? 0
                ),

            'forfeited_deposit_revenue' =>
                (float)(
                    $apiSummary['forfeitedDepositRevenue']
                    ?? 0
                ),

            'pending_deposit' =>
                (float)(
                    $apiSummary['pendingDeposit']
                    ?? 0
                ),

            'refunded_deposit' =>
                (float)(
                    $apiSummary['refundedDeposit']
                    ?? 0
                ),

            'completed_orders' =>
                (int)(
                    $apiSummary['completedOrders']
                    ?? 0
                ),

            'pending_orders' =>
                (int)(
                    $apiSummary['pendingOrders']
                    ?? 0
                ),

            'cancelled_orders' =>
                (int)(
                    $apiSummary['cancelledOrders']
                    ?? 0
                )
        ];


        $totalSold =
            (int)(
                $data['totalSold']
                ?? 0
            );


        $totalCustomers =
            (int)(
                $data['totalCustomers']
                ?? 0
            );


        foreach (
            $data['topProducts']
            ?? []
            as $product
        ) {
            $topProducts[] = [
                'product_code' =>
                    $product['productCode']
                    ?? '',

                'product_name' =>
                    $product['productName']
                    ?? '',

                'total_quantity' =>
                    (int)(
                        $product['totalQuantity']
                        ?? 0
                    ),

                'total_revenue' =>
                    (float)(
                        $product['totalRevenue']
                        ?? 0
                    )
            ];
        }


        foreach (
            $data['topCustomers']
            ?? []
            as $customer
        ) {
            $topCustomers[] = [
                'customer_code' =>
                    $customer['customerCode']
                    ?? '',

                'full_name' =>
                    $customer['fullName']
                    ?? '',

                'total_orders' =>
                    (int)(
                        $customer['totalOrders']
                        ?? 0
                    ),

                'total_spent' =>
                    (float)(
                        $customer['totalSpent']
                        ?? 0
                    )
            ];
        }


        foreach (
            $data['chartRows']
            ?? []
            as $row
        ) {
            $saleDate =
                substr(
                    (string)(
                        $row['saleDate']
                        ?? ''
                    ),
                    0,
                    10
                );

            $chartRows[] = [
                'sale_date' =>
                    $saleDate,

                'total_orders' =>
                    (int)(
                        $row['totalOrders']
                        ?? 0
                    ),

                'revenue' =>
                    (float)(
                        $row['revenue']
                        ?? 0
                    )
            ];
        }


        foreach ($chartRows as $row) {

            $chartLabels[] =
                date(
                    'd/m',
                    strtotime(
                        $row['sale_date']
                    )
                );

            $chartRevenue[] =
                (float)$row['revenue'];

            $chartOrders[] =
                (int)$row['total_orders'];
        }

    } elseif ($httpCode === 401) {

        $apiError =
            'Phiên đăng nhập đã hết hạn hoặc JWT không hợp lệ. '
            . 'Vui lòng đăng xuất và đăng nhập lại.';

    } elseif ($httpCode === 403) {

        $apiError =
            $data['message']
            ?? 'Bạn không có quyền xem báo cáo.';

    } else {

        $apiError =
            $data['message']
            ?? (
                'Không thể tải báo cáo. HTTP Status: '
                . $httpCode
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

                <h1>Báo cáo</h1>

                <p>
                    Báo cáo / Tổng quan
                </p>

            </div>

        </div>


        <!-- =========================
             FILTER
        ========================== -->

        <div class="card report-filter-card">

            <form
                method="GET"
                class="report-filter-form"
            >

                <div class="form-group">

                    <label>Từ ngày</label>

                    <input
                        type="date"
                        name="from_date"
                        value="<?= htmlspecialchars($fromDate) ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Đến ngày</label>

                    <input
                        type="date"
                        name="to_date"
                        value="<?= htmlspecialchars($toDate) ?>"
                    >

                </div>


                <div class="report-filter-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Lọc
                    </button>

                    <a
                        href="/quanlyquanao/reports/index.php"
                        class="btn btn-light"
                    >
                        Đặt lại
                    </a>

                </div>

            </form>


            <div class="report-quick-filter">

                <a
                    href="?quick=today"
                    class="report-quick-btn <?= $quick === 'today' ? 'active' : '' ?>"
                >
                    Hôm nay
                </a>

                <a
                    href="?quick=7days"
                    class="report-quick-btn <?= $quick === '7days' ? 'active' : '' ?>"
                >
                    7 ngày
                </a>

                <a
                    href="?quick=30days"
                    class="report-quick-btn <?= $quick === '30days' ? 'active' : '' ?>"
                >
                    30 ngày
                </a>

                <a
                    href="?quick=month"
                    class="report-quick-btn <?= $quick === 'month' ? 'active' : '' ?>"
                >
                    Tháng này
                </a>

            </div>

        </div>


        <!-- =========================
             CARDS
        ========================== -->

        <div class="report-stats">


            <div class="stat-card">

                <div class="stat-icon">
                    💰
                </div>

                <div class="stat-info">

                    <span>Tổng doanh thu</span>

                    <h3>
                        <?= number_format(
                            $summary['total_revenue'],
                            0,
                            ',',
                            '.'
                        ) ?> đ
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    🛍
                </div>

                <div class="stat-info">

                    <span>Doanh thu bán hàng</span>

                    <h3>
                        <?= number_format(
                            $summary['sales_revenue'],
                            0,
                            ',',
                            '.'
                        ) ?> đ
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    💵
                </div>

                <div class="stat-info">

                    <span>Tiền cọc khách mất</span>

                    <h3>
                        <?= number_format(
                            $summary['forfeited_deposit_revenue'],
                            0,
                            ',',
                            '.'
                        ) ?> đ
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ⏳
                </div>

                <div class="stat-info">

                    <span>Tiền cọc đang treo</span>

                    <h3>
                        <?= number_format(
                            $summary['pending_deposit'],
                            0,
                            ',',
                            '.'
                        ) ?> đ
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ↩
                </div>

                <div class="stat-info">

                    <span>Tiền cọc đã hoàn</span>

                    <h3>
                        <?= number_format(
                            $summary['refunded_deposit'],
                            0,
                            ',',
                            '.'
                        ) ?> đ
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    🧾
                </div>

                <div class="stat-info">

                    <span>Đơn hoàn thành</span>

                    <h3>
                        <?= (int)$summary['completed_orders'] ?>
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    👕
                </div>

                <div class="stat-info">

                    <span>Sản phẩm đã bán</span>

                    <h3>
                        <?= $totalSold ?>
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    👥
                </div>

                <div class="stat-info">

                    <span>Khách phát sinh đơn</span>

                    <h3>
                        <?= $totalCustomers ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- =========================
             BIỂU ĐỒ
        ========================== -->

        <div class="card report-chart-card">

            <div class="section-title">

                <div>

                    <h3>
                        Doanh thu theo ngày
                    </h3>

                    <p>
                        <?= $quick !== '' || isset($_GET['from_date']) || isset($_GET['to_date'])
                            ? 'Theo khoảng thời gian đã chọn'
                            : '7 ngày gần nhất'
                        ?>
                    </p>

                </div>

            </div>


            <div class="report-chart-wrap">

                <canvas
                    id="revenueChart"
                ></canvas>

            </div>

        </div>


        <!-- =========================
             BẢNG DOANH THU
        ========================== -->

        <div
            class="card"
            style="margin-top:20px;"
        >

            <h3>
                Chi tiết doanh thu
            </h3>


            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Ngày</th>
                            <th>Đơn hoàn thành</th>
                            <th>Doanh thu</th>
                        </tr>

                    </thead>


                    <tbody>

                    <?php if (
                        empty($chartRows)
                    ): ?>

                        <tr>

                            <td
                                colspan="3"
                                class="empty-data"
                            >
                                Chưa có dữ liệu doanh thu.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach (
                            $chartRows as $row
                        ): ?>

                            <tr>

                                <td>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime(
                                            $row['sale_date']
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <?= (int)$row[
                                        'total_orders'
                                    ] ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= number_format(
                                            $row['revenue'],
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

        </div>


        <!-- =========================
             TOP
        ========================== -->

        <div class="report-grid">


            <div class="card">

                <h3>
                    Top sản phẩm bán chạy
                </h3>

                <div class="table-responsive">

                    <table class="table">

                        <thead>

                            <tr>
                                <th>Sản phẩm</th>
                                <th>Đã bán</th>
                                <th>Doanh thu</th>
                            </tr>

                        </thead>


                        <tbody>

                        <?php if (
                            empty($topProducts)
                        ): ?>

                            <tr>

                                <td
                                    colspan="3"
                                    class="empty-data"
                                >
                                    Chưa có dữ liệu.
                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach (
                                $topProducts as $product
                            ): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $product[
                                                    'product_code'
                                                ]
                                            ) ?>
                                        </strong>

                                        <br>

                                        <?= htmlspecialchars(
                                            $product[
                                                'product_name'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= (int)$product[
                                            'total_quantity'
                                        ] ?>

                                    </td>


                                    <td>

                                        <?= number_format(
                                            $product[
                                                'total_revenue'
                                            ],
                                            0,
                                            ',',
                                            '.'
                                        ) ?> đ

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


            <div class="card">

                <h3>
                    Top khách hàng
                </h3>

                <div class="table-responsive">

                    <table class="table">

                        <thead>

                            <tr>
                                <th>Khách hàng</th>
                                <th>Số đơn</th>
                                <th>Chi tiêu</th>
                            </tr>

                        </thead>


                        <tbody>

                        <?php if (
                            empty($topCustomers)
                        ): ?>

                            <tr>

                                <td
                                    colspan="3"
                                    class="empty-data"
                                >
                                    Chưa có dữ liệu.
                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach (
                                $topCustomers as $customer
                            ): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $customer[
                                                    'customer_code'
                                                ]
                                            ) ?>
                                        </strong>

                                        <br>

                                        <?= htmlspecialchars(
                                            $customer[
                                                'full_name'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= (int)$customer[
                                            'total_orders'
                                        ] ?>

                                    </td>


                                    <td>

                                        <?= number_format(
                                            $customer[
                                                'total_spent'
                                            ],
                                            0,
                                            ',',
                                            '.'
                                        ) ?> đ

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </section>

</main>


<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
window.reportChartData = {
    labels: <?= json_encode(
        $chartLabels,
        JSON_UNESCAPED_UNICODE
    ) ?>,

    revenue: <?= json_encode(
        $chartRevenue
    ) ?>,

    orders: <?= json_encode(
        $chartOrders
    ) ?>
};
</script>


<?php
require_once __DIR__ . '/../includes/footer.php';
?>
