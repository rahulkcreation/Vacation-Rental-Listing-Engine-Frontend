/**
 * My Profile Dashboard Logic.
 *
 * Handles mobile sidebar toggling, dynamic screen loading via AJAX,
 * and secure logout with confirmation.
 *
 * @package ListingEngineFrontend
 */

(function($) {
    'use strict';

    const LEF_MyProfile = {
        init() {
            this.cacheDOM();
            this.bindEvents();
            
            // Determine default screen from URL hash or fallback to 'edit-profile'
            let initialScreen = 'edit-profile';
            if (window.location.hash) {
                const hashScreen = window.location.hash.substring(1);
                // Verify if the hash matches a valid screen button
                if (this.$menuBtns.filter(`[data-screen="${hashScreen}"]`).length > 0) {
                    initialScreen = hashScreen;
                }
            }
            
            // Load the determined screen on start
            this.loadScreen(initialScreen);
        },

        cacheDOM() {
            this.$wrapper       = $('#lef-myprofile-wrapper');
            this.$sidebar       = $('#lef-prof-sidebar');
            this.$backdrop      = $('#lef-prof-sidebar-backdrop');
            this.$toggle        = $('#lef-prof-menu-toggle');
            this.$close         = $('#lef-prof-sidebar-close');
            this.$bucket        = $('#lef-myprofile-content-bucket');
            this.$menuBtns      = $('.lef-prof-menu-btn[data-screen]');
            this.$logoutBtn      = $('.lef-prof-logout-trigger');
        },

        bindEvents() {
            // Sidebar Toggles
            this.$toggle.on('click', () => this.toggleSidebar(true));
            this.$close.on('click', () => this.toggleSidebar(false));
            this.$backdrop.on('click', () => this.toggleSidebar(false));

            // Navigation
            this.$menuBtns.on('click', (e) => {
                e.preventDefault();
                const screen = $(e.currentTarget).data('screen');
                if (screen) {
                    window.location.hash = screen;
                    this.loadScreen(screen);
                    this.toggleSidebar(false);
                }
            });

            // Logout
            this.$logoutBtn.on('click', (e) => {
                e.preventDefault();
                const targetUrl = $(e.currentTarget).attr('href');
                this.handleLogout(targetUrl);
            });
        },

        toggleSidebar(open) {
            if (open) {
                this.$sidebar.addClass('lef-prof-sidebar-open');
                this.$backdrop.addClass('lef-prof-sidebar-backdrop-open');
                $('body').addClass('lef-prof-sidebar-lock');
            } else {
                this.$sidebar.removeClass('lef-prof-sidebar-open');
                this.$backdrop.removeClass('lef-prof-sidebar-backdrop-open');
                $('body').removeClass('lef-prof-sidebar-lock');
            }
        },

        loadScreen(screen) {
            // Update Menu Active State
            this.$menuBtns.removeClass('lef-prof-menu-active');
            this.$menuBtns.filter(`[data-screen="${screen}"]`).addClass('lef-prof-menu-active');

            // Show Loader
            this.$bucket.addClass('is-loading');
            this.$bucket.html('<div class="lef-prof-loader"><div class="lef-spinner"></div></div>');

            // AJAX Request
            $.ajax({
                url: lefMyProfileData.ajax_url,
                type: 'POST',
                data: {
                    action: 'lef_myprofile_load_screen',
                    screen: screen,
                    nonce: lefMyProfileData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.$bucket.html(response.data.html);
                        $(document).trigger('lef_sidebar_screen_loaded', [screen]);
                    } else {
                        this.$bucket.html('<div class="lef-prof-error" style="padding: 20px; color: var(--leb-error-color);">Error loading screen. Please try again.</div>');
                        if (window.LEF_Toast) window.LEF_Toast.show(response.data.message || 'Load failed', 'error');
                    }
                },
                error: () => {
                    this.$bucket.html('<div class="lef-prof-error">Network error.</div>');
                },
                complete: () => {
                    this.$bucket.removeClass('is-loading');
                }
            });
        },

        handleLogout(redirectUrl) {
            if (!window.LEF_Confirm) {
                console.error('LEF_Confirm component not found.');
                // Fallback direct redirect if component missing
                if (redirectUrl) window.location.href = redirectUrl;
                return;
            }

            window.LEF_Confirm.open({
                title: 'Confirm Logout',
                message: 'Are you sure you want to log out of your profile?'
            }, (confirmed) => {
                if (confirmed) {
                    this.performLogoutRedirect(redirectUrl);
                }
            });
        },

        performLogoutRedirect(redirectUrl) {
            if (window.LEF_Toast) window.LEF_Toast.show('Logging out...', 'info');
            
            // Redirect directly to the URL without an extra AJAX call
            if (redirectUrl && redirectUrl !== '#') {
                window.location.href = redirectUrl;
            } else {
                window.location.href = '/';
            }
        }
    };

    $(document).ready(() => {
        if ($('#lef-myprofile-wrapper').length) {
            LEF_MyProfile.init();
        }
    });

})(jQuery);
