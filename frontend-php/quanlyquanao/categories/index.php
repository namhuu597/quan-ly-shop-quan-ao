<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('category.read');

$pageTitle = 'Danh mục sản phẩm';
$currentPage = 'categories';

$keyword = trim($_GET['keyword'] ?? '');

$apiUrl = 'http://localhost:5162/api/categories';

$categories = [];
$apiError = '';

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER =>
        apiAuthHeaders()
]);

$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    $apiError =
        'Không thể kết nối tới ASP.NET Core API. '
        . 'Hãy kiểm tra API có đang chạy tại localhost:5162 hay không.';
} elseif ($httpCode !== 200) {
    $apiError =
        'Không thể tải danh mục từ API. HTTP Status: '
        . $httpCode;
} else {
    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        $apiError =
            'Dữ liệu API trả về không đúng định dạng JSON.';
    } else {
        $categories = $decoded;
    }
}

curl_close($ch);

if ($apiError === '' && $keyword !== '') {
    $categories = array_values(
        array_filter(
            $categories,
            function ($category) use ($keyword) {
                $name = (string)($category['name'] ?? '');

                return mb_stripos(
                    $name,
                    $keyword,
                    0,
                    'UTF-8'
                ) !== false;
            }
        )
    );
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
            isset($_GET['created'])
            && $_GET['created'] === '1'
        ): ?>
            <div class="alert alert-success">
                Thêm danh mục thành công!
            </div>
        <?php endif; ?>

        <?php if (
            isset($_GET['updated'])
            && $_GET['updated'] === '1'
        ): ?>
            <div class="alert alert-success">
                Cập nhật danh mục thành công!
            </div>
        <?php endif; ?>

        <?php if (
            isset($_GET['status_changed'])
            && $_GET['status_changed'] === '1'
        ): ?>
            <div class="alert alert-success">
                Cập nhật trạng thái danh mục thành công!
            </div>
        <?php endif; ?>

        <div class="product-page-header">

            <div>
                <h1>Danh mục sản phẩm</h1>

                <p>
                    Danh mục / Danh sách
                </p>
            </div>

            <?php if (hasPermission('category.create')): ?>
                <a
                    href="/quanlyquanao/categories/create.php"
                    class="btn btn-primary"
                >
                    + Thêm danh mục
                </a>
            <?php endif; ?>

        </div>

        <div class="card product-filter">

            <form
                method="GET"
                class="category-filter-form"
            >
                <input
                    type="text"
                    name="keyword"
                    placeholder="Tìm tên danh mục..."
                    value="<?= htmlspecialchars($keyword) ?>"
                >

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tìm kiếm
                </button>

                <a
                    href="/quanlyquanao/categories/index.php"
                    class="btn btn-light"
                >
                    Đặt lại
                </a>
            </form>

        </div>

        <div class="card">

            <div class="table-responsive">

                <table class="table">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tên danh mục</th>
                            <th>Mô tả</th>
                            <th>Số sản phẩm</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (
                        $apiError === ''
                        && empty($categories)
                    ): ?>

                        <tr>
                            <td
                                colspan="6"
                                class="empty-data"
                            >
                                Chưa có danh mục nào.
                            </td>
                        </tr>

                    <?php elseif ($apiError === ''): ?>

                        <?php foreach (
                            $categories as $index => $category
                        ): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $category['name'] ?? ''
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        !empty($category['description'])
                                            ? $category['description']
                                            : '—'
                                    ) ?>
                                </td>

                                <td>
                                    <?= (int)(
                                        $category['productCount'] ?? 0
                                    ) ?>
                                </td>

                                <td>

                                    <?php if (
                                        !empty($category['isActive'])
                                    ): ?>

                                        <span class="status status-success">
                                            Đang sử dụng
                                        </span>

                                    <?php else: ?>

                                        <span class="status status-danger">
                                            Ngừng sử dụng
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <div class="table-actions">

                                        <?php if (
                                            hasPermission('category.update')
                                        ): ?>

                                            <a
                                                href="/quanlyquanao/categories/edit.php?id=<?= (int)($category['id'] ?? 0) ?>"
                                                class="action-btn"
                                                title="Sửa"
                                            >
                                                ✏️
                                            </a>

                                        <?php endif; ?>

                                        <?php if (
                                            hasPermission('category.delete')
                                        ): ?>

                                            <form
                                                method="POST"
                                                action="/quanlyquanao/categories/toggle-status.php"
                                                onsubmit="return confirm(
                                                    'Bạn có chắc muốn thay đổi trạng thái danh mục này?'
                                                );"
                                            >

                                                <?= csrfField() ?>

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)($category['id'] ?? 0) ?>"
                                                >

                                                <?php if (
                                                    !empty($category['isActive'])
                                                ): ?>

                                                    <button
                                                        type="submit"
                                                        class="action-btn action-danger"
                                                        title="Ngừng sử dụng"
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
