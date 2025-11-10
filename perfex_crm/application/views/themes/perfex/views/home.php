<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12 section-client-dashboard">

        <!-- Modern Welcome Section -->
        <div class="modern-welcome-section">
            <div class="modern-welcome-content">
                <div class="modern-welcome-text">
                    <h1 id="greeting"></h1>
                    <p>Here's your Intergraphics overview</p>
                    <div class="modern-user-badge">
                        <span class="badge">Client Portal</span>
                    </div>
                </div>
                <div class="modern-welcome-logo">
                    <div class="modern-logo-badge">iG</div>
                </div>
            </div>
            <div class="modern-welcome-date">
                <i class="fa fa-calendar"></i>
                <span id="current-date"></span>
            </div>
        </div>

        <?php if (has_contact_permission('projects')) { ?>
        <!-- Projects Section -->
        <div class="modern-section-header">
            <h3 class="modern-section-title">
                <i class="fa fa-folder"></i>
                <?= _l('projects_summary'); ?>
            </h3>
        </div>

        <?php get_template_part('projects/project_summary'); ?>
        <?php } ?>

        <?php hooks()->do_action('client_area_after_project_overview'); ?>

        <?php if (has_contact_permission('invoices')) { ?>
        <!-- Invoices Section -->
        <div class="modern-section-header">
            <h3 class="modern-section-title">
                <i class="fa fa-file-invoice"></i>
                <?= _l('clients_quick_invoice_info'); ?>
            </h3>
            <?php if (has_contact_permission('invoices')) { ?>
            <a href="<?= site_url('clients/statement'); ?>" class="modern-section-link">
                <?= _l('view_account_statement'); ?>
                <i class="fa fa-arrow-right"></i>
            </a>
            <?php } ?>
        </div>

        <div class="modern-panel">
            <?php get_template_part('invoices_stats'); ?>

            <hr style="margin: 24px 0;" />

            <div class="row">
                <div class="col-md-3">
                    <?php if (count($payments_years) > 0) { ?>
                    <div class="form-group">
                        <select
                            data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>"
                            class="form-control" id="payments_year" name="payments_years" data-width="100%"
                            onchange="total_income_bar_report();"
                            data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                            <?php foreach ($payments_years as $year) { ?>
                            <option
                                value="<?= e($year['year']); ?>"
                                <?php if ($year['year'] == date('Y')) {
                                    echo 'selected';
                                } ?>>
                                <?= e($year['year']); ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <?php } ?>
                    <?php if (is_client_using_multiple_currencies()) { ?>
                    <div id="currency" class="form-group mtop15" data-toggle="tooltip"
                        title="<?= _l('clients_home_currency_select_tooltip'); ?>">
                        <select
                            data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>"
                            class="form-control" name="currency">
                            <?php foreach ($currencies as $currency) {
                                $selected = '';
                                if ($currency['isdefault'] == 1) {
                                    $selected = 'selected';
                                } ?>
                            <option
                                value="<?= e($currency['id']); ?>"
                                <?= e($selected); ?>>
                                <?= e($currency['symbol']); ?>
                                -
                                <?= e($currency['name']); ?>
                            </option>
                            <?php
                            } ?>
                        </select>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="relative" style="max-height:400px;">
                        <canvas id="client-home-chart" height="400" class="animated fadeIn"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>

        <?php hooks()->do_action('client_area_dashboard_end'); ?>
    </div>
</div>

<script>
    // Greeting logic
    var greetDate = new Date();
    var hrsGreet = greetDate.getHours();

    var greet;
    if (hrsGreet < 12)
        greet = "<?= _l('good_morning'); ?>";
    else if (hrsGreet >= 12 && hrsGreet <= 17)
        greet = "<?= _l('good_afternoon'); ?>";
    else if (hrsGreet >= 17 && hrsGreet <= 24)
        greet = "<?= _l('good_evening'); ?>";

    if (greet) {
        document.getElementById('greeting').innerHTML = greet + ', <?= e($contact->firstname); ?>! 👋';
    }

    // Current date
    var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    var today = new Date();
    document.getElementById('current-date').innerHTML = today.toLocaleDateString('<?= get_locale_key($locale ?? "en"); ?>', options);
</script>

<style>
/* Override default panel styles for modern look */
.section-client-dashboard .panel_s {
    display: none;
}

.section-client-dashboard .projects-summary-heading,
.section-client-dashboard .invoices-quick-info-heading {
    display: none;
}
</style>
