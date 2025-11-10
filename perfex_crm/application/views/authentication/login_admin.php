<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('authentication/includes/head.php'); ?>

<body class="login_admin">

    <!-- Modern Login Wrapper -->
    <div class="modern-login-wrapper">
        <!-- Animated Background Waves -->
        <div class="login-wave login-wave-1"></div>
        <div class="login-wave login-wave-2"></div>

        <!-- Modern Login Card -->
        <div class="modern-login-card">
            <!-- Login Type Badge -->
            <div class="login-type-badge staff-badge">
                <i class="fa fa-briefcase"></i> Staff
            </div>

            <!-- Login Type Switch -->
            <div class="login-type-switch-container">
                <span class="login-type-label" id="client-label">Cliente</span>
                <div class="login-type-switch staff-active" id="login-type-toggle">
                    <div class="login-type-switch-slider"></div>
                    <i class="fa fa-user login-type-switch-icon client-icon"></i>
                    <i class="fa fa-briefcase login-type-switch-icon staff-icon"></i>
                </div>
                <span class="login-type-label active" id="staff-label">Staff</span>
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
                    <?= _l('admin_auth_login_heading'); ?>
                </h2>
                <p class="modern-login-subheading">
                    <?= _l('welcome_back_sign_in'); ?>
                </p>
            </div>

            <?php $this->load->view('authentication/includes/alerts'); ?>

            <!-- Login Form -->
            <?= form_open($this->uri->uri_string(), ['class' => 'modern-login-form', 'id' => 'modern-login-form']); ?>

            <?= validation_errors('<div class="modern-error modern-text-center">', '</div>'); ?>

            <?php hooks()->do_action('after_admin_login_form_start'); ?>

            <!-- Email Input -->
            <div class="modern-form-group">
                <label for="email" class="modern-label">
                    <?= _l('admin_auth_login_email'); ?>
                </label>
                <div class="modern-input-wrapper">
                    <i class="fa fa-envelope modern-input-icon"></i>
                    <input type="email"
                           class="modern-input"
                           name="email"
                           id="email"
                           placeholder="<?= _l('admin_auth_login_email'); ?>"
                           autofocus="1"
                           value="<?= set_value('email'); ?>">
                </div>
            </div>

            <!-- Password Input -->
            <div class="modern-form-group">
                <label for="password" class="modern-label" style="display: flex; justify-content: space-between; align-items: center;">
                    <span><?= _l('admin_auth_login_password'); ?></span>
                    <a href="<?= admin_url('authentication/forgot_password'); ?>"
                       class="modern-forgot-link"
                       style="margin: 0; font-size: 13px;">
                        <?= _l('admin_auth_login_fp'); ?>
                    </a>
                </label>
                <div class="modern-input-wrapper">
                    <i class="fa fa-lock modern-input-icon"></i>
                    <input type="password"
                           class="modern-input"
                           name="password"
                           id="password"
                           placeholder="<?= _l('admin_auth_login_password'); ?>">
                </div>
            </div>

            <!-- reCAPTCHA -->
            <?php if (show_recaptcha()) { ?>
            <div class="modern-recaptcha-wrapper">
                <div class="g-recaptcha" data-sitekey="<?= get_option('recaptcha_site_key'); ?>"></div>
            </div>
            <?php } ?>

            <!-- Remember Me Checkbox -->
            <div class="modern-checkbox-wrapper">
                <input type="checkbox" name="remember" id="remember" class="modern-checkbox">
                <label for="remember" class="modern-checkbox-label">
                    <?= _l('admin_auth_login_remember_me'); ?>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="modern-btn modern-btn-primary" id="login-submit-btn">
                <?= _l('admin_auth_login_button'); ?>
            </button>

            <?php hooks()->do_action('before_admin_login_form_close'); ?>

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
                // Toggle to Client login
                if (toggle.classList.contains('staff-active')) {
                    // Redirect to client login with smooth transition
                    window.location.href = '<?= site_url('authentication/login'); ?>';
                }
            });
        }

        // Label click handlers
        if (clientLabel) {
            clientLabel.addEventListener('click', function() {
                window.location.href = '<?= site_url('authentication/login'); ?>';
            });
        }
    });
    </script>

</body>

</html>
