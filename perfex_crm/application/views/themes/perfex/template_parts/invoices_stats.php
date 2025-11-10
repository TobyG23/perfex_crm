<?php defined('BASEPATH') or exit('No direct script access allowed');

$where_total = 'clientid=' . get_client_user_id() . ' AND status !=5';
if (get_option('exclude_invoice_from_client_area_with_draft_status') == 1) {
    $where_total .= ' AND status != 6';
}

$total_invoices            = total_rows(db_prefix() . 'invoices', $where_total);
$total_open                = total_rows(db_prefix() . 'invoices', ['status' => 1, 'clientid' => get_client_user_id()]);
$total_paid                = total_rows(db_prefix() . 'invoices', ['status' => 2, 'clientid' => get_client_user_id()]);
$total_not_paid_completely = total_rows(db_prefix() . 'invoices', ['status' => 3, 'clientid' => get_client_user_id()]);
$total_overdue             = total_rows(db_prefix() . 'invoices', ['status' => 4, 'clientid' => get_client_user_id()]);

$percent_open                = ($total_invoices > 0 ? number_format(($total_open * 100) / $total_invoices, 2) : 0);
$percent_paid                = ($total_invoices > 0 ? number_format(($total_paid * 100) / $total_invoices, 2) : 0);
$percent_overdue             = ($total_invoices > 0 ? number_format(($total_overdue * 100) / $total_invoices, 2) : 0);
$percent_not_paid_completely = ($total_invoices > 0 ? number_format(($total_not_paid_completely * 100) / $total_invoices, 2) : 0);

?>

<!-- Modern Invoice Stats Grid -->
<div class="modern-stats-grid">
    <!-- Unpaid Invoices -->
    <a href="<?= site_url('clients/invoices/1'); ?>" class="modern-stat-card modern-stat-card-1">
        <div class="modern-stat-header">
            <div class="modern-stat-icon-wrapper">
                <i class="fa fa-file-invoice"></i>
            </div>
            <div class="modern-stat-badge"><?= number_format($percent_open, 0); ?>%</div>
        </div>
        <div class="modern-stat-content">
            <h3><?= e($total_open); ?></h3>
            <p><?= _l('invoice_status_unpaid'); ?></p>
        </div>
        <div class="modern-stat-arrow">
            <i class="fa fa-arrow-right"></i>
        </div>
        <div style="margin-top: 12px; height: 4px; background: #f0f0f0; border-radius: 4px; overflow: hidden;">
            <div style="height: 100%; background: linear-gradient(90deg, #e63946 0%, #c41e3a 100%); width: <?= e($percent_open); ?>%; transition: width 0.6s ease;"></div>
        </div>
    </a>

    <!-- Paid Invoices -->
    <a href="<?= site_url('clients/invoices/2'); ?>" class="modern-stat-card modern-stat-card-2">
        <div class="modern-stat-header">
            <div class="modern-stat-icon-wrapper">
                <i class="fa fa-check-circle"></i>
            </div>
            <div class="modern-stat-badge"><?= number_format($percent_paid, 0); ?>%</div>
        </div>
        <div class="modern-stat-content">
            <h3><?= e($total_paid); ?></h3>
            <p><?= _l('invoice_status_paid'); ?></p>
        </div>
        <div class="modern-stat-arrow">
            <i class="fa fa-arrow-right"></i>
        </div>
        <div style="margin-top: 12px; height: 4px; background: #f0f0f0; border-radius: 4px; overflow: hidden;">
            <div style="height: 100%; background: linear-gradient(90deg, #28a745 0%, #20c997 100%); width: <?= e($percent_paid); ?>%; transition: width 0.6s ease;"></div>
        </div>
    </a>

    <!-- Overdue Invoices -->
    <a href="<?= site_url('clients/invoices/4'); ?>" class="modern-stat-card modern-stat-card-3">
        <div class="modern-stat-header">
            <div class="modern-stat-icon-wrapper">
                <i class="fa fa-exclamation-triangle"></i>
            </div>
            <div class="modern-stat-badge"><?= number_format($percent_overdue, 0); ?>%</div>
        </div>
        <div class="modern-stat-content">
            <h3><?= e($total_overdue); ?></h3>
            <p><?= _l('invoice_status_overdue'); ?></p>
        </div>
        <div class="modern-stat-arrow">
            <i class="fa fa-arrow-right"></i>
        </div>
        <div style="margin-top: 12px; height: 4px; background: #f0f0f0; border-radius: 4px; overflow: hidden;">
            <div style="height: 100%; background: linear-gradient(90deg, #ffc107 0%, #ff9800 100%); width: <?= e($percent_overdue); ?>%; transition: width 0.6s ease;"></div>
        </div>
    </a>

    <!-- Partially Paid Invoices -->
    <a href="<?= site_url('clients/invoices/3'); ?>" class="modern-stat-card modern-stat-card-4">
        <div class="modern-stat-header">
            <div class="modern-stat-icon-wrapper">
                <i class="fa fa-hourglass-half"></i>
            </div>
            <div class="modern-stat-badge"><?= number_format($percent_not_paid_completely, 0); ?>%</div>
        </div>
        <div class="modern-stat-content">
            <h3><?= e($total_not_paid_completely); ?></h3>
            <p><?= _l('invoice_status_not_paid_completely'); ?></p>
        </div>
        <div class="modern-stat-arrow">
            <i class="fa fa-arrow-right"></i>
        </div>
        <div style="margin-top: 12px; height: 4px; background: #f0f0f0; border-radius: 4px; overflow: hidden;">
            <div style="height: 100%; background: linear-gradient(90deg, #17a2b8 0%, #138496 100%); width: <?= e($percent_not_paid_completely); ?>%; transition: width 0.6s ease;"></div>
        </div>
    </a>
</div>
