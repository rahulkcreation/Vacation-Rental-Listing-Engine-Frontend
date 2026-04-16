/**
 * database.js
 * 
 * Handles interactions for the Database Management screen.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initial fetch for all DB cards
    const dbCards = document.querySelectorAll('.lef-db-card');
    
    dbCards.forEach(card => {
        const tableName = card.getAttribute('data-table');
        if (tableName) {
            lef_db_fetch_status(tableName, card);
        }
    });
});

/**
 * Perform AJAX request to fetch table status
 * @param {string} table The table name
 * @param {HTMLElement} cardEle The wrapping card element
 */
function lef_db_fetch_status(table, cardEle) {
    if (!table) return;

    // Use WordPress global ajaxurl or define a fallback assuming wp-admin context
    const ajaxEndpoint = typeof lef_admin_obj !== 'undefined' ? lef_admin_obj.ajax_url : '/wp-admin/admin-ajax.php';
    
    // Set loading state
    lef_db_update_badge(cardEle.querySelector('#status-created'), 'pending', 'Checking...');
    lef_db_update_badge(cardEle.querySelector('#status-rows'), 'pending', 'Checking...');

    const formData = new FormData();
    formData.append('action', 'lef_db_refresh');
    formData.append('table', table);

    fetch(ajaxEndpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if (res.success && res.data) {
            const data = res.data;
            lef_db_update_badge(cardEle.querySelector('#status-created'), data.created ? 'success' : 'error', data.created ? '✓ Yes' : '✗ No');
            lef_db_update_badge(cardEle.querySelector('#status-rows'), data.complete ? 'success' : 'error', data.complete ? '✓ All Present' : '✗ Missing');
            
            if (window.LEF_Toast) {
                window.LEF_Toast.show(`${table} status refreshed.`, 'info');
            }
        } else {
            console.error('Failed to fetch DB status', res);
            if (window.LEF_Toast) {
                window.LEF_Toast.show(`Failed to fetch status for ${table}.`, 'error');
            }
        }
    })
    .catch(err => {
        console.error(err);
        if (window.LEF_Toast) {
            window.LEF_Toast.show('Network error while fetching DB status.', 'error');
        }
    });
}

/**
 * Handle Manual Refresh Button Click
 */
window.lef_db_refresh = function(table) {
    const cardEle = document.querySelector(`.lef-db-card[data-table="${table}"]`);
    if (cardEle) {
        lef_db_fetch_status(table, cardEle);
    }
};

/**
 * Handle Create / Repair Button Click
 */
window.lef_db_repair = function(table) {
    if (!table) return;

    if (window.LEF_Confirm) {
        window.LEF_Confirm.open({
            title: 'Repair Database Table',
            message: `Are you sure you want to create or repair the table: ${table}? This will execute database modifications.`
        }, function(confirmed) {
            if (confirmed) {
                lef_db_execute_repair(table);
            }
        });
    } else {
        // Fallback Native Confirm
        if (confirm(`Are you sure you want to create or repair the table: ${table}?`)) {
            lef_db_execute_repair(table);
        }
    }
};

/**
 * Perform actual AJAX request for repairing table
 */
function lef_db_execute_repair(table) {
    const cardEle = document.querySelector(`.lef-db-card[data-table="${table}"]`);
    if (!cardEle) return;

    const ajaxEndpoint = typeof lef_admin_obj !== 'undefined' ? lef_admin_obj.ajax_url : '/wp-admin/admin-ajax.php';
    
    // Set visually to pending
    lef_db_update_badge(cardEle.querySelector('#status-created'), 'pending', 'Working...');
    lef_db_update_badge(cardEle.querySelector('#status-rows'), 'pending', 'Working...');

    const formData = new FormData();
    formData.append('action', 'lef_db_repair');
    formData.append('table', table);

    fetch(ajaxEndpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if (res.success && res.data) {
            const data = res.data;
            lef_db_update_badge(cardEle.querySelector('#status-created'), data.created ? 'success' : 'error', data.created ? '✓ Yes' : '✗ No');
            lef_db_update_badge(cardEle.querySelector('#status-rows'), data.complete ? 'success' : 'error', data.complete ? '✓ All Present' : '✗ Missing');
            
            if (window.LEF_Toast) {
                window.LEF_Toast.show(`Successfully repaired ${table}.`, 'success');
            }
        } else {
            console.error('Failed to repair table', res);
            if (window.LEF_Toast) {
                const msg = res.data && res.data.message ? res.data.message : `Failed to repair ${table}.`;
                window.LEF_Toast.show(msg, 'error');
            }
        }
    })
    .catch(err => {
        console.error(err);
        if (window.LEF_Toast) {
            window.LEF_Toast.show('Network error while repairing table.', 'error');
        }
    });
}

/**
 * Helper: Update Badge Class & Text
 */
function lef_db_update_badge(badgeEle, type, text) {
    if (!badgeEle) return;
    badgeEle.className = 'badge'; // reset
    badgeEle.classList.add(`badge--${type}`);
    badgeEle.innerText = text;
}
