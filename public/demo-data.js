// Demo data for static Netlify deployment
// This replaces PHP backend functionality for demo purposes

const demoData = {
    // Demo parking slots
    slots: [
        {id: 1, slot_code: 'A1', slot_type: 'standard', floor: 1, status: 'available'},
        {id: 2, slot_code: 'A2', slot_type: 'standard', floor: 1, status: 'occupied'},
        {id: 3, slot_code: 'A3', slot_type: 'standard', floor: 1, status: 'available'},
        {id: 4, slot_code: 'A4', slot_type: 'vip', floor: 1, status: 'available'},
        {id: 5, slot_code: 'A5', slot_type: 'vip', floor: 1, status: 'occupied'},
        {id: 6, slot_code: 'A6', slot_type: 'disabled', floor: 1, status: 'available'},
        {id: 7, slot_code: 'B1', slot_type: 'standard', floor: 2, status: 'available'},
        {id: 8, slot_code: 'B2', slot_type: 'standard', floor: 2, status: 'occupied'},
        {id: 9, slot_code: 'B3', slot_type: 'standard', floor: 2, status: 'available'},
        {id: 10, slot_code: 'B4', slot_type: 'vip', floor: 2, status: 'available'}
    ],
    
    // Demo users
    users: [
        {id: 1, full_name: 'John Doe', email: 'john@example.com', role: 'client', is_active: 1},
        {id: 2, full_name: 'Jane Smith', email: 'jane@example.com', role: 'client', is_active: 1},
        {id: 3, full_name: 'Admin User', email: 'admin@parking.com', role: 'admin', is_active: 1}
    ],
    
    // Demo parking sessions
    sessions: [
        {id: 1, user_id: 1, slot_id: 2, checkin_time: '2024-03-28 10:00:00', checkout_time: null, fee_amount: 0, status: 'active'},
        {id: 2, user_id: 2, slot_id: 5, checkin_time: '2024-03-28 09:30:00', checkout_time: null, fee_amount: 0, status: 'active'},
        {id: 3, user_id: 1, slot_id: 3, checkin_time: '2024-03-28 08:00:00', checkout_time: '2024-03-28 10:30:00', fee_amount: 400, status: 'completed'}
    ],
    
    // Demo payments
    payments: [
        {id: 1, session_id: 3, user_id: 1, amount: 400, payment_method: 'card', payment_status: 'paid', paid_at: '2024-03-28 10:35:00'}
    ]
};

// Mock API functions for demo
const mockAPI = {
    // Get slot status
    getSlotStatus: () => {
        return new Promise((resolve) => {
            setTimeout(() => resolve(demoData.slots), 500);
        });
    },
    
    // Get dashboard stats
    getDashboardStats: () => {
        return new Promise((resolve) => {
            const stats = {
                total_slots: demoData.slots.length,
                available_slots: demoData.slots.filter(s => s.status === 'available').length,
                occupied_slots: demoData.slots.filter(s => s.status === 'occupied').length,
                active_sessions: demoData.sessions.filter(s => s.status === 'active').length,
                total_users: demoData.users.filter(u => u.role === 'client').length,
                today_revenue: 800,
                total_revenue: 2500
            };
            setTimeout(() => resolve(stats), 500);
        });
    },
    
    // Check email availability
    checkEmailAvailability: (email) => {
        return new Promise((resolve) => {
            const exists = demoData.users.some(u => u.email === email);
            setTimeout(() => resolve(!exists), 300);
        });
    },
    
    // Get user history
    getUserHistory: (userId) => {
        return new Promise((resolve) => {
            const userSessions = demoData.sessions.filter(s => s.user_id == userId);
            setTimeout(() => resolve(userSessions), 500);
        });
    }
};

// Demo notification system
const showDemoNotification = (message, type = 'info') => {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    // Set background color based on type
    switch(type) {
        case 'success':
            notification.style.backgroundColor = '#10b981';
            break;
        case 'error':
            notification.style.backgroundColor = '#ef4444';
            break;
        case 'warning':
            notification.style.backgroundColor = '#f59e0b';
            break;
        default:
            notification.style.backgroundColor = '#3b82f6';
    }
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
};

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { demoData, mockAPI, showDemoNotification };
}
