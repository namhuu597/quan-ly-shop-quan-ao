<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('product.create');

$pageTitle = 'Thêm sản phẩm';
$currentPage = 'products';

$error = '';
$success = '';

$apiBaseUrl = 'http://localhost:5162/api';


// =========================================
// HÀM GỌI GET API
// =========================================
function productApiGet(string $url): array
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER =>
            apiAuthHeaders()
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


// =========================================
// LẤY DỮ LIỆU CHO FORM TỪ ASP.NET CORE
// =========================================
$categories = [];
$brands = [];
$sizes = [];
$colors = [];

$optionsResult = productApiGet(
    $apiBaseUrl . '/products/form-options'
);

if ($optionsResult['response'] === false) {

    $error =
        'Không thể kết nối tới ASP.NET Core API. '
        . $optionsResult['curlError'];

} elseif ($optionsResult['httpCode'] !== 200) {

    $error =
        'Không thể tải dữ liệu cho form sản phẩm. HTTP Status: '
        . $optionsResult['httpCode'];

} else {

    $optionsData = json_decode(
        $optionsResult['response'],
        true
    );

    if (is_array($optionsData)) {
        $categories = is_array($optionsData['categories'] ?? null)
            ? $optionsData['categories']
            : [];

        $brands = is_array($optionsData['brands'] ?? null)
            ? $optionsData['brands']
            : [];

        $sizes = is_array($optionsData['sizes'] ?? null)
            ? $optionsData['sizes']
            : [];

        $colors = is_array($optionsData['colors'] ?? null)
            ? $optionsData['colors']
            : [];
    } else {
        $error = 'Dữ liệu form từ API không hợp lệ.';
    }
}


// =========================================
// XỬ LÝ FORM THÊM SẢN PHẨM
// =========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrfToken();

    $productCode = trim($_POST['product_code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $categoryId = trim((string)($_POST['category_id'] ?? ''));
    $brandId = trim((string)($_POST['brand_id'] ?? ''));
    $basePrice = trim((string)($_POST['base_price'] ?? ''));
    $status = strtoupper(trim($_POST['status'] ?? 'ACTIVE'));
    $description = trim($_POST['description'] ?? '');

    $sizeIds = $_POST['variant_size_id'] ?? [];
    $colorIds = $_POST['variant_color_id'] ?? [];
    $skus = $_POST['variant_sku'] ?? [];
    $variantPrices = $_POST['variant_price'] ?? [];
    $stocks = $_POST['variant_stock'] ?? [];

    $primaryImageIndex = (int)($_POST['primary_image_index'] ?? 0);


    if (
        $productCode === ''
        || $name === ''
        || $categoryId === ''
        || $basePrice === ''
    ) {

        $error = 'Vui lòng nhập đầy đủ các trường bắt buộc.';

    } elseif (!is_numeric($basePrice) || (float)$basePrice < 0) {

        $error = 'Giá bán không hợp lệ.';

    } elseif (empty($sizeIds)) {

        $error = 'Sản phẩm phải có ít nhất một biến thể.';

    } else {

        $variants = [];
        $variantError = '';

        for ($i = 0; $i < count($sizeIds); $i++) {

            $sizeId = trim((string)($sizeIds[$i] ?? ''));
            $colorId = trim((string)($colorIds[$i] ?? ''));
            $sku = trim((string)($skus[$i] ?? ''));
            $variantPrice = trim((string)($variantPrices[$i] ?? ''));
            $stock = trim((string)($stocks[$i] ?? '0'));

            if (
                $sizeId === ''
                || $colorId === ''
                || $sku === ''
            ) {
                $variantError =
                    'Vui lòng nhập đầy đủ Size, Màu và SKU cho tất cả biến thể.';
                break;
            }

            if (!is_numeric($stock) || (int)$stock < 0) {
                $variantError = 'Tồn kho không được nhỏ hơn 0.';
                break;
            }

            if (
                $variantPrice !== ''
                && (!is_numeric($variantPrice) || (float)$variantPrice < 0)
            ) {
                $variantError = 'Giá riêng của biến thể không hợp lệ.';
                break;
            }

            $variants[] = [
                'sizeId' => (int)$sizeId,
                'colorId' => (int)$colorId,
                'sku' => $sku,
                'price' => $variantPrice !== ''
                    ? (float)$variantPrice
                    : null,
                'stockQuantity' => (int)$stock
            ];
        }

        if ($variantError !== '') {

            $error = $variantError;

        } else {

            $productPayload = [
                'productCode' => $productCode,
                'categoryId' => (int)$categoryId,
                'brandId' => $brandId !== ''
                    ? (int)$brandId
                    : null,
                'name' => $name,
                'description' => $description !== ''
                    ? $description
                    : null,
                'basePrice' => (float)$basePrice,
                'status' => $status,
                'variants' => $variants
            ];

            $postFields = [
                'productJson' => json_encode(
                    $productPayload,
                    JSON_UNESCAPED_UNICODE
                ),
                'primaryImageIndex' => (string)$primaryImageIndex
            ];


            // =========================================
            // ĐÍNH KÈM NHIỀU ẢNH ĐỂ ASP.NET XỬ LÝ
            // =========================================
            if (
                isset($_FILES['images'])
                && isset($_FILES['images']['tmp_name'])
                && is_array($_FILES['images']['tmp_name'])
            ) {
                foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {

                    $uploadError =
                        $_FILES['images']['error'][$index]
                        ?? UPLOAD_ERR_NO_FILE;

                    if ($uploadError === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    if ($uploadError !== UPLOAD_ERR_OK) {
                        $error = 'Có lỗi khi tải hình ảnh lên.';
                        break;
                    }

                    if (!is_uploaded_file($tmpName)) {
                        $error = 'Tệp hình ảnh tải lên không hợp lệ.';
                        break;
                    }

                    $originalName =
                        $_FILES['images']['name'][$index]
                        ?? ('image_' . $index);

                    $mimeType =
                        $_FILES['images']['type'][$index]
                        ?? 'application/octet-stream';

                    $postFields['images[' . $index . ']'] =
                        new CURLFile(
                            $tmpName,
                            $mimeType,
                            $originalName
                        );
                }
            }


            if ($error === '') {

                $ch = curl_init();

                curl_setopt_array($ch, [
                    CURLOPT_URL => $apiBaseUrl . '/products',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postFields,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_HTTPHEADER =>
                        apiAuthHeaders()
                ]);

                $response = curl_exec($ch);
                $httpCode = (int)curl_getinfo(
                    $ch,
                    CURLINFO_HTTP_CODE
                );
                $curlError = curl_error($ch);

                curl_close($ch);

                if ($response === false) {

                    $error =
                        'Không thể kết nối tới ASP.NET Core API. '
                        . $curlError;

                } else {

                    $data = json_decode($response, true);

                    if ($httpCode === 201) {

                        header(
                            'Location: /quanlyquanao/products/index.php?created=1'
                        );
                        exit;

                    }

                    $error =
                        $data['message']
                        ?? (
                            'Không thể thêm sản phẩm. HTTP Status: '
                            . $httpCode
                        );

                    if (
                        $httpCode === 500
                        && !empty($data['detail'])
                    ) {
                        $error .= ' Chi tiết: ' . $data['detail'];
                    }
                }
            }
        }
    }
}


// Load giao diện
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="main">

    <?php
    require_once __DIR__ . '/../includes/topbar.php';
    ?>


    <section class="content">
        <?php if ($error !== ''): ?>

    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>

<?php endif; ?>

        <div class="product-page-header">

            <div>
                <h1>Thêm sản phẩm</h1>

                <p>
                    Sản phẩm / Thêm mới
                </p>
            </div>

        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <?= csrfField() ?>

            <div class="create-product-grid">


                <!-- CỘT TRÁI -->

                <div class="card">

                    <h3>Thông tin cơ bản</h3>


                    <div class="form-grid">


                        <div class="form-group">

                            <label>
                                Mã sản phẩm
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="product_code"
                                placeholder="Ví dụ: SP001"
                                value="<?= htmlspecialchars($_POST['product_code'] ?? '') ?>"
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
                                placeholder="Nhập tên sản phẩm"
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Danh mục
                                <span class="required">*</span>
                            </label>

                            <select name="category_id">

                                <option value="">
                                    Chọn danh mục
                                </option>

                                <?php foreach ($categories as $category): ?>

                                    <option
                                        value="<?= $category['id'] ?>"
                                        <?= (string)($_POST['category_id'] ?? '') === (string)$category['id'] ? 'selected' : '' ?>
                                    >

                                        <?= htmlspecialchars(
                                            $category['name']
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Thương hiệu
                            </label>

                            <select name="brand_id">

                                <option value="">
                                    Không có thương hiệu
                                </option>

                                <?php foreach ($brands as $brand): ?>

                                    <option
                                        value="<?= $brand['id'] ?>"
                                        <?= (string)($_POST['brand_id'] ?? '') === (string)$brand['id'] ? 'selected' : '' ?>
                                    >

                                        <?= htmlspecialchars(
                                            $brand['name']
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
                                placeholder="Nhập giá bán"
                                value="<?= htmlspecialchars($_POST['base_price'] ?? '') ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Trạng thái
                            </label>

                            <select name="status">

                                <option value="ACTIVE" <?= ($_POST['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' ?>>
                                    Đang kinh doanh
                                </option>

                                <option value="INACTIVE" <?= ($_POST['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>
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
                                placeholder="Nhập mô tả sản phẩm..."
                            ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

                        </div>


                    </div>

                </div>



                <!-- CỘT PHẢI -->

                <div class="card">

                    <h3>Hình ảnh sản phẩm</h3>

                    <p class="form-help">
                        Có thể thêm nhiều ảnh cho một sản phẩm.
                        Ở bước sau chúng ta sẽ chọn 1 ảnh làm ảnh đại diện.
                    </p>


                    <div class="image-upload-box">

                        <div class="upload-icon">
                            ＋
                        </div>

                        <strong>
                            Chọn hình ảnh sản phẩm
                        </strong>

                        <span>
                            JPG, JPEG, PNG, WEBP
                        </span>

                        <input
                            type="file"
                            id="productImages"
                            name="images[]"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                        >

                    </div>


                    <div
                        id="imagePreviewArea"
                        class="image-preview-area"
>
                    <p id="imagePlaceholder">
                          Danh sách hình ảnh sẽ hiển thị tại đây.
                    </p>
</div>

<input
    type="hidden"
    name="primary_image_index"
    id="primaryImageIndex"
    value="0"
>

                </div>


            </div>



            <!-- VARIANT -->

            <div class="card product-variant-section">

                <div class="section-title">

                    <div>
                        <h3>Biến thể sản phẩm</h3>

                        <p>
                            Size, màu sắc và số lượng tồn kho
                            sẽ được thiết lập ở bước tiếp theo.
                        </p>
                    </div>

                </div>


                <div id="variantContainer">

    <div class="variant-row">

        <div class="variant-field">
            <label>Size</label>

            <select name="variant_size_id[]">
                <option value="">Chọn size</option>

                <?php foreach ($sizes as $size): ?>
                    <option value="<?= $size['id'] ?>">
                        <?= htmlspecialchars($size['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>


        <div class="variant-field">
            <label>Màu sắc</label>

            <select name="variant_color_id[]">
                <option value="">Chọn màu</option>

                <?php foreach ($colors as $color): ?>
                    <option value="<?= $color['id'] ?>">
                        <?= htmlspecialchars($color['name']) ?>
                    </option>
                <?php endforeach; ?>
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
                placeholder="Để trống = giá chung"
            >
        </div>


        




        <div class="variant-field">
            <label>Tồn kho ban đầu</label>

            <input
                type="number"
                name="variant_stock[]"
                min="0"
                value="0"
                placeholder="0"
            >
        </div>

        <div class="variant-action">
            <button
                type="button"
                class="remove-variant-btn"
                disabled
            >
                Xóa
            </button>
        </div>

    </div>

</div>


<button
    type="button"
    id="addVariantBtn"
    class="btn btn-light add-variant-btn"
>
    + Thêm biến thể
</button>   



            <!-- ACTION -->

            <div class="form-actions">

                <a
                    href="/quanlyquanao/products/index.php"
                    class="btn btn-light"
                >
                    Hủy
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lưu sản phẩm
                </button>

            </div>

        </form>

    </section>

</main>



<script>
document.addEventListener('DOMContentLoaded', function () {

    // =====================================================
    // THÊM / XÓA BIẾN THỂ
    // =====================================================
    const variantContainer = document.getElementById('variantContainer');
    const addVariantBtn = document.getElementById('addVariantBtn');

    function updateVariantRemoveButtons() {
        if (!variantContainer) return;

        const rows = variantContainer.querySelectorAll('.variant-row');

        rows.forEach(function (row) {
            const btn = row.querySelector('.remove-variant-btn');

            if (!btn) return;

            btn.disabled = rows.length <= 1;
        });
    }

    if (variantContainer && addVariantBtn) {

        addVariantBtn.addEventListener('click', function () {

            const firstRow = variantContainer.querySelector('.variant-row');

            if (!firstRow) {
                return;
            }

            const newRow = firstRow.cloneNode(true);

            newRow.querySelectorAll('select').forEach(function (select) {
                select.selectedIndex = 0;
            });

            newRow.querySelectorAll('input').forEach(function (input) {

                if (input.name === 'variant_stock[]') {
                    input.value = '0';
                } else {
                    input.value = '';
                }

            });

            const removeBtn =
                newRow.querySelector('.remove-variant-btn');

            if (removeBtn) {
                removeBtn.disabled = false;
            }

            variantContainer.appendChild(newRow);

            updateVariantRemoveButtons();
        });


        variantContainer.addEventListener('click', function (event) {

            const removeBtn =
                event.target.closest('.remove-variant-btn');

            if (!removeBtn) {
                return;
            }

            const rows =
                variantContainer.querySelectorAll('.variant-row');

            if (rows.length <= 1) {
                return;
            }

            const row =
                removeBtn.closest('.variant-row');

            if (row) {
                row.remove();
            }

            updateVariantRemoveButtons();
        });


        updateVariantRemoveButtons();
    }


    // =====================================================
    // CHỌN VÀ XEM TRƯỚC HÌNH ẢNH
    // =====================================================
    const productImages =
        document.getElementById('productImages');

    const imagePreviewArea =
        document.getElementById('imagePreviewArea');

    const imagePlaceholder =
        document.getElementById('imagePlaceholder');

    const primaryImageIndex =
        document.getElementById('primaryImageIndex');

    const uploadBox =
        document.querySelector('.image-upload-box');


    if (uploadBox && productImages) {

        uploadBox.addEventListener('click', function (event) {

            if (event.target === productImages) {
                return;
            }

            productImages.click();
        });

    }


    if (
        productImages
        && imagePreviewArea
        && primaryImageIndex
    ) {

        productImages.addEventListener('change', function () {

            const files = Array.from(productImages.files);

            imagePreviewArea.innerHTML = '';

            if (files.length === 0) {

                const placeholder = document.createElement('p');
                placeholder.id = 'imagePlaceholder';
                placeholder.textContent =
                    'Danh sách hình ảnh sẽ hiển thị tại đây.';

                imagePreviewArea.appendChild(placeholder);

                primaryImageIndex.value = '0';

                return;
            }


            files.forEach(function (file, index) {

                if (!file.type.startsWith('image/')) {
                    return;
                }

                const item = document.createElement('div');
                item.className = 'image-preview-item';

                const img = document.createElement('img');
                img.alt = file.name;

                const reader = new FileReader();

                reader.onload = function (event) {
                    img.src = event.target.result;
                };

                reader.readAsDataURL(file);


                const info = document.createElement('div');
                info.className = 'image-preview-info';

                const name = document.createElement('small');
                name.textContent = file.name;

                const primaryBtn = document.createElement('button');
                primaryBtn.type = 'button';
                primaryBtn.className =
                    index === 0
                        ? 'btn btn-primary image-primary-btn'
                        : 'btn btn-light image-primary-btn';

                primaryBtn.textContent =
                    index === 0
                        ? 'Ảnh đại diện'
                        : 'Chọn làm đại diện';

                primaryBtn.addEventListener('click', function () {

                    primaryImageIndex.value = String(index);

                    imagePreviewArea
                        .querySelectorAll('.image-primary-btn')
                        .forEach(function (btn) {
                            btn.className =
                                'btn btn-light image-primary-btn';
                            btn.textContent =
                                'Chọn làm đại diện';
                        });

                    primaryBtn.className =
                        'btn btn-primary image-primary-btn';

                    primaryBtn.textContent =
                        'Ảnh đại diện';
                });


                info.appendChild(name);
                info.appendChild(primaryBtn);

                item.appendChild(img);
                item.appendChild(info);

                imagePreviewArea.appendChild(item);
            });


            primaryImageIndex.value = '0';
        });

    }

});
</script>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>