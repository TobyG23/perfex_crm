<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$is_approved = isset($project->overall_design_approved) && $project->overall_design_approved == 1;
$can_approve = !$is_approved && is_client_logged_in();
?>
<?php if (! isset($discussion)) { ?>

<?php if ($is_approved) { ?>
<div class="alert alert-success mtop15 mbot15">
    <i class="fa fa-check-circle"></i>
    <strong><?= _l('design_approved'); ?></strong><br>
    <?= _l('design_approved_message', _dt($project->overall_design_approved_at)); ?>
</div>
<?php } elseif ($can_approve) { ?>
<div class="panel panel-warning mtop15 mbot15">
    <div class="panel-body">
        <h4 class="tw-mt-0">
            <i class="fa fa-check-square-o"></i> <?= _l('approve_overall_design'); ?>
        </h4>
        <p class="text-muted"><?= _l('approve_design_description'); ?></p>
        <button type="button" class="btn btn-success" onclick="showApprovalModal()">
            <i class="fa fa-check"></i> <?= _l('approve_design_button'); ?>
        </button>
    </div>
</div>
<?php } ?>

<?php if ($project->settings->open_discussions == 1 && !$is_approved) { ?>
<a href="#" onclick="new_discussion();return false;"
    class="btn btn-primary mtop5"><?= _l('new_project_discussion'); ?></a>
<hr />
<?php } elseif ($is_approved) { ?>
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> <?= _l('discussions_locked_after_approval'); ?>
</div>
<hr />
<?php } ?>

<!-- Miles Stones -->
<div class="modal fade" id="discussion" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?= form_open(site_url('clients/project/' . $project->id), ['id' => 'discussion_form']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <span
                        class="edit-title"><?= _l('edit_discussion'); ?></span>
                    <span
                        class="add-title"><?= _l('new_project_discussion'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <?= form_hidden('project_id', $project->id); ?>
                        <?= form_hidden('action', 'new_discussion'); ?>
                        <div id="additional_discussion"></div>
                        <?= render_input('subject', 'project_discussion_subject'); ?>
                        <?= render_textarea('description', 'project_discussion_description'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default"
                    data-dismiss="modal"><?= _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"
                    data-loading-text="<?= _l('wait_text'); ?>"
                    data-autocomplete="off"
                    data-form="#discussion_form"><?= _l('submit'); ?></button>
            </div>
        </div>
        <!-- /.modal-content -->
        <?= form_close(); ?>
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->
<!-- Mile stones end -->

<table class="table dt-table" data-order-col="1" data-order-type="desc">
    <thead>
        <tr>
            <th>
                <?= _l('project_discussion_subject'); ?>
            </th>
            <th>
                <?= _l('project_discussion_last_activity'); ?>
            </th>
            <th>
                <?= _l('project_discussion_total_comments'); ?>
            </th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($discussions as $discussion) { ?>
        <tr>
            <td>
                <a
                    href="<?= site_url('clients/project/' . $project->id . '?group=' . $group . '&discussion_id=' . $discussion['id']); ?>">
                    <?= e($discussion['subject']); ?>
                </a>
            </td>
            <td
                data-order="<?= e($discussion['last_activity']); ?>">
                <?= e(! is_null($discussion['last_activity']) ? time_ago($discussion['last_activity']) : _l('project_discussion_no_activity')); ?>
            </td>
            <td>
                <?= e($discussion['total_comments']); ?>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>

<!-- Modal de Confirmación de Aprobación -->
<div class="modal fade" id="approvalConfirmationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-exclamation-triangle"></i> <?= _l('confirm_design_approval'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h4><i class="fa fa-warning"></i> <?= _l('important_disclaimer'); ?></h4>
                    <p><strong><?= _l('approval_disclaimer_text'); ?></strong></p>
                    <ul>
                        <li><?= _l('approval_disclaimer_point_1'); ?></li>
                        <li><?= _l('approval_disclaimer_point_2'); ?></li>
                        <li><?= _l('approval_disclaimer_point_3'); ?></li>
                        <li><?= _l('approval_disclaimer_point_4'); ?></li>
                    </ul>
                </div>

                <div class="form-group">
                    <label><?= _l('approval_comments_optional'); ?></label>
                    <textarea id="approvalComments" class="form-control" rows="3"
                        placeholder="<?= _l('approval_comments_placeholder'); ?>"></textarea>
                </div>

                <div class="checkbox checkbox-primary">
                    <input type="checkbox" id="confirmApprovalCheckbox">
                    <label for="confirmApprovalCheckbox">
                        <strong><?= _l('i_have_read_and_accept'); ?></strong>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?= _l('cancel'); ?>
                </button>
                <button type="button" class="btn btn-success" id="confirmApprovalBtn" disabled onclick="approveDesign()">
                    <i class="fa fa-check"></i> <?= _l('confirm_approval'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showApprovalModal() {
    $('#approvalConfirmationModal').modal('show');
}

// Enable/disable confirm button based on checkbox
$('#confirmApprovalCheckbox').on('change', function() {
    $('#confirmApprovalBtn').prop('disabled', !this.checked);
});

function approveDesign() {
    if (!$('#confirmApprovalCheckbox').is(':checked')) {
        alert('<?= _l('must_accept_terms'); ?>');
        return;
    }

    var comments = $('#approvalComments').val();
    var projectId = <?= $project->id; ?>;

    $('#confirmApprovalBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?= _l('processing'); ?>');

    $.post('<?= site_url("clients/approve_overall_design"); ?>', {
        project_id: projectId,
        comments: comments
    }, function(response) {
        if (response.success) {
            $('#approvalConfirmationModal').modal('hide');

            // Show success message
            alert_float('success', response.message);

            // Update UI dynamically without full page reload
            updateApprovalUI();
        } else {
            alert_float('danger', response.message);
            $('#confirmApprovalBtn').prop('disabled', false).html('<i class="fa fa-check"></i> <?= _l('confirm_approval'); ?>');
        }
    }, 'json').fail(function() {
        alert_float('danger', '<?= _l('error_occurred'); ?>');
        $('#confirmApprovalBtn').prop('disabled', false).html('<i class="fa fa-check"></i> <?= _l('confirm_approval'); ?>');
    });
}

function updateApprovalUI() {
    // Remove the approval panel
    $('.panel.panel-warning').fadeOut(400, function() {
        $(this).remove();
    });

    // Remove the "New Discussion" button
    $('a[onclick*="new_discussion"]').fadeOut(400, function() {
        $(this).remove();
    });

    // Add approval success message
    var successHtml = '<div class="alert alert-success mtop15 mbot15" style="display: none;">' +
        '<i class="fa fa-check-circle"></i> ' +
        '<strong><?= _l('design_approved'); ?></strong><br>' +
        '<?= _l('design_approved_message'); ?>' +
        '</div>';

    // Add locked message
    var lockedHtml = '<div class="alert alert-info" style="display: none;">' +
        '<i class="fa fa-info-circle"></i> <?= _l('discussions_locked_after_approval'); ?>' +
        '</div>' +
        '<hr />';

    // Insert messages at the beginning of the discussions area
    $('table.dt-table').before(lockedHtml);
    $('table.dt-table').before(successHtml);

    // Fade in the new messages
    $('.alert-success').fadeIn(600);
    $('.alert-info').fadeIn(600);
}

// Reset modal when closed
$('#approvalConfirmationModal').on('hidden.bs.modal', function() {
    $('#confirmApprovalCheckbox').prop('checked', false);
    $('#confirmApprovalBtn').prop('disabled', true);
    $('#approvalComments').val('');
});
</script>

<?php
} else { ?>
<?= form_hidden('discussion_user_profile_image_url', $discussion_user_profile_image_url); ?>
<?= form_hidden('discussion_id', $discussion->id); ?>
<h3 class="tw-font-medium tw-mt-0 tw-text-lg">
    <?= e($discussion->subject); ?>
</h3>
<p class="tw-mb-0 tw-text-neutral-700">
    <?= e(_l('project_discussion_posted_on', _d($discussion->datecreated))); ?>
</p>
<p class="tw-mb-0 tw-text-neutral-700">
    <?= e(_l('project_discussion_posted_by', $discussion->staff_id == 0 ? get_contact_full_name($discussion->contact_id) : get_staff_full_name($discussion->staff_id))); ?>
</p>
<p class="tw-text-neutral-700">
    <?= _l('project_discussion_total_comments'); ?>:
    <?= total_rows(db_prefix() . 'projectdiscussioncomments', ['discussion_id' => $discussion->id, 'discussion_type' => 'regular']); ?>
</p>
<div class="tw-text-neutral-500">
    <?= process_text_content_for_display($discussion->description); ?>
</div>
<hr />
<div id="discussion-comments" class="tc-content"></div>
<?php } ?>
