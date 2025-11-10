<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Custom Hooks for Perfex CRM
 * This file is loaded automatically by the system
 */

// Include Design Approvals Hooks
if (file_exists(APPPATH . 'hooks/design_approvals_hooks.php')) {
    include_once APPPATH . 'hooks/design_approvals_hooks.php';
}

// Include Client Notifications Hooks
if (file_exists(APPPATH . 'hooks/client_notifications_hooks.php')) {
    include_once APPPATH . 'hooks/client_notifications_hooks.php';
}
