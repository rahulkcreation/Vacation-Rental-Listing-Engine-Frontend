<?php

/**
 * Edit Profile Sub-template.
 *
 * Handles display of user details, password update, phone validation,
 * and image upload flow with OTP verification.
 *
 * @package ListingEngineFrontend
 */

if (! defined('ABSPATH')) {
    exit;
}

$user    = wp_get_current_user();
$user_id = $user->ID;

// Fetch current details
$full_name     = get_user_meta($user_id, 'full_name', true);
$mobile_number = get_user_meta($user_id, 'mobile_number', true);
$profile_pic   = lef_get_user_profile_pic($user_id);

// Initial fallback
if (empty($full_name)) {
    $full_name = $user->display_name;
}

// Initials for avatar fallback
$names    = explode(' ', $full_name);
$initials = '';
foreach ($names as $n) {
    $initials .= strtoupper(substr($n, 0, 1));
}
$initials = substr($initials, 0, 2);

// Parse mobile number to split code and number
$current_code  = '+91';
$current_flag  = '🇮🇳';
$phone_display = $mobile_number;

if (!empty($mobile_number)) {
    $parts = explode(' ', $mobile_number, 2);
    if (count($parts) === 2) {
        $current_code  = $parts[0];
        $phone_display = $parts[1];
        
        // Match flag using library data
        $lib_countries = lef_get_country_data();
        foreach ($lib_countries as $c) {
            if ($c['code'] === $current_code) {
                $current_flag = $c['flag'];
                break;
            }
        }
    }
}
?>


<div class="lef-global-plugin-wrapper" id="lef-edit-prof-wrapper">
    <!-- Title  -->
    <div class="lef-edit-prof-title-row">
        <h2 class="lef-edit-prof-panel-title">Edit Profile</h2>
        <p class="lef-edit-prof-panel-subtitle">Update your personal information and security settings.</p>
    </div>
    <!-- Profile Photo Section -->
    <div class="lef-edit-prof-photo-row">
        <div class="lef-edit-prof-photo-preview <?php echo $profile_pic ? 'lef-edit-prof-photo-has-image' : ''; ?>" id="lef-edit-prof-avatar-preview">
            <span><?php echo esc_html($initials); ?></span>
            <?php if ($profile_pic) : ?>
                <img src="<?php echo esc_url($profile_pic); ?>" alt="Profile preview" onerror="this.onerror=null; this.src='<?php echo esc_url(lef_get_asset_url('global-assets/images/placeholder-avatar.png')); ?>';">
            <?php endif; ?>
        </div>
        <div class="lef-edit-prof-photo-content">
            <p class="lef-edit-prof-photo-title">Profile Picture</p>
            <p class="lef-edit-prof-photo-text">Upload a clear profile image (Max 1MB, JPEG/WEBP/AVIF).</p>

            <div class="lef-edit-prof-upload-controls">
                <label class="lef-edit-prof-upload-btn" for="lef-edit-prof-pic-input">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <path d="m17 8-5-5-5 5"></path>
                        <path d="M12 3v12"></path>
                    </svg>
                    Upload Photo
                </label>
                <input type="file" id="lef-edit-prof-pic-input" style="display:none;" accept="image/jpeg,image/webp,image/avif">
            </div>

            <div class="lef-edit-prof-upload-progress-wrapper" id="lef-edit-prof-upload-progress-wrapper">
                <div class="lef-edit-prof-progress-bar-container">
                    <div id="lef-edit-prof-upload-progress-fill" class="lef-edit-prof-upload-progress-fill"></div>
                </div>
                <div class="lef-edit-prof-upload-status-row">
                    <span id="lef-edit-prof-upload-status-text" class="lef-edit-prof-upload-status-text">Uploading... <span id="lef-edit-prof-upload-percent">0</span>%</span>
                    <button id="lef-edit-prof-upload-retry" class="lef-edit-prof-upload-retry">Retry</button>
                </div>
            </div>
        </div>
    </div>

    <form class="lef-edit-prof-form" id="lef-edit-prof-form">
        <div class="lef-edit-prof-grid">
            <!-- Full Name -->
            <div class="lef-edit-prof-field">
                <label class="lef-edit-prof-label">Full Name</label>
                <input type="text" name="full_name" class="lef-edit-prof-input" value="<?php echo esc_attr($full_name); ?>" required>
            </div>

            <!-- Username (Read Only) -->
            <div class="lef-edit-prof-field">
                <label class="lef-edit-prof-label">Username</label>
                <input type="text" class="lef-edit-prof-input" value="<?php echo esc_attr($user->user_login); ?>" disabled>
                <p class="lef-edit-prof-help">Username cannot be changed.</p>
            </div>

            <!-- Email -->
            <div class="lef-edit-prof-field">
                <label class="lef-edit-prof-label">Email Address</label>
                <input type="email" name="email" id="lef-edit-prof-email" class="lef-edit-prof-input" value="<?php echo esc_attr($user->user_email); ?>" required>
                <div class="lef-edit-prof-field-error" id="lef-edit-prof-email-error"></div>
            </div>

            <!-- Phone Number -->
            <div class="lef-edit-prof-field">
                <label class="lef-edit-prof-label">Phone Number</label>
                <div class="lef-edit-prof-phone-input-group">
                    <div class="lef-edit-prof-country-select-wrapper">
                        <button type="button" class="lef-edit-prof-input" id="lef-edit-prof-country-btn">
                            <span id="lef-edit-prof-selected-flag"><?php echo esc_html($current_flag); ?></span> <span id="lef-edit-prof-selected-code"><?php echo esc_html($current_code); ?></span>
                        </button>
                        <div class="lef-edit-prof-country-dropdown" id="lef-edit-prof-country-dropdown">
                            <!-- Dropdown items will be populated by JS -->
                        </div>
                    </div>
                    <input type="tel" name="phone" id="lef-edit-prof-phone" class="lef-edit-prof-input lef-edit-prof-phone-input" value="<?php echo esc_attr($phone_display); ?>" placeholder="Enter number">
                </div>
                <div class="lef-edit-prof-field-error" id="lef-edit-prof-phone-error"></div>
            </div>

            <!-- New Password -->
            <div class="lef-edit-prof-field">
                <label class="lef-edit-prof-label">New Password (leave blank to keep current)</label>
                <div class="lef-edit-prof-pass-wrapper">
                    <input type="password" name="password" id="lef-edit-prof-pass" class="lef-edit-prof-input" autocomplete="new-password">
                    <button type="button" class="lef-edit-prof-pass-toggle" data-target="lef-edit-prof-pass">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <!-- Strength Indicator -->
                <div class="lef-edit-prof-pass-strength" id="lef-edit-prof-pass-strength">
                    <div class="lef-edit-prof-strength-meter">
                        <div class="strength-segment"></div>
                        <div class="strength-segment"></div>
                        <div class="strength-segment"></div>
                        <div class="strength-segment"></div>
                    </div>
                    <p id="lef-edit-prof-pass-hint" class="lef-edit-prof-pass-hint"></p>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="lef-edit-prof-field">
                <label class="lef-edit-prof-label">Confirm Password</label>
                <div class="lef-edit-prof-pass-wrapper">
                    <input type="password" id="lef-edit-prof-pass-confirm" class="lef-edit-prof-input" autocomplete="new-password">
                    <button type="button" class="lef-edit-prof-pass-toggle" data-target="lef-edit-prof-pass-confirm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <div class="lef-edit-prof-field-error" id="lef-edit-prof-match-error"></div>
            </div>
        </div>

        <div class="lef-edit-prof-actions">
            <button class="lef-edit-prof-save-btn" type="submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <path d="M17 21v-8H7v8"></path>
                    <path d="M7 3v5h8"></path>
                </svg>
                Save Changes
            </button>
        </div>
    </form>
</div>

<!-- OTP Verification Popup -->
<div class="lef-edit-prof-otp-overlay" id="lef-edit-prof-otp-overlay">
    <div class="lef-edit-prof-otp-dialog">
        <div class="lef-edit-prof-otp-head">
            <div>
                <h2 class="lef-edit-prof-otp-title">Verify Changes</h2>
                <p class="lef-edit-prof-otp-text">Enter the 6-digit code sent to <?php echo esc_html($user->user_email); ?></p>
            </div>
            <button class="lef-edit-prof-otp-close" type="button" id="lef-edit-prof-otp-close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>

        <form id="lef-edit-prof-otp-form">
            <div class="lef-edit-prof-otp-body">
                <label class="lef-edit-prof-label">One-Time Password</label>
                <input class="lef-edit-prof-input lef-edit-prof-otp-input-field" type="text" id="lef-edit-prof-otp-input" inputmode="numeric" maxlength="6" pattern="[0-9]*" placeholder="000000">
                <div id="lef-edit-prof-otp-timer" class="lef-edit-prof-otp-timer">Expires in: <span id="lef-edit-prof-otp-countdown">60</span>s</div>
            </div>

            <div class="lef-edit-prof-otp-actions">
                <button class="lef-edit-prof-otp-submit" type="submit">
                    Confirm & Save
                </button>
            </div>
        </form>
    </div>
</div>
<?php
// End of template
?>