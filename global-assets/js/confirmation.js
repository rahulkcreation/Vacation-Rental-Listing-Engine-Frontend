/**
 * confirmation.js
 * 
 * Global Confirmation Modal component.
 * Uses the #lef-confirmation-modal from confirmation.php.
 */
const LEF_Confirm = {
    modal: null,
    callback: null,

    init() {
        // Modal is already provided by confirmation.php in the footer
        this.modal = document.getElementById('lef-confirmation-modal');
        
        // Setup internal event listeners once
        if (this.modal && !this.modal.dataset.eventsBound) {
            this.modal.querySelector('#lef-confirm-no').onclick = () => this.close(false);
            this.modal.querySelector('#lef-confirm-yes').onclick = () => this.close(true);
            this.modal.dataset.eventsBound = "true";
        }
    },

    open(options, callback) {
        if (!this.modal) this.init();
        if (!this.modal) return;

        this.callback = callback;
        document.getElementById('lef-confirm-title').innerText = options.title || 'Confirm Action';
        document.getElementById('lef-confirm-message').innerText = options.message || 'Are you sure?';

        this.modal.classList.add('show');
    },

    close(confirmed) {
        if (!this.modal) return;
        this.modal.classList.remove('show');
        if (this.callback) {
            this.callback(confirmed);
        }
    }
};

window.LEF_Confirm = LEF_Confirm;
