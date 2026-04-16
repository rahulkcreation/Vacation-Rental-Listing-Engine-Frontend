/**
 * list-view.js
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        
        // 1. Image Carousel Logic
        $('.lef-property-grid').on('click', '.lef-image-nav', function(e) {
            e.stopPropagation();
            const $btn = $(this);
            const $container = $btn.closest('.lef-card-image-container');
            const images = JSON.parse($container.attr('data-images'));
            let current = parseInt($container.attr('data-current'));
            const direction = $btn.hasClass('lef-nav-next') ? 1 : -1;

            current = (current + direction + images.length) % images.length;
            
            $container.attr('data-current', current);
            $container.find('.lef-card-image').attr('src', images[current]);
        });

        // 2. Favorite Toggle (Database Driven)
        $('.lef-property-grid').on('click', '.lef-favorite-btn', function(e) {
            e.stopPropagation();
            
            const $btn = $(this);
            const propertyId = $btn.data('id');

            // 1. Check Login Status
            if (!lefData || lefData.isLoggedIn !== '1') {
                if (window.LEF_Toast) {
                    LEF_Toast.show('Please login to add in wishlist', 'error');
                }
                return;
            }

            // 2. Trigger AJAX Toggle
            $btn.addClass('is-loading');

            $.ajax({
                url: lefData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'lef_toggle_wishlist',
                    property_id: propertyId,
                    nonce: lefData.wishlistNonce
                },
                success: function(res) {
                    $btn.removeClass('is-loading');
                    if (res.success) {
                        const status = res.data.status;
                        $btn.toggleClass('is-active', status === 'added');
                        
                        if (window.LEF_Toast) {
                            LEF_Toast.show(res.data.message, 'success');
                        }
                    } else {
                        if (window.LEF_Toast) {
                            LEF_Toast.show(res.data.message || 'Failed to update wishlist', 'error');
                        }
                    }
                },
                error: function() {
                    $btn.removeClass('is-loading');
                    if (window.LEF_Toast) {
                        LEF_Toast.show('Network error. Please try again.', 'error');
                    }
                }
            });
        });

        // 3. Card Click Redirect
        $('.lef-property-grid').on('click', '.lef-property-card', function() {
            const redirectUrl = $(this).attr('data-redirect');
            
            if (redirectUrl === 'error_not_found') {
                LEF_Toast.show('Page not found', 'error');
            } else {
                window.location.href = redirectUrl;
            }
        });

    });

})(jQuery);
