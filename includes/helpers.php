<?php
/**
 * Global Helper Functions for Listing Engine Frontend.
 *
 * This file contains standalone functions used across multiple templates
 * and components (Reviews, User Profiles) to prevent fatal redeclaration errors.
 *
 * @package ListingEngineFrontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Format a date string into "d F Y" (e.g., 25 December 2026).
 *
 * @param string $date_str The date string to format.
 * @return string Formatted date or empty string.
 */
function lef_format_review_date( $date_str ) {
	$ts = strtotime( $date_str );
	return $ts ? date( 'd F Y', $ts ) : '';
}

/**
 * Truncate a string to a specific character limit.
 *
 * @param string $text  The text to truncate.
 * @param int    $limit The character limit.
 * @return string Truncated text with ellipsis.
 */
function lef_truncate_review( $text, $limit ) {
	if ( mb_strlen( $text ) > $limit ) {
		return mb_substr( $text, 0, $limit ) . '...';
	}
	return $text;
}

/**
 * Render 5 SVG stars for a review score.
 *
 * @param float|int $rating The rating score (0-5).
 * @return string HTML for the 5 stars.
 */
function lef_render_review_stars( $rating ) {
	$rating = round( floatval( $rating ) );
	$rating = max( 0, min( 5, $rating ) );

	$filled_star   = '<svg viewBox="0 0 32 32" class="lef-star-filled"><path d="m15.1 1.58-4.13 8.88-9.86 1.27a1 1 0 0 0-.54 1.74l7.3 6.57-1.97 9.85a1 1 0 0 0 1.48 1.06l8.62-5 8.63 5a1 1 0 0 0 1.48-1.06l-1.97-9.85 7.3-6.57a1 1 0 0 0-.55-1.73l-9.86-1.28-4.12-8.88a1 1 0 0 0-1.82 0z" /></svg>';
	$outlined_star = '<svg viewBox="0 0 32 32" class="lef-star-outline"><path d="m15.1 1.58-4.13 8.88-9.86 1.27a1 1 0 0 0-.54 1.74l7.3 6.57-1.97 9.85a1 1 0 0 0 1.48 1.06l8.62-5 8.63 5a1 1 0 0 0 1.48-1.06l-1.97-9.85 7.3-6.57a1 1 0 0 0-.55-1.73l-9.86-1.28-4.12-8.88a1 1 0 0 0-1.82 0z" /></svg>';

	$stars_html = '';
	for ( $i = 0; $i < 5; $i++ ) {
		$stars_html .= ( $i < $rating ) ? $filled_star : $outlined_star;
	}
	return $stars_html;
}

/**
 * Robustly fetch user profile picture URL.
 *
 * @param int $user_id User ID.
 * @return string Profile picture URL.
 */
function lef_get_user_profile_pic( $user_id ) {
	$placeholder = lef_get_asset_url('global-assets/images/placeholder-avatar.png');

	if ( ! $user_id ) {
		return esc_url( $placeholder );
	}

	$pic_meta = get_user_meta( $user_id, 'profile_pic', true );
	$pic_url  = '';

	if ( ! empty( $pic_meta ) ) {
		if ( strpos( $pic_meta, '{' ) === 0 || strpos( $pic_meta, '[' ) === 0 ) {
			$pic_data = json_decode( $pic_meta, true );
			if ( is_array( $pic_data ) ) {
				$pic_url = isset( $pic_data['url'] ) ? $pic_data['url'] : ( isset( $pic_data['path'] ) ? $pic_data['path'] : '' );
			}
		}
		if ( empty( $pic_url ) && is_string( $pic_meta ) ) {
			$pic_url = trim( $pic_meta );
		}
	}

	if ( ! empty( $pic_url ) ) {
		// If it's a local URL, check if the file exists on the server
		$upload_dir = wp_upload_dir();
		$base_url   = $upload_dir['baseurl'];
		$base_path  = $upload_dir['basedir'];

		if ( strpos( $pic_url, $base_url ) === 0 ) {
			$relative_path = str_replace( $base_url, '', $pic_url );
			$full_path     = $base_path . $relative_path;
			
			if ( ! file_exists( $full_path ) ) {
				$pic_url = ''; // Force placeholder fallback
			}
		}
	}

	return empty( $pic_url ) ? esc_url( $placeholder ) : esc_url( $pic_url );
}

/**
 * Generate a unique reservation number.
 * Format: RES-XXXXXX (6 alphanumeric uppercase characters).
 *
 * @return string The generated reservation number.
 */
function lef_generate_reservation_number() {
	global $wpdb;
	$prefix = 'RES-';
	$table  = $wpdb->prefix . 'ls_reservation';

	do {
		$random_part = strtoupper( wp_generate_password( 6, false, false ) );
		$res_number  = $prefix . $random_part;

		// Check if this number already exists in the database
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table WHERE reservation_number = %s",
			$res_number
		) );
	} while ( $exists > 0 );

	return $res_number;
}

/**
 * Get country data (Name, Calling Code, Flag) using libphonenumber library.
 *
 * @return array List of country objects.
 */
function lef_get_country_data() {
    // Safe-load vendor autoloader to avoid "already in use" conflicts
    if ( ! class_exists( 'ComposerAutoloaderInit7dde314dfc2f42ca073f6e2ef0716be4' ) ) {
        if ( file_exists( LEF_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
            require_once LEF_PLUGIN_DIR . 'vendor/autoload.php';
        }
    }

    if ( ! class_exists( '\libphonenumber\PhoneNumberUtil' ) ) {
        return array();
    }

    $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    $regions   = $phoneUtil->getSupportedRegions();
    $data      = array();

    foreach ( $regions as $region ) {
        $calling_code = $phoneUtil->getCountryCodeForRegion( $region );
        $name         = class_exists( '\Giggsey\Locale\Locale' ) 
                        ? \Giggsey\Locale\Locale::getDisplayRegion( '-' . $region, 'en' )
                        : $region;

        $data[] = array(
            'name' => $name,
            'code' => '+' . $calling_code,
            'iso'  => $region,
            'flag' => lef_get_flag_emoji( $region )
        );
    }

    // Sort by name
    usort( $data, function( $a, $b ) {
        return strcmp( $a['name'], $b['name'] );
    } );

    return $data;
}

/**
 * Convert ISO Country Code to Emoji Flag.
 *
 * @param string $iso 2-letter country code.
 * @return string Emoji flag.
 */
function lef_get_flag_emoji( $iso ) {
    $iso = strtoupper( $iso );
    if ( strlen( $iso ) !== 2 ) {
        return '';
    }
    
    // mb_chr is PHP 7.2+
    if ( function_exists( 'mb_chr' ) ) {
        return mb_chr( ord( $iso[0] ) + 127397, 'UTF-8' ) . mb_chr( ord( $iso[1] ) + 127397, 'UTF-8' );
    }
    
    // Fallback for older PHP
    return mb_convert_encoding( '&#' . ( 127397 + ord( $iso[0] ) ) . ';&#' . ( 127397 + ord( $iso[1] ) ) . ';', 'UTF-8', 'HTML-ENTITIES' );
}

/**
 * Validate Phone Number logic using libphonenumber.
 *
 * @param string $phone Full phone number with code or just number.
 * @param string $iso   Optional 2-letter country code for strict parsing.
 * @return bool True if valid, false otherwise.
 */
function lef_validate_phone_number( $phone, $iso = '' ) {
    if ( ! class_exists( '\libphonenumber\PhoneNumberUtil' ) ) {
        // Fallback or lazy load
        if ( file_exists( LEF_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
             require_once LEF_PLUGIN_DIR . 'vendor/autoload.php';
        }
    }

    if ( ! class_exists( '\libphonenumber\PhoneNumberUtil' ) ) {
        return ! empty( $phone ); // Extreme fallback
    }

    $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    try {
        $pObj = $phoneUtil->parse( $phone, strtoupper( $iso ) );
        return $phoneUtil->isValidNumber( $pObj );
    } catch ( \libphonenumber\NumberParseException $e ) {
        return false;
    }
}

/**
 * Check if email or mobile number is already in use by another user.
 *
 * @param string $type            'email' or 'mobile_number'.
 * @param string $value           The value to check.
 * @param int    $current_user_id To exclude current user.
 * @return bool True if duplicate exists, false otherwise.
 */
function lef_is_contact_duplicate( $type, $value, $current_user_id ) {
    global $wpdb;

    if ( $type === 'email' ) {
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM $wpdb->users WHERE user_email = %s AND ID != %d",
            $value,
            $current_user_id
        ) );
        return (bool) $exists;
    }

    if ( $type === 'mobile_number' ) {
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'mobile_number' AND meta_value = %s AND user_id != %d",
            $value,
            $current_user_id
        ) );
        return (bool) $exists;
    }

    return false;
}
