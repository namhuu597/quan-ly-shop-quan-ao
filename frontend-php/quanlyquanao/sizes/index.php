<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('size.read');

$pageTitle = 'Quản lý Size';
$currentPage = 'sizes';

$apiUrl =
    'http://localhost:5162/api/sizes';

$sizes = [];
$apiError = '';

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


if ($response === false) {

    $apiError =
        'Không thể kết nối tới ASP.NET Core API. '
        . $curlError;

} elseif ($httpCode !== 200) {

    $apiError =
        'Không thể tải danh sách Size. '
        . 'HTTP Status: '
        . $httpCode;

} else {

    $data =
        json_decode(
            $response,
            true
        );

    if (is_array($data)) {

        $sizes =
            $data;

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
                <?= htmlspecialchars($apiError) ?>
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['created'])
            && $_GET['created'] === '1'
        ): ?>

            <div class="alert alert-success">
                Thêm Size thành công!
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['updated'])
            && $_GET['updated'] === '1'
        ): ?>

            <div class="alert alert-success">
                Cập nhật Size thành công!
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['status_changed'])
            && $_GET['status_changed'] === '1'
        ): ?>

            <div class="alert alert-success">
                Cập nhật trạng thái Size thành công!
            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>Danh sách Size</h1>

                <p>
                    Size / Danh sách
                </p>

            </div>


            <?php if (hasPermission('size.create')): ?>

                <a
                    href="/quanlyquanao/sizes/create.php"
                    class="btn btn-primary"
                >
                    + Thêm Size
                </a>

            <?php endif; ?>

        </div>


        <div class="card">

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Tên Size</th>
                            <th>Số biến thể đang dùng</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($sizes)): ?>

                        <tr>
                            <td
                                colspan="5"
                                class="empty-data"
                            >
                                Chưa có Size nào.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($sizes as $index => $size): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $size['name']
                                        ) ?>
                                    </strong>

                                </td>


                                <td>
                                    <?= $size['variantCount'] ?>
                                </td>


                                <td>

                                    <?php if ($size['isActive']): ?>

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
                                            hasPermission('size.update')
                                        ): ?>

                                            <a
                                                href="/quanlyquanao/sizes/edit.php?id=<?= $size['id'] ?>"
                                                class="action-btn"
                                                title="Sửa"
                                            >
                                                ✏️
                                            </a>

                                        <?php endif; ?>


                                        <?php if (
                                            hasPermission('size.delete')
                                        ): ?>

                                            <form
                                                method="POST"
                                                action="/quanlyquanao/sizes/toggle-status.php"
                                                onsubmit="return confirm(
                                                    'Bạn có chắc muốn thay đổi trạng thái Size này?'
                                                );"
                                            >

                                                <?= csrfField() ?>

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= $size['id'] ?>"
                                                >

                                                <?php if ($size['isActive']): ?>

                                                    <button
                                                        type="submit"
                                                        class="action-btn action-danger"
                                                    >
                                                        ⛔
                                                    </button>

                                                <?php else: ?>

                                                    <button
                                                        type="submit"
                                                        class="action-btn action-success"
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
