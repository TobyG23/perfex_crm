<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Design Approvals Hooks
 * Adds the design approvals tab to project views
 */

// Hook to add design approvals tab AFTER project tabs are initialized
hooks()->add_action('after_init_project_tabs', 'add_design_approvals_tab_to_projects');

function add_design_approvals_tab_to_projects()
{
    $CI = &get_instance();

    // Get project ID from URL
    $project_id = null;

    // Try to get from URI segment (admin/projects/view/ID)
    if ($CI->uri->segment(2) == 'projects' && $CI->uri->segment(3) == 'view' && $CI->uri->segment(4)) {
        $project_id = $CI->uri->segment(4);
    }

    // If we have a project ID, check if it has design approval enabled
    if ($project_id) {
        $CI->db->where('id', $project_id);
        $project = $CI->db->get(db_prefix() . 'projects')->row();

        if ($project && isset($project->requires_design_approval) && $project->requires_design_approval == 1) {
            // Add design approvals tab using app_tabs
            $CI->app_tabs->add_project_tab('project_designs', [
                'name'     => 'Diseños',
                'icon'     => 'fa fa-file-image-o',
                'view'     => 'admin/projects/project_designs_staff',
                'position' => 90,
            ]);
        }
    }
}
