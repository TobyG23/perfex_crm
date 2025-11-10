<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- Project Design Approvals - Client View -->
<div class="panel_s project-design-approvals">
    <div class="panel-body">
        <h4 class="no-margin"><?php echo _l('project_design_approvals'); ?></h4>
        <hr class="hr-panel-heading">

        <div id="design-approvals-container">
            <!-- Loading State -->
            <div id="designs-loading" class="text-center" style="padding: 40px;">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
                <p><?php echo _l('loading'); ?>...</p>
            </div>

            <!-- Error State -->
            <div id="designs-error" class="alert alert-danger" style="display: none;">
                <span id="error-message"></span>
            </div>

            <!-- Empty State -->
            <div id="designs-empty" class="text-center" style="display: none; padding: 40px;">
                <i class="fa fa-file-o fa-3x text-muted"></i>
                <p class="text-muted"><?php echo _l('no_designs_submitted_yet'); ?></p>
            </div>

            <!-- Designs List -->
            <div id="designs-list" style="display: none;">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th><?php echo _l('design_title'); ?></th>
                            <th><?php echo _l('description'); ?></th>
                            <th><?php echo _l('submitted_by'); ?></th>
                            <th><?php echo _l('date'); ?></th>
                            <th><?php echo _l('status'); ?></th>
                            <th><?php echo _l('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="designs-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Design Detail Modal -->
<div class="modal fade" id="designDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="designDetailTitle"></h4>
            </div>
            <div class="modal-body">
                <!-- Design Information -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><?php echo _l('design_information'); ?></h4>
                    </div>
                    <div class="panel-body">
                        <div id="designInfo"></div>
                    </div>
                </div>

                <!-- Approval Section (only for pending designs) -->
                <div class="panel panel-warning" id="approvalPanel" style="display: none;">
                    <div class="panel-heading">
                        <h4 class="panel-title"><?php echo _l('approval_actions'); ?></h4>
                    </div>
                    <div class="panel-body">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="acknowledgeDisclaimer">
                                <strong class="text-danger" id="disclaimerText"></strong>
                            </label>
                        </div>
                        <div class="btn-group btn-group-justified" style="margin-top: 15px;">
                            <div class="btn-group">
                                <button type="button" class="btn btn-success" id="btnApproveDesign" disabled>
                                    <i class="fa fa-check"></i> <?php echo _l('approve_design'); ?>
                                </button>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-danger" id="btnRejectDesign">
                                    <i class="fa fa-times"></i> <?php echo _l('reject_design'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h4 class="panel-title"><?php echo _l('comments'); ?></h4>
                    </div>
                    <div class="panel-body">
                        <div id="commentsContainer"></div>

                        <!-- Add Comment -->
                        <div style="margin-top: 20px;">
                            <textarea class="form-control" id="newComment" rows="3" placeholder="<?php echo _l('write_comment'); ?>..."></textarea>
                            <button type="button" class="btn btn-primary" id="btnAddComment" style="margin-top: 10px;">
                                <i class="fa fa-paper-plane"></i> <?php echo _l('send_comment'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
var projectId = <?php echo $project->id; ?>;
var currentDesign = null;

$(function() {
    loadDesigns();

    // Disclaimer checkbox handler
    $('#acknowledgeDisclaimer').on('change', function() {
        $('#btnApproveDesign').prop('disabled', !$(this).is(':checked'));
    });

    // Approve button handler
    $('#btnApproveDesign').on('click', function() {
        approveDesign(currentDesign.id);
    });

    // Reject button handler
    $('#btnRejectDesign').on('click', function() {
        showRejectDialog();
    });

    // Add comment button handler
    $('#btnAddComment').on('click', function() {
        addComment();
    });
});

function loadDesigns() {
    $('#designs-loading').show();
    $('#designs-error').hide();
    $('#designs-empty').hide();
    $('#designs-list').hide();

    $.ajax({
        url: site_url + 'api/projects/' + projectId + '/designs',
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + '<?php echo "client_" . get_contact_user_id(); ?>'
        },
        success: function(response) {
            $('#designs-loading').hide();

            if (response.success && response.data.length > 0) {
                renderDesigns(response.data);
                $('#designs-list').show();
            } else {
                $('#designs-empty').show();
            }
        },
        error: function(xhr, status, error) {
            $('#designs-loading').hide();
            $('#designs-error').show();
            $('#error-message').text('Error loading designs: ' + error);
        }
    });
}

function renderDesigns(designs) {
    var tbody = $('#designs-tbody');
    tbody.empty();

    designs.forEach(function(design) {
        var statusBadge = getStatusBadge(design.status);
        var row = $('<tr>');

        row.append($('<td>').html('<strong>' + design.title + '</strong>'));
        row.append($('<td>').text(design.description || '-'));
        row.append($('<td>').text(design.uploaded_by_name));
        row.append($('<td>').text(new Date(design.created_at).toLocaleDateString()));
        row.append($('<td>').html(statusBadge));

        var actionsCell = $('<td>');
        var viewBtn = $('<button>')
            .addClass('btn btn-sm btn-info')
            .html('<i class="fa fa-eye"></i> View')
            .on('click', function() {
                showDesignDetail(design);
            });
        actionsCell.append(viewBtn);
        row.append(actionsCell);

        tbody.append(row);
    });
}

function getStatusBadge(status) {
    var badges = {
        'pending': '<span class="label label-warning">Pendiente</span>',
        'approved': '<span class="label label-success">Aprobado</span>',
        'rejected': '<span class="label label-danger">Rechazado</span>'
    };
    return badges[status] || status;
}

function showDesignDetail(design) {
    currentDesign = design;

    $('#designDetailTitle').text(design.title);

    // Populate design info
    var infoHtml = '<dl class="dl-horizontal">';
    infoHtml += '<dt>Descripción:</dt><dd>' + (design.description || '-') + '</dd>';
    infoHtml += '<dt>Enviado por:</dt><dd>' + design.uploaded_by_name + '</dd>';
    infoHtml += '<dt>Fecha:</dt><dd>' + new Date(design.created_at).toLocaleString() + '</dd>';
    infoHtml += '<dt>Estado:</dt><dd>' + getStatusBadge(design.status) + '</dd>';

    if (design.file_name) {
        infoHtml += '<dt>Archivo:</dt><dd>' + design.file_name + '</dd>';
    }

    if (design.status === 'approved') {
        infoHtml += '<dt>Aprobado por:</dt><dd>' + (design.approved_by_name || '-') + '</dd>';
        infoHtml += '<dt>Fecha aprobación:</dt><dd>' + new Date(design.approved_at).toLocaleString() + '</dd>';
    }

    infoHtml += '</dl>';
    $('#designInfo').html(infoHtml);

    // Show/hide approval panel
    if (design.status === 'pending') {
        $('#approvalPanel').show();
        $('#disclaimerText').text(design.approval_note);
        $('#acknowledgeDisclaimer').prop('checked', false);
        $('#btnApproveDesign').prop('disabled', true);
    } else {
        $('#approvalPanel').hide();
    }

    // Load comments
    renderComments(design.comments || []);

    $('#designDetailModal').modal('show');
}

function renderComments(comments) {
    var container = $('#commentsContainer');
    container.empty();

    if (comments.length === 0) {
        container.html('<p class="text-muted text-center">No hay comentarios aún</p>');
        return;
    }

    comments.forEach(function(comment) {
        var commentDiv = $('<div>')
            .addClass('well well-sm')
            .addClass(comment.user_type === 'staff' ? 'bg-info' : 'bg-warning');

        var header = $('<div>').css('margin-bottom', '5px');
        header.append($('<strong>').text(comment.user_name + ' '));
        header.append($('<span>')
            .addClass('label')
            .addClass(comment.user_type === 'staff' ? 'label-primary' : 'label-default')
            .text(comment.user_type === 'staff' ? 'Staff' : 'Cliente'));
        header.append($('<small>')
            .addClass('text-muted')
            .css('margin-left', '10px')
            .text(new Date(comment.created_at).toLocaleString()));

        commentDiv.append(header);
        commentDiv.append($('<p>').text(comment.comment).css('margin', '0'));

        container.append(commentDiv);
    });
}

function addComment() {
    var comment = $('#newComment').val().trim();

    if (!comment || !currentDesign) {
        return;
    }

    $('#btnAddComment').prop('disabled', true).text('Enviando...');

    $.ajax({
        url: site_url + 'api/designs/' + currentDesign.id + '/comments',
        type: 'POST',
        headers: {
            'Authorization': 'Bearer ' + '<?php echo "client_" . get_contact_user_id(); ?>',
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({ comment: comment }),
        success: function(response) {
            if (response.success) {
                $('#newComment').val('');
                alert_float('success', 'Comentario agregado exitosamente');
                loadDesigns();
                // Refresh design to show new comment
                setTimeout(function() {
                    var design = designs.find(d => d.id === currentDesign.id);
                    if (design) {
                        showDesignDetail(design);
                    }
                }, 500);
            }
        },
        error: function(xhr, status, error) {
            alert_float('danger', 'Error al agregar comentario: ' + error);
        },
        complete: function() {
            $('#btnAddComment').prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Enviar Comentario');
        }
    });
}

function approveDesign(designId) {
    if (!confirm('¿Está seguro de que desea aprobar este diseño? Esta acción no se puede deshacer.')) {
        return;
    }

    $('#btnApproveDesign').prop('disabled', true).text('Aprobando...');

    $.ajax({
        url: site_url + 'api/designs/' + designId + '/approve',
        type: 'PUT',
        headers: {
            'Authorization': 'Bearer ' + '<?php echo "client_" . get_contact_user_id(); ?>',
            'Content-Type': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                alert_float('success', 'Diseño aprobado exitosamente');
                $('#designDetailModal').modal('hide');
                loadDesigns();

                // Show disclaimer alert
                if (response.disclaimer) {
                    setTimeout(function() {
                        alert(response.disclaimer);
                    }, 500);
                }
            }
        },
        error: function(xhr, status, error) {
            alert_float('danger', 'Error al aprobar diseño: ' + error);
            $('#btnApproveDesign').prop('disabled', false).html('<i class="fa fa-check"></i> Aprobar Diseño');
        }
    });
}

function showRejectDialog() {
    var reason = prompt('¿Por qué rechaza este diseño?');

    if (reason === null) {
        return; // User cancelled
    }

    rejectDesign(currentDesign.id, reason);
}

function rejectDesign(designId, reason) {
    $('#btnRejectDesign').prop('disabled', true).text('Rechazando...');

    $.ajax({
        url: site_url + 'api/designs/' + designId + '/reject',
        type: 'PUT',
        headers: {
            'Authorization': 'Bearer ' + '<?php echo "client_" . get_contact_user_id(); ?>',
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({ reason: reason }),
        success: function(response) {
            if (response.success) {
                alert_float('warning', 'Diseño rechazado');
                $('#designDetailModal').modal('hide');
                loadDesigns();
            }
        },
        error: function(xhr, status, error) {
            alert_float('danger', 'Error al rechazar diseño: ' + error);
            $('#btnRejectDesign').prop('disabled', false).html('<i class="fa fa-times"></i> Rechazar Diseño');
        }
    });
}
</script>

<style>
.project-design-approvals .panel-heading {
    background-color: #f5f5f5;
}

.bg-info {
    background-color: #d9edf7 !important;
}

.bg-warning {
    background-color: #fcf8e3 !important;
}
</style>
