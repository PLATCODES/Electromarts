// Common JavaScript functions

// Confirm before deleting
function confirmDelete(message = 'Are you sure you want to delete this?') {
    return confirm(message);
}

// Toggle password visibility
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
}

// Show/hide loading spinner
function showLoading() {
    document.getElementById('loading-spinner').style.display = 'block';
}

function hideLoading() {
    document.getElementById('loading-spinner').style.display = 'none';
}

// Add to cart functionality
function addToCart(deviceId, deviceName, price) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    const existingItem = cart.find(item => item.id === deviceId);
    
    if (existingItem) {
        existingItem.quantity += 1;
        existingItem.subtotal = existingItem.quantity * existingItem.price;
    } else {
        cart.push({
            id: deviceId,
            name: deviceName,
            price: parseFloat(price),
            quantity: 1,
            subtotal: parseFloat(price)
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    alert(`${deviceName} added to cart!`);
}

// Update cart count in navbar
function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    
    const cartCount = document.getElementById('cart-count');
    if (cartCount) {
        cartCount.textContent = totalItems;
    }
}

// Remove from cart
function removeFromCart(deviceId) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart = cart.filter(item => item.id !== deviceId);
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
}

// Calculate cart total
function calculateCartTotal() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    return cart.reduce((sum, item) => sum + item.subtotal, 0).toFixed(2);
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.style.borderColor = 'red';
        } else {
            input.style.borderColor = '#ddd';
        }
    });
    
    return isValid;
}

// Search functionality
function searchDevices() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const deviceCards = document.querySelectorAll('.device-card');
    
    deviceCards.forEach(card => {
        const deviceName = card.querySelector('.device-name').textContent.toLowerCase();
        const deviceDesc = card.querySelector('.device-desc')?.textContent.toLowerCase() || '';
        
        if (deviceName.includes(searchTerm) || deviceDesc.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Filter by category
function filterByCategory(category) {
    const deviceCards = document.querySelectorAll('.device-card');
    
    deviceCards.forEach(card => {
        const deviceCategory = card.dataset.category;
        
        if (category === 'all' || deviceCategory === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    
    // Add event listeners for search
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keyup', searchDevices);
    }
    
    // Add event listeners for category filters
    const categoryButtons = document.querySelectorAll('.category-filter');
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.dataset.category;
            filterByCategory(category);
            
            // Update active button
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });
});

// Format price
function formatPrice(price) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(price);
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Add notification styles
const style = document.createElement('style');
style.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        z-index: 10000;
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-width: 300px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        animation: slideIn 0.3s ease-out;
    }
    
    .notification-success {
        background-color: #2ecc71;
    }
    
    .notification-error {
        background-color: #e74c3c;
    }
    
    .notification-warning {
        background-color: #f39c12;
    }
    
    .notification button {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        margin-left: 20px;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);

// Enhanced logout functionality
function handleLogout() {
    // Clear localStorage (cart data)
    localStorage.removeItem('cart');
    
    // Clear sessionStorage
    sessionStorage.clear();
    
    // Show logout message
    showNotification('Logging out...', 'success');
    
    // Redirect after delay
    setTimeout(() => {
        window.location.href = 'logout.php';
    }, 1000);
    
    return false; // Prevent default link behavior
}

// Add logout confirmation
document.addEventListener('DOMContentLoaded', function() {
    // Attach logout confirmation to all logout links
    const logoutLinks = document.querySelectorAll('a[href*="logout"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to logout?')) {
                e.preventDefault();
                return false;
            }
            
            // Handle logout with custom function
            e.preventDefault();
            handleLogout();
        });
    });
    
    // Auto-logout after 30 minutes of inactivity
    let logoutTimer;
    function resetLogoutTimer() {
        clearTimeout(logoutTimer);
        logoutTimer = setTimeout(() => {
            if (confirm('You have been inactive for 30 minutes. Would you like to stay logged in?')) {
                resetLogoutTimer();
            } else {
                handleLogout();
            }
        }, 30 * 60 * 1000); // 30 minutes
    }
    
    // Reset timer on user activity
    ['click', 'mousemove', 'keypress', 'scroll'].forEach(event => {
        window.addEventListener(event, resetLogoutTimer);
    });
    
    // Initialize timer if user is logged in
    if (document.body.classList.contains('logged-in')) {
        resetLogoutTimer();
    }
});
