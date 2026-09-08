document.addEventListener('DOMContentLoaded', function () {

    const container =
        document.getElementById('editVariantContainer');

    const addButton =
        document.getElementById('addEditVariantBtn');

    if (!container || !addButton) {
        return;
    }


    addButton.addEventListener('click', function () {

        const firstRow =
            container.querySelector('.edit-variant-row');

        if (!firstRow) {
            return;
        }


        const newRow =
            firstRow.cloneNode(true);


        // ID = 0 nghĩa là biến thể mới
        newRow.querySelector(
            'input[name="variant_id[]"]'
        ).value = '0';


        newRow.querySelector(
            '.delete-variant-flag'
        ).value = '0';


        // Reset select
        newRow.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
        });


        // Reset input
        newRow.querySelectorAll(
            'input[type="text"], input[type="number"]'
        ).forEach(input => {

            if (input.name === 'variant_stock[]') {
                input.value = '0';
            } else {
                input.value = '';
            }

        });


        newRow.style.display = '';

        container.appendChild(newRow);

    });



    container.addEventListener('click', function (event) {

        if (
            !event.target.classList.contains(
                'remove-edit-variant-btn'
            )
        ) {
            return;
        }


        const row =
            event.target.closest('.edit-variant-row');

        const variantId =
            row.querySelector(
                'input[name="variant_id[]"]'
            ).value;


        // Biến thể đã tồn tại trong DB
        if (variantId !== '0') {

            const confirmDelete = confirm(
                'Bạn có chắc muốn xóa biến thể này?'
            );

            if (!confirmDelete) {
                return;
            }


            row.querySelector(
                '.delete-variant-flag'
            ).value = '1';


            row.style.display = 'none';

        } else {

            // Biến thể mới chưa lưu DB
            row.remove();

        }

    });

});
document.addEventListener('DOMContentLoaded', function () {

    const picker =
        document.getElementById('colorPicker');

    const input =
        document.getElementById('colorCodeInput');

    if (!picker || !input) {
        return;
    }

    picker.addEventListener('input', function () {
        input.value = picker.value.toUpperCase();
    });

    input.addEventListener('input', function () {

        const value = input.value.trim();

        if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
            picker.value = value;
        }

    });

});
document.addEventListener('DOMContentLoaded', function () {

    const container =
        document.getElementById('orderItemContainer');

    const addButton =
        document.getElementById('addOrderItemBtn');

    const totalDisplay =
        document.getElementById('orderTotal');

    const grandTotalDisplay =
        document.getElementById('orderGrandTotal');


    if (
        !container
        || !addButton
        || !totalDisplay
        || !grandTotalDisplay
    ) {
        return;
    }


    function formatMoney(value) {

        return Number(value)
            .toLocaleString('vi-VN')
            + ' đ';
    }


    function calculateRow(row) {

        const select =
            row.querySelector(
                '.order-variant-select'
            );

        const quantityInput =
            row.querySelector(
                '.order-quantity'
            );

        const priceDisplay =
            row.querySelector(
                '.order-price-display'
            );

        const stockDisplay =
            row.querySelector(
                '.order-stock-display'
            );

        const subtotalDisplay =
            row.querySelector(
                '.order-subtotal-display'
            );


        const option =
            select.options[
                select.selectedIndex
            ];


        const price =
            Number(
                option.dataset.price || 0
            );

        const stock =
            Number(
                option.dataset.stock || 0
            );

        let quantity =
            Number(
                quantityInput.value || 1
            );


        if (quantity < 1) {
            quantity = 1;
            quantityInput.value = 1;
        }


        if (
            stock > 0
            && quantity > stock
        ) {

            quantity = stock;

            quantityInput.value =
                stock;
        }


        priceDisplay.value =
            formatMoney(price);

        stockDisplay.value =
            stock;

        subtotalDisplay.value =
            formatMoney(
                price * quantity
            );


        row.dataset.subtotal =
            price * quantity;
    }


    function calculateTotal() {

        let total = 0;


        container
            .querySelectorAll(
                '.order-item-row'
            )
            .forEach(row => {

                total += Number(
                    row.dataset.subtotal
                    || 0
                );

            });


        totalDisplay.textContent =
            formatMoney(total);

        grandTotalDisplay.textContent =
            formatMoney(total);
    }


    function updateRemoveButtons() {

        const rows =
            container.querySelectorAll(
                '.order-item-row'
            );

        rows.forEach(row => {

            const button =
                row.querySelector(
                    '.remove-order-item'
                );

            button.disabled =
                rows.length === 1;

        });
    }


    container.addEventListener(
        'change',
        function (event) {

            if (
                event.target.classList.contains(
                    'order-variant-select'
                )
                ||
                event.target.classList.contains(
                    'order-quantity'
                )
            ) {

                const row =
                    event.target.closest(
                        '.order-item-row'
                    );

                calculateRow(row);

                calculateTotal();
            }

        }
    );


    container.addEventListener(
        'input',
        function (event) {

            if (
                event.target.classList.contains(
                    'order-quantity'
                )
            ) {

                const row =
                    event.target.closest(
                        '.order-item-row'
                    );

                calculateRow(row);

                calculateTotal();
            }

        }
    );


    addButton.addEventListener(
        'click',
        function () {

            const firstRow =
                container.querySelector(
                    '.order-item-row'
                );

            const newRow =
                firstRow.cloneNode(true);


            newRow.querySelector(
                '.order-variant-select'
            ).selectedIndex = 0;


            newRow.querySelector(
                '.order-quantity'
            ).value = 1;


            newRow.querySelector(
                '.order-price-display'
            ).value = '0 đ';


            newRow.querySelector(
                '.order-stock-display'
            ).value = '0';


            newRow.querySelector(
                '.order-subtotal-display'
            ).value = '0 đ';


            newRow.dataset.subtotal = 0;


            container.appendChild(
                newRow
            );


            updateRemoveButtons();

        }
    );


    container.addEventListener(
        'click',
        function (event) {

            if (
                !event.target.classList.contains(
                    'remove-order-item'
                )
            ) {
                return;
            }


            const rows =
                container.querySelectorAll(
                    '.order-item-row'
                );


            if (rows.length > 1) {

                event.target
                    .closest(
                        '.order-item-row'
                    )
                    .remove();


                updateRemoveButtons();

                calculateTotal();
            }

        }
    );


    updateRemoveButtons();

});
document.addEventListener('DOMContentLoaded', function () {

    // =========================
    // TÌM KHÁCH HÀNG
    // =========================

    const customerSearch =
        document.getElementById('customerSearch');

    const customerSelect =
        document.getElementById('customerSelect');

    if (customerSearch && customerSelect) {

        const customerOptions =
            Array.from(customerSelect.options).map(option => ({
                value: option.value,
                text: option.textContent.trim(),
                search: (
                    option.dataset.search
                    || option.textContent
                    || ''
                )
                    .toLowerCase()
                    .trim()
            }));


        customerSearch.addEventListener(
            'input',
            function () {

                const keyword =
                    this.value
                        .toLowerCase()
                        .trim();

                const currentValue =
                    customerSelect.value;

                customerSelect.innerHTML = '';


                customerOptions.forEach(item => {

                    if (
                        item.value === ''
                        || keyword === ''
                        || item.search.includes(keyword)
                    ) {

                        const option =
                            document.createElement('option');

                        option.value =
                            item.value;

                        option.textContent =
                            item.text;

                        option.dataset.search =
                            item.search;

                        customerSelect.appendChild(
                            option
                        );
                    }

                });


                const currentValueExists =
                    Array.from(customerSelect.options)
                        .some(option =>
                            option.value === currentValue
                        );


                if (currentValueExists) {

                    customerSelect.value =
                        currentValue;

                } else if (
                    keyword !== ''
                    && customerSelect.options.length > 1
                ) {

                    customerSelect.selectedIndex = 1;

                } else {

                    customerSelect.selectedIndex = 0;

                }

            }
        );

    }



    // =========================
    // TÌM SẢN PHẨM
    // =========================

    const productSearch =
        document.getElementById('productSearch');

    const orderItemContainer =
        document.getElementById('orderItemContainer');


    if (
        productSearch
        && orderItemContainer
    ) {

        const firstSelect =
            orderItemContainer.querySelector(
                '.order-variant-select'
            );


        if (!firstSelect) {
            return;
        }


        const productOptions =
            Array.from(firstSelect.options).map(option => ({
                value: option.value,
                text: option.textContent.trim(),
                price: option.dataset.price || '0',
                stock: option.dataset.stock || '0',
                search: (
                    option.dataset.search
                    || option.textContent
                    || ''
                )
                    .toLowerCase()
                    .trim()
            }));


        function rebuildProductSelect(
            select,
            keyword
        ) {

            const currentValue =
                select.value;

            select.innerHTML = '';


            productOptions.forEach(item => {

                if (
                    item.value === ''
                    || keyword === ''
                    || item.search.includes(keyword)
                ) {

                    const option =
                        document.createElement('option');


                    option.value =
                        item.value;

                    option.textContent =
                        item.text;

                    option.dataset.price =
                        item.price;

                    option.dataset.stock =
                        item.stock;

                    option.dataset.search =
                        item.search;


                    select.appendChild(
                        option
                    );
                }

            });


            const currentValueExists =
                Array.from(select.options)
                    .some(option =>
                        option.value === currentValue
                    );


            if (currentValueExists) {

                select.value =
                    currentValue;

            } else if (
                keyword !== ''
                && select.options.length > 1
            ) {

                select.selectedIndex = 1;

            } else {

                select.selectedIndex = 0;

            }


            select.dispatchEvent(
                new Event(
                    'change',
                    {
                        bubbles: true
                    }
                )
            );

        }


        productSearch.addEventListener(
            'input',
            function () {

                const keyword =
                    this.value
                        .toLowerCase()
                        .trim();


                orderItemContainer
                    .querySelectorAll(
                        '.order-variant-select'
                    )
                    .forEach(select => {

                        rebuildProductSelect(
                            select,
                            keyword
                        );

                    });

            }
        );


        // Nếu bấm "+ Thêm sản phẩm"
        // thì dòng mới cũng áp dụng từ khóa đang tìm
        const observer =
            new MutationObserver(
                function (mutations) {

                    const hasNewRow =
                        mutations.some(
                            mutation =>
                                mutation.addedNodes.length > 0
                        );


                    if (!hasNewRow) {
                        return;
                    }


                    const keyword =
                        productSearch.value
                            .toLowerCase()
                            .trim();


                    if (keyword === '') {
                        return;
                    }


                    orderItemContainer
                        .querySelectorAll(
                            '.order-variant-select'
                        )
                        .forEach(select => {

                            rebuildProductSelect(
                                select,
                                keyword
                            );

                        });

                }
            );


        observer.observe(
            orderItemContainer,
            {
                childList: true
            }
        );

    }

});
document.addEventListener('DOMContentLoaded', function () {

    const chartCanvas =
        document.getElementById('revenueChart');

    if (
        !chartCanvas
        || typeof Chart === 'undefined'
        || !window.reportChartData
    ) {
        return;
    }


    const reportData =
        window.reportChartData;


    new Chart(
        chartCanvas,
        {
            type: 'bar',

            data: {

                labels:
                    reportData.labels,

                datasets: [

                    {
                        label: 'Doanh thu',

                        data:
                            reportData.revenue,

                        borderWidth: 1,

                        yAxisID: 'y'
                    },

                    {
                        label: 'Số đơn',

                        data:
                            reportData.orders,

                        type: 'line',

                        borderWidth: 2,

                        tension: 0.3,

                        yAxisID: 'y1'
                    }

                ]
            },


            options: {

                responsive: true,

                maintainAspectRatio: false,


                interaction: {
                    mode: 'index',
                    intersect: false
                },


                plugins: {

                    legend: {
                        position: 'top'
                    },


                    tooltip: {

                        callbacks: {

                            label: function (context) {

                                if (
                                    context.dataset.label
                                    === 'Doanh thu'
                                ) {

                                    return (
                                        'Doanh thu: '
                                        + Number(
                                            context.raw
                                        ).toLocaleString(
                                            'vi-VN'
                                        )
                                        + ' đ'
                                    );
                                }


                                return (
                                    'Số đơn: '
                                    + context.raw
                                );

                            }

                        }

                    }

                },


                scales: {

                    y: {

                        beginAtZero: true,

                        position: 'left',


                        ticks: {

                            callback:
                                function (value) {

                                    return (
                                        Number(
                                            value
                                        ).toLocaleString(
                                            'vi-VN'
                                        )
                                        + ' đ'
                                    );

                                }

                        }

                    },


                    y1: {

                        beginAtZero: true,

                        position: 'right',


                        grid: {
                            drawOnChartArea: false
                        },


                        ticks: {
                            precision: 0
                        }

                    }

                }

            }

        }
    );

});
document.addEventListener('DOMContentLoaded', function () {

    const canvas =
        document.getElementById('dashboardRevenueChart');

    if (
        !canvas
        || typeof Chart === 'undefined'
        || !window.dashboardChartData
    ) {
        return;
    }


    const data =
        window.dashboardChartData;


    new Chart(
        canvas,
        {
            type: 'bar',

            data: {

                labels:
                    data.labels,

                datasets: [
                    {
                        label: 'Doanh thu',

                        data:
                            data.revenue,

                        borderWidth: 1
                    }
                ]
            },


            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {
                        display: true,
                        position: 'top'
                    },


                    tooltip: {

                        callbacks: {

                            label:
                                function (context) {

                                    return (
                                        'Doanh thu: '
                                        + Number(
                                            context.raw
                                        ).toLocaleString(
                                            'vi-VN'
                                        )
                                        + ' đ'
                                    );

                                }

                        }

                    }

                },


                scales: {

                    y: {

                        beginAtZero: true,

                        ticks: {

                            callback:
                                function (value) {

                                    return (
                                        Number(
                                            value
                                        ).toLocaleString(
                                            'vi-VN'
                                        )
                                        + ' đ'
                                    );

                                }

                        }

                    }

                }

            }

        }
    );

});
document.addEventListener(
    'DOMContentLoaded',
    function () {

        const menuBtn =
            document.getElementById(
                'mobileMenuBtn'
            );

        const sidebar =
            document.querySelector(
                '.sidebar'
            );

        const overlay =
            document.getElementById(
                'sidebarOverlay'
            );


        if (
            !menuBtn
            || !sidebar
            || !overlay
        ) {
            return;
        }


        function openMenu() {

            sidebar.classList.add(
                'mobile-open'
            );

            overlay.classList.add(
                'show'
            );

        }


        function closeMenu() {

            sidebar.classList.remove(
                'mobile-open'
            );

            overlay.classList.remove(
                'show'
            );

        }


        menuBtn.addEventListener(
            'click',
            function () {

                if (
                    sidebar.classList.contains(
                        'mobile-open'
                    )
                ) {

                    closeMenu();

                } else {

                    openMenu();

                }

            }
        );


        overlay.addEventListener(
            'click',
            closeMenu
        );


        sidebar
            .querySelectorAll('a')
            .forEach(link => {

                link.addEventListener(
                    'click',
                    function () {

                        if (
                            window.innerWidth
                            <= 768
                        ) {
                            closeMenu();
                        }

                    }
                );

            });

    }
);
document.addEventListener('DOMContentLoaded', function () {

    const mobileMenuBtn =
        document.getElementById('mobileMenuBtn');

    const sidebar =
        document.querySelector('.sidebar');

    const sidebarOverlay =
        document.getElementById('sidebarOverlay');


    if (
        !mobileMenuBtn
        || !sidebar
        || !sidebarOverlay
    ) {
        return;
    }


    function openSidebar() {

        sidebar.classList.add('mobile-open');

        sidebarOverlay.classList.add('show');

        document.body.classList.add(
            'sidebar-open'
        );
    }


    function closeSidebar() {

        sidebar.classList.remove(
            'mobile-open'
        );

        sidebarOverlay.classList.remove(
            'show'
        );

        document.body.classList.remove(
            'sidebar-open'
        );
    }


    mobileMenuBtn.addEventListener(
        'click',
        function () {

            if (
                sidebar.classList.contains(
                    'mobile-open'
                )
            ) {
                closeSidebar();
            } else {
                openSidebar();
            }

        }
    );


    sidebarOverlay.addEventListener(
        'click',
        closeSidebar
    );


    sidebar
        .querySelectorAll('a')
        .forEach(function (link) {

            link.addEventListener(
                'click',
                function () {

                    if (
                        window.innerWidth
                        <= 768
                    ) {
                        closeSidebar();
                    }

                }
            );

        });


    window.addEventListener(
        'resize',
        function () {

            if (
                window.innerWidth > 768
            ) {
                closeSidebar();
            }

        }
    );

});
document.addEventListener('DOMContentLoaded', function () {

    const menuBtn =
        document.getElementById('mobileMenuBtn');

    const sidebar =
        document.querySelector('.sidebar');

    const overlay =
        document.getElementById('sidebarOverlay');


    console.log('menuBtn:', menuBtn);
    console.log('sidebar:', sidebar);
    console.log('overlay:', overlay);


    if (!menuBtn || !sidebar || !overlay) {
        console.log('Thiếu phần tử menu mobile');
        return;
    }


    menuBtn.addEventListener('click', function () {

        sidebar.classList.toggle('mobile-open');

        overlay.classList.toggle('show');

        document.body.classList.toggle(
            'sidebar-open'
        );

    });


    overlay.addEventListener('click', function () {

        sidebar.classList.remove(
            'mobile-open'
        );

        overlay.classList.remove(
            'show'
        );

        document.body.classList.remove(
            'sidebar-open'
        );

    });

});