/**
 * dashboard.js
 *
 * JavaScript for the Admin Dashboard page.
 * Handles: copy-to-clipboard shortcode buttons with visual feedback.
 * Prefix: lef-dash-
 *
 * @package ListingEngineFrontend
 */

(function ($) {
    'use strict';

    /**
     * Initialize the Dashboard module.
     * Binds all event listeners after the DOM is ready.
     */
    function initDashboard() {

        // ── Copy-to-clipboard for shortcode buttons ─────────────────
        $(document).on('click', '.lef-dash-copy-btn', function () {
            const $btn      = $(this);
            const targetId  = $btn.data('lef-copy');
            const $codeEl   = $('#' + targetId);

            if (!$codeEl.length) return;

            const text = $codeEl.text().trim();

            /**
             * Copy text to clipboard.
             * Uses the modern Clipboard API with a textarea fallback for older browsers.
             *
             * @param {string} str - The text to copy.
             */
            function copyToClipboard(str) {
                if (navigator.clipboard && window.isSecureContext) {
                    // Modern Clipboard API
                    navigator.clipboard.writeText(str).then(function () {
                        showCopiedState($btn);
                    }).catch(function () {
                        legacyCopy(str, $btn);
                    });
                } else {
                    legacyCopy(str, $btn);
                }
            }

            /**
             * Legacy copy method using a temporary textarea element.
             *
             * @param {string}   str  - Text to copy.
             * @param {jQuery}   $el  - The button element to update state on.
             */
            function legacyCopy(str, $el) {
                var $tmp = $('<textarea>').val(str).css({
                    position: 'fixed',
                    opacity:  0
                }).appendTo('body');

                $tmp[0].select();

                try {
                    document.execCommand('copy');
                    showCopiedState($el);
                } catch (err) {
                    console.warn('LEF Dashboard: copy failed.', err);
                }

                $tmp.remove();
            }

            /**
             * Update the button to show a "copied" confirmation state,
             * then revert after 2 seconds.
             *
             * @param {jQuery} $el - The copy button element.
             */
            function showCopiedState($el) {
                // Swap icon to a checkmark
                var originalHTML = $el.html();

                $el.html(
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
                    '<polyline points="20 6 9 17 4 12"></polyline>' +
                    '</svg>'
                ).addClass('is-copied');

                setTimeout(function () {
                    $el.html(originalHTML).removeClass('is-copied');
                }, 2000);
            }

            copyToClipboard(text);
        });

        console.log('LEF Dashboard: initialized.');
    }

    // Run on DOM ready
    $(document).ready(function () {
        initDashboard();
    });

})(jQuery);
