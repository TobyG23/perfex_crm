<?php
/**
 * CORS Preflight Handler
 * Handles OPTIONS requests for CORS preflight
 */

// Send CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Credentials: true');

// Return 200 for OPTIONS
http_response_code(200);
exit();
