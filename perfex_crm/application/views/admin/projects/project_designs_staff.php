<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- Project Design Approvals - Staff View -->
<div class="panel_s">
    <div class="panel-body">
        <div class="row">
            <div class="col-md-8">
                <h4 class="no-margin"><?php echo _l('project_design_approvals'); ?></h4>
            </div>
            <div class="col-md-4 text-right">
                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#submitDesignModal">
                    <i class="fa fa-plus"></i> <?php echo _l('submit_new_design'); ?>
                </button>
            </div>
        </div>
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
                <p><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#submitDesignModal">
                    <i class="fa fa-plus"></i> <?php echo _l('submit_first_design'); ?>
                </button></p>
            </div>

            <!-- Designs List -->
            <div id="designs-list" style="display: none;">
                <table class="table table-striped table-hover dt-table">
                    <thead>
                        <tr>
                            <th><?php echo _l('design_title'); ?></th>
                            <th><?php echo _l('description'); ?></th>
                            <th><?php echo _l('submitted_by'); ?></th>
                            <th><?php echo _l('date'); ?></th>
                            <th><?php echo _l('status'); ?></th>
                            <th><?php echo _l('client_response'); ?></th>
                            <th><?php echo _l('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="designs-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Submit New Design Modal -->
<div class="modal fade" id="submitDesignModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo _l('submit_new_design'); ?></h4>
            </div>
            <form id="submitDesignForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="designTitle" class="control-label">
                            <?php echo _l('design_title'); ?> <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="designTitle" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="designDescription" class="control-label">
                            <?php echo _l('description'); ?>
                        </label>
                        <textarea class="form-control" id="designDescription" name="description" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="designFile" class="control-label">
                            <?php echo _l('attach_file'); ?>
                        </label>
                        <input type="file" class="form-control" id="designFile" name="file">
                        <p class="help-block"><?php echo _l('design_file_help_text'); ?></p>
                    </div>

                    <input type="hidden" id="fileId" name="file_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-paper-plane"></i> <?php echo _l('submit_design'); ?>
                    </button>
                </div>
            </form>
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

                <!-- Activity Log -->
                <div class="panel panel-info" id="activityPanel">
                    <div class="panel-heading">
                        <h4 class="panel-title"><?php echo _l('activity_log'); ?></h4>
                    </div>
                    <div class="panel-body">
                        <div id="activityLog"></div>
                    </div>
                </div>

                <!-- Client Comments -->
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <h4 class="panel-title"><?php echo _l('client_comments'); ?></h4>
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
var staffId = <?php echo get_staff_user_id(); ?>;

$(function() {
    loadDesigns();

    // Submit design form handler
    $('#submitDesignForm').on('submit', function(e) {
        e.preventDefault();
        submitDesign();
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
            'Authorization': 'Bearer staff_' + staffId
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

        // Client response column
        var responseCell = $('<td>');
        var commentCount = design.comments ? design.comments.length : 0;
        var clientComments = design.comments ? design.comments.filter(c => c.user_type === 'client').length : 0;

        if (clientComments > 0) {
            responseCell.html('<span class="badge badge-info">' + clientComments + ' comentario(s)</span>');
        } else if (design.status === 'pending') {
            responseCell.html('<span class="text-muted">Esperando respuesta</span>');
        } else {
            responseCell.html('<span class="text-muted">-</span>');
        }
        row.append(responseCell);

        // Actions
        var actionsCell = $('<td>');
        var viewBtn = $('<button>')
            .addClass('btn btn-sm btn-info')
            .html('<i class="fa fa-eye"></i>')
            .attr('title', 'Ver detalles')
            .on('click', function() {
                showDesignDetail(design);
            });
        actionsCell.append(viewBtn);
        row.append(actionsCell);

        tbody.append(row);
    });

    // Initialize DataTable if not already initialized
    if (!$.fn.DataTable.isDataTable('.dt-table')) {
        $('.dt-table').DataTable({
            order: [[3, 'desc']], // Order by date descending
            pageLength: 25
        });
    }
}

function getStatusBadge(status) {
    var badges = {
        'pending': '<span class="label label-warning">Pendiente</span>',
        'approved': '<span class="label label-success">Aprobado</span>',
        'rejected': '<span class="label label-danger">Rechazado</span>'
    };
    return badges[status] || status;
}

function submitDesign() {
    var formData = {
        title: $('#designTitle').val(),
        description: $('#designDescription').val(),
        file_id: $('#fileId').val() || null
    };

    var submitBtn = $('#submitDesignForm button[type="submit"]');
    submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enviando...');

    $.ajax({
        url: site_url + 'api/projects/' + projectId + '/designs',
        type: 'POST',
        headers: {
            'Authorization': 'Bearer staff_' + staffId,
            'Content-Type': 'application/json'
        },
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.success) {
                alert_float('success', 'Diseño enviado exitosamente');
                $('#submitDesignModal').modal('hide');
                $('#submitDesignForm')[0].reset();
                loadDesigns();
            }
        },
        error: function(xhr, status, error) {
            alert_float('danger', 'Error al enviar diseño: ' + (xhr.responseJSON?.error || error));
        },
        complete: function() {
            submitBtn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Enviar Diseño');
        }
    });
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

    // Load comments
    renderComments(design.comments || []);

    // Show activity log
    renderActivityLog(design);

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
            .addClass(comment.user_type === 'staff' ? 'bg-info' : 'bg-success');

        var header = $('<div>').css('margin-bottom', '5px');
        header.append($('<strong>').text(comment.user_name + ' '));
        header.append($('<span>')
            .addClass('label')
            .addClass(comment.user_type === 'staff' ? 'label-primary' : 'label-success')
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

function renderActivityLog(design) {
    var logHtml = '<ul class="list-unstyled">';

    logHtml += '<li><i class="fa fa-paper-plane text-info"></i> <strong>Diseño enviado</strong> - ' + new Date(design.created_at).toLocaleString() + '</li>';

    if (design.status === 'approved') {
        logHtml += '<li><i class="fa fa-check text-success"></i> <strong>Diseño aprobado</strong> por ' + design.approved_by_name + ' - ' + new Date(design.approved_at).toLocaleString() + '</li>';
    } else if (design.status === 'rejected') {
        logHtml += '<li><i class="fa fa-times text-danger"></i> <strong>Diseño rechazado</strong> - ' + (design.updated_at ? new Date(design.updated_at).toLocaleString() : '') + '</li>';
    }

    logHtml += '</ul>';
    $('#activityLog').html(logHtml);
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
            'Authorization': 'Bearer staff_' + staffId,
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({ comment: comment }),
        success: function(response) {
            if (response.success) {
                $('#newComment').val('');
                alert_float('success', 'Comentario agregado exitosamente');
                loadDesigns();
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
</script>

<style>
.bg-info {
    background-color: #d9edf7 !important;
}

.bg-success {
    background-color: #dff0d8 !important;
}

.badge-info {
    background-color: #5bc0de;
    color: white;
    padding: 3px 7px;
    border-radius: 10px;
}
</style>
