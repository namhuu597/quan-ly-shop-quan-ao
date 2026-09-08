<?php

session_start();

$error = '';

$apiBaseUrl =
    'http://localhost:5162/api';


// =========================================
// GỌI API LOGIN
// =========================================

function callLoginApi(
    string $url,
    array $payload
): array {

    $ch =
        curl_init();

    curl_setopt_array(
        $ch,
        [
            CURLOPT_URL =>
                $url,

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_POST =>
                true,

            CURLOPT_CONNECTTIMEOUT =>
                5,

            CURLOPT_TIMEOUT =>
                20,

            CURLOPT_HTTPHEADER =>
                [
                    'Accept: application/json',
                    'Content-Type: application/json'
                ],

            CURLOPT_POSTFIELDS =>
                json_encode(
                    $payload,
                    JSON_UNESCAPED_UNICODE
                )
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
        'response' =>
            $response,

        'httpCode' =>
            $httpCode,

        'curlError' =>
            $curlError
    ];
}


// =========================================
// XỬ LÝ ĐĂNG NHẬP
// =========================================

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    $username =
        trim(
            $_POST['username']
            ?? ''
        );

    $password =
        $_POST['password']
        ?? '';


    if (
        $username === ''
        || $password === ''
    ) {

        $error =
            'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';

    } else {

        $result =
            callLoginApi(
                $apiBaseUrl
                . '/auth/login',
                [
                    'username' =>
                        $username,

                    'password' =>
                        $password
                ]
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
                && is_array($data)
                && !empty(
                    $data['accessToken']
                )
            ) {

                session_regenerate_id(
                    true
                );


                $_SESSION['user'] = [
                    'id' =>
                        $data['id']
                        ?? 0,

                    'username' =>
                        $data['username']
                        ?? '',

                    'full_name' =>
                        $data['fullName']
                        ?? '',

                    'email' =>
                        $data['email']
                        ?? '',

                    'roles' =>
                        $data['roles']
                        ?? [],

                    'permissions' =>
                        $data['permissions']
                        ?? []
                ];


                $_SESSION['access_token'] =
                    $data['accessToken'];


                $_SESSION['token_expires_at'] =
                    $data['expiresAt']
                    ?? null;


                header(
                    'Location: admin/dashboard.php'
                );

                exit;

            } else {

                $error =
                    $data['message']
                    ?? (
                        'Đăng nhập thất bại. HTTP Status: '
                        . $result['httpCode']
                    );
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đăng nhập - Quản lý Shop Quần Áo</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6fb;
            color: #111827;
        }

        .login-page {
            min-height: 100vh;
            display: flex;
        }

        /* =========================
           LEFT SIDE
        ========================== */
        .login-brand {
            width: 48%;
            min-height: 100vh;
            background: #111a3a;
            color: #ffffff;
            padding: 56px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .login-brand::before,
        .login-brand::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(99, 79, 244, 0.18);
        }

        .login-brand::before {
            width: 360px;
            height: 360px;
            right: -150px;
            top: -120px;
        }

        .login-brand::after {
            width: 260px;
            height: 260px;
            left: -100px;
            bottom: -100px;
        }

        .brand-logo {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: #6350f4;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .brand-logo strong {
            display: block;
            font-size: 24px;
            letter-spacing: 0.2px;
        }

        .brand-logo span {
            display: block;
            margin-top: 4px;
            font-size: 14px;
            color: #b8c0dd;
        }

        .brand-content {
            position: relative;
            z-index: 1;
            max-width: 520px;
        }

        .brand-content h1 {
            margin: 0 0 18px;
            font-size: 42px;
            line-height: 1.2;
        }

        .brand-content p {
            margin: 0;
            color: #c7cee3;
            font-size: 17px;
            line-height: 1.7;
        }

        .brand-footer {
            position: relative;
            z-index: 1;
            color: #8f99bd;
            font-size: 13px;
        }

        /* =========================
           RIGHT SIDE
        ========================== */
        .login-main {
            width: 52%;
            min-height: 100vh;
            padding: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
        }

        .login-heading {
            margin-bottom: 30px;
        }

        .login-heading h2 {
            margin: 0 0 10px;
            font-size: 32px;
            line-height: 1.2;
        }

        .login-heading p {
            margin: 0;
            color: #7b8190;
            font-size: 15px;
            line-height: 1.6;
        }

        .login-alert {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            padding: 13px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 14px;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 17px;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            height: 50px;
            border: 1px solid #d8deea;
            border-radius: 10px;
            padding: 0 46px 0 44px;
            font-family: inherit;
            font-size: 15px;
            color: #111827;
            background: #ffffff;
            outline: none;
            transition: 0.2s ease;
        }

        .form-control::placeholder {
            color: #9aa2b1;
        }

        .form-control:focus {
            border-color: #6350f4;
            box-shadow: 0 0 0 3px rgba(99, 80, 244, 0.10);
        }

        .password-toggle {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border: 0;
            background: transparent;
            cursor: pointer;
            border-radius: 8px;
            font-size: 17px;
        }

        .password-toggle:hover {
            background: #f2f3f8;
        }

        .login-button {
            width: 100%;
            height: 50px;
            border: 0;
            border-radius: 10px;
            background: #6350f4;
            color: #ffffff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s ease;
            margin-top: 4px;
        }

        .login-button:hover {
            background: #5744e8;
            transform: translateY(-1px);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-note {
            margin-top: 22px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #8a91a1;
            font-size: 13px;
            line-height: 1.6;
        }

        /* =========================
           MOBILE
        ========================== */
        @media (max-width: 900px) {

            .login-page {
                display: block;
            }

            .login-brand {
                display: none;
            }

            .login-main {
                width: 100%;
                min-height: 100vh;
                padding: 28px 20px;
            }

            .login-card {
                max-width: 420px;
            }

            .mobile-brand {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                margin-bottom: 34px;
            }

            .mobile-brand-icon {
                width: 42px;
                height: 42px;
                border-radius: 12px;
                background: #6350f4;
                color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
            }

            .mobile-brand strong {
                font-size: 20px;
            }

            .login-heading {
                text-align: center;
            }
        }

        @media (min-width: 901px) {
            .mobile-brand {
                display: none;
            }
        }

        @media (max-width: 480px) {

            .login-main {
                padding: 22px 16px;
            }

            .login-heading h2 {
                font-size: 27px;
            }

            .form-control,
            .login-button {
                height: 48px;
            }
        }
    </style>
</head>

<body>

<div class="login-page">

    <section class="login-brand">

        <div class="brand-logo">
            <div class="brand-logo-icon">♧</div>

            <div>
                <strong>SHOP CLOTHES</strong>
                <span>Quản lý shop quần áo</span>
            </div>
        </div>


        <div class="brand-content">

            <h1>
                Quản lý cửa hàng<br>
                đơn giản và hiệu quả
            </h1>

            <p>
                Theo dõi sản phẩm, khách hàng, đơn hàng, tồn kho
                và hoạt động bán hàng trong cùng một hệ thống.
            </p>

        </div>


        <div class="brand-footer">
            Hệ thống quản lý Shop Clothes
        </div>

    </section>


    <main class="login-main">

        <div class="login-card">

            <div class="mobile-brand">

                <div class="mobile-brand-icon">♧</div>

                <strong>SHOP CLOTHES</strong>

            </div>


            <div class="login-heading">

                <h2>Đăng nhập</h2>

                <p>
                    Nhập thông tin tài khoản để truy cập hệ thống quản lý.
                </p>

            </div>


            <?php if ($error): ?>

                <div class="login-alert">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <form method="POST" autocomplete="on">

                <div class="form-group">

                    <label for="username">
                        Tên đăng nhập
                    </label>

                    <div class="input-wrap">

                        <span class="input-icon">♙</span>

                        <input
                            class="form-control"
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Nhập tên đăng nhập"
                            autocomplete="username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            autofocus
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="password">
                        Mật khẩu
                    </label>

                    <div class="input-wrap">

                        <span class="input-icon">⌑</span>

                        <input
                            class="form-control"
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Nhập mật khẩu"
                            autocomplete="current-password"
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            aria-label="Hiện mật khẩu"
                            title="Hiện/ẩn mật khẩu"
                        >
                            👁
                        </button>

                    </div>

                </div>


                <button
                    class="login-button"
                    type="submit"
                >
                    Đăng nhập
                </button>

            </form>


            <div class="login-note">
                Chỉ tài khoản được cấp quyền mới có thể truy cập hệ thống.
            </div>

        </div>

    </main>

</div>


<script>
const passwordInput = document.getElementById('password');
const passwordToggle = document.getElementById('passwordToggle');

if (passwordInput && passwordToggle) {

    passwordToggle.addEventListener('click', function () {

        const isPassword =
            passwordInput.type === 'password';

        passwordInput.type =
            isPassword ? 'text' : 'password';

        passwordToggle.setAttribute(
            'aria-label',
            isPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'
        );
    });
}
</script>

</body>
</html>
