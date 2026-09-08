<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('inventory.import');

$pageTitle = 'Nhập kho';
$currentPage = 'inventory';

$error = '';

$apiBaseUrl =
    'http://localhost:5162/api';


// =========================================
// HÀM GỌI API
// =========================================

function callInventoryApi(
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
// LOAD BIẾN THỂ
// =========================================

$optionsResult =
    callInventoryApi(
        $apiBaseUrl
        . '/inventory/import-options'
    );


if (
    $optionsResult['response']
    === false
) {
    die(
        'Không thể kết nối tới ASP.NET Core API. '
        . $optionsResult['curlError']
    );
}


$optionsData =
    json_decode(
        $optionsResult['response'],
        true
    );


if (
    $optionsResult['httpCode']
    !== 200
    || !is_array($optionsData)
) {
    die(
        $optionsData['message']
        ?? (
            'Không thể tải danh sách biến thể. HTTP Status: '
            . $optionsResult['httpCode']
        )
    );
}


$variants = [];

foreach (
    $optionsData
    as $variant
) {

    $variants[] = [
        'variant_id' =>
            $variant['variantId']
            ?? 0,

        'sku' =>
            $variant['sku']
            ?? '',

        'stock_quantity' =>
            $variant['stockQuantity']
            ?? 0,

        'product_code' =>
            $variant['productCode']
            ?? '',

        'product_name' =>
            $variant['productName']
            ?? '',

        'size_name' =>
            $variant['sizeName']
            ?? '',

        'color_name' =>
            $variant['colorName']
            ?? ''
    ];
}


// =========================================
// XỬ LÝ NHẬP KHO QUA API
// =========================================

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    verifyCsrfToken();

    $variantId =
        (int)(
            $_POST['variant_id']
            ?? 0
        );

    $quantity =
        (int)(
            $_POST['quantity']
            ?? 0
        );

    $reason =
        trim(
            $_POST['reason']
            ?? ''
        );


    if ($variantId <= 0) {

        $error =
            'Vui lòng chọn sản phẩm.';

    } elseif ($quantity <= 0) {

        $error =
            'Số lượng nhập phải lớn hơn 0.';

    } elseif ($reason === '') {

        $error =
            'Vui lòng nhập lý do nhập kho.';

    } else {

        $payload = [
            'variantId' =>
                $variantId,

            'quantity' =>
                $quantity,

            'reason' =>
                $reason,

            'createdBy' =>
                (int)$_SESSION['user']['id']
        ];


        $result =
            callInventoryApi(
                $apiBaseUrl
                . '/inventory/import',
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
                    'Location: /quanlyquanao/inventory/index.php?imported=1'
                );

                exit;

            } else {

                $error =
                    $data['message']
                    ?? (
                        'Không thể nhập kho. HTTP Status: '
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

<style>
.inventory-search-wrap {
    position: relative;
}

.inventory-search-wrap input {
    width: 100%;
    min-height: 46px;
    padding: 0 42px 0 14px;
}

.inventory-search-icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    opacity: .55;
    pointer-events: none;
}

.inventory-search-help {
    margin-top: 7px;
    color: #8a93a5;
    font-size: 12px;
}
</style>

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

                <h1>Nhập kho</h1>

                <p>
                    Kho hàng / Nhập kho
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

            <form method="POST">

                <?= csrfField() ?>

                <div class="form-group">

                    <label>
                        Tìm sản phẩm
                    </label>

                    <div class="inventory-search-wrap">

                        <input
                            type="text"
                            id="variantSearch"
                            autocomplete="off"
                            placeholder="Tìm theo tên sản phẩm, mã sản phẩm hoặc SKU..."
                        >

                        <span class="inventory-search-icon">
                            🔎
                        </span>

                    </div>

                    <div class="inventory-search-help">
                        Có thể tìm thêm theo Size và Màu sắc.
                    </div>

                </div>


                <div
                    class="form-group"
                    style="margin-top:18px;"
                >

                    <label>
                        Sản phẩm / Biến thể
                        <span class="required">*</span>
                    </label>


                    <select
                        name="variant_id"
                        id="variantSelect"
                        required
                    >

                        <option
                            value=""
                            data-search=""
                        >
                            -- Chọn sản phẩm --
                        </option>


                        <?php foreach (
                            $variants as $variant
                        ): ?>

                            <?php
                            $searchText =
                                $variant['product_code']
                                . ' '
                                . $variant['product_name']
                                . ' '
                                . $variant['sku']
                                . ' '
                                . $variant['size_name']
                                . ' '
                                . $variant['color_name'];
                            ?>

                            <option
                                value="<?= $variant['variant_id'] ?>"
                                data-search="<?= htmlspecialchars(
                                    mb_strtolower($searchText, 'UTF-8')
                                ) ?>"
                                <?= (
                                    (int)($_POST['variant_id'] ?? 0)
                                    ===
                                    (int)$variant['variant_id']
                                ) ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars(
                                    $variant['product_code']
                                    . ' - '
                                    . $variant['product_name']
                                    . ' | Size: '
                                    . $variant['size_name']
                                    . ' | Màu: '
                                    . $variant['color_name']
                                    . ' | SKU: '
                                    . $variant['sku']
                                    . ' | Tồn: '
                                    . $variant['stock_quantity']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div
                    class="form-group"
                    style="margin-top:18px;"
                >

                    <label>
                        Số lượng nhập
                        <span class="required">*</span>
                    </label>


                    <input
                        type="number"
                        name="quantity"
                        min="1"
                        value="<?= htmlspecialchars(
                            $_POST['quantity'] ?? '1'
                        ) ?>"
                        required
                    >

                </div>


                <div
                    class="form-group"
                    style="margin-top:18px;"
                >

                    <label>
                        Lý do nhập kho
                        <span class="required">*</span>
                    </label>


                    <textarea
                        name="reason"
                        rows="4"
                        required
                        placeholder="Ví dụ: Nhập hàng từ nhà cung cấp..."
                    ><?= htmlspecialchars(
                        $_POST['reason'] ?? ''
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
                        Xác nhận nhập kho
                    </button>

                </div>

            </form>

        </div>

    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const searchInput =
        document.getElementById('variantSearch');

    const select =
        document.getElementById('variantSelect');

    if (!searchInput || !select) {
        return;
    }


    const allOptions =
        Array.from(select.options);


    function normalize(value) {

        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }


    function filterOptions() {

        const keyword =
            normalize(searchInput.value);

        const currentValue =
            select.value;


        select.innerHTML = '';


        allOptions.forEach(function (option, index) {

            if (index === 0) {
                select.appendChild(option);
                return;
            }

            const searchText =
                normalize(
                    option.dataset.search
                    || option.textContent
                );


            if (
                keyword === ''
                || searchText.includes(keyword)
            ) {
                select.appendChild(option);
            }
        });


        const stillExists =
            Array.from(select.options)
                .some(function (option) {
                    return option.value === currentValue;
                });


        if (stillExists) {
            select.value = currentValue;
        } else {
            select.value = '';
        }
    }


    searchInput.addEventListener(
        'input',
        filterOptions
    );

});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
