<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('product.update');

$pageTitle = 'Sửa sản phẩm';
$currentPage = 'products';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Sản phẩm không hợp lệ.');
}

$apiBaseUrl =
    'http://localhost:5162/api';

$error = '';


// =========================================
// HÀM GỌI API GET
// =========================================

function getApi(string $url): array
{
    $ch = curl_init();

    curl_setopt_array(
        $ch,
        [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER =>
                apiAuthHeaders()
        ]
    );

    $response = curl_exec($ch);

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


// =========================================
// HÀM GỌI API MULTIPART
// =========================================

function sendMultipartApi(
    string $url,
    string $method,
    array $fields
): array {

    $ch = curl_init();

    curl_setopt_array(
        $ch,
        [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER =>
                apiAuthHeaders()
        ]
    );

    $response = curl_exec($ch);

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


// =========================================
// LOAD PRODUCT TỪ ASP.NET
// =========================================

function loadProductFromApi(
    string $apiBaseUrl,
    int $id
): array {

    $result =
        getApi(
            $apiBaseUrl
            . '/products/'
            . $id
        );

    if ($result['response'] === false) {
        die(
            'Không thể kết nối tới ASP.NET Core API. '
            . $result['curlError']
        );
    }

    if ($result['httpCode'] === 404) {
        die('Không tìm thấy sản phẩm.');
    }

    if ($result['httpCode'] !== 200) {
        die(
            'Không thể tải dữ liệu sản phẩm. HTTP Status: '
            . $result['httpCode']
        );
    }

    $data =
        json_decode(
            $result['response'],
            true
        );

    if (!is_array($data)) {
        die(
            'Dữ liệu sản phẩm từ API không hợp lệ.'
        );
    }

    return $data;
}


// =========================================
// LOAD OPTIONS
// =========================================

$optionsResult =
    getApi(
        $apiBaseUrl
        . '/products/form-options'
    );

if ($optionsResult['response'] === false) {
    die(
        'Không thể kết nối tới ASP.NET Core API. '
        . $optionsResult['curlError']
    );
}

if ($optionsResult['httpCode'] !== 200) {
    die(
        'Không thể tải dữ liệu cho form. HTTP Status: '
        . $optionsResult['httpCode']
    );
}

$options =
    json_decode(
        $optionsResult['response'],
        true
    );

if (!is_array($options)) {
    die(
        'Dữ liệu form từ API không hợp lệ.'
    );
}

$categories =
    $options['categories'] ?? [];

$brands =
    $options['brands'] ?? [];

$sizes =
    $options['sizes'] ?? [];

$colors =
    $options['colors'] ?? [];

$product =
    loadProductFromApi(
        $apiBaseUrl,
        $id
    );

$images =
    is_array($product['images'] ?? null)
        ? $product['images']
        : [];

$variants =
    is_array($product['variants'] ?? null)
        ? $product['variants']
        : [];


// =========================================
// UPDATE QUA ASP.NET API
// =========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrfToken();

    $productCode =
        trim(
            $_POST['product_code']
            ?? ''
        );

    $name =
        trim(
            $_POST['name']
            ?? ''
        );

    $categoryId =
        (int)(
            $_POST['category_id']
            ?? 0
        );

    $brandIdRaw =
        $_POST['brand_id']
        ?? '';

    $basePrice =
        $_POST['base_price']
        ?? '';

    $status =
        $_POST['status']
        ?? 'ACTIVE';

    $description =
        trim(
            $_POST['description']
            ?? ''
        );

    $variantIds =
        $_POST['variant_id']
        ?? [];

    $sizeIds =
        $_POST['variant_size_id']
        ?? [];

    $colorIds =
        $_POST['variant_color_id']
        ?? [];

    $skus =
        $_POST['variant_sku']
        ?? [];

    $variantPrices =
        $_POST['variant_price']
        ?? [];

    $deleteVariants =
        $_POST['delete_variant']
        ?? [];

    $deleteImageIds =
        $_POST['delete_image_ids']
        ?? [];

    $primaryImageId =
        (int)(
            $_POST['primary_image_id']
            ?? 0
        );

    $newPrimaryIndexRaw =
        $_POST['new_primary_index']
        ?? '';

    if (
        $productCode === ''
        || $name === ''
        || $categoryId <= 0
        || $basePrice === ''
    ) {

        $error =
            'Vui lòng nhập đầy đủ các trường bắt buộc.';

    } elseif ((float)$basePrice < 0) {

        $error =
            'Giá bán không hợp lệ.';

    } else {

        $variantPayload = [];

        $rowCount =
            max(
                count($variantIds),
                count($sizeIds),
                count($colorIds),
                count($skus),
                count($variantPrices),
                count($deleteVariants)
            );

        for (
            $i = 0;
            $i < $rowCount;
            $i++
        ) {
            $priceRaw =
                $variantPrices[$i]
                ?? '';

            $variantPayload[] = [
                'id' =>
                    (int)(
                        $variantIds[$i]
                        ?? 0
                    ),

                'sizeId' =>
                    (int)(
                        $sizeIds[$i]
                        ?? 0
                    ),

                'colorId' =>
                    (int)(
                        $colorIds[$i]
                        ?? 0
                    ),

                'sku' =>
                    trim(
                        $skus[$i]
                        ?? ''
                    ),

                'price' =>
                    $priceRaw === ''
                        ? null
                        : (float)$priceRaw,

                'delete' =>
                    (int)(
                        $deleteVariants[$i]
                        ?? 0
                    ) === 1
            ];
        }

        $payload = [
            'productCode' =>
                $productCode,

            'categoryId' =>
                $categoryId,

            'brandId' =>
                $brandIdRaw === ''
                    ? null
                    : (int)$brandIdRaw,

            'name' =>
                $name,

            'description' =>
                $description === ''
                    ? null
                    : $description,

            'basePrice' =>
                (float)$basePrice,

            'status' =>
                $status,

            'variants' =>
                $variantPayload
        ];

        $fields = [
            'productJson' =>
                json_encode(
                    $payload,
                    JSON_UNESCAPED_UNICODE
                ),

            'deleteImageIdsJson' =>
                json_encode(
                    array_values(
                        array_map(
                            'intval',
                            $deleteImageIds
                        )
                    )
                )
        ];

        if ($primaryImageId > 0) {
            $fields['primaryImageId'] =
                (string)$primaryImageId;
        }

        if ($newPrimaryIndexRaw !== '') {
            $fields['newPrimaryIndex'] =
                (string)(
                    (int)$newPrimaryIndexRaw
                );
        }

        if (
            isset($_FILES['images'])
            && isset(
                $_FILES['images']['tmp_name']
            )
        ) {
            $fileNumber = 0;

            foreach (
                $_FILES['images']['tmp_name']
                as $index => $tmpName
            ) {
                if (
                    ($_FILES['images']['error'][$index]
                        ?? UPLOAD_ERR_NO_FILE)
                    !== UPLOAD_ERR_OK
                ) {
                    continue;
                }

                if (
                    !is_uploaded_file(
                        $tmpName
                    )
                ) {
                    continue;
                }

                $originalName =
                    $_FILES['images']['name'][$index]
                    ?? (
                        'image_'
                        . $fileNumber
                    );

                $mimeType =
                    $_FILES['images']['type'][$index]
                    ?? 'application/octet-stream';

                $fields[
                    'images['
                    . $fileNumber
                    . ']'
                ] =
                    new CURLFile(
                        $tmpName,
                        $mimeType,
                        $originalName
                    );

                $fileNumber++;
            }
        }

        $updateResult =
            sendMultipartApi(
                $apiBaseUrl
                . '/products/'
                . $id,
                'PUT',
                $fields
            );

        if (
            $updateResult['response']
            === false
        ) {

            $error =
                'Không thể kết nối tới ASP.NET Core API. '
                . $updateResult['curlError'];

        } else {

            $responseData =
                json_decode(
                    $updateResult['response'],
                    true
                );

            if (
                $updateResult['httpCode']
                === 200
            ) {

                header(
                    'Location: /quanlyquanao/products/view.php?id='
                    . $id
                    . '&updated=1'
                );

                exit;

            } else {

                $error =
                    $responseData['message']
                    ?? (
                        'Không thể cập nhật sản phẩm. HTTP Status: '
                        . $updateResult['httpCode']
                    );

                if (
                    isset(
                        $responseData['detail']
                    )
                    && $responseData['detail'] !== ''
                ) {
                    $error .=
                        ' - '
                        . $responseData['detail'];
                }
            }
        }
    }

    // Reload dữ liệu DB qua API nếu submit lỗi.
    $product =
        loadProductFromApi(
            $apiBaseUrl,
            $id
        );

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
}


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


        <?php if ($error !== ''): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars(
                    $error
                ) ?>
            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>Sửa sản phẩm</h1>

                <p>
                    Sản phẩm /
                    <?= htmlspecialchars(
                        $product['productCode']
                        ?? ''
                    ) ?>
                    / Sửa
                </p>

            </div>

        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <?= csrfField() ?>


            <div class="create-product-grid">


                <!-- =========================
                     THÔNG TIN
                ========================== -->

                <div class="card">

                    <h3>
                        Thông tin cơ bản
                    </h3>


                    <div class="form-grid">


                        <div class="form-group">

                            <label>
                                Mã sản phẩm
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="product_code"
                                value="<?= htmlspecialchars(
                                    $_POST['product_code']
                                    ?? $product['productCode']
                                    ?? ''
                                ) ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Tên sản phẩm
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="name"
                                value="<?= htmlspecialchars(
                                    $_POST['name']
                                    ?? $product['name']
                                    ?? ''
                                ) ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Danh mục
                                <span class="required">*</span>
                            </label>

                            <?php
                            $selectedCategory =
                                $_POST['category_id']
                                ?? $product['categoryId']
                                ?? '';
                            ?>

                            <select
                                name="category_id"
                                required
                            >

                                <?php foreach (
                                    $categories
                                    as $category
                                ): ?>

                                    <option
                                        value="<?= (int)(
                                            $category['id']
                                            ?? 0
                                        ) ?>"
                                        <?= (
                                            (string)$selectedCategory
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

                        </div>


                        <div class="form-group">

                            <label>
                                Thương hiệu
                            </label>

                            <?php
                            $selectedBrand =
                                $_POST['brand_id']
                                ?? $product['brandId']
                                ?? '';
                            ?>

                            <select name="brand_id">

                                <option value="">
                                    Không có thương hiệu
                                </option>

                                <?php foreach (
                                    $brands
                                    as $brand
                                ): ?>

                                    <option
                                        value="<?= (int)(
                                            $brand['id']
                                            ?? 0
                                        ) ?>"
                                        <?= (
                                            (string)$selectedBrand
                                            ===
                                            (string)(
                                                $brand['id']
                                                ?? ''
                                            )
                                        )
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= htmlspecialchars(
                                            $brand['name']
                                            ?? ''
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Giá bán
                                <span class="required">*</span>
                            </label>

                            <input
                                type="number"
                                name="base_price"
                                min="0"
                                value="<?= htmlspecialchars(
                                    $_POST['base_price']
                                    ?? $product['basePrice']
                                    ?? 0
                                ) ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Trạng thái
                            </label>

                            <?php
                            $selectedStatus =
                                $_POST['status']
                                ?? $product['status']
                                ?? 'ACTIVE';
                            ?>

                            <select name="status">

                                <option
                                    value="ACTIVE"
                                    <?= $selectedStatus === 'ACTIVE'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Đang kinh doanh
                                </option>

                                <option
                                    value="INACTIVE"
                                    <?= $selectedStatus === 'INACTIVE'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Ngừng kinh doanh
                                </option>

                            </select>

                        </div>


                        <div class="form-group full-width">

                            <label>
                                Mô tả sản phẩm
                            </label>

                            <textarea
                                name="description"
                                rows="6"
                            ><?= htmlspecialchars(
                                $_POST['description']
                                ?? $product['description']
                                ?? ''
                            ) ?></textarea>

                        </div>

                    </div>

                </div>


                <!-- =========================
                     HÌNH ẢNH
                ========================== -->

                <div class="card">

                    <h3>
                        Hình ảnh sản phẩm
                    </h3>

                    <p class="form-help">
                        Có thể thêm nhiều ảnh, xóa ảnh cũ
                        và chọn một ảnh làm ảnh chính.
                    </p>


                    <div class="edit-image-list">

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

                            <?php foreach (
                                $images
                                as $image
                            ): ?>

                                <div class="edit-image-item">

                                    <img
                                        src="/quanlyquanao/<?= htmlspecialchars(
                                            $image['imagePath']
                                            ?? ''
                                        ) ?>"
                                        alt=""
                                    >


                                    <label class="primary-radio">

                                        <input
                                            type="radio"
                                            name="primary_image_id"
                                            value="<?= (int)(
                                                $image['id']
                                                ?? 0
                                            ) ?>"
                                            <?= !empty(
                                                $image['isPrimary']
                                            )
                                                ? 'checked'
                                                : '' ?>
                                        >

                                        Ảnh chính

                                    </label>


                                    <label class="delete-image-check">

                                        <input
                                            type="checkbox"
                                            name="delete_image_ids[]"
                                            value="<?= (int)(
                                                $image['id']
                                                ?? 0
                                            ) ?>"
                                        >

                                        Xóa ảnh

                                    </label>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>


                    <div
                        class="image-upload-box"
                        style="margin-top:20px;"
                    >

                        <div class="upload-icon">
                            ＋
                        </div>

                        <strong>
                            Thêm hình ảnh mới
                        </strong>

                        <span>
                            JPG, JPEG, PNG, WEBP
                        </span>

                        <input
                            type="file"
                            id="editProductImages"
                            name="images[]"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                        >

                    </div>


                    <div
                        id="editImagePreview"
                        class="image-preview-area"
                    >
                        <p>
                            Ảnh mới sẽ hiển thị tại đây.
                        </p>
                    </div>


                    <input
                        type="hidden"
                        name="new_primary_index"
                        id="newPrimaryIndex"
                        value=""
                    >

                </div>

            </div>


            <!-- =========================
                 VARIANTS
            ========================== -->

            <div class="card product-variant-section">

                <h3>
                    Biến thể sản phẩm
                </h3>

                <p class="form-help">
                    Tồn kho hiện tại được quản lý ở Kho hàng.
                    Khi thêm biến thể mới, tồn kho ban đầu là 0.
                </p>


                <div id="editVariantContainer">

                    <?php foreach (
                        $variants
                        as $variant
                    ): ?>

                        <div
                            class="variant-row edit-variant-row"
                        >

                            <input
                                type="hidden"
                                name="variant_id[]"
                                value="<?= (int)(
                                    $variant['id']
                                    ?? 0
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="delete_variant[]"
                                class="delete-variant-flag"
                                value="0"
                            >


                            <div class="variant-field">

                                <label>Size</label>

                                <select
                                    name="variant_size_id[]"
                                >

                                    <?php foreach (
                                        $sizes
                                        as $size
                                    ): ?>

                                        <option
                                            value="<?= (int)(
                                                $size['id']
                                                ?? 0
                                            ) ?>"
                                            <?= (
                                                (string)(
                                                    $variant['sizeId']
                                                    ?? ''
                                                )
                                                ===
                                                (string)(
                                                    $size['id']
                                                    ?? ''
                                                )
                                            )
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= htmlspecialchars(
                                                $size['name']
                                                ?? ''
                                            ) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <div class="variant-field">

                                <label>
                                    Màu sắc
                                </label>

                                <select
                                    name="variant_color_id[]"
                                >

                                    <?php foreach (
                                        $colors
                                        as $color
                                    ): ?>

                                        <option
                                            value="<?= (int)(
                                                $color['id']
                                                ?? 0
                                            ) ?>"
                                            <?= (
                                                (string)(
                                                    $variant['colorId']
                                                    ?? ''
                                                )
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

                            </div>


                            <div class="variant-field">

                                <label>SKU</label>

                                <input
                                    type="text"
                                    name="variant_sku[]"
                                    value="<?= htmlspecialchars(
                                        $variant['sku']
                                        ?? ''
                                    ) ?>"
                                >

                            </div>


                            <div class="variant-field">

                                <label>
                                    Giá riêng
                                </label>

                                <input
                                    type="number"
                                    name="variant_price[]"
                                    min="0"
                                    value="<?= htmlspecialchars(
                                        $variant['price']
                                        ?? ''
                                    ) ?>"
                                    placeholder="Giá chung"
                                >

                            </div>


                            <div class="variant-field">

                                <label>
                                    Tồn kho
                                </label>

                                <input
                                    type="text"
                                    value="<?= (int)(
                                        $variant['stockQuantity']
                                        ?? 0
                                    ) ?>"
                                    disabled
                                >

                            </div>


                            <div class="variant-action">

                                <button
                                    type="button"
                                    class="remove-edit-variant-btn"
                                >
                                    Xóa
                                </button>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>


                <button
                    type="button"
                    id="addEditVariantBtn"
                    class="btn btn-light"
                >
                    + Thêm biến thể
                </button>

            </div>


            <div class="form-actions">

                <a
                    href="/quanlyquanao/products/view.php?id=<?= $id ?>"
                    class="btn btn-light"
                >
                    Hủy
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lưu thay đổi
                </button>

            </div>

        </form>

    </section>

</main>


<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {

        // =====================================
        // ẢNH MỚI
        // =====================================

        const imageInput =
            document.getElementById(
                'editProductImages'
            );

        const preview =
            document.getElementById(
                'editImagePreview'
            );

        const newPrimaryIndex =
            document.getElementById(
                'newPrimaryIndex'
            );

        const uploadBox =
            document.querySelector(
                '.image-upload-box'
            );


        if (
            uploadBox
            && imageInput
        ) {
            uploadBox.addEventListener(
                'click',
                function (event) {

                    if (
                        event.target
                        === imageInput
                    ) {
                        return;
                    }

                    imageInput.click();
                }
            );
        }


        if (
            imageInput
            && preview
            && newPrimaryIndex
        ) {

            imageInput.addEventListener(
                'change',
                function () {

                    const files =
                        Array.from(
                            imageInput.files
                        );

                    preview.innerHTML = '';

                    newPrimaryIndex.value = '';

                    if (
                        files.length === 0
                    ) {
                        preview.innerHTML =
                            '<p>Ảnh mới sẽ hiển thị tại đây.</p>';

                        return;
                    }


                    files.forEach(
                        function (
                            file,
                            index
                        ) {

                            const item =
                                document.createElement(
                                    'div'
                                );

                            item.className =
                                'image-preview-item';


                            const img =
                                document.createElement(
                                    'img'
                                );

                            img.alt =
                                file.name;


                            const reader =
                                new FileReader();

                            reader.onload =
                                function (event) {
                                    img.src =
                                        event.target.result;
                                };

                            reader.readAsDataURL(
                                file
                            );


                            const info =
                                document.createElement(
                                    'div'
                                );

                            info.className =
                                'image-preview-info';


                            const fileName =
                                document.createElement(
                                    'small'
                                );

                            fileName.textContent =
                                file.name;


                            const button =
                                document.createElement(
                                    'button'
                                );

                            button.type =
                                'button';

                            button.className =
                                'btn btn-light image-primary-btn';

                            button.textContent =
                                'Chọn làm đại diện';


                            button.addEventListener(
                                'click',
                                function () {

                                    newPrimaryIndex.value =
                                        String(index);

                                    document
                                        .querySelectorAll(
                                            'input[name="primary_image_id"]'
                                        )
                                        .forEach(
                                            function (radio) {
                                                radio.checked =
                                                    false;
                                            }
                                        );

                                    preview
                                        .querySelectorAll(
                                            '.image-primary-btn'
                                        )
                                        .forEach(
                                            function (btn) {

                                                btn.className =
                                                    'btn btn-light image-primary-btn';

                                                btn.textContent =
                                                    'Chọn làm đại diện';
                                            }
                                        );

                                    button.className =
                                        'btn btn-primary image-primary-btn';

                                    button.textContent =
                                        'Ảnh đại diện';
                                }
                            );


                            info.appendChild(
                                fileName
                            );

                            info.appendChild(
                                button
                            );

                            item.appendChild(
                                img
                            );

                            item.appendChild(
                                info
                            );

                            preview.appendChild(
                                item
                            );
                        }
                    );
                }
            );
        }


        document
            .querySelectorAll(
                'input[name="primary_image_id"]'
            )
            .forEach(
                function (radio) {

                    radio.addEventListener(
                        'change',
                        function () {

                            if (
                                newPrimaryIndex
                            ) {
                                newPrimaryIndex.value =
                                    '';
                            }

                            if (preview) {
                                preview
                                    .querySelectorAll(
                                        '.image-primary-btn'
                                    )
                                    .forEach(
                                        function (btn) {

                                            btn.className =
                                                'btn btn-light image-primary-btn';

                                            btn.textContent =
                                                'Chọn làm đại diện';
                                        }
                                    );
                            }
                        }
                    );
                }
            );


        // =====================================
        // BIẾN THỂ
        // =====================================

        const container =
            document.getElementById(
                'editVariantContainer'
            );

        const addButton =
            document.getElementById(
                'addEditVariantBtn'
            );


        const sizeOptions =
            <?= json_encode(
                $sizes,
                JSON_UNESCAPED_UNICODE
            ) ?>;

        const colorOptions =
            <?= json_encode(
                $colors,
                JSON_UNESCAPED_UNICODE
            ) ?>;


        function buildOptions(
            items,
            placeholder
        ) {
            let html =
                '<option value="">'
                + placeholder
                + '</option>';

            items.forEach(
                function (item) {

                    html +=
                        '<option value="'
                        + String(item.id)
                        + '">'
                        + String(item.name)
                        + '</option>';
                }
            );

            return html;
        }


        if (
            container
            && addButton
        ) {

            addButton.addEventListener(
                'click',
                function () {

                    const row =
                        document.createElement(
                            'div'
                        );

                    row.className =
                        'variant-row edit-variant-row new-variant-row';

                    row.innerHTML = `
                        <input
                            type="hidden"
                            name="variant_id[]"
                            value="0"
                        >

                        <input
                            type="hidden"
                            name="delete_variant[]"
                            class="delete-variant-flag"
                            value="0"
                        >

                        <div class="variant-field">
                            <label>Size</label>
                            <select name="variant_size_id[]">
                                ${buildOptions(sizeOptions, 'Chọn size')}
                            </select>
                        </div>

                        <div class="variant-field">
                            <label>Màu sắc</label>
                            <select name="variant_color_id[]">
                                ${buildOptions(colorOptions, 'Chọn màu')}
                            </select>
                        </div>

                        <div class="variant-field">
                            <label>SKU</label>
                            <input
                                type="text"
                                name="variant_sku[]"
                                placeholder="VD: HD-M-DEN"
                            >
                        </div>

                        <div class="variant-field">
                            <label>Giá riêng</label>
                            <input
                                type="number"
                                name="variant_price[]"
                                min="0"
                                placeholder="Giá chung"
                            >
                        </div>

                        <div class="variant-field">
                            <label>Tồn kho</label>
                            <input
                                type="text"
                                value="0"
                                disabled
                            >
                        </div>

                        <div class="variant-action">
                            <button
                                type="button"
                                class="remove-edit-variant-btn"
                            >
                                Xóa
                            </button>
                        </div>
                    `;

                    container.appendChild(
                        row
                    );
                }
            );


            container.addEventListener(
                'click',
                function (event) {

                    const button =
                        event.target.closest(
                            '.remove-edit-variant-btn'
                        );

                    if (!button) {
                        return;
                    }

                    const row =
                        button.closest(
                            '.edit-variant-row'
                        );

                    if (!row) {
                        return;
                    }


                    const variantId =
                        row.querySelector(
                            'input[name="variant_id[]"]'
                        );

                    const deleteFlag =
                        row.querySelector(
                            '.delete-variant-flag'
                        );


                    if (
                        variantId
                        && Number(
                            variantId.value
                        ) > 0
                    ) {

                        if (deleteFlag) {
                            deleteFlag.value =
                                '1';
                        }

                        row.style.display =
                            'none';

                    } else {

                        row.remove();
                    }
                }
            );
        }
    }
);
</script>


<?php
require_once __DIR__ . '/../includes/footer.php';
?>
