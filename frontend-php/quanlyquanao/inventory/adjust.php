<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('inventory.adjust');

$pageTitle = 'Điều chỉnh tồn kho';
$currentPage = 'inventory';

$error = '';

$variantId =
    (int)(
        $_GET['variant_id']
        ?? 0
    );

if ($variantId <= 0) {
    die(
        'Biến thể sản phẩm không hợp lệ.'
    );
}

$apiBaseUrl =
    'http://localhost:5162/api';


// =========================================
// HÀM GỌI API
// =========================================

function callInventoryAdjustApi(
    string $url,
    string $method = 'GET',
    ?array $payload = null
): array {

    $ch =
        curl_init();

    $headers =
        apiAuthHeaders();

    $options = [
        CURLOPT_URL =>
            $url,

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_CONNECTTIMEOUT =>
            5,

        CURLOPT_TIMEOUT =>
            30
    ];


    if ($method !== 'GET') {
        $options[
            CURLOPT_CUSTOMREQUEST
        ] = $method;
    }


    if ($payload !== null) {

        $headers[] =
            'Content-Type: application/json';

        $options[
            CURLOPT_POSTFIELDS
        ] =
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
            );
    }


    $options[
        CURLOPT_HTTPHEADER
    ] = $headers;


    curl_setopt_array(
        $ch,
        $options
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
        'response' =>
            $response,

        'httpCode' =>
            $httpCode,

        'curlError' =>
            $curlError
    ];
}


// =========================================
// LOAD BIẾN THỂ TỪ ASP.NET API
// =========================================

$variantResult =
    callInventoryAdjustApi(
        $apiBaseUrl
        . '/inventory/variant/'
        . $variantId
    );


if (
    $variantResult['response']
    === false
) {
    die(
        'Không thể kết nối tới ASP.NET Core API. '
        . $variantResult['curlError']
    );
}


$variantData =
    json_decode(
        $variantResult['response'],
        true
    );


if ($variantResult['httpCode'] === 404) {
    die(
        $variantData['message']
        ?? 'Không tìm thấy sản phẩm hoặc biến thể đã ngừng sử dụng.'
    );
}


if (
    $variantResult['httpCode']
    !== 200
    || !is_array($variantData)
) {
    die(
        $variantData['message']
        ?? (
            'Không thể tải thông tin biến thể. HTTP Status: '
            . $variantResult['httpCode']
        )
    );
}


// Chuyển về key cũ để giữ nguyên giao diện
$variant = [
    'variant_id' =>
        $variantData['variantId']
        ?? 0,

    'sku' =>
        $variantData['sku']
        ?? '',

    'stock_quantity' =>
        $variantData['stockQuantity']
        ?? 0,

    'product_code' =>
        $variantData['productCode']
        ?? '',

    'product_name' =>
        $variantData['productName']
        ?? '',

    'size_name' =>
        $variantData['sizeName']
        ?? '',

    'color_name' =>
        $variantData['colorName']
        ?? ''
];


// =========================================
// XỬ LÝ ĐIỀU CHỈNH QUA API
// =========================================

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    verifyCsrfToken();

    $newStock =
        (int)(
            $_POST['new_stock']
            ?? -1
        );

    $reason =
        trim(
            $_POST['reason']
            ?? ''
        );


    if ($newStock < 0) {

        $error =
            'Tồn kho mới không được nhỏ hơn 0.';

    } elseif ($reason === '') {

        $error =
            'Vui lòng nhập lý do điều chỉnh.';

    } else {

        $payload = [
            'variantId' =>
                $variantId,

            'newStock' =>
                $newStock,

            'reason' =>
                $reason
        ];


        $result =
            callInventoryAdjustApi(
                $apiBaseUrl
                . '/inventory/adjust',
                'POST',
                $payload
            );


        if (
            $result['response']
            === false
        ) {

            $error =
                'Không thể kết nối tới ASP.NET Core API. '
                . $result['curlError'];

        } else {

            $data =
                json_decode(
                    $result['response'],
                    true
                );


            if (
                $result['httpCode']
                === 200
            ) {

                header(
                    'Location: /quanlyquanao/inventory/index.php?adjusted=1'
                );

                exit;

            } elseif (
                $result['httpCode']
                === 401
            ) {

                $error =
                    'Phiên đăng nhập đã hết hạn hoặc JWT không hợp lệ. '
                    . 'Vui lòng đăng xuất và đăng nhập lại.';

            } elseif (
                $result['httpCode']
                === 403
            ) {

                $error =
                    $data['message']
                    ?? 'Bạn không có quyền điều chỉnh tồn kho.';

            } else {

                $error =
                    $data['message']
                    ?? (
                        'Không thể điều chỉnh tồn kho. HTTP Status: '
                        . $result['httpCode']
                    );


                if (
                    !empty(
                        $data['detail']
                        ?? ''
                    )
                ) {
                    $error .=
                        ' - '
                        . $data['detail'];
                }
            }
        }
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


        <?php if ($error !== ''): ?>

            <div class="alert alert-danger">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <div class="product-page-header">

            <div>

                <h1>Điều chỉnh tồn kho</h1>

                <p>
                    Kho hàng /
                    <?= htmlspecialchars(
                        $variant['sku']
                    ) ?>
                </p>

            </div>


            <a
                href="/quanlyquanao/inventory/index.php"
                class="btn btn-light"
            >
                ← Quay lại
            </a>

        </div>


        <div class="card inventory-form-card">

            <div class="inventory-product-info">

                <h3>
                    <?= htmlspecialchars(
                        $variant['product_name']
                    ) ?>
                </h3>


                <p>
                    Mã SP:
                    <strong>
                        <?= htmlspecialchars(
                            $variant['product_code']
                        ) ?>
                    </strong>
                </p>


                <p>
                    SKU:
                    <strong>
                        <?= htmlspecialchars(
                            $variant['sku']
                        ) ?>
                    </strong>
                </p>


                <p>
                    Size:
                    <strong>
                        <?= htmlspecialchars(
                            $variant['size_name']
                        ) ?>
                    </strong>
                </p>


                <p>
                    Màu:
                    <strong>
                        <?= htmlspecialchars(
                            $variant['color_name']
                        ) ?>
                    </strong>
                </p>

            </div>


            <form method="POST">

                <?= csrfField() ?>

                <div class="form-group">

                    <label>
                        Tồn kho hiện tại
                    </label>

                    <input
                        type="number"
                        value="<?= (int)$variant['stock_quantity'] ?>"
                        readonly
                    >

                </div>


                <div
                    class="form-group"
                    style="margin-top:18px;"
                >

                    <label>
                        Tồn kho thực tế
                        <span class="required">*</span>
                    </label>

                    <input
                        type="number"
                        name="new_stock"
                        min="0"
                        value="<?= htmlspecialchars(
                            $_POST['new_stock']
                            ?? $variant['stock_quantity']
                        ) ?>"
                        required
                    >

                </div>


                <div
                    class="form-group"
                    style="margin-top:18px;"
                >

                    <label>
                        Lý do điều chỉnh
                        <span class="required">*</span>
                    </label>

                    <textarea
                        name="reason"
                        rows="4"
                        required
                        placeholder="Ví dụ: Kiểm kê thực tế thiếu 4 sản phẩm..."
                    ><?= htmlspecialchars(
                        $_POST['reason']
                        ?? ''
                    ) ?></textarea>

                </div>


                <div class="form-actions">

                    <a
                        href="/quanlyquanao/inventory/index.php"
                        class="btn btn-light"
                    >
                        Hủy
                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Xác nhận điều chỉnh
                    </button>

                </div>

            </form>

        </div>

    </section>

</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
