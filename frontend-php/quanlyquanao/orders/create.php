<?php

require_once __DIR__ . '/../includes/auth.php';

requirePermission('order.create');

$pageTitle = 'Tạo đơn hàng';
$currentPage = 'orders';

$error = '';

$apiBaseUrl =
    'http://localhost:5162/api';


// =========================================
// HÀM GỌI API
// =========================================

function callOrdersApi(
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
    ] =
        $headers;


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
// LOAD KHÁCH HÀNG + BIẾN THỂ
// =========================================

$optionsResult =
    callOrdersApi(
        $apiBaseUrl
        . '/orders/form-options'
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
            'Không thể tải dữ liệu tạo đơn hàng. HTTP Status: '
            . $optionsResult['httpCode']
        )
    );
}


// Chuyển dữ liệu API về đúng key cũ
// để giữ nguyên giao diện + JavaScript hiện tại.
$customers = [];

foreach (
    $optionsData['customers']
        ?? []
    as $customer
) {
    $customers[] = [
        'id' =>
            $customer['id']
            ?? 0,

        'customer_code' =>
            $customer['customerCode']
            ?? '',

        'full_name' =>
            $customer['fullName']
            ?? '',

        'phone' =>
            $customer['phone']
            ?? '',

        'email' =>
            $customer['email']
            ?? '',

        'address' =>
            $customer['address']
            ?? ''
    ];
}


$variants = [];

foreach (
    $optionsData['variants']
        ?? []
    as $variant
) {
    $variants[] = [
        'variant_id' =>
            $variant['variantId']
            ?? 0,

        'sku' =>
            $variant['sku']
            ?? '',

        'variant_price' =>
            $variant['variantPrice']
            ?? null,

        'stock_quantity' =>
            $variant['stockQuantity']
            ?? 0,

        'product_id' =>
            $variant['productId']
            ?? 0,

        'product_code' =>
            $variant['productCode']
            ?? '',

        'product_name' =>
            $variant['productName']
            ?? '',

        'base_price' =>
            $variant['basePrice']
            ?? 0,

        'size_name' =>
            $variant['sizeName']
            ?? '',

        'color_name' =>
            $variant['colorName']
            ?? ''
    ];
}


// =========================================
// TẠO ĐƠN HÀNG QUA ASP.NET API
// =========================================

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    verifyCsrfToken();

    $customerIdRaw =
        trim(
            $_POST['customer_id']
            ?? ''
        );

    $customerId =
        $customerIdRaw !== ''
            ? (int)$customerIdRaw
            : null;

    $guestName =
        trim(
            $_POST['guest_name']
            ?? ''
        );

    $guestPhone =
        trim(
            $_POST['guest_phone']
            ?? ''
        );

    $guestEmail =
        trim(
            $_POST['guest_email']
            ?? ''
        );

    $guestAddress =
        trim(
            $_POST['guest_address']
            ?? ''
        );

    $variantIds =
        $_POST['variant_id']
        ?? [];

    $quantities =
        $_POST['quantity']
        ?? [];

    $note =
        trim(
            $_POST['note']
            ?? ''
        );

    $depositAmountRaw =
        trim(
            $_POST['deposit_amount']
            ?? '0'
        );

    $depositAmount =
        is_numeric(
            $depositAmountRaw
        )
            ? (float)$depositAmountRaw
            : -1;


    if (
        $customerId === null
        && $guestName === ''
    ) {

        $error =
            'Vui lòng nhập họ tên khách vãng lai.';

    } elseif (
        $customerId === null
        && $guestPhone !== ''
        && !preg_match(
            '/^[0-9+\s.-]{8,20}$/',
            $guestPhone
        )
    ) {

        $error =
            'Số điện thoại khách vãng lai không hợp lệ.';

    } elseif (
        $customerId === null
        && $guestEmail !== ''
        && !filter_var(
            $guestEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Email khách vãng lai không hợp lệ.';

    } elseif (
        !is_array($variantIds)
        || !is_array($quantities)
        || count($variantIds) === 0
    ) {

        $error =
            'Vui lòng chọn ít nhất một sản phẩm.';

    } elseif ($depositAmount < 0) {

        $error =
            'Tiền cọc không được nhỏ hơn 0.';

    } else {

        $items = [];

        foreach (
            $variantIds
            as $index => $variantIdRaw
        ) {
            $variantId =
                (int)$variantIdRaw;

            $quantity =
                (int)(
                    $quantities[$index]
                    ?? 0
                );


            if ($variantId <= 0) {
                continue;
            }


            if ($quantity <= 0) {
                $error =
                    'Số lượng sản phẩm phải lớn hơn 0.';

                break;
            }


            $items[] = [
                'variantId' =>
                    $variantId,

                'quantity' =>
                    $quantity
            ];
        }


        if (
            $error === ''
            && empty($items)
        ) {
            $error =
                'Vui lòng chọn ít nhất một sản phẩm.';
        }


        if ($error === '') {

            $payload = [
                'customerId' =>
                    $customerId,

                'guestName' =>
                    $customerId === null
                        ? $guestName
                        : null,

                'guestPhone' =>
                    $customerId === null
                    && $guestPhone !== ''
                        ? $guestPhone
                        : null,

                'guestEmail' =>
                    $customerId === null
                    && $guestEmail !== ''
                        ? $guestEmail
                        : null,

                'guestAddress' =>
                    $customerId === null
                    && $guestAddress !== ''
                        ? $guestAddress
                        : null,

                'depositAmount' =>
                    $depositAmount,

                'note' =>
                    $note !== ''
                        ? $note
                        : null,

                'items' =>
                    $items
            ];


            $createResult =
                callOrdersApi(
                    $apiBaseUrl
                    . '/orders',
                    'POST',
                    $payload
                );


            if (
                $createResult['response']
                === false
            ) {

                $error =
                    'Không thể kết nối tới ASP.NET Core API. '
                    . $createResult['curlError'];

            } else {

                $responseData =
                    json_decode(
                        $createResult['response'],
                        true
                    );


                if (
                    $createResult['httpCode']
                    === 201
                ) {

                    $newOrderId =
                        (int)(
                            $responseData['id']
                            ?? 0
                        );


                    header(
                        'Location: /quanlyquanao/orders/view.php?id='
                        . $newOrderId
                        . '&created=1'
                    );

                    exit;

                } else {

                    $error =
                        $responseData['message']
                        ?? (
                            'Không thể tạo đơn hàng. HTTP Status: '
                            . $createResult['httpCode']
                        );


                    if (
                        !empty(
                            $responseData['detail']
                            ?? ''
                        )
                    ) {
                        $error .=
                            ' - '
                            . $responseData['detail'];
                    }
                }
            }
        }
    }
}


require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>
/* ===== THÔNG TIN KHÁCH HÀNG - ĐỒNG BỘ VỚI DANH SÁCH SẢN PHẨM ===== */

.customer-card-unified {
    padding: 22px 24px;
}

.customer-card-unified .section-title {
    margin-bottom: 18px;
}

.customer-card-unified .section-title h3 {
    margin: 0;
}

.customer-card-unified .section-title p {
    margin: 6px 0 0;
    color: #8a93a5;
    font-size: 13px;
}

.customer-mode-actions {
    display: flex;
    gap: 10px;
}

.customer-mode-actions .btn.active {
    background: #6557f5;
    color: #fff;
    border-color: #6557f5;
}

.customer-search-row {
    position: relative;
    margin-bottom: 16px;
}

.customer-search-row label,
.customer-info-field label {
    display: block;
    margin-bottom: 7px;
    color: #4c5566;
    font-size: 13px;
    font-weight: 700;
}

.customer-search-input-wrap {
    position: relative;
}

.customer-search-input-wrap input {
    width: 100%;
    min-height: 46px;
    padding: 0 42px 0 14px;
    border: 1px solid #dfe3eb;
    border-radius: 9px;
    background: #fff;
    color: #1f2937;
    outline: none;
}

.customer-search-input-wrap input:focus {
    border-color: #bfc5d2;
    box-shadow: none;
}

.customer-search-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    opacity: .55;
    pointer-events: none;
}

.customer-suggestions {
    position: absolute;
    z-index: 50;
    left: 0;
    right: 0;
    top: calc(100% + 6px);
    max-height: 270px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #dfe3eb;
    border-radius: 10px;
    box-shadow: 0 14px 30px rgba(20, 31, 56, .12);
}

.customer-suggestion-item {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    padding: 12px 14px;
    cursor: pointer;
    border-bottom: 1px solid #eef1f5;
}

.customer-suggestion-item:last-child {
    border-bottom: 0;
}

.customer-suggestion-item:hover {
    background: #f7f6ff;
}

.customer-suggestion-main strong {
    display: block;
    margin-bottom: 3px;
}

.customer-suggestion-main span,
.customer-suggestion-code {
    color: #7a8496;
    font-size: 12px;
}

.customer-info-panel {
    display: grid;
    grid-template-columns: 1.1fr .8fr 1.1fr 1.4fr;
    gap: 18px;

    padding: 20px 20px 22px;

    border: 1px solid #e7eaf1;
    border-radius: 12px;
    background: #fff;
}

.customer-info-field input {
    width: 100%;
    min-height: 46px;
    padding: 0 14px;
    border: 1px solid #dfe3eb;
    border-radius: 9px;
    background: #fff;
    color: #1f2937;
    outline: none;
}

.customer-info-field input:focus {
    border-color: #bfc5d2;
    box-shadow: none;
}

.customer-info-field input[readonly] {
    background: #fff;
    color: #596275;
}

.customer-info-help {
    margin-top: 10px;
    color: #8a93a5;
    font-size: 12px;
}
.customer-info-field {
    min-width: 0;
}

.guest-required {
    color: #e24b4b;
}

@media (max-width: 1050px) {
    .customer-info-panel {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 650px) {
    .customer-card-unified .section-title {
        align-items: flex-start;
        flex-direction: column;
        gap: 12px;
    }

    .customer-mode-actions {
        width: 100%;
    }

    .customer-mode-actions .btn {
        flex: 1;
    }

    .customer-info-panel {
        grid-template-columns: 1fr;
    }
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

                <h1>Tạo đơn hàng</h1>

                <p>
                    Đơn hàng / Tạo mới
                </p>

            </div>

        </div>


        <form method="POST" id="orderForm">

            <?= csrfField() ?>


            <!-- =====================
                 KHÁCH HÀNG
            ====================== -->

            <div class="card customer-card-unified">

                <div class="section-title">

                    <div>
                        <h3>Thông tin khách hàng</h3>

                        <p>
                              Nhập mã KH hoặc SĐT để tìm khách trong hệ thống.
                              Nếu là khách vãng lai, có thể nhập họ tên, SĐT, email và địa chỉ bên dưới.
                        </p>
                    </div>

                    <div class="customer-mode-actions">

                        <button
                            type="button"
                            class="btn btn-light active"
                            id="systemCustomerBtn"
                        >
                            Khách trong hệ thống
                        </button>

                        <button
                            type="button"
                            class="btn btn-light"
                            id="guestCustomerBtn"
                        >
                            Khách vãng lai
                        </button>

                    </div>

                </div>


                <input
                    type="hidden"
                    name="customer_id"
                    id="customerId"
                    value="<?= htmlspecialchars(
                        $_POST['customer_id'] ?? ''
                    ) ?>"
                >


                <div
                    class="customer-search-row"
                    id="systemCustomerSearchBox"
                >

                    <label>
                        Tìm khách hàng
                    </label>

                    <div class="customer-search-input-wrap">

                        <input
                            type="text"
                            id="customerSearch"
                            autocomplete="off"
                            placeholder="Nhập mã KH hoặc SĐT..."
                        >

                        <span class="customer-search-icon">
                            🔎
                        </span>

                    </div>


                    <div
                        id="customerSuggestions"
                        class="customer-suggestions"
                        style="display:none;"
                    ></div>

                </div>


                <div class="customer-info-panel">

                    <div class="customer-info-field">

                        <label>
                            Họ tên
                            <span
                                id="guestNameRequired"
                                class="guest-required"
                                style="display:none;"
                            >*</span>
                        </label>

                        <input
                            type="text"
                            name="guest_name"
                            id="customerFullName"
                            value="<?= htmlspecialchars(
                                $_POST['guest_name'] ?? ''
                            ) ?>"
                            placeholder="Họ tên khách hàng"
                            readonly
                        >

                    </div>


                    <div class="customer-info-field">

                        <label>Số điện thoại</label>

                        <input
                            type="text"
                            name="guest_phone"
                            id="customerPhone"
                            value="<?= htmlspecialchars(
                                $_POST['guest_phone'] ?? ''
                            ) ?>"
                            placeholder="Số điện thoại"
                            readonly
                        >

                    </div>


                    <div class="customer-info-field">

                        <label>Email</label>

                        <input
                            type="email"
                            name="guest_email"
                            id="customerEmail"
                            value="<?= htmlspecialchars(
                                $_POST['guest_email'] ?? ''
                            ) ?>"
                            placeholder="Email"
                            readonly
                        >

                    </div>


                    <div class="customer-info-field">

                        <label>Địa chỉ</label>

                        <input
                            type="text"
                            name="guest_address"
                            id="customerAddress"
                            value="<?= htmlspecialchars(
                                $_POST['guest_address'] ?? ''
                            ) ?>"
                            placeholder="Địa chỉ"
                            readonly
                        >

                    </div>

                </div>


                <div
                    class="customer-info-help"
                    id="customerInfoHelp"
                >
                    Chọn khách từ kết quả tìm kiếm, thông tin còn lại sẽ tự động điền.
                </div>

            </div>


            <!-- =====================
                 SẢN PHẨM
            ====================== -->

            <div
                class="card"
                style="margin-top:20px;"
            >

                <div class="section-title">

                    <div>
                        <h3>Danh sách sản phẩm</h3>

                        <p>
                            Chọn đúng sản phẩm, Size và Màu sắc.
                        </p>
                    </div>

                </div>


                <div class="order-product-search">

                    <label>Tìm sản phẩm</label>

                    <input
                        type="text"
                        id="productSearch"
                        placeholder="Tìm theo tên sản phẩm, mã sản phẩm hoặc SKU..."
                    >

                </div>


                <div id="orderItemContainer">

                    <div class="order-item-row">

                        <div class="order-field order-product-field">

                            <label>Sản phẩm / Biến thể</label>

                            <select
                                name="variant_id[]"
                                class="order-variant-select"
                            >

                                <option
                                    value=""
                                    data-price="0"
                                    data-stock="0"
                                >
                                    Chọn sản phẩm
                                </option>


                                <?php foreach ($variants as $variant): ?>

                                    <?php
                                    $price =
                                        $variant['variant_price']
                                        !== null
                                            ? $variant['variant_price']
                                            : $variant['base_price'];
                                    ?>

                                    <option
                                        value="<?= $variant['variant_id'] ?>"
                                        data-price="<?= $price ?>"
                                        data-stock="<?= $variant['stock_quantity'] ?>"
                                        data-search="<?= htmlspecialchars(
                                            strtolower(
                                                $variant['product_code']
                                                . ' '
                                                . $variant['product_name']
                                                . ' '
                                                . $variant['sku']
                                                . ' '
                                                . $variant['size_name']
                                                . ' '
                                                . $variant['color_name']
                                            )
                                        ) ?>"
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


                        <div class="order-field">

                            <label>Đơn giá</label>

                            <input
                                type="text"
                                class="order-price-display"
                                value="0 đ"
                                readonly
                            >

                        </div>


                        <div class="order-field">

                            <label>Tồn kho</label>

                            <input
                                type="text"
                                class="order-stock-display"
                                value="0"
                                readonly
                            >

                        </div>


                        <div class="order-field">

                            <label>Số lượng</label>

                            <input
                                type="number"
                                name="quantity[]"
                                class="order-quantity"
                                value="1"
                                min="1"
                            >

                        </div>


                        <div class="order-field">

                            <label>Thành tiền</label>

                            <input
                                type="text"
                                class="order-subtotal-display"
                                value="0 đ"
                                readonly
                            >

                        </div>


                        <div class="order-item-action">

                            <button
                                type="button"
                                class="remove-order-item"
                                disabled
                            >
                                Xóa
                            </button>

                        </div>

                    </div>

                </div>


                <button
                    type="button"
                    id="addOrderItemBtn"
                    class="btn btn-light"
                >
                    + Thêm sản phẩm
                </button>

            </div>


            <!-- =====================
                 GHI CHÚ + TỔNG TIỀN
            ====================== -->

            <div class="order-bottom-grid">


                <div class="card">

                    <h3>Ghi chú</h3>

                    <div class="form-group">

                        <textarea
                            name="note"
                            rows="5"
                            placeholder="Nhập ghi chú đơn hàng..."
                        ><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>

                    </div>

                </div>


                <div class="card order-summary">

                    <h3>Tổng đơn hàng</h3>


                    <div class="order-summary-row">

                        <span>Tổng tiền hàng</span>

                        <strong id="orderTotal">
                            0 đ
                        </strong>

                    </div>


                    <div class="order-summary-row">

                        <span>Tiền cọc</span>

                        <div style="width:160px;">
                            <input
                                type="number"
                                name="deposit_amount"
                                id="depositAmount"
                                min="0"
                                step="1000"
                                value="<?= htmlspecialchars(
                                    $_POST['deposit_amount'] ?? '0'
                                ) ?>"
                                style="width:100%; text-align:right;"
                            >
                        </div>

                    </div>


                    <div class="order-summary-row">

                        <span>Còn phải thanh toán</span>

                        <strong id="remainingAmount">
                            0 đ
                        </strong>

                    </div>


                    <div class="order-summary-row total">

                        <span>Tổng giá trị đơn</span>

                        <strong id="orderGrandTotal">
                            0 đ
                        </strong>

                    </div>


                    <p
                        class="form-help"
                        style="margin-top:12px;"
                    >
                        Tiền cọc đang là khoản treo và chưa được tính vào doanh thu
                        cho đến khi đơn hoàn thành.
                    </p>

                </div>

            </div>


            <!-- =====================
                 ACTION
            ====================== -->

            <div class="form-actions">

                <a
                    href="/quanlyquanao/orders/index.php"
                    class="btn btn-light"
                >
                    Hủy
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tạo đơn hàng
                </button>

            </div>

        </form>

    </section>

</main>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const customers = <?= json_encode(
        $customers,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    ) ?>;

    const customerId =
        document.getElementById('customerId');

    const customerSearch =
        document.getElementById('customerSearch');

    const suggestions =
        document.getElementById('customerSuggestions');

    const systemSearchBox =
        document.getElementById('systemCustomerSearchBox');

    const fullName =
        document.getElementById('customerFullName');

    const phone =
        document.getElementById('customerPhone');

    const email =
        document.getElementById('customerEmail');

    const address =
        document.getElementById('customerAddress');

    const systemButton =
        document.getElementById('systemCustomerBtn');

    const guestButton =
        document.getElementById('guestCustomerBtn');

    const guestNameRequired =
        document.getElementById('guestNameRequired');

    const infoHelp =
        document.getElementById('customerInfoHelp');

    let currentMode = 'system';


    function normalize(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }


    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value ?? '');
        return div.innerHTML;
    }


    function clearCustomerFields() {
        fullName.value = '';
        phone.value = '';
        email.value = '';
        address.value = '';
    }


    function setSystemMode(clearFields = true) {

        currentMode = 'system';

        systemButton.classList.add('active');
        guestButton.classList.remove('active');

        systemSearchBox.style.display = 'block';

        fullName.readOnly = true;
        phone.readOnly = true;
        email.readOnly = true;
        address.readOnly = true;

        guestNameRequired.style.display = 'none';

        if (clearFields) {
            customerId.value = '';
            customerSearch.value = '';
            clearCustomerFields();
        }

        suggestions.style.display = 'none';
        suggestions.innerHTML = '';

        infoHelp.textContent =
            'Nhập mã KH hoặc SĐT, chọn đúng khách và hệ thống sẽ tự động điền thông tin.';
    }


    function setGuestMode() {

        currentMode = 'guest';

        customerId.value = '';
        customerSearch.value = '';

        systemButton.classList.remove('active');
        guestButton.classList.add('active');

        systemSearchBox.style.display = 'none';

        clearCustomerFields();

        fullName.readOnly = false;
        phone.readOnly = false;
        email.readOnly = false;
        address.readOnly = false;

        fullName.placeholder =
            'Nhập họ tên khách vãng lai';

        phone.placeholder =
            'Nhập SĐT liên hệ';

        email.placeholder =
            'Nhập email (nếu có)';

        address.placeholder =
            'Nhập địa chỉ (nếu có)';

        guestNameRequired.style.display = 'inline';

        suggestions.style.display = 'none';
        suggestions.innerHTML = '';

        infoHelp.textContent =
            '';
    }


    function selectCustomer(customer) {

        currentMode = 'system';

        customerId.value = customer.id;

        customerSearch.value =
            customer.customer_code
            + ' - '
            + (customer.phone || customer.full_name);

        fullName.value =
            customer.full_name || '';

        phone.value =
            customer.phone || '';

        email.value =
            customer.email || '';

        address.value =
            customer.address || '';

        fullName.readOnly = true;
        phone.readOnly = true;
        email.readOnly = true;
        address.readOnly = true;

        guestNameRequired.style.display = 'none';

        suggestions.style.display = 'none';
        suggestions.innerHTML = '';

        infoHelp.textContent =
            'Đã chọn '
            + customer.customer_code
            + '. Thông tin khách hàng được lấy từ hệ thống.';
    }


    function renderSuggestions(keyword) {

        const q = normalize(keyword.trim());

        if (q === '') {
            suggestions.style.display = 'none';
            suggestions.innerHTML = '';
            return;
        }

        const matched = customers
            .filter(function (customer) {

                const haystack = normalize(
                    customer.customer_code
                    + ' '
                    + (customer.phone || '')
                );

                return haystack.includes(q);
            })
            .slice(0, 8);


        if (matched.length === 0) {

            suggestions.innerHTML = `
                <div class="customer-suggestion-item">
                    <div class="customer-suggestion-main">
                        <strong>Không tìm thấy khách hàng</strong>
                        <span>Kiểm tra lại mã KH/SĐT hoặc chọn Khách vãng lai.</span>
                    </div>
                </div>
            `;

            suggestions.style.display = 'block';
            return;
        }


        suggestions.innerHTML = '';

        matched.forEach(function (customer) {

            const item =
                document.createElement('div');

            item.className =
                'customer-suggestion-item';

            item.innerHTML = `
                <div class="customer-suggestion-main">
                    <strong>${escapeHtml(customer.full_name)}</strong>
                    <span>${escapeHtml(customer.phone || 'Chưa có SĐT')}</span>
                </div>

                <div class="customer-suggestion-code">
                    ${escapeHtml(customer.customer_code)}
                </div>
            `;

            item.addEventListener(
                'click',
                function () {
                    selectCustomer(customer);
                }
            );

            suggestions.appendChild(item);
        });

        suggestions.style.display = 'block';
    }


    customerSearch.addEventListener(
        'input',
        function () {

            if (currentMode !== 'system') {
                return;
            }

            customerId.value = '';
            clearCustomerFields();

            renderSuggestions(
                customerSearch.value
            );
        }
    );


    customerSearch.addEventListener(
        'focus',
        function () {

            if (currentMode === 'system') {
                renderSuggestions(
                    customerSearch.value
                );
            }
        }
    );


    systemButton.addEventListener(
        'click',
        function () {
            setSystemMode(true);
        }
    );


    guestButton.addEventListener(
        'click',
        setGuestMode
    );


    document.addEventListener(
        'click',
        function (event) {

            if (
                !event.target.closest(
                    '.customer-search-row'
                )
            ) {
                suggestions.style.display = 'none';
            }
        }
    );


    const initialId =
        Number(customerId.value || 0);

    if (initialId > 0) {

        setSystemMode(false);

        const initialCustomer =
            customers.find(
                function (customer) {
                    return Number(customer.id)
                        === initialId;
                }
            );

        if (initialCustomer) {
            selectCustomer(initialCustomer);
        }

    } else {

        const initialGuestName =
            <?= json_encode(
                $_POST['guest_name'] ?? '',
                JSON_UNESCAPED_UNICODE
            ) ?>;

        const initialGuestPhone =
            <?= json_encode(
                $_POST['guest_phone'] ?? '',
                JSON_UNESCAPED_UNICODE
            ) ?>;

        const initialGuestEmail =
            <?= json_encode(
                $_POST['guest_email'] ?? '',
                JSON_UNESCAPED_UNICODE
            ) ?>;

        const initialGuestAddress =
            <?= json_encode(
                $_POST['guest_address'] ?? '',
                JSON_UNESCAPED_UNICODE
            ) ?>;

        if (
            initialGuestName !== ''
            || initialGuestPhone !== ''
            || initialGuestEmail !== ''
            || initialGuestAddress !== ''
        ) {
            setGuestMode();

            fullName.value =
                initialGuestName;

            phone.value =
                initialGuestPhone;

            email.value =
                initialGuestEmail;

            address.value =
                initialGuestAddress;
        } else {
            setSystemMode(true);
        }
    }

});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const depositInput =
        document.getElementById('depositAmount');

    const remainingDisplay =
        document.getElementById('remainingAmount');

    const grandTotalDisplay =
        document.getElementById('orderGrandTotal');

    const orderContainer =
        document.getElementById('orderItemContainer');

    if (
        !depositInput
        || !remainingDisplay
        || !grandTotalDisplay
        || !orderContainer
    ) {
        return;
    }


    function getOrderTotal() {

        let total = 0;

        orderContainer
            .querySelectorAll('.order-item-row')
            .forEach(function (row) {

                const select =
                    row.querySelector('.order-variant-select');

                const quantityInput =
                    row.querySelector('.order-quantity');

                if (!select || !quantityInput) {
                    return;
                }

                const option =
                    select.options[select.selectedIndex];

                const price =
                    Number(option?.dataset.price || 0);

                const quantity =
                    Math.max(
                        0,
                        Number(quantityInput.value || 0)
                    );

                total += price * quantity;
            });

        return total;
    }


    function formatMoney(value) {
        return Math.max(0, Number(value || 0))
            .toLocaleString('vi-VN')
            + ' đ';
    }


    function updateDepositSummary() {

        const total = getOrderTotal();

        let deposit =
            Number(depositInput.value || 0);

        if (deposit < 0) {
            deposit = 0;
        }

        const remaining =
            Math.max(0, total - deposit);

        remainingDisplay.textContent =
            formatMoney(remaining);

        if (deposit > total && total > 0) {
            depositInput.setCustomValidity(
                'Tiền cọc không được lớn hơn tổng giá trị đơn hàng.'
            );
        } else {
            depositInput.setCustomValidity('');
        }
    }


    depositInput.addEventListener(
        'input',
        updateDepositSummary
    );


    orderContainer.addEventListener(
        'change',
        updateDepositSummary
    );

    orderContainer.addEventListener(
        'input',
        updateDepositSummary
    );


    const observer = new MutationObserver(
        updateDepositSummary
    );

    observer.observe(
        orderContainer,
        {
            childList: true,
            subtree: true
        }
    );


    updateDepositSummary();
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>