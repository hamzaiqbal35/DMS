// Customer Panel Main JavaScript

// Global variables
const BASE_URL = 'http://localhost/DMS/';

// Utility functions
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function showLoading(element) {
    if (element) {
        element.disabled = true;
        const spinner = element.querySelector('.spinner-border');
        const icon = element.querySelector('i');
        if (spinner) spinner.classList.remove('d-none');
        if (icon) icon.classList.add('d-none');
    }
}

function hideLoading(element) {
    if (element) {
        element.disabled = false;
        const spinner = element.querySelector('.spinner-border');
        const icon = element.querySelector('i');
        if (spinner) spinner.classList.add('d-none');
        if (icon) icon.classList.remove('d-none');
    }
}

// Cart functions
// Remove or comment out the global addToCart function to prevent double notifications
// function addToCart(itemId, quantity = 1) {
//     if (!isCustomerLoggedIn()) {
//         showAlert('Please login to add items to cart', 'warning');
//         // Redirect to login page
//         setTimeout(() => {
//             window.location.href = BASE_URL + 'customer.php?page=login';
//         }, 2000);
//         return;
//     }
//     
//     showAlert('Adding to cart...', 'info');
//     
//     fetch(`${BASE_URL}api/customer/add-to-cart.php`, {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/json',
//         },
//         body: JSON.stringify({
//             item_id: itemId,
//             quantity: quantity
//         }),
//         credentials: 'include'
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.status === 'success') {
//             showAlert('Product added to cart successfully!', 'success');
//             updateCartCount();
//         } else {
//             showAlert(data.message || 'Failed to add product to cart', 'danger');
//         }
//     })
//     .catch(error => {
//         showAlert('An error occurred. Please try again.', 'danger');
//     });
// }

function updateCartCount() {
    // This function will be implemented to update cart count in navigation
    // For now, we'll reload the page to update the count
    setTimeout(() => {
        location.reload();
    }, 1000);
}

function isCustomerLoggedIn() {
    // Check if customer is logged in by looking for customer session
    // Method 1: Check for data attribute on body
    const bodyElement = document.querySelector('body[data-customer-logged-in="true"]');
    if (bodyElement) {
        return true;
    }
    
    // Method 2: Check for customer dropdown in navigation
    const customerDropdown = document.querySelector('#customerDropdown');
    if (customerDropdown) {
        return true;
    }
    
    // Method 3: Check for cart link (only shown when logged in)
    const cartLink = document.querySelector('a[href*="page=cart"]');
    if (cartLink && cartLink.closest('.navbar-nav')) {
        return true;
    }
    
    // Method 4: Check if we're on a protected page (this means we're logged in)
    const currentUrl = window.location.href;
    const protectedPages = ['dashboard', 'cart', 'checkout', 'my-orders', 'profile'];
    for (const page of protectedPages) {
        if (currentUrl.includes(page)) {
            return true;
        }
    }
    
    return false;
}

// Form validation
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePassword(password) {
    return password.length >= 6;
}

function validatePhone(phone) {
    const re = /^[\+]?[1-9][\d]{0,15}$/;
    return re.test(phone);
}

// API helper functions
async function apiCall(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include'
    };
    
    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        throw error;
    }
}

// Search and filter functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Product search
const searchProducts = debounce(function(searchTerm) {
    if (searchTerm.length < 2) return;
    
    fetch(`${BASE_URL}api/customer/get-products.php?search=${encodeURIComponent(searchTerm)}`, { credentials: 'include' })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                updateProductList(data.products);
            }
        })
        .catch(error => {
        });
}, 300);

function updateProductList(products) {
    const container = document.getElementById('productList');
    if (!container) return;
    
    if (products.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-4">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No products found</h5>
                <p class="text-muted">Try adjusting your search terms.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = products.map(product => `
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card product-card h-100">
                <img src="${product.image_path || BASE_URL + 'assets/images/logo.png'}" 
                     class="card-img-top" alt="${product.item_name}">
                <div class="card-body">
                    <h5 class="card-title">${product.item_name}</h5>
                    <p class="card-text">${product.description || 'Quality steel product'}</p>
                    <div class="product-price">
                        Rs. ${parseFloat(product.customer_price || product.unit_price).toFixed(2)}
                    </div>
                    <div class="product-actions">
                        <a href="${BASE_URL}customer.php?page=product-details&id=${product.item_id}" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>View Details
                        </a>
                        <button class="btn btn-success btn-sm" onclick="addToCart(${product.item_id})">
                            <i class="fas fa-cart-plus me-1"></i>Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Initialize customer panel
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Initialize search functionality
    const searchInput = document.getElementById('productSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchProducts(this.value);
        });
    }
    
    // Initialize quantity spinners
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        const minusBtn = input.parentNode.querySelector('.quantity-minus');
        const plusBtn = input.parentNode.querySelector('.quantity-plus');
        
        if (minusBtn) {
            minusBtn.addEventListener('click', function() {
                const currentValue = parseInt(input.value) || 1;
                if (currentValue > 1) {
                    input.value = currentValue - 1;
                }
            });
        }
        
        if (plusBtn) {
            plusBtn.addEventListener('click', function() {
                const currentValue = parseInt(input.value) || 1;
                input.value = currentValue + 1;
            });
        }
    });
});

// Export functions for use in other scripts
window.CustomerPanel = {
    showAlert,
    showLoading,
    hideLoading,
    updateCartCount,
    isCustomerLoggedIn,
    validateEmail,
    validatePassword,
    validatePhone,
    apiCall,
    searchProducts,
    updateProductList
};

// Cart: Update quantity
$(document).on('change', '.cart-qty-input', function() {
    const cartId = $(this).data('cart-id');
    const newQty = parseInt($(this).val());
    if (newQty < 1) {
        toastr.error('Quantity must be at least 1.');
        return;
    }
    fetch('api/customer/update-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ cart_id: cartId, quantity: newQty })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            toastr.success('Cart updated.');
            reloadCart();
        } else {
            toastr.error(data.message || 'Failed to update cart.');
        }
    })
    .catch(() => toastr.error('Error updating cart.'));
});

// Helper to reload cart (fetch and update cart table)
function reloadCart() {
    fetch('api/customer/get-cart.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            // You may need to re-render the cart table here
            location.reload(); // Simple reload for now
        }
    });
} 