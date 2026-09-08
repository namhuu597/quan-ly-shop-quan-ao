<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('product.read');

$pageTitle = 'Chi tiết sản phẩm';
$currentPage = 'products';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Sản phẩm không hợp lệ.');
}


// =========================================
// GỌI ASP.NET CORE API
// =========================================

$apiUrl =
    'http://localhost:5162/api/products/'
    . $id;


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


// =========================================
// KIỂM TRA API
// =========================================

if ($response === false) {

    die(
        'Không thể kết nối tới ASP.NET Core API. '
        . $curlError
    );
}


if ($httpCode === 404) {

    die(
        'Không tìm thấy sản phẩm.'
    );
}


if ($httpCode !== 200) {

    die(
        'Không thể tải dữ liệu sản phẩm. '
        . 'HTTP Status: '
        . $httpCode
    );
}


$product =
    json_decode(
        $response,
        true
    );


if (!is_array($product)) {

    die(
        'Dữ liệu sản phẩm từ API không hợp lệ.'
    );
}


// =========================================
// LẤY ẢNH + BIẾN THỂ TỪ RESPONSE
// =========================================

$images =
    is_array(
        $product['images']
        ?? null
    )
        ? $product['images']
        : [];


$variants =
    is_array(
        $product['variants']
        ?? null
    )
        ? $product['variants']
        : [];


require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="main">

    <header class="topbar">

        <div>
            <strong>
                Quản lý sản phẩm
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
                Cập nhật sản phẩm thành công!
            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>
                    Chi tiết sản phẩm
                </h1>

                <p>

                    Sản phẩm /

                    <?= htmlspecialchars(
                        $product['productCode']
                        ?? ''
                    ) ?>

                </p>

            </div>


            <div class="detail-header-actions">

                <a
                    href="/quanlyquanao/products/index.php"
                    class="btn btn-light"
                >
                    ← Quay lại
                </a>


                <?php if (
                    hasPermission(
                        'product.update'
                    )
                ): ?>

                    <a
                        href="/quanlyquanao/products/edit.php?id=<?= $id ?>"
                        class="btn btn-primary"
                    >
                        Sửa sản phẩm
                    </a>

                <?php endif; ?>

            </div>

        </div>


        <div class="product-detail-grid">


            <!-- =========================
                 THÔNG TIN
            ========================== -->

            <div class="card">

                <h3>
                    Thông tin sản phẩm
                </h3>


                <div class="detail-list">


                    <div class="detail-item">

                        <span>
                            Mã sản phẩm
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $product['productCode']
                                ?? ''
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Tên sản phẩm
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $product['name']
                                ?? ''
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Danh mục
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $product['categoryName']
                                ?? ''
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Thương hiệu
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                !empty(
                                    $product['brandName']
                                )
                                    ? $product['brandName']
                                    : 'Không có'
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Giá bán
                        </span>

                        <strong>

                            <?= number_format(
                                (float)(
                                    $product['basePrice']
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
                            Tổng tồn kho
                        </span>

                        <strong>

                            <?= (int)(
                                $product['totalStock']
                                ?? 0
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Trạng thái
                        </span>


                        <?php if (
                            (
                                $product['status']
                                ?? ''
                            )
                            === 'ACTIVE'
                        ): ?>

                            <span
                                class="status status-success"
                            >
                                Đang kinh doanh
                            </span>

                        <?php else: ?>

                            <span
                                class="status status-danger"
                            >
                                Ngừng kinh doanh
                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <div class="product-description">

                    <h4>
                        Mô tả
                    </h4>

                    <p>

                        <?= nl2br(
                            htmlspecialchars(
                                !empty(
                                    $product['description']
                                )
                                    ? $product['description']
                                    : 'Chưa có mô tả.'
                            )
                        ) ?>

                    </p>

                </div>

            </div>


            <!-- =========================
                 HÌNH ẢNH
            ========================== -->

            <div class="card">

                <h3>
                    Hình ảnh sản phẩm
                </h3>


                <?php if (
                    empty($images)
                ): ?>

                    <div class="detail-no-image">

                        👕

                        <span>
                            Chưa có hình ảnh
                        </span>

                    </div>


                <?php else: ?>


                    <div class="detail-images">


                        <?php foreach (
                            $images
                            as $image
                        ): ?>

                            <div class="detail-image-item">

                                <img
                                    src="/quanlyquanao/<?= htmlspecialchars(
                                        $image['imagePath']
                                        ?? ''
                                    ) ?>"
                                    alt=""
                                >


                                <?php if (
                                    !empty(
                                        $image['isPrimary']
                                    )
                                ): ?>

                                    <span class="primary-badge">
                                        Ảnh chính
                                    </span>

                                <?php endif; ?>


                            </div>

                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>

            </div>

        </div>


        <!-- =========================
             BIẾN THỂ
        ========================== -->

        <div class="card product-detail-variants">

            <h3>
                Biến thể sản phẩm
            </h3>


            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>SKU</th>
                            <th>Size</th>
                            <th>Màu sắc</th>
                            <th>Giá</th>
                            <th>Tồn kho</th>
                            <th>Trạng thái</th>
                        </tr>

                    </thead>


                    <tbody>


                    <?php if (
                        empty($variants)
                    ): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="empty-data"
                            >
                                Sản phẩm chưa có biến thể.
                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $variants
                            as $variant
                        ): ?>


                            <tr>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $variant['sku']
                                            ?? ''
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $variant['sizeName']
                                        ?? ''
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $variant['colorName']
                                        ?? ''
                                    ) ?>

                                </td>


                                <td>

                                    <?php

                                    $price =
                                        $variant['price']
                                        ?? $product['basePrice']
                                        ?? 0;

                                    ?>

                                    <?= number_format(
                                        (float)$price,
                                        0,
                                        ',',
                                        '.'
                                    ) ?> đ

                                </td>


                                <td>

                                    <?php

                                    $stock =
                                        (int)(
                                            $variant[
                                                'stockQuantity'
                                            ]
                                            ?? 0
                                        );

                                    ?>


                                    <?php if (
                                        $stock <= 5
                                    ): ?>

                                        <span class="stock-low">

                                            <?= $stock ?>

                                        </span>

                                    <?php else: ?>

                                        <?= $stock ?>

                                    <?php endif; ?>

                                </td>


                                <td>


                                    <?php if (
                                        !empty(
                                            $variant[
                                                'isActive'
                                            ]
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
                                            Ngừng
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

    </section>

</main>


<?php
require_once __DIR__ . '/../includes/footer.php';
?>