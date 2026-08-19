document.addEventListener('DOMContentLoaded', function () {
    // Initialize Cart Offcanvas and Modals
    const cartOffcanvasEl = document.getElementById('cartOffcanvas');
    let cartOffcanvas = null;
    if (cartOffcanvasEl) {
        cartOffcanvas = new bootstrap.Offcanvas(cartOffcanvasEl);
    }

    // --- 1. Location Selection Modal Gating ---
    const locationModalEl = document.getElementById('locationModal');
    if (locationModalEl) {
        const hasLocation = locationModalEl.getAttribute('data-has-location') === 'true';
        const locationModal = new bootstrap.Modal(locationModalEl, {
            backdrop: 'static',
            keyboard: false
        });

        if (!hasLocation) {
            locationModal.show();
        }

        // Handle Confirm Location Submission
        const confirmLocationBtn = document.getElementById('btnConfirmLocation');
        if (confirmLocationBtn) {
            confirmLocationBtn.addEventListener('click', function () {
                const areaSelect = document.getElementById('areaSelect');
                const areaId = areaSelect.value;
                if (!areaId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Select Area',
                        text: 'Please select your delivery area to proceed!',
                        confirmButtonColor: '#FF6B00'
                    });
                    return;
                }

                // Send to backend via fetch
                fetch('api/add_to_cart.php?action=set_location', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `area_id=${areaId}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        locationModal.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Welcome!',
                            text: 'Delivery area set successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong. Please try again.',
                            confirmButtonColor: '#FF6B00'
                        });
                    }
                });
            });
        }
    }

    // Trigger change location manually
    const btnChangeLocation = document.getElementById('btnChangeLocation');
    if (btnChangeLocation && locationModalEl) {
        btnChangeLocation.addEventListener('click', function(e) {
            e.preventDefault();
            const locationModal = bootstrap.Modal.getInstance(locationModalEl) || new bootstrap.Modal(locationModalEl);
            locationModal.show();
        });
    }


    // --- 2. Product Detail Modal Load (AJAX) ---
    const productDetailModalEl = document.getElementById('productDetailModal');
    let productDetailModal = null;
    if (productDetailModalEl) {
        productDetailModal = new bootstrap.Modal(productDetailModalEl);
    }

    // Bind click handlers to all product cards
    document.querySelectorAll('.btn-customize, .product-card-link').forEach(el => {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            loadProductModal(productId);
        });
    });

    function loadProductModal(productId) {
        const modalContent = document.querySelector('#productDetailModal .modal-content');
        modalContent.innerHTML = `
            <div class="modal-body text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Preparing customization details...</p>
            </div>
        `;
        productDetailModal.show();

        fetch(`product-modal.php?id=${productId}`)
            .then(res => {
                if (!res.ok) throw new Error('Network error');
                return res.text();
            })
            .then(html => {
                modalContent.innerHTML = html;
                initializeModalCalculator();
            })
            .catch(err => {
                modalContent.innerHTML = `
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Error</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p class="text-danger">Failed to load product details. Please try again.</p>
                    </div>
                `;
            });
    }


    // --- 3. Dynamic Pricing Calculator in Modal ---
    function initializeModalCalculator() {
        const modalForm = document.getElementById('customizationForm');
        if (!modalForm) return;

        const basePrice = parseFloat(modalForm.getAttribute('data-base-price')) || 0;
        const sizeSelect = modalForm.querySelector('select[name="size_id"]');
        const sizeInputs = modalForm.querySelectorAll('input[name="size_id"]');
        const drinkInput = modalForm.querySelector('select[name="drink_id"]');
        const qtyInput = modalForm.querySelector('input[name="quantity"]');
        const btnMinus = modalForm.querySelector('.btn-qty-minus');
        const btnPlus = modalForm.querySelector('.btn-qty-plus');
        const displayTotal = document.getElementById('modalTotalPrice');

        function calculateTotal() {
            let total = 0;

            // 1. Get Size Price (or Fallback to Base Price)
            let selectedSizePrice = basePrice;
            if (sizeSelect) {
                const selectedOpt = sizeSelect.options[sizeSelect.selectedIndex];
                selectedSizePrice = parseFloat(selectedOpt.getAttribute('data-price')) || 0;
            } else if (sizeInputs.length > 0) {
                sizeInputs.forEach(radio => {
                    if (radio.checked) {
                        selectedSizePrice = parseFloat(radio.getAttribute('data-price')) || 0;
                    }
                });
            }
            total += selectedSizePrice;

            // 2. Addons Price (Checkboxes)
            const addonChecks = modalForm.querySelectorAll('.addon-check:checked');
            addonChecks.forEach(check => {
                total += parseFloat(check.getAttribute('data-price')) || 0;
            });

            // 3. Drink Price (Select Dropdown OR Radio List Box)
            const drinkSelect = modalForm.querySelector('select[name="drink_id"]');
            const drinkRadios = modalForm.querySelectorAll('input[name="drink_id"]');
            let selectedDrinkPrice = 0;
            if (drinkSelect) {
                const selectedDrink = drinkSelect.options[drinkSelect.selectedIndex];
                selectedDrinkPrice = parseFloat(selectedDrink.getAttribute('data-price')) || 0;
            } else if (drinkRadios.length > 0) {
                drinkRadios.forEach(radio => {
                    if (radio.checked) {
                        selectedDrinkPrice = parseFloat(radio.getAttribute('data-price')) || 0;
                    }
                });
            }
            total += selectedDrinkPrice;

            // 4. Quantity Stepper
            const quantity = parseInt(qtyInput.value) || 1;
            total = total * quantity;

            // 5. Update UI
            if (displayTotal) {
                displayTotal.innerText = 'Rs. ' + total.toFixed(2);
            }
        }

        // Bind addon checkboxes to price update
        const addonChecks = modalForm.querySelectorAll('.addon-check');
        addonChecks.forEach(check => check.addEventListener('change', calculateTotal));

        // Event Listeners for Live Pricing
        if (sizeSelect) sizeSelect.addEventListener('change', calculateTotal);
        sizeInputs.forEach(input => input.addEventListener('change', calculateTotal));
        const drinkSelect = modalForm.querySelector('select[name="drink_id"]');
        const drinkRadios = modalForm.querySelectorAll('input[name="drink_id"]');
        if (drinkSelect) drinkSelect.addEventListener('change', calculateTotal);
        drinkRadios.forEach(radio => radio.addEventListener('change', calculateTotal));

        if (btnMinus && btnPlus && qtyInput) {
            btnMinus.addEventListener('click', function () {
                let currentVal = parseInt(qtyInput.value) || 1;
                if (currentVal > 1) {
                    qtyInput.value = currentVal - 1;
                    calculateTotal();
                }
            });
            btnPlus.addEventListener('click', function () {
                let currentVal = parseInt(qtyInput.value) || 1;
                qtyInput.value = currentVal + 1;
                calculateTotal();
            });
            qtyInput.addEventListener('change', function () {
                let val = parseInt(this.value);
                if (isNaN(val) || val < 1) {
                    this.value = 1;
                }
                calculateTotal();
            });
        }

        // Initialize Calculation once
        calculateTotal();

        // --- 4. Add to Cart Form Submission (AJAX) ---
        modalForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(modalForm);
            const params = new URLSearchParams(formData).toString();

            fetch('api/add_to_cart.php?action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    productDetailModal.hide();
                    updateCartBadge(data.cart_count);
                    loadCartSidebar();
                    
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'success',
                        title: 'Item added to cart!'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Could not add item to cart.',
                        confirmButtonColor: '#FF6B00'
                    });
                }
            });
        });
    }


    // --- 5. Cart Sidebar Sync and Hydration ---
    window.loadCartSidebar = function() {
        const sidebarContainer = document.getElementById('cartItemsContainer');
        if (!sidebarContainer) return;

        fetch('api/add_to_cart.php?action=get_cart')
            .then(res => res.json())
            .then(data => {
                updateCartBadge(data.cart_count);
                
                // Set totals
                document.getElementById('cartSubtotal').innerText = 'Rs. ' + data.subtotal.toFixed(2);
                document.getElementById('cartDeliveryFee').innerText = 'Rs. ' + data.delivery_fee.toFixed(2);
                document.getElementById('cartGrandTotal').innerText = 'Rs. ' + data.grand_total.toFixed(2);

                if (Object.keys(data.items).length === 0) {
                    sidebarContainer.innerHTML = `
                        <div class="text-center py-5">
                            <i class="bi bi-cart-x fs-1 text-muted"></i>
                            <p class="mt-3 mb-0 text-muted">Your cart is empty.</p>
                        </div>
                    `;
                    const checkoutBtn = document.getElementById('btnProceedCheckout');
                    if (checkoutBtn) checkoutBtn.classList.add('disabled');
                    return;
                }

                const checkoutBtn = document.getElementById('btnProceedCheckout');
                if (checkoutBtn) checkoutBtn.classList.remove('disabled');

                let html = '';
                for (const key in data.items) {
                    const item = data.items[key];
                    
                    // Format Addons description
                    let addonsStr = '';
                    if (item.addons && item.addons.length > 0) {
                        addonsStr = 'Add-ons: ' + item.addons.map(a => `${a.name} (+Rs. ${a.price})`).join(', ');
                    }

                    // Format Drink description
                    let drinkStr = item.drink_name ? `Drink: ${item.drink_name}` : '';
                    let detailsArr = [];
                    if (item.size_name) detailsArr.push(`Size: ${item.size_name}`);
                    if (drinkStr) detailsArr.push(drinkStr);
                    if (addonsStr) detailsArr.push(addonsStr);
                    const metaText = detailsArr.join(' | ');

                    const imgSrc = item.image_url;

                    html += `
                        <div class="cart-item-row" data-key="${key}">
                            <img src="${imgSrc}" class="cart-item-img" alt="${item.product_name}" onerror="this.src='https://placehold.co/100x100/FFF8F0/FF6B00?text=Food'">
                            <div class="cart-item-details">
                                <div class="cart-item-title">${item.product_name}</div>
                                <div class="cart-item-meta">${metaText}</div>
                                <div class="cart-item-actions">
                                    <div class="quantity-stepper scale-down">
                                        <button type="button" class="btn-sidebar-minus" data-key="${key}">-</button>
                                        <input type="text" value="${item.quantity}" readonly>
                                        <button type="button" class="btn-sidebar-plus" data-key="${key}">+</button>
                                    </div>
                                    <span class="cart-item-price">Rs. ${item.line_total.toFixed(2)}</span>
                                    <button class="cart-remove-btn" data-key="${key}">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                }
                sidebarContainer.innerHTML = html;
                bindSidebarEvents();
            });
    }

    function bindSidebarEvents() {
        // Sidebar quantity minus
        document.querySelectorAll('.btn-sidebar-minus').forEach(btn => {
            btn.addEventListener('click', function () {
                const key = this.getAttribute('data-key');
                const input = this.nextElementSibling;
                let val = parseInt(input.value) || 1;
                if (val > 1) {
                    updateCartQuantity(key, val - 1);
                } else {
                    removeCartItem(key);
                }
            });
        });

        // Sidebar quantity plus
        document.querySelectorAll('.btn-sidebar-plus').forEach(btn => {
            btn.addEventListener('click', function () {
                const key = this.getAttribute('data-key');
                const input = this.previousElementSibling;
                let val = parseInt(input.value) || 1;
                updateCartQuantity(key, val + 1);
            });
        });

        // Sidebar remove button
        document.querySelectorAll('.cart-remove-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const key = this.getAttribute('data-key');
                removeCartItem(key);
            });
        });
    }

    function updateCartQuantity(key, newQty) {
        fetch('api/add_to_cart.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `cart_key=${key}&quantity=${newQty}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadCartSidebar();
            }
        });
    }

    function removeCartItem(key) {
        fetch('api/add_to_cart.php?action=remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `cart_key=${key}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadCartSidebar();
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                Toast.fire({
                    icon: 'info',
                    title: 'Item removed from cart'
                });
            }
        });
    }

    function updateCartBadge(count) {
        const badgeEl = document.querySelector('.cart-badge');
        if (badgeEl) {
            badgeEl.innerText = count;
            if (count === 0) {
                badgeEl.style.display = 'none';
            } else {
                badgeEl.style.display = 'flex';
            }
        }
    }

    // Initial sidebar load
    loadCartSidebar();
});
