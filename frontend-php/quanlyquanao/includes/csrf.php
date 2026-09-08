<?php

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {

        $_SESSION['csrf_token'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}


function csrfField(): string
{
    $token = htmlspecialchars(
        generateCsrfToken(),
        ENT_QUOTES,
        'UTF-8'
    );

    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}


function verifyCsrfToken(): void
{
    $sessionToken =
        $_SESSION['csrf_token'] ?? '';

    $formToken =
        $_POST['csrf_token'] ?? '';

    if (
        empty($sessionToken)
        ||
        empty($formToken)
        ||
        !hash_equals(
            $sessionToken,
            $formToken
        )
    ) {

        http_response_code(403);

        exit(
            '403 - Yêu cầu không hợp lệ. CSRF token không đúng.'
        );
    }
}