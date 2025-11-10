<?php
/**
 * Direct test of projects endpoint
 * Access: http://perfex-local.test/test_projects_direct.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate the API request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/perfex_crm/api/projects';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer client_7';

// Set up CodeIgniter environment
define('BASEPATH', TRUE);
define('ENVIRONMENT', 'development');

echo "<pre>";
echo "=== Testing Projects Endpoint ===\n\n";

// Load CodeIgniter
require_once('index.php');

echo "\n=== Test Complete ===";
echo "</pre>";
?>
