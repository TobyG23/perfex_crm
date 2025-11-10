<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration: Project Design Approvals System
 * Adds tables for design approvals, client comments, and approval tracking
 */
class Migration_Project_design_approvals extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Table: project_designs
        // Stores design submissions from staff to clients
        if (!$CI->db->table_exists(db_prefix() . 'project_designs')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'project_designs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `project_id` int(11) NOT NULL,
                `title` varchar(255) NOT NULL,
                `description` text NULL,
                `uploaded_by` int(11) NOT NULL COMMENT "Staff ID who uploaded",
                `file_id` int(11) NULL COMMENT "Reference to tblfiles",
                `status` enum("pending","approved","rejected") DEFAULT "pending",
                `approved_by` int(11) NULL COMMENT "Contact ID who approved",
                `approved_at` datetime NULL,
                `approval_note` text NULL COMMENT "Disclaimer shown to client",
                `created_at` datetime NOT NULL,
                `updated_at` datetime NULL,
                PRIMARY KEY (`id`),
                KEY `project_id` (`project_id`),
                KEY `uploaded_by` (`uploaded_by`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
        }

        // Table: project_design_comments
        // Stores client and staff comments on designs
        if (!$CI->db->table_exists(db_prefix() . 'project_design_comments')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'project_design_comments` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `design_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `user_type` enum("staff","client") NOT NULL,
                `comment` text NOT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `design_id` (`design_id`),
                KEY `user_type` (`user_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
        }

        // Table: project_approval_logs
        // Tracks all approval actions for audit purposes
        if (!$CI->db->table_exists(db_prefix() . 'project_approval_logs')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'project_approval_logs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `design_id` int(11) NOT NULL,
                `project_id` int(11) NOT NULL,
                `action` enum("submitted","approved","rejected","commented") NOT NULL,
                `user_id` int(11) NOT NULL,
                `user_type` enum("staff","client") NOT NULL,
                `ip_address` varchar(45) NULL,
                `user_agent` varchar(255) NULL,
                `details` text NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `design_id` (`design_id`),
                KEY `project_id` (`project_id`),
                KEY `action` (`action`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
        }

        // Add new column to projects table for design approval workflow
        if (!$CI->db->field_exists('requires_design_approval', db_prefix() . 'projects')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'projects`
                ADD COLUMN `requires_design_approval` tinyint(1) DEFAULT 0 COMMENT "Enable design approval workflow for this project"');
        }

        if (!$CI->db->field_exists('approval_status_id', db_prefix() . 'projects')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'projects`
                ADD COLUMN `approval_status_id` int(11) NULL COMMENT "Project status ID to set when design is approved"');
        }
    }

    public function down()
    {
        $CI = &get_instance();

        // Drop tables in reverse order
        if ($CI->db->table_exists(db_prefix() . 'project_approval_logs')) {
            $CI->db->query('DROP TABLE `' . db_prefix() . 'project_approval_logs`');
        }

        if ($CI->db->table_exists(db_prefix() . 'project_design_comments')) {
            $CI->db->query('DROP TABLE `' . db_prefix() . 'project_design_comments`');
        }

        if ($CI->db->table_exists(db_prefix() . 'project_designs')) {
            $CI->db->query('DROP TABLE `' . db_prefix() . 'project_designs`');
        }

        // Remove columns from projects table
        if ($CI->db->field_exists('requires_design_approval', db_prefix() . 'projects')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'projects` DROP COLUMN `requires_design_approval`');
        }

        if ($CI->db->field_exists('approval_status_id', db_prefix() . 'projects')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'projects` DROP COLUMN `approval_status_id`');
        }
    }
}
