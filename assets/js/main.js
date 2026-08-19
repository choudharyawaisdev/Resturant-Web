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
                fetch('api/add_to_cart?action=set_location', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=set_location&area_id=${encodeURIComponent(areaId)}`
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
                            text: data.message || 'Something went wrong. Please try again.',
                            confirmButtonColor: '#FF6B00'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Unable to connect to server. Please try again.',
                        confirmButtonColor: '#FF6B00'
                    });
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

        fetch(`product-modal?id=${productId}&_t=${Date.now()}`)
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
            if (!formData.has('action')) {
                formData.append('action', 'add');
            }
            const params = new URLSearchParams(formData).toString();

            fetch('api/add_to_cart?action=add', {
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
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to add item to cart.',
                    confirmButtonColor: '#FF6B00'
                });
            });
        });
    }


    // --- 5. Cart Sidebar Sync and Hydration ---
    window.loadCartSidebar = function() {
        const sidebarContainer = document.getElementById('cartItemsContainer');
        if (!sidebarContainer) return;

        fetch('api/add_to_cart?action=get_cart')
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
                for (const [key, item] of Object.entries(data.items)) {
                    let addonsHtml = '';
                    if (item.addons && item.addons.length > 0) {
                        addonsHtml = `<div class="small text-muted">+ ${item.addons.map(a => a.name).join(', ')}</div>`;
                    }
                    let drinkHtml = '';
                    if (item.drink_name) {
                        drinkHtml = `<div class="small text-muted">+ ${item.drink_name}</div>`;
                    }
                    let sizeHtml = '';
                    if (item.size_name) {
                        sizeHtml = `<span class="badge bg-light text-dark border me-1">${item.size_name}</span>`;
                    }

                    html += `
                        <div class="cart-item d-flex align-items-center justify-content-between p-3 border-bottom position-relative">
                            <img src="${item.image_url}" class="rounded-3 me-3" style="width: 50px; height: 50px; object-fit: cover;" alt="${item.product_name}">
                            <div class="flex-grow-1 me-2">
                                <h6 class="mb-0 fw-bold fs-6">${item.product_name}</h6>
                                <div class="mb-1">${sizeHtml}</div>
                                ${addonsHtml}
                                ${drinkHtml}
                                <div class="cart-item-price fw-bold text-orange mt-1">Rs. ${item.line_total.toFixed(2)}</div>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-2">
                                <button class="btn btn-sm text-danger border-0 p-0 btn-remove-item" data-key="${key}" title="Remove Item">
                                    <i class="bi bi-trash3"></i>
                                </button>
                                <div class="input-group input-group-sm cart-qty-group" style="width: 90px;">
                                    <button class="btn btn-outline-secondary btn-decrease-qty" data-key="${key}" type="button">-</button>
                                    <input type="text" class="form-control text-center bg-white p-0" value="${item.quantity}" readonly>
                                    <button class="btn btn-outline-secondary btn-increase-qty" data-key="${key}" type="button">+</button>
                                </div>
                            </div>
                        </div>
                    `;
                }

                sidebarContainer.innerHTML = html;

                // Bind Cart Action Buttons
                bindCartEvents();
            })
            .catch(err => {
                console.error('Error fetching cart:', err);
            });
    };

    function bindCartEvents() {
        document.querySelectorAll('.btn-decrease-qty').forEach(btn => {
            btn.addEventListener('click', function() {
                const key = this.getAttribute('data-key');
                const input = this.parentElement.querySelector('input');
                let qty = parseInt(input.value);
                if (qty > 1) {
                    updateCartQuantity(key, qty - 1);
                } else {
                    removeCartItem(key);
                }
            });
        });

        document.querySelectorAll('.btn-increase-qty').forEach(btn => {
            btn.addEventListener('click', function() {
                const key = this.getAttribute('data-key');
                const input = this.parentElement.querySelector('input');
                let qty = parseInt(input.value);
                updateCartQuantity(key, qty + 1);
            });
        });

        document.querySelectorAll('.btn-remove-item').forEach(btn => {
            btn.addEventListener('click', function() {
                const key = this.getAttribute('data-key');
                removeCartItem(key);
            });
        });
    }

    function updateCartQuantity(key, newQty) {
        fetch('api/add_to_cart?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update&cart_key=${encodeURIComponent(key)}&quantity=${encodeURIComponent(newQty)}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadCartSidebar();
            }
        });
    }

    function removeCartItem(key) {
        fetch('api/add_to_cart?action=remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=remove&cart_key=${encodeURIComponent(key)}`
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
        const badgeEls = document.querySelectorAll('.cart-badge');
        badgeEls.forEach(badgeEl => {
            badgeEl.innerText = count;
            if (count === 0) {
                badgeEl.style.display = 'none';
            } else {
                badgeEl.style.display = 'flex';
            }
        });
    }

    // Initial sidebar load
    loadCartSidebar();
});
