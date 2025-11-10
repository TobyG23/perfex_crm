<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row section-heading">
    <div class="col-md-12">
        <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700 section-text">
            <i class="fa-regular fa-bell tw-mr-2"></i><?= _l('notifications'); ?>
        </h4>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel_s">
            <div class="panel-body">
                <div class="tw-mb-4 tw-flex tw-justify-between tw-items-center">
                    <div>
                        <?php if ($total_unread > 0) { ?>
                        <span class="badge bg-info"><?= $total_unread; ?> <?= _l('notifications_new'); ?></span>
                        <?php } ?>
                    </div>
                    <div>
                        <a href="#" id="mark-all-read" class="btn btn-default btn-sm">
                            <i class="fa fa-check tw-mr-1"></i><?= _l('mark_all_as_read'); ?>
                        </a>
                    </div>
                </div>

                <?php if (count($notifications) > 0) { ?>
                <div class="notifications-list">
                    <?php foreach ($notifications as $notification) {
                        $unread_class = $notification['isread'] == 0 ? 'unread-notification' : '';

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

                        // Parse additional data
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
                                        $temp = $status['name'];
                                    }
                                    $additional_data[$i] = $temp;
                                }
                                $i++;
                            }
                        }

                        $description = _l($notification['description'], $additional_data);

                        // Add sender name
                        if (($notification['fromcompany'] == null && $notification['fromuserid'] != 0)
                            || ($notification['fromcompany'] == null && $notification['fromclientid'] != 0)) {
                            if ($notification['fromuserid'] != 0) {
                                $from_name = get_staff_full_name($notification['fromuserid']);
                            } else {
                                $from_name = get_contact_full_name($notification['fromclientid']);
                            }
                            $description = $from_name . ' - ' . $description;
                        }
                    ?>
                    <div class="notification-item <?= $unread_class; ?> tw-p-4 tw-border-b tw-border-neutral-200 tw-flex tw-justify-between tw-items-start hover:tw-bg-neutral-50 tw-transition-colors" data-notification-id="<?= $notification['id']; ?>">
                        <div class="tw-flex-1">
                            <a href="<?= $link; ?>" class="notification-link tw-block">
                                <div class="tw-flex tw-items-start">
                                    <?php if (($notification['fromcompany'] == null && $notification['fromuserid'] != 0) || ($notification['fromcompany'] == null && $notification['fromclientid'] != 0)) { ?>
                                    <div class="tw-mr-3">
                                        <?php if ($notification['fromuserid'] != 0) {
                                            echo staff_profile_image($notification['fromuserid'], ['tw-w-10 tw-h-10 tw-rounded-full']);
                                        } else {
                                            echo '<img src="' . contact_profile_image_url($notification['fromclientid'], 'thumb') . '" class="tw-w-10 tw-h-10 tw-rounded-full">';
                                        } ?>
                                    </div>
                                    <?php } ?>
                                    <div class="tw-flex-1">
                                        <p class="tw-mb-1 tw-text-neutral-700 <?= $notification['isread'] == 0 ? 'tw-font-semibold' : ''; ?>">
                                            <?= $description; ?>
                                        </p>
                                        <p class="tw-text-sm tw-text-neutral-500 tw-mb-0">
                                            <i class="fa fa-clock-o tw-mr-1"></i><?= time_ago($notification['date']); ?>
                                        </p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php if ($notification['isread'] == 0) { ?>
                        <div class="tw-ml-3">
                            <button class="mark-as-read-btn btn btn-sm btn-default" data-id="<?= $notification['id']; ?>" data-toggle="tooltip" title="<?= _l('mark_as_read'); ?>">
                                <i class="fa fa-check"></i>
                            </button>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } ?>
                </div>
                <?php } else { ?>
                <div class="tw-text-center tw-py-8">
                    <i class="fa fa-bell-o tw-text-6xl tw-text-neutral-300 tw-mb-4"></i>
                    <p class="tw-text-neutral-500 tw-text-lg"><?= _l('nav_no_notifications'); ?></p>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<style>
.notification-item.unread-notification {
    background-color: #f8f9fa;
    border-left: 3px solid #03a9f4;
}

.notification-link {
    text-decoration: none;
    color: inherit;
}

.notification-link:hover {
    text-decoration: none;
}

.notifications-list .notification-item:last-child {
    border-bottom: none;
}
</style>

<script>
$(document).ready(function() {
    // Mark single notification as read
    $('.mark-as-read-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var btn = $(this);
        var notificationId = btn.data('id');

        $.post('<?= site_url("clients/mark_notification_read"); ?>', {
            id: notificationId
        }, function(response) {
            if (response.success) {
                var item = btn.closest('.notification-item');
                item.removeClass('unread-notification');
                item.find('.tw-font-semibold').removeClass('tw-font-semibold');
                btn.parent().fadeOut();

                // Update badge count
                updateBadgeCount();
            }
        }, 'json');
    });

    // Mark all as read
    $('#mark-all-read').on('click', function(e) {
        e.preventDefault();

        $.post('<?= site_url("clients/mark_all_notifications_read"); ?>', {}, function(response) {
            if (response.success) {
                $('.notification-item').removeClass('unread-notification');
                $('.notification-item .tw-font-semibold').removeClass('tw-font-semibold');
                $('.mark-as-read-btn').parent().fadeOut();
                $('.badge.bg-info').fadeOut();

                // Update badge count
                updateBadgeCount();

                alert_float('success', '<?= _l("notifications_marked_as_read"); ?>');
            }
        }, 'json');
    });

    function updateBadgeCount() {
        var unreadCount = $('.notification-item.unread-notification').length;
        var badge = $('.notifications-badge');
        if (unreadCount > 0) {
            badge.text(unreadCount).show();
        } else {
            badge.hide();
        }
    }
});
</script>
