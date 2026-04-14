<?php
/**
 * Email Template: Reservation Request
 *
 * Generates the HTML email body for reservation notifications.
 * Uses inline styles because email clients do not support CSS variables.
 *
 * @package ListingEngineFrontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─────────────────────────────────────────────────────────────
// Email Builder Function
// ─────────────────────────────────────────────────────────────

/**
 * Build the reservation email HTML.
 *
 * @param array  $data  Associative array of placeholder values.
 * @param string $type  'admin' or 'user' — controls greeting and sections.
 * @return string       The complete inline-styled HTML email body.
 */
function lef_get_reservation_email_html( $data, $type = 'admin' ) {

	// ── Determine greeting text based on recipient type ──
	if ( $type === 'user' ) {
		$greeting_line1 = 'Hi, ' . esc_html( $data['user_name'] );
		$greeting_line2 = 'Your reservation request for the property <strong>' . esc_html( $data['property_name'] ) . '</strong> has been generated.';
		$subtitle       = 'Booking Confirmation Pending';
	} else {
		$greeting_line1 = 'Hi Admin,';
		$greeting_line2 = 'There is a new reservation request for the property <strong>' . esc_html( $data['property_name'] ) . '</strong> from <strong>' . esc_html( $data['user_name'] ) . '</strong>.';
		$subtitle       = 'New Booking for Your Property';
	}

	// ── Start HTML ──
	$html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; background-color: #FAFAFA; margin: 0; padding: 0;">';

	$html .= '<div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px 20px;">';

	/* ── Header ── */
	$html .= '<div style="text-align: center; margin-bottom: 30px;">';
	$html .= '<h1 style="font-size: 26px; font-weight: 500; color: #F15E74; margin: 0;">Reservation Request</h1>';
	$html .= '<p style="font-size: 13px; color: #5a6e7c; margin-top: 6px; font-weight: 400;">' . esc_html( $subtitle ) . '</p>';
	$html .= '</div>';

	/* ── Body Greeting ── */
	$html .= '<div style="color: #5a6e7c; font-size: 14px; font-weight: 400; line-height: 1.6; margin-bottom: 20px;">';
	$html .= '<p>' . esc_html( $greeting_line1 ) . '</p>';
	$html .= '<p>' . $greeting_line2 . '</p>';
	$html .= '</div>';

	/* ── Request Details Section ── */
	$html .= '<div style="margin-bottom: 25px; padding: 20px; background-color: #FAFAFA; border-radius: 8px;">';
	$html .= '<h2 style="font-size: 17px; font-weight: 500; color: #2C3E50; margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #F15E74;">Request Details</h2>';

	$html .= lef_email_detail_row( 'Check-in:', esc_html( $data['check_in'] ) );
	$html .= lef_email_detail_row( 'Check-out:', esc_html( $data['check_out'] ) );
	$html .= lef_email_detail_row( 'Total Guests:', esc_html( $data['adults'] ) . ' adults, ' . esc_html( $data['children'] ) . ' children, ' . esc_html( $data['infants'] ) . ' infants' );
	$html .= lef_email_detail_row( 'Total Price:', esc_html( $data['total_price'] ) );
	$html .= lef_email_detail_row( 'Request Date:', esc_html( $data['request_date'] ) );

	$html .= '</div>';

	/* ── Traveller Details Section ── */
	$html .= '<div style="margin-bottom: 25px; padding: 20px; background-color: #FAFAFA; border-radius: 8px;">';
	$html .= '<h2 style="font-size: 17px; font-weight: 500; color: #2C3E50; margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #F15E74;">Traveller Details</h2>';

	$html .= lef_email_detail_row( 'Name:', esc_html( $data['user_name'] ) );
	$html .= lef_email_detail_row( 'Email:', esc_html( $data['user_email'] ) );
	$html .= lef_email_detail_row( 'Phone:', esc_html( $data['user_phone'] ) );

	$html .= '</div>';

	/* ── Host Details (Admin email only) ── */
	if ( $type === 'admin' ) {
		$html .= '<div style="margin-bottom: 25px; padding: 20px; background-color: #FAFAFA; border-radius: 8px;">';
		$html .= '<h2 style="font-size: 17px; font-weight: 500; color: #2C3E50; margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #F15E74;">Host Details</h2>';

		$html .= lef_email_detail_row( 'Name:', esc_html( $data['host_name'] ) );
		$html .= lef_email_detail_row( 'Email:', esc_html( $data['host_email'] ) );
		$html .= lef_email_detail_row( 'Phone:', esc_html( $data['host_phone'] ) );

		$html .= '</div>';
	}

	/* ── Buttons ── */
	$html .= '<div style="text-align: center; margin-top: 30px;">';
	$html .= '<a href="' . esc_url( $data['property_url'] ) . '" style="display: inline-block; padding: 12px 20px; font-size: 12px; font-weight: 500; text-decoration: none; border-radius: 6px; margin: 0 10px; background-color: #F15E74; color: #ffffff;">View Property</a>';

	if ( $type === 'admin' ) {
		$html .= '<a href="' . esc_url( $data['request_url'] ) . '" style="display: inline-block; padding: 12px 20px; font-size: 12px; font-weight: 500; text-decoration: none; border-radius: 6px; margin: 0 10px; background-color: #2C3E50; color: #ffffff;">View Request</a>';
	}

	$html .= '</div>';

	/* ── Footer ── */
	$html .= '<div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e9eef3; color: #5a6e7c; font-size: 12px;">';
	$html .= '<p>This is an automated email from your booking system.</p>';
	$html .= '</div>';

	$html .= '</div></body></html>';

	return $html;
}

/**
 * Helper: Build a single label–value row for the email.
 *
 * @param string $label The field label.
 * @param string $value The field value.
 * @return string       Inline-styled HTML row.
 */
function lef_email_detail_row( $label, $value ) {
	return '<div style="display: flex; margin-bottom: 10px; font-size: 13px;">'
		 . '<span style="font-weight: 500; color: #2C3E50; width: 140px; min-width: 140px;">' . $label . '</span>'
		 . '<span style="color: #5a6e7c;">' . $value . '</span>'
		 . '</div>';
}
