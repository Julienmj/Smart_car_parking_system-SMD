/**
 * Admin Pages JavaScript - Common functionality
 */

// Initialize admin pages
document.addEventListener('DOMContentLoaded', function() {
    initializeAdminPages();
});

function initializeAdminPages() {
    // Initialize modals
    initializeModals();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize table interactions
    initializeTableInteractions();
}

// Modal management
function initializeModals() {
    // Close modals on background click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });
    
    // Close modals on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
    
    // Close buttons
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showError(field, 'This field is required');
            isValid = false;
        } else {
            clearError(field);
        }
    });
    
    return isValid;
}

function showError(field, message) {
    clearError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
    field.classList.add('error');
}

function clearError(field) {
    const errorDiv = field.parentNode.querySelector('.form-error');
    if (errorDiv) {
        errorDiv.remove();
    }
    field.classList.remove('error');
}

// Search functionality
function initializeSearch() {
    const searchInputs = document.querySelectorAll('[id$="Search"]');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            performSearch(this);
        });
    });
}

function performSearch(searchInput) {
    const searchTerm = searchInput.value.toLowerCase();
    const targetSelector = searchInput.dataset.target || '.data-table tbody tr';
    const rows = document.querySelectorAll(targetSelector);
    
    let visibleCount = 0;
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const isVisible = text.includes(searchTerm);
        
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
    });
    
    // Show no results message
    updateNoResultsMessage(visibleCount === 0 && searchTerm);
}

function updateNoResultsMessage(show) {
    let noResultsMsg = document.querySelector('.no-results-message');
    
    if (show && !noResultsMsg) {
        noResultsMsg = document.createElement('div');
        noResultsMsg.className = 'no-results-message';
        noResultsMsg.textContent = 'No results found matching your search';
        noResultsMsg.style.cssText = `
            text-align: center;
            padding: var(--spacing-xl);
            color: var(--text-muted);
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            margin: var(--spacing-lg) 0;
        `;
        
        const table = document.querySelector('.data-table');
        if (table && table.parentNode) {
            table.parentNode.insertBefore(noResultsMsg, table.nextSibling);
        }
    } else if (!show && noResultsMsg) {
        noResultsMsg.remove();
    }
}

// Table interactions
function initializeTableInteractions() {
    // Add hover effects to table rows
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.classList.add('hover');
        });
        
        row.addEventListener('mouseleave', function() {
            this.classList.remove('hover');
        });
    });
}

// Utility functions
function showLoading(element) {
    if (element) {
        element.classList.add('loading');
        element.disabled = true;
    }
}

function hideLoading(element) {
    if (element) {
        element.classList.remove('loading');
        element.disabled = false;
    }
}

function showToast(message, type = 'info') {
    // Create toast if SmartParking is available
    if (window.SmartParking && window.SmartParking.showToast) {
        window.SmartParking.showToast(message, type);
    } else {
        // Fallback toast
        console.log(`${type}: ${message}`);
    }
}

// Export functions for global use
window.AdminUtils = {
    openModal,
    closeModal,
    validateForm,
    showLoading,
    hideLoading,
    showToast
};
