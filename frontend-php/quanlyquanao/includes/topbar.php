<?php
$user = $_SESSION['user'] ?? null;
?>

<header class="topbar">

    <div class="topbar-left">

        <button
            type="button"
            class="mobile-menu-btn"
            id="mobileMenuBtn"
            aria-label="Mở menu"
            title="Mở menu"
        >
            ☰
        </button>

        <div class="topbar-title">
            <strong>
                <?= htmlspecialchars(
                    $pageTitle ?? 'Quản lý shop'
                ) ?>
            </strong>
        </div>

    </div>


    <div class="topbar-right">

        <?php if ($user): ?>

            <div class="top-user">

                <div class="avatar">
                    <?= htmlspecialchars(
                        strtoupper(
                            substr(
                                $user['full_name'] ?? 'U',
                                0,
                                1
                            )
                        )
                    ) ?>
                </div>

                <div class="top-user-info">

                    <strong>
                        <?= htmlspecialchars(
                            $user['full_name']
                            ?? 'Người dùng'
                        ) ?>
                    </strong>

                    <?php if (
                        !empty($user['roles'][0])
                    ): ?>

                        <span>
                            <?= htmlspecialchars(
                                $user['roles'][0]
                            ) ?>
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </div>

</header>