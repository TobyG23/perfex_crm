<?php



defined('BASEPATH') or exit('No direct script access allowed');



/*

| -------------------------------------------------------------------------

| URI ROUTING

| -------------------------------------------------------------------------

| This file lets you re-map URI requests to specific controller functions.

|

| Typically there is a one-to-one relationship between a URL string

| and its corresponding controller class/method. The segments in a

| URL normally follow this pattern:

|

|   example.com/class/method/id/

|

| In some instances, however, you may want to remap this relationship

| so that a different class/function is called than the one

| corresponding to the URL.

|

| Please see the user guide for complete details:

|

|   http://codeigniter.com/user_guide/general/routing.html

|

| -------------------------------------------------------------------------

| RESERVED ROUTES

| -------------------------------------------------------------------------

|

| There are three reserved routes:

|

|   $route['default_controller'] = 'welcome';

|

| This route indicates which controller class should be loaded if the

| URI contains no data. In the above example, the "welcome" class

| would be loaded.

|

|   $route['404_override'] = 'errors/page_missing';

|

| This route will tell the Router which controller/method to use if those

| provided in the URL cannot be matched to a valid route.

|

|   $route['translate_uri_dashes'] = FALSE;

|

| This is not exactly a route, but allows you to automatically route

| controller and method names that contain dashes. '-' isn't a valid

| class or method name character, so it requires translation.

| When you set this option to TRUE, it will replace ALL dashes in the

| controller and method URI segments.

|

| Examples: my-controller/index -> my_controller/index

|       my-controller/my-method -> my_controller/my_method

*/



$route['default_controller']   = 'clients';

$route['404_override']         = '';

$route['translate_uri_dashes'] = false;



/**

 * Dashboard clean route

 */

$route['admin'] = 'admin/dashboard';

$config['csrf_exclude_uris'] = [
    'forms/wtl/[0-9a-z]+', 
    'forms/ticket', 
    'forms/quote/[0-9a-z]+', 
    'admin/tasks/timer_tracking', 
    'api\/.+',  // ← Esta línea debe estar
    'razorpay/success\/.+'
];

/**

 * Misc controller routes

 */

$route['admin/access_denied'] = 'admin/misc/access_denied';

$route['admin/not_found']     = 'admin/misc/not_found';



/**

 * Staff Routes

 */

$route['admin/profile']           = 'admin/staff/profile';

$route['admin/profile/(:num)']    = 'admin/staff/profile/$1';

$route['admin/tasks/view/(:any)'] = 'admin/tasks/index/$1';



/**

 * Items search rewrite

 */

$route['admin/items/search'] = 'admin/invoice_items/search';



/**

 * In case if client access directly to url without the arguments redirect to clients url

 */

$route['/'] = 'clients';



/**

 * @deprecated

 */

$route['viewinvoice/(:num)/(:any)'] = 'invoice/index/$1/$2';



/**

 * @since 2.0.0

 */

$route['invoice/(:num)/(:any)'] = 'invoice/index/$1/$2';



/**

 * @deprecated

 */

$route['viewestimate/(:num)/(:any)'] = 'estimate/index/$1/$2';



/**

 * @since 2.0.0

 */

$route['estimate/(:num)/(:any)'] = 'estimate/index/$1/$2';

$route['subscription/(:any)']    = 'subscription/index/$1';



/**

 * @deprecated

 */

$route['viewproposal/(:num)/(:any)'] = 'proposal/index/$1/$2';



/**

 * @since 2.0.0

 */

$route['proposal/(:num)/(:any)'] = 'proposal/index/$1/$2';



/**

 * @since 2.0.0

 */

$route['contract/(:num)/(:any)'] = 'contract/index/$1/$2';



/**

 * @since 2.0.0

 */

$route['knowledge-base']                 = 'knowledge_base/index';

$route['knowledge-base/search']          = 'knowledge_base/search';

$route['knowledge-base/article']         = 'knowledge_base/index';

$route['knowledge-base/article/(:any)']  = 'knowledge_base/article/$1';

$route['knowledge-base/category']        = 'knowledge_base/index';

$route['knowledge-base/category/(:any)'] = 'knowledge_base/category/$1';



/**

 * @deprecated 2.2.0

 */

if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'add_kb_answer') === false) {

    $route['knowledge-base/(:any)']         = 'knowledge_base/article/$1';

    $route['knowledge_base/(:any)']         = 'knowledge_base/article/$1';

    $route['clients/knowledge_base/(:any)'] = 'knowledge_base/article/$1';

    $route['clients/knowledge-base/(:any)'] = 'knowledge_base/article/$1';

}



/**

 * @deprecated 2.2.0

 * Fallback for auth clients area, changed in version 2.2.0

 */

$route['clients/reset_password']  = 'authentication/reset_password';

$route['clients/forgot_password'] = 'authentication/forgot_password';

$route['clients/logout']          = 'authentication/logout';

$route['clients/register']        = 'authentication/register';

$route['clients/login']           = 'authentication/login';



// Aliases for short routes

$route['reset_password']  = 'authentication/reset_password';

$route['forgot_password'] = 'authentication/forgot_password';

$route['login']           = 'authentication/login';

$route['logout']          = 'authentication/logout';

$route['register']        = 'authentication/register';



/**

 * Terms and conditions and Privacy Policy routes

 */

$route['terms-and-conditions'] = 'terms_and_conditions';

$route['privacy-policy']       = 'privacy_policy';



/**

 * @since 2.3.0

 * Routes for admin/modules URL because Modules.php class is used in application/third_party/MX

 */

$route['admin/modules']               = 'admin/mods';

$route['admin/modules/(:any)']        = 'admin/mods/$1';

$route['admin/modules/(:any)/(:any)'] = 'admin/mods/$1/$2';



// Public single ticket route

$route['forms/tickets/(:any)'] = 'forms/public_ticket/$1';



/**

 * @since  2.3.0

 * Route for clients set password URL, because it's using the same controller for staff to

 * If user addded block /admin by .htaccess this won't work, so we need to rewrite the URL

 * In future if there is implementation for clients set password, this route should be removed

 */

$route['authentication/set_password/(:num)/(:num)/(:any)'] = 'admin/authentication/set_password/$1/$2/$3';


// ===== API ROUTES FOR MOBILE APP & WEB =====
// Authentication
$route['api/login'] = 'api/api/login';

// Dashboard
$route['api/dashboard'] = 'api/api/dashboard';

// Clients
$route['api/clients'] = 'api/api/clients';
$route['api/clients/(:num)'] = 'api/api/client/$1';

// Invoices
$route['api/invoices'] = 'api/api/invoices';
$route['api/invoices/(:num)'] = 'api/api/invoice/$1';

// Debug (temporary)
$route['api/debug/tables'] = 'api/api/debug_tables';
$route['api/debug/make-file-visible/(:num)'] = 'api/api/debug_make_file_visible/$1';

// Projects
$route['api/projects'] = 'api/api/projects';
$route['api/projects/(:num)'] = 'api/api/project/$1';
$route['api/project/(:num)'] = 'api/api/project/$1'; // Singular form
$route['api/projects/(:num)/designs'] = 'api/api/project_designs/$1'; // Project designs
$route['api/projects/(:num)/files'] = 'api/api/project_files/$1'; // Project files
$route['api/project-files/(:num)/approve'] = 'api/api/approve_project_file/$1'; // Approve file
$route['api/project-files/(:num)/reject'] = 'api/api/reject_project_file/$1'; // Reject file
$route['api/projects/(:num)/discussions'] = 'api/api/project_discussions/$1'; // Project discussions (GET and POST)
$route['api/discussions/(:num)'] = 'api/api/discussion/$1'; // Single discussion
$route['api/discussions/(:num)/comments'] = 'api/api/add_discussion_comment/$1'; // Add comment

// Tasks
$route['api/tasks'] = 'api/api/tasks';
$route['api/tasks/(:num)'] = 'api/api/task/$1';

// Proposals
$route['api/proposals'] = 'api/api/proposals';
$route['api/proposals/(:num)'] = 'api/api/proposal/$1';

// Files/Designs
$route['api/files'] = 'api/api/files';

// Tickets
$route['api/tickets'] = 'api/api/tickets';
$route['api/tickets/(:num)'] = 'api/api/ticket/$1';
$route['api/tickets/(:num)/reply'] = 'api/api/ticket_reply/$1';

// Leads (Staff only)
$route['api/leads'] = 'api/api/leads';
$route['api/leads/(:num)'] = 'api/api/lead/$1';

// Estimates (Staff only)
$route['api/estimates'] = 'api/api/estimates';
$route['api/estimates/(:num)'] = 'api/api/estimate/$1';

// Expenses (Staff only)
$route['api/expenses'] = 'api/api/expenses';

// Contracts (Staff only)
$route['api/contracts'] = 'api/api/contracts';
$route['api/contracts/(:num)'] = 'api/api/contract/$1';

// Notifications
$route['api/notifications'] = 'api/api/notifications';
$route['api/notifications/(:num)/read'] = 'api/api/mark_notification_read/$1';
$route['api/notifications/read-all'] = 'api/api/mark_all_notifications_read';
$route['api/notifications/mark-all-read'] = 'api/api/mark_all_notifications_read';

// Device tokens (for push notifications)
$route['api/device-token'] = 'api/api/device_token';



// For backward compatilibilty

$route['survey/(:num)/(:any)'] = 'surveys/participate/index/$1/$2';
$route['api/login'] = 'api/api/login';
$route['api/dashboard'] = 'api/api/dashboard';
$route['api/clients'] = 'api/api/clients';
$route['api/clients/(:num)'] = 'api/api/client/$1';
$route['api/tickets'] = 'api/api/tickets';
$route['api/tickets/(:num)'] = 'api/api/ticket/$1';
$route['api/tickets/(:num)/reply'] = 'api/api/ticket_reply/$1';
$route['api/projects'] = 'api/api/projects';
$route['api/projects/(:num)'] = 'api/api/project/$1';


if (file_exists(APPPATH . 'config/my_routes.php')) {

    include_once(APPPATH . 'config/my_routes.php');

}


