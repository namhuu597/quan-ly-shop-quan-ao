<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Quản lý Shop Quần Áo';
}

$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link
        rel="stylesheet"
        href="/quanlyquanao/assets/css/style.css"
    >
</head>

<body>

<div class="app">