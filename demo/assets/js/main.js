/**
 * Smart Car Parking System - Main JavaScript
 * Handles all client-side functionality
 */

// Global variables
let sessionTimer = null;
let selectedSlot = null;
let refreshInterval = null;

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize dropdown menus
    initializeDropdowns();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize modals
    initializeModals();
    
    // Initialize parking map interactions
    initializeParkingMap();
    
    // Initialize session timer if on dashboard
    if (document.querySelector('.session-timer')) {
        startSessionTimer();
    }
    
    // Initialize auto-refresh for slot status
    if (document.querySelector('.parking-grid')) {
        startSlotRefresh();
    }
    
    // Initialize card formatting for payment forms
    initializeCardFormatting();
    
    // Initialize confirm dialogs
    initializeConfirmDialogs();
    
    // Initialize toast notifications
    initializeToasts();
}

// Toast Notifications
function showToast(message, type = 'info', duration = null) {
    // Set duration based on type if not specified
    if (duration === null) {
        switch(type) {
            case 'success': duration = 2500; break;
            case 'error': duration = 4000; break;
            case 'info': duration = 3000; break;
            default: duration = 3000;
        }
    }
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    // Add close button
    const closeBtn = document.createElement('button');
    closeBtn.className = 'toast-close';
    closeBtn.innerHTML = '×';
    closeBtn.onclick = () => removeToast(toast);
    toast.appendChild(closeBtn);
    
    // Add to container
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
        
        // Add container styles
        container.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 1001;
            max-width: 400px;
        `;
    }
    
    container.appendChild(toast);
    
    // Store timeout reference
    let timeoutId = setTimeout(() => removeToast(toast), duration);
    
    // Pause auto-dismiss on hover
    toast.addEventListener('mouseenter', () => {
        clearTimeout(timeoutId);
    });
    
    // Resume auto-dismiss on mouse leave
    toast.addEventListener('mouseleave', () => {
        timeoutId = setTimeout(() => removeToast(toast), 1000);
    });
    
    // Animate in
    setTimeout(() => toast.classList.add('show'), 10);
}

function removeToast(toast) {
    toast.classList.remove('show');
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 300);
}

function initializeToasts() {
    // Add toast styles
    const style = document.createElement('style');
    style.textContent = `
        .toast {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-sm);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .toast-success {
            border-color: var(--success-green);
            color: var(--success-green);
        }
        
        .toast-error {
            border-color: var(--error-red);
            color: var(--error-red);
        }
        
        .toast-info {
            border-color: var(--info-blue);
            color: var(--info-blue);
        }
        
        .toast-close {
            background: none;
            border: none;
            color: inherit;
            font-size: 1.5rem;
            cursor: pointer;
            margin-left: var(--spacing-md);
            opacity: 0.7;
        }
        
        .toast-close:hover {
            opacity: 1;
        }
    `;
    document.head.appendChild(style);
}

// Form Validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => {
                // Clear error on input
                const errorElement = input.parentNode.querySelector('.form-error');
                if (errorElement) {
                    errorElement.textContent = '';
                }
            });
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('.form-input[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(input) {
    const value = input.value.trim();
    const type = input.type;
    const name = input.name;
    let error = '';
    
    // Required validation
    if (input.hasAttribute('required') && !value) {
        error = `${getFieldName(name)} is required`;
    }
    
    // Email validation
    else if (type === 'email' && value && !isValidEmail(value)) {
        error = 'Please enter a valid email address';
    }
    
    // Password validation
    else if (name === 'password' && value && value.length < 6) {
        error = 'Password must be at least 6 characters';
    }
    
    // Password confirmation
    else if (name === 'confirm_password') {
        const password = document.querySelector('input[name="password"]');
        if (password && value !== password.value) {
            error = 'Passwords do not match';
        }
    }
    
    // Phone validation (if needed)
    else if (name === 'phone' && value && !isValidPhone(value)) {
        error = 'Please enter a valid phone number';
    }
    
    // Display error
    const errorElement = input.parentNode.querySelector('.form-error');
    if (errorElement) {
        errorElement.textContent = error;
    }
    
    return !error;
}

function getFieldName(name) {
    return name.charAt(0).toUpperCase() + name.slice(1).replace('_', ' ');
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return /^[\d\s\-\+\(\)]+$/.test(phone) && phone.length >= 10;
}

// Parking Map Interactions — only active on client dashboard
function initializeParkingMap() {
    const slots = document.querySelectorAll('.parking-slot.available');
    slots.forEach(slot => {
        slot.addEventListener('click', function() {
            // selectSlot is defined per-page (dashboard-client.php)
            if (typeof selectSlot === 'function') {
                selectSlot(this);
            }
        });
    });
}

function selectSlot(slotElement) {
    // Default no-op — overridden by dashboard-client.php
}

// Session Timer
function startSessionTimer() {
    const timerElement = document.querySelector('.session-timer');
    const checkinTimeElement = document.querySelector('[data-checkin-time]');
    
    if (!timerElement || !checkinTimeElement) return;
    
    const checkinTime = new Date(checkinTimeElement.dataset.checkinTime);
    
    function updateTimer() {
        const now = new Date();
        const duration = Math.floor((now - checkinTime) / 1000); // seconds
        
        const hours = Math.floor(duration / 3600);
        const minutes = Math.floor((duration % 3600) / 60);
        const seconds = duration % 60;
        
        timerElement.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }
    
    updateTimer();
    sessionTimer = setInterval(updateTimer, 1000);
}

function stopSessionTimer() {
    if (sessionTimer) {
        clearInterval(sessionTimer);
        sessionTimer = null;
    }
}

// Auto-refresh Slot Status
function startSlotRefresh() {
    refreshInterval = setInterval(() => {
        refreshSlotStatus();
    }, 30000); // Refresh every 30 seconds
}

function refreshSlotStatus() {
    fetch('api/get-slot-status.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.slots) {
                data.data.slots.forEach(slot => {
                    updateSlotStatus(slot.id, slot.current_status);
                });
            }
        })
        .catch(error => {
            console.error('Error refreshing slot status:', error);
        });
}

function updateSlotStatus(slotId, status) {
    const slotElement = document.querySelector(`[data-slot-id="${slotId}"]`);
    if (!slotElement) return;

    slotElement.classList.remove('available', 'occupied', 'user-slot');
    slotElement.classList.add(status);

    if (status === 'available') {
        slotElement.style.cursor = 'pointer';
        slotElement.onclick = () => { if (typeof selectSlot === 'function') selectSlot(slotElement); };
    } else {
        slotElement.style.cursor = 'not-allowed';
        slotElement.onclick = null;
    }
}

// Card Formatting
function initializeCardFormatting() {
    const cardInput = document.querySelector('input[name="card_number"]');
    if (!cardInput) return;
    
    cardInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        e.target.value = formattedValue;
    });
    
    cardInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.querySelector('input[name="expiry"]').focus();
        }
    });
    
    // Expiry date formatting
    const expiryInput = document.querySelector('input[name="expiry"]');
    if (expiryInput) {
        expiryInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            e.target.value = value;
        });
    }
    
    // CVV formatting
    const cvvInput = document.querySelector('input[name="cvv"]');
    if (cvvInput) {
        cvvInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 3);
        });
    }
}

// Confirm Dialogs
function initializeConfirmDialogs() {
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    
    confirmButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.dataset.confirm;
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// Modals
function initializeModals() {
    // Modal triggers
    const modalTriggers = document.querySelectorAll('[data-modal]');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.dataset.modal;
            openModal(modalId);
        });
    });
    
    // Modal close buttons
    const closeButtons = document.querySelectorAll('.modal-close');
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal);
        });
    });
    
    // Close modal on background click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target);
        }
    });
    
    // Close modal on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.active');
            if (openModal) {
                closeModal(openModal);
            }
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Focus first input
        const firstInput = modal.querySelector('input, button, select, textarea');
        if (firstInput) {
            firstInput.focus();
        }
    }
}

function closeModal(modal) {
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            showTooltip(this);
        });
        
        element.addEventListener('mouseleave', function() {
            hideTooltip();
        });
    });
}

function showTooltip(element) {
    const text = element.dataset.tooltip;
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    
    // Position tooltip
    const rect = element.getBoundingClientRect();
    tooltip.style.cssText = `
        position: fixed;
        top: ${rect.top - 30}px;
        left: ${rect.left + rect.width / 2}px;
        transform: translateX(-50%);
        background: var(--card-bg);
        color: var(--text-primary);
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        border: 1px solid var(--border-color);
        z-index: 1000;
        white-space: nowrap;
    `;
    
    document.body.appendChild(tooltip);
    
    // Adjust position if needed
    const tooltipRect = tooltip.getBoundingClientRect();
    if (tooltipRect.left < 0) {
        tooltip.style.left = '10px';
        tooltip.style.transform = 'none';
    }
    if (tooltipRect.right > window.innerWidth) {
        tooltip.style.left = 'auto';
        tooltip.style.right = '10px';
        tooltip.style.transform = 'none';
    }
}

function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

// Dropdown Menus
function initializeDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        
        if (trigger) {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                toggleDropdown(dropdown);
            });
        }
        
        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                closeDropdown(dropdown);
            }
        });
    });
}

function toggleDropdown(dropdown) {
    const menu = dropdown.querySelector('.dropdown-menu');
    const isOpen = menu.style.display === 'block';
    
    // Close all other dropdowns
    document.querySelectorAll('.dropdown-menu').forEach(m => {
        m.style.display = 'none';
    });
    
    // Toggle current dropdown
    menu.style.display = isOpen ? 'none' : 'block';
}

function closeDropdown(dropdown) {
    const menu = dropdown.querySelector('.dropdown-menu');
    if (menu) {
        menu.style.display = 'none';
    }
}

// AJAX Functions
function ajaxRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    return fetch(url, options)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
}

// Loading States
function showLoading(element) {
    element.disabled = true;
    element.dataset.originalText = element.textContent;
    element.innerHTML = '<span class="loading"></span> Loading...';
}

function hideLoading(element) {
    element.disabled = false;
    element.textContent = element.dataset.originalText;
}

// Utility Functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-RW', {
        style: 'currency',
        currency: 'RWF'
    }).format(amount);
}

function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleString('en-RW', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatDuration(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    
    if (hours > 0) {
        return `${hours}h ${mins}m`;
    }
    return `${mins}m`;
}

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

// Parking History (for client dashboard)
function showParkingHistory() {
    ajaxRequest('api/get-parking-history.php')
        .then(data => {
            displayParkingHistory(data);
        })
        .catch(error => {
            showToast('Error loading parking history', 'error');
            console.error('Error:', error);
        });
}

function displayParkingHistory(sessions) {
    const modal = document.createElement('div');
    modal.className = 'modal active';
    modal.id = 'parking-history-modal';
    
    let content = `
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Parking History</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Slot</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Duration</th>
                                <th>Fee</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    sessions.forEach(session => {
        content += `
            <tr>
                <td>${session.slot_code}</td>
                <td>${formatDateTime(session.checkin_time)}</td>
                <td>${session.checkout_time ? formatDateTime(session.checkout_time) : 'Active'}</td>
                <td>${session.duration ? formatDuration(session.duration) : '-'}</td>
                <td>${session.fee_amount ? formatCurrency(session.fee_amount) : '-'}</td>
            </tr>
        `;
    });
    
    content += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    modal.innerHTML = content;
    document.body.appendChild(modal);
    
    // Initialize modal close
    modal.querySelector('.modal-close').addEventListener('click', () => {
        modal.remove();
    });
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    stopSessionTimer();
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

// Export functions for use in other scripts
window.SmartParking = {
    showToast,
    showLoading,
    hideLoading,
    formatCurrency,
    formatDateTime,
    formatDuration,
    ajaxRequest,
    openModal,
    closeModal,
    showParkingHistory
};
