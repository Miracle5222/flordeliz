; (function () {
    function init() {
        const customerSelect = document.getElementById('customerSelect');
        const customerName = document.getElementById('customerName');
        const customerPhone = document.getElementById('customerPhone');
        const customerCategory = document.getElementById('customerCategory');
        const productSelect = document.getElementById('productSelect');
        const quantity = document.getElementById('quantity');
        const downpaymentInput = document.getElementById('downpayment');
        const form = document.getElementById('createOrderForm');

        // Bulk discount tiers
        const discountTiers = [
            { min: 100, discount: 0.05 },  // 5% off
            { min: 500, discount: 0.10 },  // 10% off
            { min: 1000, discount: 0.15 }  // 15% off
        ];

        // Store products
        let orderProducts = [];

        // Customer selection
        if (customerSelect) {
            customerSelect.addEventListener('change', function () {
                if (this.value) {
                    const option = this.options[this.selectedIndex];
                    customerName.value = option.dataset.name;
                    customerPhone.value = option.dataset.phone;
                    customerCategory.value = option.dataset.category;
                    customerName.setAttribute('data-customer-id', this.value);
                } else {
                    customerName.value = '';
                    customerPhone.value = '';
                    customerCategory.value = '';
                    customerName.removeAttribute('data-customer-id');
                }
            });
        }

        // Add product
        window.addProduct = function () {
            if (!productSelect || !productSelect.value) {
                showAlert('Please select a product', 'error');
                return;
            }

            const option = productSelect.options[productSelect.selectedIndex];
            const productId = productSelect.value;
            const productName = option.dataset.name;
            const unitPrice = parseFloat(option.dataset.price);
            const qty = parseInt(quantity.value);

            if (qty <= 0) {
                showAlert('Quantity must be greater than 0', 'error');
                return;
            }

            // Check if product already exists
            const existing = orderProducts.find(p => p.product_id == productId);
            if (existing) {
                existing.quantity += qty;
                existing.subtotal = existing.quantity * existing.unit_price;
            } else {
                orderProducts.push({
                    product_id: productId,
                    name: productName,
                    quantity: qty,
                    unit_price: unitPrice,
                    subtotal: qty * unitPrice
                });
            }

            // Reset inputs
            productSelect.value = '';
            quantity.value = '1';

            // Update display
            updateProductsList();
            updateSummary();
        };

        // Remove product
        window.removeProduct = function (productId) {
            orderProducts = orderProducts.filter(p => p.product_id != productId);
            updateProductsList();
            updateSummary();
        };

        function updateProductsList() {
            const list = document.getElementById('productsList');
            const summary = document.getElementById('summaryProducts');

            if (!list || !summary) return;

            if (orderProducts.length === 0) {
                list.innerHTML = '<p class="text-gray-500 text-sm">No products added</p>';
                summary.innerHTML = '<p class="text-gray-500 text-sm">No products added</p>';
                return;
            }

            list.innerHTML = orderProducts.map((p, idx) => `
                <div class="flex justify-between items-center bg-gray-50 p-4 rounded-lg">
                    <div>
                        <p class="font-semibold text-gray-900">${p.name}</p>
                        <p class="text-sm text-gray-600">${p.quantity} x ₱${p.unit_price.toFixed(2)} = ₱${p.subtotal.toFixed(2)}</p>
                    </div>
                    <button type="button" onclick="removeProduct(${p.product_id})" class="text-red-600 hover:text-red-800 font-semibold">Remove</button>
                </div>
            `).join('');

            summary.innerHTML = orderProducts.map(p => `
                <div class="flex justify-between text-sm">
                    <span class="text-gray-700">${p.quantity}x ${p.name}</span>
                    <span class="font-semibold">₱${p.subtotal.toFixed(2)}</span>
                </div>
            `).join('');
        }

        function calculateDiscount(subtotal) {
            for (let tier of discountTiers) {
                if (subtotal >= tier.min) {
                    return subtotal * tier.discount;
                }
            }
            return 0;
        }

        function updateSummary() {
            const subtotalEl = document.getElementById('subtotal');
            const discountEl = document.getElementById('discount');
            const totalEl = document.getElementById('total');
            const downpaymentDisplay = document.getElementById('downpaymentDisplay');
            const remainingEl = document.getElementById('remaining');

            if (!subtotalEl || !discountEl || !totalEl || !downpaymentDisplay || !remainingEl) return;

            if (orderProducts.length === 0) {
                subtotalEl.textContent = '₱0.00';
                discountEl.textContent = '₱0.00';
                totalEl.textContent = '₱0.00';
                downpaymentDisplay.textContent = '₱0.00';
                remainingEl.textContent = '₱0.00';
                return;
            }

            const subtotal = orderProducts.reduce((sum, p) => sum + p.subtotal, 0);
            const discount = calculateDiscount(subtotal);
            const total = subtotal - discount;
            const downpayment = parseFloat(downpaymentInput.value) || 0;
            const remaining = total - downpayment;

            subtotalEl.textContent = '₱' + subtotal.toFixed(2);
            discountEl.textContent = '₱' + discount.toFixed(2);
            totalEl.textContent = '₱' + total.toFixed(2);
            downpaymentDisplay.textContent = '₱' + downpayment.toFixed(2);
            remainingEl.textContent = '₱' + remaining.toFixed(2);
        }

        // Update summary on downpayment change
        if (downpaymentInput) downpaymentInput.addEventListener('change', updateSummary);

        // Form submission
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                if (!customerName.value.trim()) {
                    showAlert('Please enter customer name', 'error');
                    return;
                }

                if (orderProducts.length === 0) {
                    showAlert('Please add at least one product', 'error');
                    return;
                }

                if (!document.getElementById('deliveryDate').value) {
                    showAlert('Please select delivery date', 'error');
                    return;
                }

                // Prepare form data
                const formData = new FormData();
                formData.append('customer_id', customerName.getAttribute('data-customer-id') || '');
                formData.append('customer_name', customerName.value);
                formData.append('customer_phone', customerPhone.value);
                formData.append('customer_category', customerCategory.value);
                formData.append('delivery_date', document.getElementById('deliveryDate').value);
                formData.append('downpayment', downpaymentInput.value || 0);
                formData.append('notes', document.getElementById('notes').value);
                formData.append('products', JSON.stringify(orderProducts));

                // Submit via AJAX
                fetch(this.getAttribute('action') || '', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert(`✓ Order ${data.order_number} created successfully!`, 'success');
                            setTimeout(() => {
                                window.location.href = '../orders.php';
                            }, 2000);
                        } else {
                            showAlert(data.message || 'Error creating order', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('Error creating order', 'error');
                    });
            });
        }

        function showAlert(message, type) {
            const container = document.getElementById('alert-container');
            if (!container) return;
            const bgColor = type === 'success' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
            const textColor = type === 'success' ? 'text-green-800' : 'text-red-800';
            const icon = type === 'success' ? '✓' : '✕';

            const alertEl = document.createElement('div');
            alertEl.className = `border rounded-lg p-4 mb-4 ${bgColor} ${textColor}`;
            alertEl.innerHTML = `<span class="font-semibold">${icon}</span> ${message}`;
            container.appendChild(alertEl);

            setTimeout(() => {
                alertEl.remove();
            }, 4000);
        }

        // Initialize summary
        updateSummary();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
