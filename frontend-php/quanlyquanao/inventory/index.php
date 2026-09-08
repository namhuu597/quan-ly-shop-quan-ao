<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('inventory.read');

$pageTitle = 'Quản lý kho hàng';
$currentPage = 'inventory';

$keyword = trim($_GET['keyword'] ?? '');
$stockStatus = $_GET['stock_status'] ?? '';

$apiBaseUrl = 'http://localhost:5162/api';

function getInventoryApi(string $url): array
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => apiAuthHeaders()
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    return [
        'response' => $response,
        'httpCode' => $httpCode,
        'curlError' => $curlError
    ];
}

$queryParams = [];

if ($keyword !== '') {
    $queryParams['keyword'] = $keyword;
}

if ($stockStatus !== '') {
    $queryParams['stockStatus'] = $stockStatus;
}

$apiUrl = $apiBaseUrl . '/inventory';

if (!empty($queryParams)) {
    $apiUrl .= '?' . http_build_query($queryParams);
}

$variants = [];
$stats = [
    'total_variants' => 0,
    'total_stock' => 0,
    'out_stock' => 0,
    'low_stock' => 0
];
$apiError = '';

$result = getInventoryApi($apiUrl);

if ($result['response'] === false) {
    $apiError =
        'Không thể kết nối tới ASP.NET Core API. '
        . $result['curlError'];
} else {
    $data = json_decode($result['response'], true);

    if ($result['httpCode'] === 200 && is_array($data)) {
        $apiStats = $data['stats'] ?? [];

        $stats = [
            'total_variants' => (int)($apiStats['totalVariants'] ?? 0),
            'total_stock' => (int)($apiStats['totalStock'] ?? 0),
            'out_stock' => (int)($apiStats['outStock'] ?? 0),
            'low_stock' => (int)($apiStats['lowStock'] ?? 0)
        ];

        foreach (($data['variants'] ?? []) as $variant) {
            $variants[] = [
                'variant_id' => $variant['variantId'] ?? 0,
                'sku' => $variant['sku'] ?? '',
                'stock_quantity' => $variant['stockQuantity'] ?? 0,
                'is_active' => $variant['isActive'] ?? false,
                'product_code' => $variant['productCode'] ?? '',
                'product_name' => $variant['productName'] ?? '',
                'size_name' => $variant['sizeName'] ?? '',
                'color_name' => $variant['colorName'] ?? '',
                'color_code' => $variant['colorCode'] ?? null
            ];
        }
    } else {
        $apiError =
            $data['message']
            ?? (
                'Không thể tải dữ liệu kho. HTTP Status: '
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
            isset($_GET['imported'])
            && $_GET['imported'] === '1'
        ): ?>

            <div class="alert alert-success">
                Nhập kho thành công!
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['adjusted'])
            && $_GET['adjusted'] === '1'
        ): ?>

            <div class="alert alert-success">
                Điều chỉnh tồn kho thành công!
            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>Kho hàng</h1>

                <p>
                    Kho hàng / Danh sách tồn kho
                </p>

            </div>


            <div class="detail-header-actions">

                <?php if (
                    hasPermission('inventory.import')
                ): ?>

                    <a
                        href="/quanlyquanao/inventory/import.php"
                        class="btn btn-primary"
                    >
                        + Nhập kho
                    </a>

                <?php endif; ?>


                <a
                    href="/quanlyquanao/inventory/history.php"
                    class="btn btn-light"
                >
                    Lịch sử kho
                </a>

            </div>

        </div>


        <!-- =========================
             THỐNG KÊ
        ========================== -->

        <div class="inventory-stats">


            <div class="stat-card">

                <div class="stat-icon">
                    📦
                </div>

                <div class="stat-info">

                    <span>
                        Tổng biến thể
                    </span>

                    <h3>
                        <?= (int)$stats['total_variants'] ?>
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    👕
                </div>

                <div class="stat-info">

                    <span>
                        Tổng tồn kho
                    </span>

                    <h3>
                        <?= (int)$stats['total_stock'] ?>
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ⚠️
                </div>

                <div class="stat-info">

                    <span>
                        Sắp hết hàng
                    </span>

                    <h3>
                        <?= (int)$stats['low_stock'] ?>
                    </h3>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ❌
                </div>

                <div class="stat-info">

                    <span>
                        Hết hàng
                    </span>

                    <h3>
                        <?= (int)$stats['out_stock'] ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- =========================
             TÌM KIẾM
        ========================== -->

        <div class="card inventory-filter">

            <form
                method="GET"
                class="inventory-filter-form"
            >

                <input
                    type="text"
                    name="keyword"
                    placeholder="Tìm mã SP, tên sản phẩm, SKU, Size, Màu..."
                    value="<?= htmlspecialchars($keyword) ?>"
                >


                <select name="stock_status">

                    <option value="">
                        Tất cả tồn kho
                    </option>


                    <option
                        value="available"
                        <?= $stockStatus === 'available'
                            ? 'selected'
                            : '' ?>
                    >
                        Còn nhiều
                    </option>


                    <option
                        value="low"
                        <?= $stockStatus === 'low'
                            ? 'selected'
                            : '' ?>
                    >
                        Sắp hết
                    </option>


                    <option
                        value="out"
                        <?= $stockStatus === 'out'
                            ? 'selected'
                            : '' ?>
                    >
                        Hết hàng
                    </option>

                </select>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tìm kiếm
                </button>


                <a
                    href="/quanlyquanao/inventory/index.php"
                    class="btn btn-light"
                >
                    Đặt lại
                </a>

            </form>

        </div>


        <!-- =========================
             DANH SÁCH KHO
        ========================== -->

        <div class="card">

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
                            <th>Tồn kho</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($variants)): ?>

                        <tr>

                            <td
                                colspan="9"
                                class="empty-data"
                            >
                                Không tìm thấy dữ liệu kho.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach (
                            $variants as $index => $variant
                        ): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $variant['product_code']
                                        ) ?>
                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $variant['product_name']
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $variant['sku']
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $variant['size_name']
                                    ) ?>

                                </td>


                                <td>

                                    <div class="order-color-cell">

                                        <span
                                            class="order-color-dot"
                                            style="background:
                                                <?= htmlspecialchars(
                                                    $variant['color_code']
                                                    ?: '#cccccc'
                                                ) ?>;"
                                        ></span>

                                        <?= htmlspecialchars(
                                            $variant['color_name']
                                        ) ?>

                                    </div>

                                </td>


                                <td>

                                    <strong class="inventory-stock-number">

                                        <?= (int)$variant[
                                            'stock_quantity'
                                        ] ?>

                                    </strong>

                                </td>


                                <td>

                                    <?php
                                    $stock =
                                        (int)$variant[
                                            'stock_quantity'
                                        ];
                                    ?>


                                    <?php if ($stock === 0): ?>

                                        <span class="status status-danger">
                                            Hết hàng
                                        </span>


                                    <?php elseif ($stock <= 5): ?>

                                        <span class="status status-warning">
                                            Sắp hết
                                        </span>


                                    <?php else: ?>

                                        <span class="status status-success">
                                            Còn hàng
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <div class="table-actions">

                                        <?php if (
                                            hasPermission(
                                                'inventory.adjust'
                                            )
                                        ): ?>

                                            <a
                                                href="/quanlyquanao/inventory/adjust.php?variant_id=<?= $variant['variant_id'] ?>"
                                                class="action-btn"
                                                title="Điều chỉnh kho"
                                            >
                                                ✏️
                                            </a>

                                        <?php endif; ?>


                                        <a
                                            href="/quanlyquanao/inventory/history.php?variant_id=<?= $variant['variant_id'] ?>"
                                            class="action-btn"
                                            title="Lịch sử kho"
                                        >
                                            👁
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