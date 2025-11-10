<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Modern Login Wrapper -->
<div class="modern-login-wrapper">
    <!-- Animated Background Waves -->
    <div class="login-wave login-wave-1"></div>
    <div class="login-wave login-wave-2"></div>

    <!-- Modern Login Card -->
    <div class="modern-login-card">
        <!-- Login Type Badge -->
        <div class="login-type-badge client-badge">
            <i class="fa fa-user"></i> Cliente
        </div>

        <!-- Login Type Switch -->
        <div class="login-type-switch-container">
            <span class="login-type-label active" id="client-label">Cliente</span>
            <div class="login-type-switch client-active" id="login-type-toggle">
                <div class="login-type-switch-slider"></div>
                <i class="fa fa-user login-type-switch-icon client-icon"></i>
                <i class="fa fa-briefcase login-type-switch-icon staff-icon"></i>
            </div>
            <span class="login-type-label" id="staff-label">Staff</span>
        </div>

        <!-- Header Section with Logo -->
        <div class="modern-login-header">
            <div class="modern-logo-container">
                <div class="modern-logo-image-wrapper">
                    <img src="<?= base_url('assets/images/intergraphics-logo.png'); ?>"
                         alt="Company Logo"
                         class="modern-logo-image">
                </div>
            </div>
            <h2 class="modern-login-heading">
                <?= _l(get_option('allow_registration') == 1 ? 'clients_login_heading_register' : 'clients_login_heading_no_register'); ?>
            </h2>
            <p class="modern-login-subheading">
                <?= get_option('allow_registration') == 1 ? _l('clients_login_subheading') : _l('clients_login_heading_no_register'); ?>
            </p>
        </div>

        <!-- Login Form -->
        <?= form_open($this->uri->uri_string(), ['class' => 'modern-login-form', 'id' => 'modern-login-form']); ?>
        <?php hooks()->do_action('clients_login_form_start'); ?>

        <!-- Language Selection -->
        <?php if (! is_language_disabled()) { ?>
        <div class="modern-form-group">
            <label for="language" class="modern-label">
                <i class="fa fa-globe"></i> <?= _l('language'); ?>
            </label>
            <select name="language" id="language" class="modern-select selectpicker"
                onchange="change_contact_language(this)"
                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>"
                data-live-search="true">
                <?php $selected = (get_contact_language() != '') ? get_contact_language() : get_option('active_language'); ?>
                <?php foreach ($this->app->get_available_languages() as $availableLanguage) { ?>
                <option value="<?= e($availableLanguage); ?>"
                    <?= ($availableLanguage == $selected) ? 'selected' : '' ?>>
                    <?= e(ucfirst($availableLanguage)); ?>
                </option>
                <?php } ?>
            </select>
        </div>
        <?php } ?>

        <!-- Email Input -->
        <div class="modern-form-group">
            <label for="email" class="modern-label">
                <?= _l('clients_login_email'); ?>
            </label>
            <div class="modern-input-wrapper">
                <i class="fa fa-envelope modern-input-icon"></i>
                <input type="text"
                       class="modern-input <?= form_error('email') ? 'error' : ''; ?>"
                       name="email"
                       id="email"
                       placeholder="<?= _l('clients_login_email'); ?>"
                       autofocus="true"
                       value="<?= set_value('email'); ?>">
            </div>
            <?php if (form_error('email')): ?>
                <div class="modern-error"><?= strip_tags(form_error('email')); ?></div>
            <?php endif; ?>
        </div>

        <!-- Password Input -->
        <div class="modern-form-group">
            <label for="password" class="modern-label">
                <?= _l('clients_login_password'); ?>
            </label>
            <div class="modern-input-wrapper">
                <i class="fa fa-lock modern-input-icon"></i>
                <input type="password"
                       class="modern-input <?= form_error('password') ? 'error' : ''; ?>"
                       name="password"
                       id="password"
                       placeholder="<?= _l('clients_login_password'); ?>">
            </div>
            <?php if (form_error('password')): ?>
                <div class="modern-error"><?= strip_tags(form_error('password')); ?></div>
            <?php endif; ?>
        </div>

        <!-- reCAPTCHA -->
        <?php if (show_recaptcha_in_customers_area()) { ?>
        <div class="modern-recaptcha-wrapper">
            <div class="g-recaptcha" data-sitekey="<?= get_option('recaptcha_site_key'); ?>"></div>
        </div>
        <?php if (form_error('g-recaptcha-response')): ?>
            <div class="modern-error modern-text-center"><?= strip_tags(form_error('g-recaptcha-response')); ?></div>
        <?php endif; ?>
        <?php } ?>

        <!-- Remember Me Checkbox -->
        <div class="modern-checkbox-wrapper">
            <input type="checkbox" name="remember" id="remember" class="modern-checkbox">
            <label for="remember" class="modern-checkbox-label">
                <?= _l('clients_login_remember'); ?>
            </label>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="modern-btn modern-btn-primary" id="login-submit-btn">
            <?= _l('clients_login_login_string'); ?>
        </button>

        <!-- Register Button (if enabled) -->
        <?php if (get_option('allow_registration') == 1) { ?>
        <a href="<?= site_url('authentication/register'); ?>" class="modern-btn modern-btn-secondary">
            <?= _l('clients_register_string'); ?>
        </a>
        <?php } ?>

        <!-- Forgot Password Link -->
        <a href="<?= site_url('authentication/forgot_password'); ?>" class="modern-forgot-link">
            <?= _l('customer_forgot_password'); ?>
        </a>

        <?php hooks()->do_action('clients_login_form_end'); ?>
        <?= form_close(); ?>
    </div>
</div>

<!-- Form Submit Handler with Loading State -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('modern-login-form');
    const submitBtn = document.getElementById('login-submit-btn');

    if (form && submitBtn) {
        form.addEventListener('submit', function() {
            submitBtn.classList.add('modern-btn-loading');
            submitBtn.disabled = true;
        });
    }

    // Login Type Switch Handler
    const toggle = document.getElementById('login-type-toggle');
    const clientLabel = document.getElementById('client-label');
    const staffLabel = document.getElementById('staff-label');

    if (toggle) {
        toggle.addEventListener('click', function() {
            // Toggle to Staff login
            if (toggle.classList.contains('client-active')) {
                // Redirect to staff login with smooth transition
                window.location.href = '<?= admin_url('authentication'); ?>';
            }
        });
    }

    // Label click handlers
    if (staffLabel) {
        staffLabel.addEventListener('click', function() {
            window.location.href = '<?= admin_url('authentication'); ?>';
        });
    }
});
</script>
