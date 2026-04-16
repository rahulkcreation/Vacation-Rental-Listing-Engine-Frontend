/**
 * toaster.js
 * 
 * Global Toast notification component.
 * Uses the #lef-toaster-container from toaster.php.
 */
const LEF_Toast = {
    container: null,

    init() {
        // Container is already provided by toaster.php in the footer
        this.container = document.getElementById('lef-toaster-container');
        
        // Fallback for extreme cases
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'lef-toaster-container';
            this.container.className = 'lef-toaster-container';
            document.body.appendChild(this.container);
        }
    },

    show(message, type = 'info', duration = 2000) {
        if (!this.container) this.init();

        const toast = document.createElement('div');
        toast.className = `lef-toast ${type}`;
        
        // Simple icon based on type
        let icon = '🔔';
        if (type === 'error') icon = '❌';
        if (type === 'success') icon = '✅';

        toast.innerHTML = `
            <span class="lef-toast-icon">${icon}</span>
            <span class="lef-toast-message">${message}</span>
        `;

        this.container.appendChild(toast);

        // Animate in
        setTimeout(() => toast.classList.add('show'), 10);

        // Auto remove
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, duration);
    }
};

window.LEF_Toast = LEF_Toast;
