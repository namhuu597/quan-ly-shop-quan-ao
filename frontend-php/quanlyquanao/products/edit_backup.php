<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requirePermission('product.update');

$pageTitle = 'Sửa sản phẩm';
$currentPage = 'products';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Sản phẩm không hợp lệ.');
}


// ==========================
// LẤY PRODUCT HIỆN TẠI
// ==========================

$stmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die('Không tìm thấy sản phẩm.');
}


// ==========================
// LẤY DANH MỤC
// ==========================

$categoryStmt = $pdo->query("
    SELECT id, name
    FROM categories
    WHERE is_active = 1
    ORDER BY name
");

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================
// LẤY THƯƠNG HIỆU
// ==========================

$brandStmt = $pdo->query("
    SELECT id, name
    FROM brands
    WHERE is_active = 1
    ORDER BY name
");

$brands = $brandStmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================
// LẤY SIZE
// ==========================

$sizeStmt = $pdo->query("
    SELECT id, name
    FROM sizes
    WHERE is_active = 1
    ORDER BY id
");

$sizes = $sizeStmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================
// LẤY MÀU
// ==========================

$colorStmt = $pdo->query("
    SELECT id, name
    FROM colors
    WHERE is_active = 1
    ORDER BY name
");

$colors = $colorStmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================
// LẤY VARIANT
// ==========================

$variantStmt = $pdo->prepare("
    SELECT *
    FROM product_variants
    WHERE product_id = ?
    ORDER BY id
");

$variantStmt->execute([$id]);

$variants = $variantStmt->fetchAll(PDO::FETCH_ASSOC);


$error = '';


// ==========================
// UPDATE
// ==========================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $productCode = trim($_POST['product_code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $categoryId = $_POST['category_id'] ?? '';
    $brandId = $_POST['brand_id'] ?? null;
    $basePrice = $_POST['base_price'] ?? '';
    $status = $_POST['status'] ?? 'ACTIVE';
    $description = trim($_POST['description'] ?? '');

    $variantIds = $_POST['variant_id'] ?? [];
    $sizeIds = $_POST['variant_size_id'] ?? [];
    $colorIds = $_POST['variant_color_id'] ?? [];
    $skus = $_POST['variant_sku'] ?? [];
    $variantPrices = $_POST['variant_price'] ?? [];
    $stocks = $_POST['variant_stock'] ?? [];


    if (
        $productCode === ''
        || $name === ''
        || $categoryId === ''
        || $basePrice === ''
    ) {

        $error = 'Vui lòng nhập đầy đủ các trường bắt buộc.';

    } else {

        try {

            $pdo->beginTransaction();


            // UPDATE PRODUCT

            $updateProduct = $pdo->prepare("
                UPDATE products
                SET
                    product_code = ?,
                    category_id = ?,
                    brand_id = ?,
                    name = ?,
                    description = ?,
                    base_price = ?,
                    status = ?
                WHERE id = ?
            ");

            $updateProduct->execute([
                $productCode,
                $categoryId,
                $brandId !== '' ? $brandId : null,
                $name,
                $description !== '' ? $description : null,
                $basePrice,
                $status,
                $id
            ]);


            // UPDATE VARIANT

            $updateVariant = $pdo->prepare("
                UPDATE product_variants
                SET
                    size_id = ?,
                    color_id = ?,
                    sku = ?,
                    price = ?,
                    stock_quantity = ?
                WHERE id = ?
                AND product_id = ?
            ");


            for ($i = 0; $i < count($variantIds); $i++) {

                $variantId = $variantIds[$i] ?? 0;
                $sizeId = $sizeIds[$i] ?? '';
                $colorId = $colorIds[$i] ?? '';
                $sku = trim($skus[$i] ?? '');
                $variantPrice = $variantPrices[$i] ?? '';
                $stock = $stocks[$i] ?? 0;


                if (
                    !$variantId
                    || $sizeId === ''
                    || $colorId === ''
                    || $sku === ''
                ) {
                    throw new Exception(
                        'Thông tin biến thể không hợp lệ.'
                    );
                }


                $updateVariant->execute([
                    $sizeId,
                    $colorId,
                    $sku,
                    $variantPrice !== '' ? $variantPrice : null,
                    $stock,
                    $variantId,
                    $id
                ]);
            }


            $pdo->commit();


            header(
                'Location: /quanlyquanao/products/view.php?id='
                . $id
                . '&updated=1'
            );

            exit;


        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $e->getMessage();
        }
    }
}


require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="main">

    <header class="topbar">

        <div>
            <strong>Quản lý sản phẩm</strong>
        </div>

        <div class="topbar-right">

            <div class="top-user">

                <div class="avatar">
                    <?= strtoupper(
                        substr($_SESSION['user']['full_name'], 0, 1)
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
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>
                <h1>Sửa sản phẩm</h1>

                <p>
                    Sản phẩm /
                    <?= htmlspecialchars($product['product_code']) ?>
                    / Sửa
                </p>
            </div>

        </div>


        <form method="POST">

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
                            value="<?= htmlspecialchars($product['product_code']) ?>"
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
                            value="<?= htmlspecialchars($product['name']) ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Danh mục
                            <span class="required">*</span>
                        </label>

                        <select name="category_id">

                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= $category['id'] ?>"
                                    <?= $product['category_id'] == $category['id']
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>Thương hiệu</label>

                        <select name="brand_id">

                            <option value="">
                                Không có thương hiệu
                            </option>

                            <?php foreach ($brands as $brand): ?>

                                <option
                                    value="<?= $brand['id'] ?>"
                                    <?= $product['brand_id'] == $brand['id']
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= htmlspecialchars($brand['name']) ?>
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
                            value="<?= htmlspecialchars($product['base_price']) ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>Trạng thái</label>

                        <select name="status">

                            <option
                                value="ACTIVE"
                                <?= $product['status'] === 'ACTIVE'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Đang kinh doanh
                            </option>

                            <option
                                value="INACTIVE"
                                <?= $product['status'] === 'INACTIVE'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Ngừng kinh doanh
                            </option>

                        </select>

                    </div>


                    <div class="form-group full-width">

                        <label>Mô tả sản phẩm</label>

                        <textarea
                            name="description"
                            rows="6"
                        ><?= htmlspecialchars($product['description'] ?? '') ?></textarea>

                    </div>

                </div>

            </div>


            <div
                class="card product-variant-section"
                style="margin-top:20px;"
            >

                <h3>Biến thể sản phẩm</h3>


                <?php foreach ($variants as $variant): ?>

                    <div class="variant-row">

                        <input
                            type="hidden"
                            name="variant_id[]"
                            value="<?= $variant['id'] ?>"
                        >


                        <div class="variant-field">

                            <label>Size</label>

                            <select name="variant_size_id[]">

                                <?php foreach ($sizes as $size): ?>

                                    <option
                                        value="<?= $size['id'] ?>"
                                        <?= $variant['size_id'] == $size['id']
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= htmlspecialchars($size['name']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="variant-field">

                            <label>Màu sắc</label>

                            <select name="variant_color_id[]">

                                <?php foreach ($colors as $color): ?>

                                    <option
                                        value="<?= $color['id'] ?>"
                                        <?= $variant['color_id'] == $color['id']
                                            ? 'selected'
                                            : '' ?>
                                    >
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
                                value="<?= htmlspecialchars($variant['sku']) ?>"
                            >

                        </div>


                        <div class="variant-field">

                            <label>Giá riêng</label>

                            <input
                                type="number"
                                name="variant_price[]"
                                min="0"
                                value="<?= htmlspecialchars($variant['price'] ?? '') ?>"
                                placeholder="Giá chung"
                            >

                        </div>


                        <div class="variant-field">

                            <label>Tồn kho</label>

                            <input
                                type="number"
                                name="variant_stock[]"
                                min="0"
                                value="<?= $variant['stock_quantity'] ?>"
                            >

                        </div>

                    </div>

                <?php endforeach; ?>

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


<?php
require_once __DIR__ . '/../includes/footer.php';
?>