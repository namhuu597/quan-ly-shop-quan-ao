<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('product.read');

$pageTitle = 'Danh sách sản phẩm';
$currentPage = 'products';


// ==========================
// NHẬN BỘ LỌC
// ==========================

$keyword =
    trim($_GET['keyword'] ?? '');

$categoryId =
    $_GET['category_id'] ?? '';

$colorId =
    $_GET['color_id'] ?? '';

$status =
    $_GET['status'] ?? '';


// ==========================
// API BASE URL
// ==========================

$apiBaseUrl =
    'http://localhost:5162/api';


// ==========================
// HÀM GET API
// ==========================

function getApi(string $url): array
{
    $ch = curl_init();

    curl_setopt_array(
        $ch,
        [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
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
        'response' => $response,
        'httpCode' => $httpCode,
        'curlError' => $curlError
    ];
}


// ==========================
// LẤY DANH MỤC
// ==========================

$categories = [];

$categoryResult =
    getApi(
        $apiBaseUrl . '/categories'
    );

if (
    $categoryResult['response'] !== false
    && $categoryResult['httpCode'] === 200
) {

    $categoryData =
        json_decode(
            $categoryResult['response'],
            true
        );

    if (is_array($categoryData)) {

        foreach (
            $categoryData as $category
        ) {

            if (
                !empty(
                    $category['isActive']
                )
            ) {

                $categories[] =
                    $category;
            }
        }
    }
}


// ==========================
// LẤY MÀU SẮC
// ==========================

$colors = [];

$colorResult =
    getApi(
        $apiBaseUrl . '/colors'
    );

if (
    $colorResult['response'] !== false
    && $colorResult['httpCode'] === 200
) {

    $colorData =
        json_decode(
            $colorResult['response'],
            true
        );

    if (is_array($colorData)) {

        foreach (
            $colorData as $color
        ) {

            if (
                !empty(
                    $color['isActive']
                )
            ) {

                $colors[] =
                    $color;
            }
        }
    }
}


// ==========================
// TẠO URL LỌC SẢN PHẨM
// ==========================

$queryParams = [];

if ($keyword !== '') {

    $queryParams['keyword'] =
        $keyword;
}

if ($categoryId !== '') {

    $queryParams['categoryId'] =
        $categoryId;
}

if ($colorId !== '') {

    $queryParams['colorId'] =
        $colorId;
}

if ($status !== '') {

    $queryParams['status'] =
        $status;
}


$productApiUrl =
    $apiBaseUrl . '/products';

if (!empty($queryParams)) {

    $productApiUrl .=
        '?'
        . http_build_query(
            $queryParams
        );
}


// ==========================
// LẤY SẢN PHẨM
// ==========================

$products = [];
$apiError = '';

$productResult =
    getApi(
        $productApiUrl
    );


if (
    $productResult['response']
    === false
) {

    $apiError =
        'Không thể kết nối tới ASP.NET Core API. '
        . $productResult['curlError'];

} elseif (
    $productResult['httpCode']
    !== 200
) {

    $apiError =
        'Không thể tải danh sách sản phẩm. '
        . 'HTTP Status: '
        . $productResult['httpCode'];

} else {

    $productData =
        json_decode(
            $productResult['response'],
            true
        );

    if (is_array($productData)) {

        $products =
            $productData;

    } else {

        $apiError =
            'Dữ liệu API trả về không hợp lệ.';
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
            isset($_GET['status_changed'])
            && $_GET['status_changed'] === '1'
        ): ?>

            <div class="alert alert-success">
                Cập nhật trạng thái sản phẩm thành công!
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['created'])
            && $_GET['created'] === '1'
        ): ?>

            <div class="alert alert-success">
                Thêm sản phẩm thành công!
            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>
                    Danh sách sản phẩm
                </h1>

                <p>
                    Sản phẩm / Danh sách
                </p>

            </div>


            <?php if (
                hasPermission(
                    'product.create'
                )
            ): ?>

                <a
                    href="/quanlyquanao/products/create.php"
                    class="btn btn-primary"
                >
                    + Thêm sản phẩm
                </a>

            <?php endif; ?>

        </div>


        <div class="card product-filter">

            <form
                method="GET"
                class="filter-form"
            >


                <div class="filter-search">

                    <input
                        type="text"
                        name="keyword"
                        placeholder="Tìm tên hoặc mã sản phẩm..."
                        value="<?= htmlspecialchars(
                            $keyword
                        ) ?>"
                    >

                </div>


                <select name="category_id">

                    <option value="">
                        Tất cả danh mục
                    </option>

                    <?php foreach (
                        $categories as $category
                    ): ?>

                        <option
                            value="<?= (int)(
                                $category['id']
                                ?? 0
                            ) ?>"
                            <?= (
                                (string)$categoryId
                                ===
                                (string)(
                                    $category['id']
                                    ?? ''
                                )
                            )
                                ? 'selected'
                                : '' ?>
                        >

                            <?= htmlspecialchars(
                                $category['name']
                                ?? ''
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <select name="color_id">

                    <option value="">
                        Tất cả màu sắc
                    </option>

                    <?php foreach (
                        $colors as $color
                    ): ?>

                        <option
                            value="<?= (int)(
                                $color['id']
                                ?? 0
                            ) ?>"
                            <?= (
                                (string)$colorId
                                ===
                                (string)(
                                    $color['id']
                                    ?? ''
                                )
                            )
                                ? 'selected'
                                : '' ?>
                        >

                            <?= htmlspecialchars(
                                $color['name']
                                ?? ''
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <select name="status">

                    <option value="">
                        Tất cả trạng thái
                    </option>

                    <option
                        value="ACTIVE"
                        <?= $status === 'ACTIVE'
                            ? 'selected'
                            : '' ?>
                    >
                        Đang kinh doanh
                    </option>

                    <option
                        value="INACTIVE"
                        <?= $status === 'INACTIVE'
                            ? 'selected'
                            : '' ?>
                    >
                        Ngừng kinh doanh
                    </option>

                </select>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tìm kiếm
                </button>


                <a
                    href="/quanlyquanao/products/index.php"
                    class="btn btn-light"
                >
                    Đặt lại
                </a>

            </form>

        </div>


        <div class="card">

            <div class="table-responsive">

                <table class="table product-table">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Hình ảnh</th>

                            <th>Mã SP</th>

                            <th>Tên sản phẩm</th>

                            <th>Danh mục</th>

                            <th>Giá bán</th>

                            <th>Tồn kho</th>

                            <th>Trạng thái</th>

                            <th>Thao tác</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (
                        $apiError === ''
                        && empty($products)
                    ): ?>

                        <tr>

                            <td
                                colspan="9"
                                class="empty-data"
                            >
                                Chưa có sản phẩm nào.
                            </td>

                        </tr>


                    <?php elseif (
                        $apiError === ''
                    ): ?>


                        <?php foreach (
                            $products
                            as $index => $product
                        ): ?>

                            <tr>


                                <td>
                                    <?= $index + 1 ?>
                                </td>


                                <td>

                                    <?php if (
                                        !empty(
                                            $product['imagePath']
                                        )
                                    ): ?>

                                        <img
                                            src="/quanlyquanao/<?= htmlspecialchars(
                                                $product['imagePath']
                                            ) ?>"
                                            class="product-thumb"
                                            alt=""
                                        >

                                    <?php else: ?>

                                        <div class="product-no-image">
                                            👕
                                        </div>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $product[
                                                'productCode'
                                            ]
                                            ?? ''
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <div class="product-name">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $product['name']
                                                ?? ''
                                            ) ?>

                                        </strong>


                                        <?php if (
                                            !empty(
                                                $product['brandName']
                                            )
                                        ): ?>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $product['brandName']
                                                ) ?>

                                            </small>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $product[
                                            'categoryName'
                                        ]
                                        ?? ''
                                    ) ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= number_format(
                                            (float)(
                                                $product[
                                                    'basePrice'
                                                ]
                                                ?? 0
                                            ),
                                            0,
                                            ',',
                                            '.'
                                        ) ?> đ

                                    </strong>

                                </td>


                                <td>

                                    <?php

                                    $totalStock =
                                        (int)(
                                            $product[
                                                'totalStock'
                                            ]
                                            ?? 0
                                        );

                                    ?>

                                    <?php if (
                                        $totalStock <= 5
                                    ): ?>

                                        <span class="stock-low">

                                            <?= $totalStock ?>

                                        </span>

                                    <?php else: ?>

                                        <?= $totalStock ?>

                                    <?php endif; ?>

                                </td>


                                <td>

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

                                </td>


                                <td>

                                    <div class="table-actions">


                                        <a
                                            href="/quanlyquanao/products/view.php?id=<?= (int)($product['id'] ?? 0) ?>"
                                            class="action-btn"
                                            title="Xem"
                                        >
                                            👁
                                        </a>


                                        <?php if (
                                            hasPermission(
                                                'product.update'
                                            )
                                        ): ?>

                                            <a
                                                href="/quanlyquanao/products/edit.php?id=<?= (int)($product['id'] ?? 0) ?>"
                                                class="action-btn"
                                                title="Sửa"
                                            >
                                                ✏️
                                            </a>

                                        <?php endif; ?>


                                        <?php if (
                                            hasPermission(
                                                'product.delete'
                                            )
                                        ): ?>

                                            <form
                                                method="POST"
                                                action="/quanlyquanao/products/toggle-status.php"
                                                onsubmit="return confirm(
                                                    'Bạn có chắc muốn thay đổi trạng thái sản phẩm này?'
                                                );"
                                                style="display:inline;"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)($product['id'] ?? 0) ?>"
                                                >


                                                <?php if (
                                                    (
                                                        $product['status']
                                                        ?? ''
                                                    )
                                                    === 'ACTIVE'
                                                ): ?>

                                                    <button
                                                        type="submit"
                                                        class="action-btn action-danger"
                                                        title="Ngừng kinh doanh"
                                                    >
                                                        ⛔
                                                    </button>

                                                <?php else: ?>

                                                    <button
                                                        type="submit"
                                                        class="action-btn action-success"
                                                        title="Kích hoạt lại"
                                                    >
                                                        ✅
                                                    </button>

                                                <?php endif; ?>

                                            </form>

                                        <?php endif; ?>


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