<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Modern Project Summary Grid -->
<div class="modern-stats-grid">
    <?php
    $card_index = 1;
    foreach ($project_statuses as $status) {
        $project_count = total_rows(db_prefix() . 'projects', ['status' => $status['id'], 'clientid' => get_client_user_id()]);
    ?>
    <a href="<?= site_url('clients/projects/' . $status['id']); ?>"
       class="modern-stat-card modern-stat-card-<?= $card_index; ?>">
        <div class="modern-stat-header">
            <div class="modern-stat-icon-wrapper">
                <i class="fa fa-folder"></i>
            </div>
            <div class="modern-stat-badge"><?= e($status['name']); ?></div>
        </div>
        <div class="modern-stat-content">
            <h3><?= e($project_count); ?></h3>
            <p><?= e($status['name']); ?> Projects</p>
        </div>
        <div class="modern-stat-arrow">
            <i class="fa fa-arrow-right"></i>
        </div>
    </a>
    <?php
        $card_index++;
    }
    ?>
</div>
