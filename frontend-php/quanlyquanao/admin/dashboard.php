<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$pageTitle = 'Tổng quan';
$currentPage = 'dashboard';

$canViewRevenue = hasRole('ADMIN') || hasRole('MANAGER');
$isEmployee = hasRole('EMPLOYEE') && !$canViewRevenue;
$currentUserId = (int)$_SESSION['user']['id'];


// ==========================
// TỔNG DOANH THU
// ==========================

$totalRevenue = 0;

if ($canViewRevenue) {

    $revenueStmt = $pdo->query("
        SELECT
            COALESCE(SUM(
                CASE
                    WHEN status = 'COMPLETED'
                        THEN total_amount

                    WHEN status = 'CANCELLED'
                        AND cancelled_by = 'CUSTOMER'
                        AND deposit_status = 'FORFEITED'
                        THEN deposit_amount

                    ELSE 0
                END
            ), 0)
        FROM orders
    ");

    $totalRevenue =
        (float)$revenueStmt->fetchColumn();}


// ==========================
// TỔNG ĐƠN HÀNG HOÀN THÀNH
// ==========================

if ($isEmployee) {

    $orderStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM orders
        WHERE status = 'COMPLETED'
        AND created_by = ?
    ");

    $orderStmt->execute([
        $currentUserId
    ]);

} else {

    $orderStmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE status = 'COMPLETED'
    ");
}

$totalOrders =
    (int)$orderStmt->fetchColumn();


// ==========================
// TỔNG KHÁCH HÀNG
// ==========================

$customerStmt = $pdo->query("
    SELECT COUNT(*)
    FROM customers
    WHERE is_active = 1
");

$totalCustomers = (int)$customerStmt->fetchColumn();


// ==========================
// TỔNG TỒN KHO
// ==========================

$stockStmt = $pdo->query("
    SELECT
        COALESCE(SUM(stock_quantity), 0)
    FROM product_variants
    WHERE is_active = 1
");

$totalStock = (int)$stockStmt->fetchColumn();


// ==========================
// ĐƠN HÀNG THEO TRẠNG THÁI
// EMPLOYEE chỉ xem đơn của chính mình
// ==========================

$orderStatus = [
    'COMPLETED' => 0,
    'PENDING' => 0,
    'CANCELLED' => 0
];

if ($isEmployee) {

    $statusStmt = $pdo->prepare("
        SELECT
            status,
            COUNT(*) AS total
        FROM orders
        WHERE created_by = ?
        GROUP BY status
    ");

    $statusStmt->execute([
        $currentUserId
    ]);

} else {

    $statusStmt = $pdo->query("
        SELECT
            status,
            COUNT(*) AS total
        FROM orders
        GROUP BY status
    ");
}

foreach (
    $statusStmt->fetchAll(PDO::FETCH_ASSOC)
    as $row
) {
    if (isset($orderStatus[$row['status']])) {
        $orderStatus[$row['status']] =
            (int)$row['total'];
    }
}


// ==========================
// ĐƠN HÀNG GẦN NHẤT
// EMPLOYEE chỉ xem đơn của chính mình
// ==========================

if ($isEmployee) {

    $recentOrderStmt = $pdo->prepare("
        SELECT
            o.id,
            o.order_code,
            o.status,
            o.created_at,
            c.full_name AS customer_name

        FROM orders o

        LEFT JOIN customers c
            ON c.id = o.customer_id

        WHERE o.created_by = ?

        ORDER BY o.created_at DESC

        LIMIT 5
    ");

    $recentOrderStmt->execute([
        $currentUserId
    ]);

} else {

    $recentOrderStmt = $pdo->query("
        SELECT
            o.id,
            o.order_code,
            o.total_amount,
            o.status,
            o.created_at,
            c.full_name AS customer_name

        FROM orders o

        LEFT JOIN customers c
            ON c.id = o.customer_id

        ORDER BY o.created_at DESC

        LIMIT 5
    ");
}

$recentOrders =
    $recentOrderStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================
// SẢN PHẨM SẮP HẾT
// ==========================

$lowStockStmt = $pdo->query("
    SELECT
        p.product_code,
        p.name AS product_name,
        pv.sku,
        pv.stock_quantity,
        s.name AS size_name,
        c.name AS color_name

    FROM product_variants pv

    JOIN products p
        ON p.id = pv.product_id

    JOIN sizes s
        ON s.id = pv.size_id

    JOIN colors c
        ON c.id = pv.color_id

    WHERE pv.is_active = 1
    AND p.status = 'ACTIVE'
    AND pv.stock_quantity <= 5

    ORDER BY pv.stock_quantity ASC, p.name ASC

    LIMIT 5
");

$lowStockProducts = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================
// DOANH THU 7 NGÀY GẦN NHẤT
// Chỉ ADMIN / MANAGER được xem
// ==========================

$dashboardChartLabels = [];
$dashboardChartRevenue = [];

if ($canViewRevenue) {

    $chartStmt = $pdo->query("
        SELECT
            DATE(created_at) AS sale_date,

            COALESCE(SUM(
                CASE
                    WHEN status = 'COMPLETED'
                        THEN total_amount

                    WHEN status = 'CANCELLED'
                        AND cancelled_by = 'CUSTOMER'
                        AND deposit_status = 'FORFEITED'
                        THEN deposit_amount

                    ELSE 0
                END
            ), 0) AS revenue

        FROM orders

        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)

        GROUP BY DATE(created_at)

        ORDER BY sale_date ASC
    ");

    $chartRows =
        $chartStmt->fetchAll(PDO::FETCH_ASSOC);

    $revenueByDate = [];

    foreach ($chartRows as $row) {
        $revenueByDate[$row['sale_date']] =
            (float)$row['revenue'];
    }

    for ($i = 6; $i >= 0; $i--) {

        $date = date(
            'Y-m-d',
            strtotime("-{$i} days")
        );

        $dashboardChartLabels[] =
            date(
                'd/m',
                strtotime($date)
            );

        $dashboardChartRevenue[] =
            $revenueByDate[$date] ?? 0;
    }
}


require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$user = $_SESSION['user'];

?>

<main class="main">
    <?php
    require_once __DIR__ . '/../includes/topbar.php';
    ?>


    <section class="content">

        <div class="page-title-bar">

            <h1>Tổng quan</h1>

            <p>
                Bảng điều khiển tổng quan hoạt động của cửa hàng
            </p>

        </div>


        <?php if ($isEmployee): ?>

            <div class="employee-dashboard">

                <!-- =========================
                     THỐNG KÊ NHÂN VIÊN
                ========================== -->

                <div class="employee-dashboard-stats">

                    <div class="stat-card">

                        <div class="stat-icon">
                            🧾
                        </div>

                        <div class="stat-info">

                            <span>
                                Đơn của tôi hoàn thành
                            </span>

                            <h3>
                                <?= $totalOrders ?>
                            </h3>

                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-icon">
                            👥
                        </div>

                        <div class="stat-info">

                            <span>
                                Khách hàng
                            </span>

                            <h3>
                                <?= $totalCustomers ?>
                            </h3>

                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-icon">
                            📦
                        </div>

                        <div class="stat-info">

                            <span>
                                Tổng tồn kho
                            </span>

                            <h3>
                                <?= $totalStock ?>
                            </h3>

                        </div>

                    </div>

                </div>


                <!-- =========================
                     HÀNG CHÍNH
                ========================== -->

                <div class="employee-main-grid">


                    <!-- ĐƠN HÀNG CỦA TÔI -->

                    <div class="card employee-status-card">

                        <div class="section-title">

                            <div>

                                <h3>
                                    Đơn hàng của tôi
                                </h3>

                                <p>
                                    Thống kê đơn do bạn tạo
                                </p>

                            </div>


                            <a
                                href="/quanlyquanao/orders/index.php"
                                class="btn btn-light"
                            >
                                Xem đơn hàng
                            </a>

                        </div>


                        <div class="employee-order-status">

                            <div class="employee-status-item">

                                <div class="employee-status-icon">
                                    ✅
                                </div>

                                <div>

                                    <span>
                                        Hoàn thành
                                    </span>

                                    <strong>
                                        <?= $orderStatus['COMPLETED'] ?>
                                    </strong>

                                </div>

                            </div>


                            <div class="employee-status-item">

                                <div class="employee-status-icon">
                                    📝
                                </div>

                                <div>

                                    <span>
                                        Chưa hoàn thành
                                    </span>

                                    <strong>
                                        <?= $orderStatus['PENDING'] ?>
                                    </strong>

                                </div>

                            </div>


                            <div class="employee-status-item">

                                <div class="employee-status-icon">
                                    ❌
                                </div>

                                <div>

                                    <span>
                                        Không hoàn thành
                                    </span>

                                    <strong>
                                        <?= $orderStatus['CANCELLED'] ?>
                                    </strong>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- ĐƠN GẦN NHẤT -->

                    <div class="card">

                        <div class="section-title">

                            <div>

                                <h3>
                                    Đơn hàng mới nhất
                                </h3>

                                <p>
                                    5 đơn hàng gần nhất do bạn tạo
                                </p>

                            </div>


                            <a
                                href="/quanlyquanao/orders/index.php"
                                class="btn btn-light"
                            >
                                Xem tất cả
                            </a>

                        </div>


                        <div class="table-responsive">

                            <table class="table">

                                <thead>

                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Khách hàng</th>
                                        <th>Trạng thái</th>
                                    </tr>

                                </thead>


                                <tbody>

                                <?php if (empty($recentOrders)): ?>

                                    <tr>

                                        <td
                                            colspan="3"
                                            class="empty-data"
                                        >
                                            Bạn chưa tạo đơn hàng nào.
                                        </td>

                                    </tr>

                                <?php else: ?>

                                    <?php foreach (
                                        $recentOrders as $order
                                    ): ?>

                                        <tr>

                                            <td>

                                                <a
                                                    href="/quanlyquanao/orders/view.php?id=<?= $order['id'] ?>"
                                                    class="inventory-order-link"
                                                >
                                                    <?= htmlspecialchars(
                                                        $order['order_code']
                                                    ) ?>
                                                </a>

                                            </td>


                                            <td>

                                                <?= htmlspecialchars(
                                                    $order['customer_name']
                                                    ?: 'Khách vãng lai'
                                                ) ?>

                                            </td>


                                            <td>

                                                <?php if (
                                                    $order['status']
                                                    === 'COMPLETED'
                                                ): ?>

                                                    <span class="status status-success">
                                                        Hoàn thành
                                                    </span>

                                                <?php elseif (
                                                    $order['status']
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

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                <!-- =========================
                     SẢN PHẨM SẮP HẾT
                ========================== -->

                <div class="card employee-low-stock-card">

                    <div class="section-title">

                        <div>

                            <h3>
                                Sản phẩm sắp hết hàng
                            </h3>

                            <p>
                                Các biến thể có tồn kho từ 5 trở xuống
                            </p>

                        </div>


                        <a
                            href="/quanlyquanao/inventory/index.php?stock_status=low"
                            class="btn btn-light"
                        >
                            Xem kho
                        </a>

                    </div>


                    <div class="table-responsive">

                        <table class="table">

                            <thead>

                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>SKU</th>
                                    <th>Size</th>
                                    <th>Màu</th>
                                    <th>Tồn kho</th>
                                </tr>

                            </thead>


                            <tbody>

                            <?php if (
                                empty($lowStockProducts)
                            ): ?>

                                <tr>

                                    <td
                                        colspan="5"
                                        class="empty-data"
                                    >
                                        Không có sản phẩm sắp hết.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach (
                                    $lowStockProducts as $product
                                ): ?>

                                    <tr>

                                        <td>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $product['product_code']
                                                ) ?>
                                            </strong>

                                            <br>

                                            <?= htmlspecialchars(
                                                $product['product_name']
                                            ) ?>

                                        </td>


                                        <td>
                                            <?= htmlspecialchars(
                                                $product['sku']
                                            ) ?>
                                        </td>


                                        <td>
                                            <?= htmlspecialchars(
                                                $product['size_name']
                                            ) ?>
                                        </td>


                                        <td>
                                            <?= htmlspecialchars(
                                                $product['color_name']
                                            ) ?>
                                        </td>


                                        <td>

                                            <?php if (
                                                (int)$product['stock_quantity']
                                                === 0
                                            ): ?>

                                                <span class="status status-danger">
                                                    0
                                                </span>

                                            <?php else: ?>

                                                <span class="status status-warning">
                                                    <?= (int)$product[
                                                        'stock_quantity'
                                                    ] ?>
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        <?php else: ?>

        <!-- =========================
             THỐNG KÊ
        ========================== -->

        <div class="dashboard-stats <?= $canViewRevenue ? '' : 'dashboard-stats-employee' ?>">

            <?php if ($canViewRevenue): ?>

            <div class="stat-card">

                <div class="stat-icon">
                    💰
                </div>

                <div class="stat-info">

                    <span>Tổng doanh thu</span>

                    <h3>
                        <?= number_format(
                            $totalRevenue,
                            0,
                            ',',
                            '.'
                        ) ?> đ
                    </h3>

                </div>

            </div>


            <?php endif; ?>

            <div class="stat-card">

                <div class="stat-icon">
                    🧾
                </div>

                <div class="stat-info">

                    <span><?= $isEmployee ? 'Đơn của tôi hoàn thành' : 'Đơn hoàn thành' ?></span>

                    <h3>
                        <?= $totalOrders ?>
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    👥
                </div>

                <div class="stat-info">

                    <span>Khách hàng</span>

                    <h3>
                        <?= $totalCustomers ?>
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    📦
                </div>

                <div class="stat-info">

                    <span>Tổng tồn kho</span>

                    <h3>
                        <?= $totalStock ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- =========================
             BIỂU ĐỒ + TRẠNG THÁI ĐƠN
        ========================== -->

        <div class="dashboard-grid">

            <?php if ($canViewRevenue): ?>

            <div class="card dashboard-chart-card">

                <div class="section-title">

                    <div>

                        <h3>
                            Doanh thu 7 ngày gần nhất
                        </h3>

                        <p>
                            Bao gồm đơn hoàn thành và tiền cọc khách mất
                        </p>

                    </div>

                </div>

                <div class="dashboard-chart-wrap">

                    <canvas
                        id="dashboardRevenueChart"
                    ></canvas>

                </div>

            </div>


            <?php endif; ?>

            <div class="card">

                <h3><?= $isEmployee ? 'Đơn hàng của tôi theo trạng thái' : 'Đơn hàng theo trạng thái' ?></h3>

                <table class="table">

                    <tbody>

                        <tr>

                            <td>Hoàn thành</td>

                            <td>
                                <strong>
                                    <?= $orderStatus['COMPLETED'] ?>
                                </strong>
                            </td>

                        </tr>


                        <tr>

                            <td>Chưa hoàn thành</td>

                            <td>
                                <strong>
                                    <?= $orderStatus['PENDING'] ?>
                                </strong>
                            </td>

                        </tr>


                        <tr>

                            <td>Không hoàn thành</td>

                            <td>
                                <strong>
                                    <?= $orderStatus['CANCELLED'] ?>
                                </strong>
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- =========================
             KHO + ĐƠN GẦN NHẤT
        ========================== -->

        <div
            class="dashboard-grid"
            style="margin-top:20px;"
        >

            <div class="card">

                <div class="section-title">

                    <div>

                        <h3>
                            Sản phẩm sắp hết hàng
                        </h3>

                        <p>
                            Tồn kho từ 5 sản phẩm trở xuống
                        </p>

                    </div>


                    <?php if (
                        hasPermission('inventory.read')
                    ): ?>

                        <a
                            href="/quanlyquanao/inventory/index.php?stock_status=low"
                            class="btn btn-light"
                        >
                            Xem kho
                        </a>

                    <?php endif; ?>

                </div>


                <div class="table-responsive">

                    <table class="table">

                        <thead>

                            <tr>
                                <th>Sản phẩm</th>
                                <th>SKU</th>
                                <th>Size</th>
                                <th>Màu</th>
                                <th>Tồn</th>
                            </tr>

                        </thead>


                        <tbody>

                        <?php if (
                            empty($lowStockProducts)
                        ): ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="empty-data"
                                >
                                    Không có sản phẩm sắp hết.
                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach (
                                $lowStockProducts as $product
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
                                        <?= htmlspecialchars(
                                            $product['sku']
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= htmlspecialchars(
                                            $product['size_name']
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= htmlspecialchars(
                                            $product['color_name']
                                        ) ?>
                                    </td>


                                    <td>

                                        <?php if (
                                            (int)$product['stock_quantity'] === 0
                                        ): ?>

                                            <span class="status status-danger">
                                                0
                                            </span>

                                        <?php else: ?>

                                            <span class="status status-warning">
                                                <?= (int)$product[
                                                    'stock_quantity'
                                                ] ?>
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


            <div class="card">

                <div class="section-title">

                    <div>

                        <h3>
                            Đơn hàng mới nhất
                        </h3>

                        <p>
                            <?= $isEmployee
                                ? '5 đơn hàng gần nhất do bạn tạo'
                                : '5 đơn hàng gần nhất'
                            ?>
                        </p>

                    </div>


                    <?php if (
                        hasPermission('order.read')
                        || hasPermission('order.read.own')
                    ): ?>

                        <a
                            href="/quanlyquanao/orders/index.php"
                            class="btn btn-light"
                        >
                            Xem tất cả
                        </a>

                    <?php endif; ?>

                </div>


                <div class="table-responsive">

                    <table class="table">

                        <thead>

                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>

                                <?php if ($canViewRevenue): ?>
                                    <th>Tổng tiền</th>
                                <?php endif; ?>

                                <th>Trạng thái</th>
                            </tr>

                        </thead>


                        <tbody>

                        <?php if (
                            empty($recentOrders)
                        ): ?>

                            <tr>

                                <td
                                    colspan="<?= $canViewRevenue ? 4 : 3 ?>"
                                    class="empty-data"
                                >
                                    Chưa có đơn hàng.
                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach (
                                $recentOrders as $order
                            ): ?>

                                <tr>

                                    <td>

                                        <?php if (
                                            hasPermission('order.read')
                                            || hasPermission('order.read.own')
                                        ): ?>

                                            <a
                                                href="/quanlyquanao/orders/view.php?id=<?= $order['id'] ?>"
                                                class="inventory-order-link"
                                            >
                                                <?= htmlspecialchars(
                                                    $order[
                                                        'order_code'
                                                    ]
                                                ) ?>
                                            </a>

                                        <?php else: ?>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $order[
                                                        'order_code'
                                                    ]
                                                ) ?>
                                            </strong>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $order['customer_name']
                                            ?: 'Khách vãng lai'
                                        ) ?>

                                    </td>


                                    <?php if ($canViewRevenue): ?>

                                        <td>

                                            <strong>

                                                <?= number_format(
                                                    $order['total_amount'],
                                                    0,
                                                    ',',
                                                    '.'
                                                ) ?> đ

                                            </strong>

                                        </td>

                                    <?php endif; ?>


                                    <td>

                                        <?php if (
                                            $order['status']
                                            === 'COMPLETED'
                                        ): ?>

                                            <span class="status status-success">
                                                Hoàn thành
                                            </span>

                                        <?php elseif (
                                            $order['status']
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

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

        <?php endif; ?>

    </section>

</main>


<?php if ($canViewRevenue): ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
window.dashboardChartData = {
    labels: <?= json_encode(
        $dashboardChartLabels,
        JSON_UNESCAPED_UNICODE
    ) ?>,

    revenue: <?= json_encode(
        $dashboardChartRevenue
    ) ?>
};
</script>

<?php endif; ?>


<?php
require_once __DIR__ . '/../includes/footer.php';
?>
