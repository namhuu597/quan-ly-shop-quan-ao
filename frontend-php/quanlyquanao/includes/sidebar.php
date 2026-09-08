<?php
$currentPage = $currentPage ?? '';
?>

<aside class="sidebar">


        

         <div class="sidebar-logo">
    <img
        src="/quanlyquanao/uploads/logo/boutique.png"
        alt="Gia Bảo Boutique   "
        class="sidebar-logo-img"
    >
</div>
        

    


    <div class="sidebar-user">

        <div class="avatar">

            <?= strtoupper(
                substr(
                    $_SESSION['user']['full_name'],
                    0,
                    1
                )
            ) ?>

        </div>


        <div class="user-info">

            <strong>

                <?= htmlspecialchars(
                    $_SESSION['user']['full_name']
                ) ?>

            </strong>


            <span>

                <?= htmlspecialchars(
                    $_SESSION['user']['roles'][0] ?? ''
                ) ?>

            </span>

        </div>

    </div>


    <nav class="sidebar-menu">


        <!-- =========================
             TỔNG QUAN
        ========================== -->

        <a
            href="/quanlyquanao/admin/dashboard.php"
            class="<?= $currentPage === 'dashboard'
                ? 'active'
                : ''
            ?>"
        >

            <span>⌂</span>

            Tổng quan

        </a>



        <!-- =========================
             SẢN PHẨM
        ========================== -->

        <?php if (
            hasPermission('product.read')
        ): ?>

            <a
                href="/quanlyquanao/products/index.php"
                class="<?= $currentPage === 'products'
                    ? 'active'
                    : ''
                ?>"
            >

                <span>▦</span>

                Sản phẩm

            </a>

        <?php endif; ?>



        <!-- =========================
             DANH MỤC
        ========================== -->

        <?php if (
            hasPermission('category.read')
        ): ?>

            <a
                href="/quanlyquanao/categories/index.php"
                class="<?= $currentPage === 'categories'
                    ? 'active'
                    : ''
                ?>"
            >

                <span>▣</span>

                Danh mục

            </a>

        <?php endif; ?>



        <!-- =========================
             SIZE
        ========================== -->

        <?php if (
            hasPermission('size.read')
        ): ?>

            <a
                href="/quanlyquanao/sizes/index.php"
                class="<?= $currentPage === 'sizes'
                    ? 'active'
                    : ''
                ?>"
            >

                <span>✂</span>

                Size

            </a>

        <?php endif; ?>



        <!-- =========================
             MÀU SẮC
        ========================== -->

        <?php if (
            hasPermission('color.read')
        ): ?>

            <a
                href="/quanlyquanao/colors/index.php"
                class="<?= $currentPage === 'colors'
                    ? 'active'
                    : ''
                ?>"
            >

                <span>◈</span>

                Màu sắc

            </a>

        <?php endif; ?>



        <!-- =========================
             KHÁCH HÀNG
        ========================== -->

        <?php if (
            hasPermission('customer.read')
        ): ?>

            <a
                href="/quanlyquanao/customers/index.php"
                class="<?= $currentPage === 'customers'
                    ? 'active'
                    : ''
                ?>"
            >

                <span>♙</span>

                Khách hàng

            </a>

        <?php endif; ?>



        <!-- =========================
             ĐƠN HÀNG
        ========================== -->

        <?php if (
            hasPermission('order.read')
            ||
            hasPermission('order.read.own')
        ): ?>

            <a
                href="/quanlyquanao/orders/index.php"
                class="<?= $currentPage === 'orders'
                    ? 'active'
                    : ''
                ?>"
            >

                <span>▤</span>

                Đơn hàng

            </a>

        <?php endif; ?>



        <!-- =========================
             KHO HÀNG
        ========================== -->

        <?php if (
            hasPermission('inventory.read')
        ): ?>

            <a
                href="/quanlyquanao/inventory/index.php"
                class="<?= $currentPage === 'inventory'
                    ? 'active'
                    : ''
                ?>"
            >

                <span>▱</span>

                Kho hàng

            </a>

        <?php endif; ?>



        <!-- =========================
             NHÂN VIÊN
        ========================== -->

        <?php if (
            hasPermission('user.read')
        ): ?>

            <a
                href="/quanlyquanao/employees/index.php"
                class="<?= $currentPage === 'employees'
                    ? 'active'
                    : ''
                ?>"
            >

                <span>♧</span>

                Nhân viên

            </a>

        <?php endif; ?>



        <!-- =========================
             BÁO CÁO
        ========================== -->

        <?php if (
            hasPermission('report.read')
        ): ?>

            <a
                href="/quanlyquanao/reports/index.php"
                class="<?= $currentPage === 'reports'
                    ? 'active'
                    : ''
                ?>"
            >

                <span>⌁</span>

                Báo cáo

            </a>

        <?php endif; ?>


    </nav>



    <!-- =========================
         ĐĂNG XUẤT
    ========================== -->

    <div class="sidebar-bottom">

        <a
            href="/quanlyquanao/logout.php"
        >

            <span>↪</span>

            Đăng xuất

        </a>

    </div>

</aside>


<!-- =========================
     OVERLAY MOBILE
========================= -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>