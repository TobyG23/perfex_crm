<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Client Notifications Hooks
 * Adds notification functionality to the client area
 */

// Add notification menu item to client navigation
hooks()->add_action('customers_navigation_start', 'add_client_notifications_menu');

function add_client_notifications_menu()
{
    $CI = &get_instance();

    if (!is_client_logged_in()) {
        return;
    }

    // Get unread notifications count
    $CI->db->select('COUNT(*) as total');
    $CI->db->from(db_prefix() . 'notifications');
    $CI->db->where('isread', 0);
    $contact_id = get_contact_user_id();
    $CI->db->where('(fromclientid = ' . $contact_id . ' OR (fromclientid IS NULL AND link LIKE "%clients/%"))');
    $result = $CI->db->get()->row();
    $unread_count = $result ? $result->total : 0;

    // Add menu item
    $CI->app_menu->add('notifications', [
        'name'     => _l('notifications') . ($unread_count > 0 ? ' <span class="badge notifications-badge bg-info">' . $unread_count . '</span>' : ''),
        'href'     => site_url('clients/notifications'),
        'icon'     => 'fa fa-bell',
        'position' => 5,
    ], 'theme');
}

// Add notification dropdown to navigation (optional - for inline notifications)
hooks()->add_action('customers_navigation_after_profile', 'add_client_notifications_dropdown');

function add_client_notifications_dropdown()
{
    if (!is_client_logged_in()) {
        return;
    }

    $CI = &get_instance();

    // Get unread notifications count
    $CI->db->select('COUNT(*) as total');
    $CI->db->from(db_prefix() . 'notifications');
    $CI->db->where('isread', 0);
    $contact_id = get_contact_user_id();
    $CI->db->where('(fromclientid = ' . $contact_id . ' OR (fromclientid IS NULL AND link LIKE "%clients/%"))');
    $result = $CI->db->get()->row();
    $unread_count = $result ? $result->total : 0;

    // Get recent notifications
    $CI->db->select('*');
    $CI->db->from(db_prefix() . 'notifications');
    $CI->db->where('(fromclientid = ' . $contact_id . ' OR (fromclientid IS NULL AND link LIKE "%clients/%"))');
    $CI->db->order_by('date', 'DESC');
    $CI->db->limit(5);
    $notifications = $CI->db->get()->result_array();

    ?>
    <li class="dropdown customers-nav-item-notifications">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-bell"></i>
            <?php if ($unread_count > 0) { ?>
                <span class="badge notifications-badge bg-info"><?= $unread_count; ?></span>
            <?php } ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-right width300 notifications-dropdown">
            <li class="dropdown-header">
                <strong><?= _l('notifications'); ?></strong>
                <?php if ($unread_count > 0) { ?>
                    <a href="#" class="pull-right text-muted mark-all-read-inline" style="font-size:12px;">
                        <?= _l('mark_all_as_read'); ?>
                    </a>
                <?php } ?>
            </li>
            <?php if (count($notifications) > 0) { ?>
                <?php foreach ($notifications as $notification) {
                    // Transform admin URLs to client URLs
                    $link = !empty($notification['link']) ? $notification['link'] : '#';
                    if ($link != '#') {
                        // Transform project URLs from admin to client area
                        // From: projects/view/3?group=... or admin/projects/view/3?group=...
                        // To: clients/project/3?group=...
                        $link = preg_replace('/(admin\/)?projects\/view\/(\d+)/', 'clients/project/$2', $link);
                        // Transform other common admin URLs if needed
                        $link = str_replace('admin/', '', $link);
                        $link = site_url($link);
                    }
                ?>
                    <li class="notification-item-inline <?= $notification['isread'] == 0 ? 'unread' : ''; ?>" data-id="<?= $notification['id']; ?>">
                        <a href="<?= $link; ?>">
                            <div class="notification-content">
                                <?php
                                $additional_data = '';
                                if (!empty($notification['additional_data'])) {
                                    $additional_data = unserialize($notification['additional_data']);
                                    $i = 0;
                                    foreach ($additional_data as $data) {
                                        if (strpos($data, '<lang>') !== false) {
                                            $lang = get_string_between($data, '<lang>', '</lang>');
                                            $temp = _l($lang);
                                            if (strpos($temp, 'project_status_') !== false) {
                                                $status = get_project_status_by_id(strafter($temp, 'project_status_'));
                                                $temp   = $status['name'];
                                            }
                                            $additional_data[$i] = $temp;
                                        }
                                        $i++;
                                    }
                                }
                                $description = _l($notification['description'], $additional_data);
                                echo '<p class="notification-text">' . $description . '</p>';
                                echo '<small class="text-muted">' . time_ago($notification['date']) . '</small>';
                                ?>
                            </div>
                        </a>
                    </li>
                <?php } ?>
                <li class="divider"></li>
                <li class="text-center">
                    <a href="<?= site_url('clients/notifications'); ?>" class="btn btn-default btn-sm">
                        <?= _l('nav_view_all_notifications'); ?>
                    </a>
                </li>
            <?php } else { ?>
                <li class="text-center">
                    <p class="text-muted" style="padding: 10px;"><?= _l('nav_no_notifications'); ?></p>
                </li>
            <?php } ?>
        </ul>
    </li>

    <style>
        .notifications-dropdown {
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-item-inline {
            border-left: 3px solid transparent;
        }

        .notification-item-inline.unread {
            background-color: #f8f9fa;
            border-left-color: #03a9f4;
        }

        .notification-item-inline a {
            padding: 10px 15px;
            display: block;
            text-decoration: none;
        }

        .notification-item-inline a:hover {
            background-color: #f1f1f1;
        }

        .notification-content {
            white-space: normal;
        }

        .notification-text {
            margin: 0 0 5px 0;
            font-size: 13px;
            color: #333;
        }

        .notifications-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            padding: 3px 6px;
            font-size: 10px;
            line-height: 1;
        }
    </style>

    <script>
        $(document).ready(function() {
            // Mark all as read inline
            $('.mark-all-read-inline').on('click', function(e) {
                e.preventDefault();
                $.post('<?= site_url("clients/mark_all_notifications_read"); ?>', {}, function(response) {
                    if (response.success) {
                        $('.notification-item-inline').removeClass('unread');
                        $('.notifications-badge').fadeOut();
                        alert_float('success', '<?= _l("notifications_marked_as_read"); ?>');
                    }
                }, 'json');
            });
        });
    </script>
    <?php
}
