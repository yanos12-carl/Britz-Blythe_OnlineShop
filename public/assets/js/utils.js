/**
 * Utility functions for the eCommerce site
 */

/**
 * Shows a temporary notification toast
 * @param {string} message - The message to display
 * @param {string} type - 'success' or 'error'
 */
function showNotification(message, type = 'success') {
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;';
        document.body.appendChild(container);
    }
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${type === 'success' ? 'alert-success' : 'alert-error'}`;
    alertDiv.style.cssText = 'margin-bottom:0.5rem;padding:1rem 1.5rem;border-radius:12px;font-weight:600;' +
        (type === 'success' ? 'background:rgba(16,185,129,0.15);color:#16a34a;border:1px solid rgba(16,185,129,0.3);' :
            'background:rgba(239,68,68,0.15);color:#dc2626;border:1px solid rgba(239,68,68,0.3);');
    alertDiv.textContent = message;
    container.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 4000);
}