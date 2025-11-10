<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API Controller for Mobile App
 * Place this file in: application/controllers/api/Api.php
 *
 * Note: CORS headers are handled in index.php before CodeIgniter loads
 */
class Api extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Skip authentication for login, test and debug endpoints
        $request_uri = $_SERVER['REQUEST_URI'];

        // Set JSON header for non-debug endpoints
        if (strpos($request_uri, '/api/debug') === false) {
            header('Content-Type: application/json');
        }

        if (strpos($request_uri, '/api/login') === false && strpos($request_uri, '/api/test') === false && strpos($request_uri, '/api/debug') === false) {
            $this->authenticate();
        }
    }
    
    /**
     * Authenticate API requests
     */
    private function authenticate()
    {
        // Try multiple methods to get the Authorization header
        $token = null;

        // Method 1: CodeIgniter's request_headers()
        $headers = $this->input->request_headers();
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        }

        // Method 2: apache_request_headers()
        if (!$token && function_exists('apache_request_headers')) {
            $apache_headers = apache_request_headers();
            if (isset($apache_headers['Authorization'])) {
                $token = str_replace('Bearer ', '', $apache_headers['Authorization']);
            }
        }

        // Method 3: $_SERVER variables
        if (!$token) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
            } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $token = str_replace('Bearer ', '', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
            }
        }

        if (!$token) {
            $this->output_error('Unauthorized - No token provided', 401);
        }

        // Parse token format: "staff_1" or "client_123"
        $token_parts = explode('_', $token);

        if (count($token_parts) !== 2) {
            $this->output_error('Invalid token format', 401);
        }

        $user_type = $token_parts[0];
        $user_id = $token_parts[1];

        if ($user_type === 'staff') {
            // Verify staff token
            $this->db->where('staffid', $user_id);
            $this->db->where('active', 1);
            $staff = $this->db->get(db_prefix() . 'staff')->row();

            if (!$staff) {
                $this->output_error('Invalid token', 401);
            }

            // Set staff user for later use
            $this->staff_id = $staff->staffid;
            $this->staff_data = $staff;
            $this->user_type = 'staff';
        } elseif ($user_type === 'client') {
            // Verify client token
            $this->db->where('id', $user_id);
            $this->db->where('active', 1);
            $contact = $this->db->get(db_prefix() . 'contacts')->row();

            if (!$contact) {
                $this->output_error('Invalid token', 401);
            }

            // Set contact user for later use
            $this->contact_id = $contact->id;
            $this->contact_data = $contact;
            $this->user_type = 'client';
        } else {
            $this->output_error('Invalid user type', 401);
        }
    }
    
    /**
     * Output JSON response
     */
    private function output_json($data, $status = 200)
    {
        http_response_code($status);
        echo json_encode($data);
        exit();
    }
    
    /**
     * Output error response
     */
    private function output_error($message, $status = 400)
    {
        $this->output_json([
            'success' => false,
            'error' => $message
        ], $status);
    }

    /**
     * TEST ENDPOINT
     * GET /api/test
     */
    public function test()
    {
        $this->output_json([
            'success' => true,
            'message' => 'API is working'
        ]);
    }

    /**
     * LOGIN
     * POST /api/login
     */
    public function login()
    {
        // Get JSON input
        $json_input = file_get_contents('php://input');
        $data = json_decode($json_input, true);

        $email = isset($data['email']) ? $data['email'] : $this->input->post('email');
        $password = isset($data['password']) ? $data['password'] : $this->input->post('password');
        $login_as = isset($data['login_as']) ? $data['login_as'] : $this->input->post('login_as');

        if (!$email || !$password) {
            $this->output_error('Email and password are required');
        }

        // If login_as is not specified, default to 'staff'
        if (!$login_as) {
            $login_as = 'staff';
        }

        // Validate login_as parameter
        if (!in_array($login_as, ['staff', 'client'])) {
            $this->output_error('Invalid login_as parameter. Must be "staff" or "client"');
        }

        $user_type = null;
        $user = null;

        // Try to authenticate based on login_as preference
        if ($login_as === 'staff') {
            // Try staff first
            $this->db->where('email', $email);
            $staff = $this->db->get(db_prefix() . 'staff')->row();

            if ($staff) {
                // Found a staff member
                $user_type = 'staff';

                // Verify password
                $password_valid = false;

                // Method 1: password_verify (works with $2y$ and $2a$ bcrypt)
                if (password_verify($password, $staff->password)) {
                    $password_valid = true;
                }

                // Method 2: app_hasher (Perfex hasher - for special formats)
                if (!$password_valid && function_exists('app_hasher')) {
                    try {
                        if (app_hasher()->CheckPassword($password, $staff->password)) {
                            $password_valid = true;
                        }
                    } catch (Exception $e) {
                        // Continue
                    }
                }

                // Method 3: MD5 (very old Perfex)
                if (!$password_valid && md5($password) === $staff->password) {
                    $password_valid = true;
                }

                if (!$password_valid) {
                    $this->output_error('Invalid credentials', 401);
                }

                // Check if active
                if ($staff->active != 1) {
                    $this->output_error('Account is inactive', 401);
                }

                $user = [
                    'id' => $staff->staffid,
                    'firstname' => $staff->firstname,
                    'lastname' => $staff->lastname,
                    'email' => $staff->email,
                    'role' => isset($staff->role) ? $staff->role : '',
                    'is_admin' => $staff->admin == 1
                ];

                $token = 'staff_' . $staff->staffid;
            } else {
                // Staff account not found
                $this->output_error('Invalid credentials', 401);
            }
        } else {
            // Login as client
            $this->db->where('email', $email);
            $contact = $this->db->get(db_prefix() . 'contacts')->row();

            if (!$contact) {
                $this->output_error('Invalid credentials', 401);
            }

            $user_type = 'client';

            // Verify password
            $password_valid = false;

            // Method 1: password_verify (works with $2y$ and $2a$ bcrypt)
            if (password_verify($password, $contact->password)) {
                $password_valid = true;
            }

            // Method 2: app_hasher (Perfex hasher)
            if (!$password_valid && function_exists('app_hasher')) {
                try {
                    if (app_hasher()->CheckPassword($password, $contact->password)) {
                        $password_valid = true;
                    }
                } catch (Exception $e) {
                    // Continue
                }
            }

            // Method 3: MD5 (very old)
            if (!$password_valid && md5($password) === $contact->password) {
                $password_valid = true;
            }

            if (!$password_valid) {
                $this->output_error('Invalid credentials', 401);
            }

            // Check if active
            if ($contact->active != 1) {
                $this->output_error('Account is inactive', 401);
            }

            // Get client info
            $this->db->where('userid', $contact->userid);
            $client = $this->db->get(db_prefix() . 'clients')->row();

            $user = [
                'id' => $contact->id,
                'firstname' => $contact->firstname,
                'lastname' => $contact->lastname,
                'email' => $contact->email,
                'company' => $client ? $client->company : '',
                'contact_id' => $contact->id
            ];

            $token = 'client_' . $contact->id;
        }

        $this->output_json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $user,
                'user_type' => $user_type
            ]
        ]);
    }
    
    /**
     * DASHBOARD STATS
     * GET /api/dashboard
     */
    public function dashboard()
    {
        try {
            // Basic stats
            $data = [
                'total_clients' => $this->db->count_all_results(db_prefix() . 'clients'),
                'total_projects' => $this->db->count_all_results(db_prefix() . 'projects'),
                'total_invoices' => $this->db->count_all_results(db_prefix() . 'invoices'),
                'open_tickets' => $this->db->where('status', 1)->count_all_results(db_prefix() . 'tickets')
            ];
            
            // Try to get recent projects
            try {
                $this->load->model('projects_model');
                $data['recent_projects'] = $this->projects_model->get('', [
                    'limit' => 5
                ]);
            } catch (Exception $e) {
                $data['recent_projects'] = [];
            }
            
            // Try to get recent tickets
            try {
                $this->load->model('tickets_model');
                $data['recent_tickets'] = $this->db->order_by('ticketid', 'DESC')->limit(5)->get(db_prefix() . 'tickets')->result();
            } catch (Exception $e) {
                $data['recent_tickets'] = [];
            }
            
            $this->output_json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading dashboard: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET CLIENTS
     * GET /api/clients
     */
    public function clients()
    {
        try {
            $search = $this->input->get('search');
            $limit = $this->input->get('limit') ?: 20;
            $page = $this->input->get('page') ?: 1;
            $offset = ($page - 1) * $limit;
            
            $this->db->select('*');
            $this->db->from(db_prefix() . 'clients');
            
            if ($search) {
                $this->db->like('company', $search);
            }
            
            $this->db->limit($limit, $offset);
            $clients = $this->db->get()->result();
            
            // Count total
            $this->db->from(db_prefix() . 'clients');
            if ($search) {
                $this->db->like('company', $search);
            }
            $total = $this->db->count_all_results();
            
            $this->output_json([
                'success' => true,
                'data' => $clients,
                'pagination' => [
                    'total' => $total,
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading clients: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET SINGLE CLIENT
     * GET /api/clients/:id
     */
    public function client($id)
    {
        $this->load->model('clients_model');
        
        $client = $this->clients_model->get($id);
        
        if (!$client) {
            $this->output_error('Client not found', 404);
        }
        
        // Get additional data
        $client->contacts = $this->clients_model->get_contacts($id);
        $client->projects = $this->db->where('clientid', $id)->get(db_prefix() . 'projects')->result();
        $client->invoices = $this->db->where('clientid', $id)->get(db_prefix() . 'invoices')->result();
        
        $this->output_json([
            'success' => true,
            'data' => $client
        ]);
    }
    
    /**
     * GET TICKETS
     * GET /api/tickets
     */
    public function tickets()
    {
        $this->load->model('tickets_model');
        
        $status = $this->input->get('status');
        $limit = $this->input->get('limit') ?: 20;
        $page = $this->input->get('page') ?: 1;
        $offset = ($page - 1) * $limit;
        
        $where = [];
        if ($status !== null) {
            $where['status'] = $status;
        }
        
        $tickets = $this->tickets_model->get('', [
            'where' => $where,
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        $total = $this->db->where($where)->count_all_results(db_prefix() . 'tickets');
        
        $this->output_json([
            'success' => true,
            'data' => $tickets,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * GET SINGLE TICKET
     * GET /api/tickets/:id
     */
    public function ticket($id)
    {
        $this->load->model('tickets_model');
        
        $ticket = $this->tickets_model->get_ticket_by_id($id);
        
        if (!$ticket) {
            $this->output_error('Ticket not found', 404);
        }
        
        // Get ticket replies
        $ticket->replies = $this->tickets_model->get_ticket_replies($id);
        
        $this->output_json([
            'success' => true,
            'data' => $ticket
        ]);
    }
    
    /**
     * CREATE TICKET REPLY
     * POST /api/tickets/:id/reply
     */
    public function ticket_reply($id)
    {
        $this->load->model('tickets_model');
        
        $json_input = file_get_contents('php://input');
        $data = json_decode($json_input, true);
        $message = isset($data['message']) ? $data['message'] : $this->input->post('message');
        
        if (!$message) {
            $this->output_error('Message is required');
        }
        
        $reply_data = [
            'ticketid' => $id,
            'userid' => $this->staff_id,
            'message' => $message,
            'date' => date('Y-m-d H:i:s')
        ];
        
        $reply_id = $this->db->insert(db_prefix() . 'ticket_replies', $reply_data);
        
        if ($reply_id) {
            $this->output_json([
                'success' => true,
                'data' => [
                    'id' => $reply_id,
                    'message' => 'Reply added successfully'
                ]
            ]);
        } else {
            $this->output_error('Failed to add reply', 500);
        }
    }
    
    /**
     * GET INVOICES
     * GET /api/invoices
     */
    public function invoices()
    {
        try {
            $status = $this->input->get('status');
            $limit = $this->input->get('limit') ?: 20;
            $page = $this->input->get('page') ?: 1;
            $offset = ($page - 1) * $limit;
            
            $this->db->select('*');
            $this->db->from(db_prefix() . 'invoices');
            
            if ($status !== null && $status !== '') {
                $this->db->where('status', $status);
            }
            
            $this->db->order_by('id', 'DESC');
            $this->db->limit($limit, $offset);
            $invoices = $this->db->get()->result();
            
            // Count total
            $this->db->from(db_prefix() . 'invoices');
            if ($status !== null && $status !== '') {
                $this->db->where('status', $status);
            }
            $total = $this->db->count_all_results();
            
            // Enrich invoice data
            foreach ($invoices as $invoice) {
                // Get client name
                $client = $this->db->where('userid', $invoice->clientid)->get(db_prefix() . 'clients')->row();
                $invoice->client_name = $client ? $client->company : 'N/A';
                
                // Get items
                $invoice->items = $this->db->where('rel_id', $invoice->id)->where('rel_type', 'invoice')->get(db_prefix() . 'itemable')->result();
            }
            
            $this->output_json([
                'success' => true,
                'data' => $invoices,
                'pagination' => [
                    'total' => $total,
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading invoices: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET SINGLE INVOICE
     * GET /api/invoices/:id
     */
    public function invoice($id)
    {
        try {
            $invoice = $this->db->where('id', $id)->get(db_prefix() . 'invoices')->row();
            
            if (!$invoice) {
                $this->output_error('Invoice not found', 404);
            }
            
            // Get client
            $invoice->client = $this->db->where('userid', $invoice->clientid)->get(db_prefix() . 'clients')->row();
            
            // Get items
            $invoice->items = $this->db->where('rel_id', $id)->where('rel_type', 'invoice')->get(db_prefix() . 'itemable')->result();
            
            // Get payments
            $invoice->payments = $this->db->where('invoiceid', $id)->get(db_prefix() . 'invoicepaymentrecords')->result();
            
            $this->output_json([
                'success' => true,
                'data' => $invoice
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading invoice: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET TASKS
     * GET /api/tasks
     */
    public function tasks()
    {
        try {
            $status = $this->input->get('status');
            $limit = $this->input->get('limit') ?: 20;
            $page = $this->input->get('page') ?: 1;
            $offset = ($page - 1) * $limit;
            
            $this->db->select('*');
            $this->db->from(db_prefix() . 'tasks');
            
            if ($status !== null && $status !== '') {
                $this->db->where('status', $status);
            }
            
            $this->db->order_by('id', 'DESC');
            $this->db->limit($limit, $offset);
            $tasks = $this->db->get()->result();
            
            // Count total
            $this->db->from(db_prefix() . 'tasks');
            if ($status !== null && $status !== '') {
                $this->db->where('status', $status);
            }
            $total = $this->db->count_all_results();
            
            // Enrich task data
            foreach ($tasks as $task) {
                // Get assignees
                $task->assignees = $this->db->where('taskid', $task->id)->get(db_prefix() . 'task_assigned')->result();
            }
            
            $this->output_json([
                'success' => true,
                'data' => $tasks,
                'pagination' => [
                    'total' => $total,
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading tasks: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET SINGLE TASK
     * GET /api/tasks/:id
     */
    public function task($id)
    {
        try {
            $task = $this->db->where('id', $id)->get(db_prefix() . 'tasks')->row();
            
            if (!$task) {
                $this->output_error('Task not found', 404);
            }
            
            // Get assignees with staff names
            $this->db->select(db_prefix() . 'task_assigned.*, CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as assigned_name, ' . db_prefix() . 'task_assigned.assigned_from as assigned_from_date');
            $this->db->from(db_prefix() . 'task_assigned');
            $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'task_assigned.staffid', 'left');
            $this->db->where(db_prefix() . 'task_assigned.taskid', $id);
            $task->assignees = $this->db->get()->result();

            // Get comments with staff names
            $this->db->select(db_prefix() . 'task_comments.*, CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as staff_full_name');
            $this->db->from(db_prefix() . 'task_comments');
            $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'task_comments.staffid', 'left');
            $this->db->where(db_prefix() . 'task_comments.taskid', $id);
            $this->db->order_by('dateadded', 'DESC');
            $task->comments = $this->db->get()->result();

            // Get attachments
            $task->attachments = $this->db->where('relid', $id)->where('rel_type', 'task')->get(db_prefix() . 'files')->result();

            // Get checklist items
            $task->checklist_items = $this->db->where('taskid', $id)->order_by('list_order', 'ASC')->get(db_prefix() . 'task_checklist_items')->result();

            // Get timesheets with staff names
            $this->db->select(db_prefix() . 'taskstimers.*, CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as staff_name');
            $this->db->from(db_prefix() . 'taskstimers');
            $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'taskstimers.staff_id', 'left');
            $this->db->where(db_prefix() . 'taskstimers.task_id', $id);
            $this->db->order_by('start_time', 'DESC');
            $task->timesheets = $this->db->get()->result();

            // Get project name if related to project
            if ($task->rel_type == 'project' && $task->rel_id) {
                $project = $this->db->where('id', $task->rel_id)->get(db_prefix() . 'projects')->row();
                $task->rel_name = $project ? $project->name : '';
            } else {
                $task->rel_name = '';
            }

            $this->output_json([
                'success' => true,
                'data' => $task
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * UPDATE TASK
     * PUT /api/tasks/:id
     */
    public function update_task($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->output_error('Method not allowed', 405);
        }

        try {
            // Get JSON input for PUT request
            $json_input = file_get_contents('php://input');
            $data = json_decode($json_input, true);

            if (!$data) {
                $this->output_error('Invalid JSON data');
            }

            // Verify task exists
            $task = $this->db->where('id', $id)->get(db_prefix() . 'tasks')->row();
            if (!$task) {
                $this->output_error('Task not found', 404);
            }

            // Prepare update data
            $update_data = [];
            if (isset($data['name'])) $update_data['name'] = $data['name'];
            if (isset($data['status'])) $update_data['status'] = $data['status'];
            if (isset($data['priority'])) $update_data['priority'] = $data['priority'];
            if (isset($data['duedate'])) $update_data['duedate'] = $data['duedate'];
            if (isset($data['description'])) $update_data['description'] = $data['description'];

            // Update task
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'tasks', $update_data);

            $this->output_json([
                'success' => true,
                'message' => 'Task updated successfully'
            ]);
        } catch (Exception $e) {
            $this->output_error('Error updating task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ADD TASK COMMENT
     * POST /api/tasks/:id/comment
     */
    public function add_task_comment($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->output_error('Method not allowed', 405);
        }

        try {
            // Get JSON input
            $json_input = file_get_contents('php://input');
            $data = json_decode($json_input, true);

            if (!isset($data['comment']) || empty(trim($data['comment']))) {
                $this->output_error('Comment is required');
            }

            // Verify task exists
            $task = $this->db->where('id', $id)->get(db_prefix() . 'tasks')->row();
            if (!$task) {
                $this->output_error('Task not found', 404);
            }

            // Insert comment
            $comment_data = [
                'content' => $data['comment'],
                'taskid' => $id,
                'staffid' => $this->staff_id,
                'dateadded' => date('Y-m-d H:i:s')
            ];

            $this->db->insert(db_prefix() . 'task_comments', $comment_data);

            $this->output_json([
                'success' => true,
                'message' => 'Comment added successfully'
            ]);
        } catch (Exception $e) {
            $this->output_error('Error adding comment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * TOGGLE CHECKLIST ITEM
     * PUT /api/tasks/checklist/:id
     */
    public function toggle_checklist($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->output_error('Method not allowed', 405);
        }

        try {
            // Get JSON input
            $json_input = file_get_contents('php://input');
            $data = json_decode($json_input, true);

            if (!isset($data['finished'])) {
                $this->output_error('Finished status is required');
            }

            // Verify checklist item exists
            $item = $this->db->where('id', $id)->get(db_prefix() . 'task_checklist_items')->row();
            if (!$item) {
                $this->output_error('Checklist item not found', 404);
            }

            // Update checklist item
            $update_data = [
                'finished' => $data['finished'] ? 1 : 0,
            ];

            if ($data['finished']) {
                $update_data['finished_from'] = $this->staff_id;
            } else {
                $update_data['finished_from'] = 0;
            }

            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'task_checklist_items', $update_data);

            $this->output_json([
                'success' => true,
                'message' => 'Checklist item updated successfully'
            ]);
        } catch (Exception $e) {
            $this->output_error('Error updating checklist item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET PROPOSALS
     * GET /api/proposals
     */
    public function proposals()
    {
        try {
            $status = $this->input->get('status');
            $limit = $this->input->get('limit') ?: 20;
            $page = $this->input->get('page') ?: 1;
            $offset = ($page - 1) * $limit;
            
            $this->db->select('*');
            $this->db->from(db_prefix() . 'proposals');
            
            if ($status !== null && $status !== '') {
                $this->db->where('status', $status);
            }
            
            $this->db->order_by('id', 'DESC');
            $this->db->limit($limit, $offset);
            $proposals = $this->db->get()->result();
            
            // Count total
            $this->db->from(db_prefix() . 'proposals');
            if ($status !== null && $status !== '') {
                $this->db->where('status', $status);
            }
            $total = $this->db->count_all_results();
            
            // Enrich proposal data
            foreach ($proposals as $proposal) {
                // Get client/lead name
                if ($proposal->rel_type == 'customer') {
                    $client = $this->db->where('userid', $proposal->rel_id)->get(db_prefix() . 'clients')->row();
                    $proposal->client_name = $client ? $client->company : 'N/A';
                } else {
                    $proposal->client_name = 'Lead';
                }
            }
            
            $this->output_json([
                'success' => true,
                'data' => $proposals,
                'pagination' => [
                    'total' => $total,
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading proposals: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET SINGLE PROPOSAL
     * GET /api/proposals/:id
     */
    public function proposal($id)
    {
        try {
            $proposal = $this->db->where('id', $id)->get(db_prefix() . 'proposals')->row();
            
            if (!$proposal) {
                $this->output_error('Proposal not found', 404);
            }
            
            // Get client info
            if ($proposal->rel_type == 'customer') {
                $proposal->client = $this->db->where('userid', $proposal->rel_id)->get(db_prefix() . 'clients')->row();
            }
            
            // Get items
            $proposal->items = $this->db->where('proposal_id', $id)->get(db_prefix() . 'proposal_items')->result();
            
            // Get comments
            $proposal->comments = $this->db->where('rel_id', $id)->where('rel_type', 'proposal')->get(db_prefix() . 'proposal_comments')->result();
            
            $this->output_json([
                'success' => true,
                'data' => $proposal
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading proposal: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET FILES/DESIGNS
     * GET /api/files
     */
    public function files()
    {
        try {
            $rel_type = $this->input->get('type'); // project, client, task, etc
            $rel_id = $this->input->get('id');
            $limit = $this->input->get('limit') ?: 50;
            $page = $this->input->get('page') ?: 1;
            $offset = ($page - 1) * $limit;
            
            $this->db->select('*');
            $this->db->from(db_prefix() . 'files');
            
            if ($rel_type) {
                $this->db->where('rel_type', $rel_type);
            }
            
            if ($rel_id) {
                $this->db->where('relid', $rel_id);
            }
            
            $this->db->order_by('dateadded', 'DESC');
            $this->db->limit($limit, $offset);
            $files = $this->db->get()->result();
            
            // Count total
            $this->db->from(db_prefix() . 'files');
            if ($rel_type) {
                $this->db->where('rel_type', $rel_type);
            }
            if ($rel_id) {
                $this->db->where('relid', $rel_id);
            }
            $total = $this->db->count_all_results();
            
            // Add download URL
            foreach ($files as $file) {
                $file->download_url = base_url('download/file/' . $file->rel_type . '/' . $file->id);
            }
            
            $this->output_json([
                'success' => true,
                'data' => $files,
                'pagination' => [
                    'total' => $total,
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading files: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET PROJECTS
     * GET /api/projects
     */
    public function projects()
    {
        try {
            $this->load->model('projects_model');

            $status = $this->input->get('status');
            $limit = $this->input->get('limit') ?: 20;
            $page = $this->input->get('page') ?: 1;
            $offset = ($page - 1) * $limit;

            // Filter by client if the user is a client
            $where = [];
            if ($this->user_type === 'client') {
                // Get the userid from contact
                $where['clientid'] = $this->contact_data->userid;
            }

            if ($status !== null && $status !== '') {
                $where['status'] = $status;
            }

            // Get projects
            $this->db->select('*');
            $this->db->from(db_prefix() . 'projects');

            foreach ($where as $key => $value) {
                $this->db->where($key, $value);
            }

            $this->db->order_by('id', 'DESC');
            $this->db->limit($limit, $offset);
            $projects = $this->db->get()->result();

            // Count total
            $this->db->from(db_prefix() . 'projects');
            foreach ($where as $key => $value) {
                $this->db->where($key, $value);
            }
            $total = $this->db->count_all_results();

            // Enrich project data
            foreach ($projects as $project) {
                // Get client name
                $client = $this->db->where('userid', $project->clientid)->get(db_prefix() . 'clients')->row();
                $project->client_name = $client ? $client->company : 'N/A';
            }

            $this->output_json([
                'success' => true,
                'data' => $projects,
                'pagination' => [
                    'total' => $total,
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading projects: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET SINGLE PROJECT
     * GET /api/projects/:id
     */
    public function project($id)
    {
        try {
            $this->load->model('projects_model');

            $project = $this->projects_model->get($id);

            if (!$project) {
                $this->output_error('Project not found', 404);
            }

            // Check if client has access to this project
            if ($this->user_type === 'client') {
                if ($project->clientid != $this->contact_data->userid) {
                    $this->output_error('Unauthorized - You do not have access to this project', 403);
                }
            }

            // Get additional data
            $project->tasks = $this->db->where('rel_id', $id)->where('rel_type', 'project')->get(db_prefix() . 'tasks')->result();
            $project->members = $this->projects_model->get_project_members($id);

            // Get client info
            $client = $this->db->where('userid', $project->clientid)->get(db_prefix() . 'clients')->row();
            $project->client_name = $client ? $client->company : 'N/A';

            $this->output_json([
                'success' => true,
                'data' => $project
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading project: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET PROJECT FILES (using tblproject_files)
     * GET /api/projects/:id/files
     */
    public function project_files($project_id)
    {
        try {
            // Verify project exists
            $project = $this->db->where('id', $project_id)->get(db_prefix() . 'projects')->row();
            if (!$project) {
                $this->output_error('Project not found', 404);
            }

            // Check access permissions
            if ($this->user_type === 'client') {
                if ($project->clientid != $this->contact_data->userid) {
                    $this->output_error('Unauthorized - You do not have access to this project', 403);
                }
            }

            // Get files from tblproject_files
            $this->db->select('pf.*, s.firstname as staff_firstname, s.lastname as staff_lastname');
            $this->db->from(db_prefix() . 'project_files pf');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = pf.staffid', 'left');
            $this->db->where('pf.project_id', $project_id);

            // Clients only see files marked as visible
            if ($this->user_type === 'client') {
                $this->db->where('pf.visible_to_customer', 1);
            }

            $this->db->order_by('pf.dateadded', 'DESC');
            $files = $this->db->get()->result();

            // Add uploaded_by name and check for approvals
            foreach ($files as $file) {
                $file->uploaded_by_name = trim($file->staff_firstname . ' ' . $file->staff_lastname);

                // Build file URL
                // Perfex stores project files in uploads/projects/{project_id}/
                $base_url = rtrim(base_url(), '/');
                $file->file_url = $base_url . '/uploads/projects/' . $project_id . '/' . $file->file_name;

                // Check if file is an image
                $image_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $file->is_image = in_array(strtolower($file->filetype), $image_types);

                // Check if there's a discussion about this file
                $file->has_discussion = false;
                $file->approval_status = null; // pending, approved, rejected

                // Check for discussions that mention this file in subject
                $discussion = $this->db->where('project_id', $project_id)
                                       ->like('subject', $file->subject)
                                       ->get(db_prefix() . 'projectdiscussions')
                                       ->row();

                if ($discussion) {
                    $file->has_discussion = true;
                    $file->discussion_id = $discussion->id;

                    // Check comments for approval/rejection
                    $approval_comment = $this->db->where('discussion_id', $discussion->id)
                                                 ->where('contact_id >', 0)
                                                 ->order_by('created', 'DESC')
                                                 ->limit(1)
                                                 ->get(db_prefix() . 'projectdiscussioncomments')
                                                 ->row();

                    if ($approval_comment) {
                        $content_lower = strtolower($approval_comment->content);
                        if (strpos($content_lower, 'aprobado') !== false || strpos($content_lower, 'apruebo') !== false) {
                            $file->approval_status = 'approved';
                        } elseif (strpos($content_lower, 'rechazado') !== false || strpos($content_lower, 'rechazo') !== false) {
                            $file->approval_status = 'rejected';
                        }
                    }
                }
            }

            $this->output_json([
                'success' => true,
                'data' => $files
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading project files: ' . $e->getMessage(), 500);
        }
    }

    /**
     * APPROVE PROJECT FILE
     * POST /api/project-files/:id/approve
     */
    public function approve_project_file($file_id)
    {
        if ($this->user_type !== 'client') {
            $this->output_error('Unauthorized - Clients only', 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->output_error('Method not allowed', 405);
        }

        try {
            $json_input = file_get_contents('php://input');
            $data = json_decode($json_input, true);

            $comment = isset($data['comment']) ? trim($data['comment']) : '';

            // Get the file
            $file = $this->db->where('id', $file_id)->get(db_prefix() . 'project_files')->row();
            if (!$file) {
                $this->output_error('File not found', 404);
            }

            // Verify access
            $project = $this->db->where('id', $file->project_id)->get(db_prefix() . 'projects')->row();
            if ($project->clientid != $this->contact_data->userid) {
                $this->output_error('Unauthorized', 403);
            }

            // Check if discussion exists for this file
            $discussion = $this->db->where('project_id', $file->project_id)
                                   ->like('subject', $file->subject)
                                   ->get(db_prefix() . 'projectdiscussions')
                                   ->row();

            // Create discussion if doesn't exist
            if (!$discussion) {
                $discussion_data = [
                    'project_id' => $file->project_id,
                    'subject' => 'Aprobación: ' . $file->subject,
                    'description' => '',
                    'show_to_customer' => 1,
                    'datecreated' => date('Y-m-d H:i:s'),
                    'staff_id' => 0,
                    'contact_id' => $this->contact_id
                ];
                $this->db->insert(db_prefix() . 'projectdiscussions', $discussion_data);
                $discussion_id = $this->db->insert_id();
            } else {
                $discussion_id = $discussion->id;
            }

            // Add approval comment
            $comment_text = "✅ APROBADO\n\n" . ($comment ? $comment : 'Sin comentarios adicionales');

            $comment_data = [
                'discussion_id' => $discussion_id,
                'discussion_type' => 'regular',
                'created' => date('Y-m-d H:i:s'),
                'content' => $comment_text,
                'staff_id' => 0,
                'contact_id' => $this->contact_id,
                'fullname' => $this->contact_data->firstname . ' ' . $this->contact_data->lastname
            ];
            $this->db->insert(db_prefix() . 'projectdiscussioncomments', $comment_data);

            // Update discussion last activity
            $this->db->where('id', $discussion_id);
            $this->db->update(db_prefix() . 'projectdiscussions', ['last_activity' => date('Y-m-d H:i:s')]);

            // Mark overall design as approved and close discussions
            $this->db->where('id', $file->project_id);
            $this->db->update(db_prefix() . 'projects', [
                'overall_design_approved' => 1,
                'overall_design_approved_by' => $this->contact_id,
                'overall_design_approved_at' => date('Y-m-d H:i:s'),
                'overall_design_approval_ip' => $this->input->ip_address()
            ]);

            // Close project discussions
            $this->db->where('project_id', $file->project_id);
            $this->db->where('name', 'open_discussions');
            $this->db->update(db_prefix() . 'project_settings', [
                'value' => 0
            ]);

            // Log activity
            $log_data = [
                'description_key' => 'client_approved_overall_design',
                'additional_data' => serialize([
                    $this->contact_data->firstname . ' ' . $this->contact_data->lastname,
                    $comment ? $comment : 'No additional comments'
                ]),
                'dateadded' => date('Y-m-d H:i:s'),
                'staff_id' => 0,
                'contact_id' => $this->contact_id,
                'fullname' => $this->contact_data->firstname . ' ' . $this->contact_data->lastname,
                'project_id' => $file->project_id,
                'visible_to_customer' => 1
            ];
            $this->db->insert(db_prefix() . 'project_activity', $log_data);

            $this->output_json([
                'success' => true,
                'message' => 'Design approved successfully. Project discussions have been closed.'
            ]);
        } catch (Exception $e) {
            $this->output_error('Error approving file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * REJECT PROJECT FILE
     * POST /api/project-files/:id/reject
     */
    public function reject_project_file($file_id)
    {
        if ($this->user_type !== 'client') {
            $this->output_error('Unauthorized - Clients only', 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->output_error('Method not allowed', 405);
        }

        try {
            $json_input = file_get_contents('php://input');
            $data = json_decode($json_input, true);

            $comment = isset($data['comment']) ? trim($data['comment']) : '';
            if (empty($comment)) {
                $this->output_error('Comment is required when rejecting a file', 400);
            }

            // Get the file
            $file = $this->db->where('id', $file_id)->get(db_prefix() . 'project_files')->row();
            if (!$file) {
                $this->output_error('File not found', 404);
            }

            // Verify access
            $project = $this->db->where('id', $file->project_id)->get(db_prefix() . 'projects')->row();
            if ($project->clientid != $this->contact_data->userid) {
                $this->output_error('Unauthorized', 403);
            }

            // Check if discussion exists for this file
            $discussion = $this->db->where('project_id', $file->project_id)
                                   ->like('subject', $file->subject)
                                   ->get(db_prefix() . 'projectdiscussions')
                                   ->row();

            // Create discussion if doesn't exist
            if (!$discussion) {
                $discussion_data = [
                    'project_id' => $file->project_id,
                    'subject' => 'Aprobación: ' . $file->subject,
                    'description' => '',
                    'show_to_customer' => 1,
                    'datecreated' => date('Y-m-d H:i:s'),
                    'staff_id' => 0,
                    'contact_id' => $this->contact_id
                ];
                $this->db->insert(db_prefix() . 'projectdiscussions', $discussion_data);
                $discussion_id = $this->db->insert_id();
            } else {
                $discussion_id = $discussion->id;
            }

            // Add rejection comment
            $comment_text = "❌ RECHAZADO\n\nMotivo: " . $comment;

            $comment_data = [
                'discussion_id' => $discussion_id,
                'discussion_type' => 'regular',
                'created' => date('Y-m-d H:i:s'),
                'content' => $comment_text,
                'staff_id' => 0,
                'contact_id' => $this->contact_id,
                'fullname' => $this->contact_data->firstname . ' ' . $this->contact_data->lastname
            ];
            $this->db->insert(db_prefix() . 'projectdiscussioncomments', $comment_data);

            // Update discussion last activity
            $this->db->where('id', $discussion_id);
            $this->db->update(db_prefix() . 'projectdiscussions', ['last_activity' => date('Y-m-d H:i:s')]);

            $this->output_json([
                'success' => true,
                'message' => 'File rejected successfully'
            ]);
        } catch (Exception $e) {
            $this->output_error('Error rejecting file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET PROJECT DISCUSSIONS
     * GET /api/projects/:id/discussions
     */
    public function project_discussions($project_id)
    {
        try {
            // Verify project exists
            $project = $this->db->where('id', $project_id)->get(db_prefix() . 'projects')->row();
            if (!$project) {
                $this->output_error('Project not found', 404);
            }

            // Check access permissions
            if ($this->user_type === 'client') {
                if ($project->clientid != $this->contact_data->userid) {
                    $this->output_error('Unauthorized - You do not have access to this project', 403);
                }
            }

            // Get discussions
            $this->db->select('pd.*, s.firstname as staff_firstname, s.lastname as staff_lastname, c.firstname as contact_firstname, c.lastname as contact_lastname');
            $this->db->from(db_prefix() . 'projectdiscussions pd');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = pd.staff_id', 'left');
            $this->db->join(db_prefix() . 'contacts c', 'c.id = pd.contact_id', 'left');
            $this->db->where('pd.project_id', $project_id);

            // Clients only see discussions marked as visible
            if ($this->user_type === 'client') {
                $this->db->where('pd.show_to_customer', 1);
            }

            // Order by last activity (NULL values go last) and then by creation date
            $this->db->order_by('ISNULL(pd.last_activity), pd.last_activity DESC, pd.datecreated DESC');
            $discussions = $this->db->get()->result();

            // Add creator name and comment count
            foreach ($discussions as $discussion) {
                if ($discussion->staff_id > 0) {
                    $discussion->created_by_name = trim($discussion->staff_firstname . ' ' . $discussion->staff_lastname);
                    $discussion->created_by_type = 'staff';
                } else {
                    $discussion->created_by_name = trim($discussion->contact_firstname . ' ' . $discussion->contact_lastname);
                    $discussion->created_by_type = 'client';
                }

                // Count comments
                $discussion->total_comments = $this->db->where('discussion_id', $discussion->id)
                                                       ->count_all_results(db_prefix() . 'projectdiscussioncomments');
            }

            $this->output_json([
                'success' => true,
                'data' => $discussions
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading discussions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET SINGLE DISCUSSION WITH COMMENTS
     * GET /api/discussions/:id
     */
    public function discussion($id)
    {
        try {
            // Get discussion
            $this->db->select('pd.*, s.firstname as staff_firstname, s.lastname as staff_lastname, c.firstname as contact_firstname, c.lastname as contact_lastname');
            $this->db->from(db_prefix() . 'projectdiscussions pd');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = pd.staff_id', 'left');
            $this->db->join(db_prefix() . 'contacts c', 'c.id = pd.contact_id', 'left');
            $this->db->where('pd.id', $id);
            $discussion = $this->db->get()->row();

            if (!$discussion) {
                $this->output_error('Discussion not found', 404);
            }

            // Check access permissions
            if ($this->user_type === 'client') {
                $project = $this->db->where('id', $discussion->project_id)->get(db_prefix() . 'projects')->row();
                if ($project->clientid != $this->contact_data->userid) {
                    $this->output_error('Unauthorized', 403);
                }
                if ($discussion->show_to_customer != 1) {
                    $this->output_error('Unauthorized - Discussion not visible to customers', 403);
                }
            }

            // Add creator name
            if ($discussion->staff_id > 0) {
                $discussion->created_by_name = trim($discussion->staff_firstname . ' ' . $discussion->staff_lastname);
                $discussion->created_by_type = 'staff';
            } else {
                $discussion->created_by_name = trim($discussion->contact_firstname . ' ' . $discussion->contact_lastname);
                $discussion->created_by_type = 'client';
            }

            // Get comments
            $comments = $this->db->where('discussion_id', $id)
                                 ->order_by('created', 'ASC')
                                 ->get(db_prefix() . 'projectdiscussioncomments')
                                 ->result();

            // Identify comment creators
            foreach ($comments as $comment) {
                if ($comment->staff_id > 0) {
                    $comment->created_by_type = 'staff';
                } else {
                    $comment->created_by_type = 'client';
                }
            }

            $discussion->comments = $comments;

            $this->output_json([
                'success' => true,
                'data' => $discussion
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading discussion: ' . $e->getMessage(), 500);
        }
    }

    /**
     * CREATE NEW DISCUSSION
     * POST /api/projects/:id/discussions
     */
    public function create_discussion($project_id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->output_error('Method not allowed', 405);
        }

        try {
            $json_input = file_get_contents('php://input');
            $data = json_decode($json_input, true);

            $subject = isset($data['subject']) ? trim($data['subject']) : '';
            $description = isset($data['description']) ? trim($data['description']) : '';

            if (empty($subject)) {
                $this->output_error('Subject is required', 400);
            }

            // Verify project exists and user has access
            $project = $this->db->where('id', $project_id)->get(db_prefix() . 'projects')->row();
            if (!$project) {
                $this->output_error('Project not found', 404);
            }

            if ($this->user_type === 'client') {
                if ($project->clientid != $this->contact_data->userid) {
                    $this->output_error('Unauthorized', 403);
                }
            }

            // Check if design has been approved (discussions should be closed)
            if (isset($project->overall_design_approved) && $project->overall_design_approved == 1) {
                $this->output_error('Discussions are locked. The design has been approved and no further discussions can be created.', 403);
            }

            // Check if discussions are open for this project
            $project_settings = $this->db->where('project_id', $project_id)
                                         ->where('name', 'open_discussions')
                                         ->get(db_prefix() . 'project_settings')
                                         ->row();

            if ($project_settings && $project_settings->value == 0) {
                $this->output_error('Discussions are closed for this project.', 403);
            }

            // Create discussion
            $discussion_data = [
                'project_id' => $project_id,
                'subject' => $subject,
                'description' => $description,
                'show_to_customer' => 1, // Always visible to customer
                'datecreated' => date('Y-m-d H:i:s'),
                'last_activity' => date('Y-m-d H:i:s')
            ];

            if ($this->user_type === 'staff') {
                $discussion_data['staff_id'] = $this->staff_id;
                $discussion_data['contact_id'] = 0;
            } else {
                $discussion_data['staff_id'] = 0;
                $discussion_data['contact_id'] = $this->contact_id;
            }

            $this->db->insert(db_prefix() . 'projectdiscussions', $discussion_data);
            $discussion_id = $this->db->insert_id();

            $this->output_json([
                'success' => true,
                'message' => 'Discussion created successfully',
                'discussion_id' => $discussion_id
            ]);
        } catch (Exception $e) {
            $this->output_error('Error creating discussion: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ADD COMMENT TO DISCUSSION
     * POST /api/discussions/:id/comments
     */
    public function add_discussion_comment($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->output_error('Method not allowed', 405);
        }

        try {
            $json_input = file_get_contents('php://input');
            $data = json_decode($json_input, true);

            $content = isset($data['content']) ? trim($data['content']) : '';

            if (empty($content)) {
                $this->output_error('Comment content is required', 400);
            }

            // Verify discussion exists
            $discussion = $this->db->where('id', $id)->get(db_prefix() . 'projectdiscussions')->row();
            if (!$discussion) {
                $this->output_error('Discussion not found', 404);
            }

            // Check access permissions
            if ($this->user_type === 'client') {
                $project = $this->db->where('id', $discussion->project_id)->get(db_prefix() . 'projects')->row();
                if ($project->clientid != $this->contact_data->userid) {
                    $this->output_error('Unauthorized', 403);
                }
            }

            // Get user fullname
            $fullname = '';
            if ($this->user_type === 'staff') {
                $staff = $this->db->where('staffid', $this->staff_id)->get(db_prefix() . 'staff')->row();
                $fullname = trim($staff->firstname . ' ' . $staff->lastname);
            } else {
                $fullname = trim($this->contact_data->firstname . ' ' . $this->contact_data->lastname);
            }

            // Add comment
            $comment_data = [
                'discussion_id' => $id,
                'discussion_type' => 'regular',
                'created' => date('Y-m-d H:i:s'),
                'content' => $content,
                'fullname' => $fullname
            ];

            if ($this->user_type === 'staff') {
                $comment_data['staff_id'] = $this->staff_id;
                $comment_data['contact_id'] = 0;
            } else {
                $comment_data['staff_id'] = 0;
                $comment_data['contact_id'] = $this->contact_id;
            }

            $this->db->insert(db_prefix() . 'projectdiscussioncomments', $comment_data);

            // Update discussion last activity
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'projectdiscussions', ['last_activity' => date('Y-m-d H:i:s')]);

            $this->output_json([
                'success' => true,
                'message' => 'Comment added successfully'
            ]);
        } catch (Exception $e) {
            $this->output_error('Error adding comment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * LEADS
     * GET /api/leads
     */
    public function leads()
    {
        // Only staff can access leads
        if ($this->user_type !== 'staff') {
            $this->output_error('Unauthorized - Staff only', 403);
        }

        $this->load->model('leads_model');

        $leads = $this->db->select('id, name, company, email, phonenumber, lead_value, status, source, assigned, dateadded')
                          ->order_by('dateadded', 'DESC')
                          ->get(db_prefix() . 'leads')
                          ->result();

        // Get status names
        foreach ($leads as &$lead) {
            $status = $this->db->where('id', $lead->status)->get(db_prefix() . 'leads_status')->row();
            $lead->status_name = $status ? $status->name : '';
            $lead->status_color = $status ? $status->color : '';

            $source = $this->db->where('id', $lead->source)->get(db_prefix() . 'leads_sources')->row();
            $lead->source_name = $source ? $source->name : '';
        }

        $this->output_json([
            'success' => true,
            'data' => $leads
        ]);
    }

    /**
     * LEAD DETAIL
     * GET /api/lead/:id
     */
    public function lead($id)
    {
        if ($this->user_type !== 'staff') {
            $this->output_error('Unauthorized - Staff only', 403);
        }

        $this->load->model('leads_model');

        $lead = $this->leads_model->get($id);

        if (!$lead) {
            $this->output_error('Lead not found', 404);
        }

        $this->output_json([
            'success' => true,
            'data' => $lead
        ]);
    }

    /**
     * ESTIMATES
     * GET /api/estimates
     */
    public function estimates()
    {
        if ($this->user_type !== 'staff') {
            $this->output_error('Unauthorized - Staff only', 403);
        }

        $this->load->model('estimates_model');

        $estimates = $this->db->select('id, number, clientid, project_id, total, status, date, expirydate')
                              ->order_by('date', 'DESC')
                              ->get(db_prefix() . 'estimates')
                              ->result();

        // Get client names
        foreach ($estimates as &$estimate) {
            $client = $this->db->where('userid', $estimate->clientid)->get(db_prefix() . 'clients')->row();
            $estimate->client_name = $client ? $client->company : '';
        }

        $this->output_json([
            'success' => true,
            'data' => $estimates
        ]);
    }

    /**
     * ESTIMATE DETAIL
     * GET /api/estimate/:id
     */
    public function estimate($id)
    {
        if ($this->user_type !== 'staff') {
            $this->output_error('Unauthorized - Staff only', 403);
        }

        $this->load->model('estimates_model');

        $estimate = $this->estimates_model->get($id);

        if (!$estimate) {
            $this->output_error('Estimate not found', 404);
        }

        $this->output_json([
            'success' => true,
            'data' => $estimate
        ]);
    }

    /**
     * EXPENSES
     * GET /api/expenses
     */
    public function expenses()
    {
        if ($this->user_type !== 'staff') {
            $this->output_error('Unauthorized - Staff only', 403);
        }

        $this->load->model('expenses_model');

        $expenses = $this->db->select('id, category, amount, name, date, clientid, project_id, billable')
                             ->order_by('date', 'DESC')
                             ->get(db_prefix() . 'expenses')
                             ->result();

        // Get category names and client names
        foreach ($expenses as &$expense) {
            $category = $this->db->where('id', $expense->category)->get(db_prefix() . 'expenses_categories')->row();
            $expense->category_name = $category ? $category->name : '';

            if ($expense->clientid) {
                $client = $this->db->where('userid', $expense->clientid)->get(db_prefix() . 'clients')->row();
                $expense->client_name = $client ? $client->company : '';
            }
        }

        $this->output_json([
            'success' => true,
            'data' => $expenses
        ]);
    }

    /**
     * CONTRACTS
     * GET /api/contracts
     */
    public function contracts()
    {
        if ($this->user_type !== 'staff') {
            $this->output_error('Unauthorized - Staff only', 403);
        }

        $this->load->model('contracts_model');

        $contracts = $this->db->select('id, subject, client, contract_value, start_date, end_date, contract_type, signed')
                              ->order_by('start_date', 'DESC')
                              ->get(db_prefix() . 'contracts')
                              ->result();

        // Get client names and contract types
        foreach ($contracts as &$contract) {
            $client = $this->db->where('userid', $contract->client)->get(db_prefix() . 'clients')->row();
            $contract->client_name = $client ? $client->company : '';

            $type = $this->db->where('id', $contract->contract_type)->get(db_prefix() . 'contract_types')->row();
            $contract->contract_type_name = $type ? $type->name : '';
        }

        $this->output_json([
            'success' => true,
            'data' => $contracts
        ]);
    }

    /**
     * CONTRACT DETAIL
     * GET /api/contract/:id
     */
    public function contract($id)
    {
        if ($this->user_type !== 'staff') {
            $this->output_error('Unauthorized - Staff only', 403);
        }

        $this->load->model('contracts_model');

        $contract = $this->contracts_model->get($id);

        if (!$contract) {
            $this->output_error('Contract not found', 404);
        }

        $this->output_json([
            'success' => true,
            'data' => $contract
        ]);
    }

    // ========================================
    // NOTIFICATIONS ENDPOINTS
    // ========================================

    /**
     * GET NOTIFICATIONS
     * GET /api/notifications
     */
    public function notifications()
    {
        try {
            $limit = $this->input->get('limit') ?: 50;
            $page = $this->input->get('page') ?: 1;
            $offset = ($page - 1) * $limit;

            // Get notifications based on user type
            $this->db->select('*');
            $this->db->from(db_prefix() . 'notifications');

            if ($this->user_type === 'staff') {
                $this->db->where('touserid', $this->staff_id);
                $this->db->where('fromuserid !=', 0); // Staff notifications
            } else {
                $this->db->where('fromclientid', $this->contact_id);
                $this->db->where('fromuserid', 0); // Client notifications
            }

            $this->db->order_by('date', 'DESC');
            $this->db->limit($limit, $offset);
            $notifications = $this->db->get()->result();

            // Count unread
            $this->db->from(db_prefix() . 'notifications');
            if ($this->user_type === 'staff') {
                $this->db->where('touserid', $this->staff_id);
                $this->db->where('fromuserid !=', 0);
            } else {
                $this->db->where('fromclientid', $this->contact_id);
                $this->db->where('fromuserid', 0);
            }
            $this->db->where('isread', 0);
            $unread_count = $this->db->count_all_results();

            $this->output_json([
                'success' => true,
                'data' => $notifications,
                'unread_count' => $unread_count
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading notifications: ' . $e->getMessage(), 500);
        }
    }

    /**
     * MARK NOTIFICATION AS READ
     * PUT /api/notifications/:id/read
     */
    public function mark_notification_read($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->output_error('Method not allowed', 405);
        }

        try {
            // Verify notification exists and belongs to user
            $notification = $this->db->where('id', $id)->get(db_prefix() . 'notifications')->row();

            if (!$notification) {
                $this->output_error('Notification not found', 404);
            }

            // Check ownership
            if ($this->user_type === 'staff' && $notification->touserid != $this->staff_id) {
                $this->output_error('Unauthorized', 403);
            } elseif ($this->user_type === 'client' && $notification->fromclientid != $this->contact_id) {
                $this->output_error('Unauthorized', 403);
            }

            // Mark as read
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'notifications', [
                'isread' => 1,
                'isread_inline' => 1
            ]);

            $this->output_json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } catch (Exception $e) {
            $this->output_error('Error marking notification as read: ' . $e->getMessage(), 500);
        }
    }

    /**
     * MARK ALL NOTIFICATIONS AS READ
     * PUT /api/notifications/read-all or /api/notifications/mark-all-read
     */
    public function mark_all_notifications_read()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->output_error('Method not allowed', 405);
        }

        try {
            // Update all notifications for this user
            if ($this->user_type === 'staff') {
                $this->db->where('touserid', $this->staff_id);
                $this->db->where('fromuserid !=', 0);
            } else {
                $this->db->where('fromclientid', $this->contact_id);
                $this->db->where('fromuserid', 0);
            }

            $this->db->update(db_prefix() . 'notifications', [
                'isread' => 1,
                'isread_inline' => 1
            ]);

            $this->output_json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
        } catch (Exception $e) {
            $this->output_error('Error marking notifications as read: ' . $e->getMessage(), 500);
        }
    }

    /**
     * REGISTER/UPDATE DEVICE TOKEN (for push notifications)
     * POST /api/device-token
     */
    public function device_token()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->output_error('Method not allowed', 405);
        }

        try {
            $json_input = file_get_contents('php://input');
            $data = json_decode($json_input, true);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Register or update token
                if (!isset($data['token']) || !isset($data['platform']) || !isset($data['device_id'])) {
                    $this->output_error('Token, platform, and device_id are required');
                }

                $token_data = [
                    'user_id' => $this->user_type === 'staff' ? $this->staff_id : $this->contact_id,
                    'user_type' => $this->user_type,
                    'token' => $data['token'],
                    'platform' => $data['platform'],
                    'device_id' => $data['device_id'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                // Check if token already exists
                $existing = $this->db->where('device_id', $data['device_id'])
                                     ->where('user_id', $token_data['user_id'])
                                     ->where('user_type', $this->user_type)
                                     ->get(db_prefix() . 'device_tokens')
                                     ->row();

                if ($existing) {
                    // Update existing token
                    $this->db->where('id', $existing->id);
                    $this->db->update(db_prefix() . 'device_tokens', $token_data);
                } else {
                    // Insert new token
                    $token_data['created_at'] = date('Y-m-d H:i:s');
                    $this->db->insert(db_prefix() . 'device_tokens', $token_data);
                }

                $this->output_json([
                    'success' => true,
                    'message' => 'Device token registered successfully'
                ]);
            } else {
                // DELETE - Unregister token
                if (!isset($data['token'])) {
                    $this->output_error('Token is required');
                }

                $this->db->where('token', $data['token']);
                $this->db->where('user_id', $this->user_type === 'staff' ? $this->staff_id : $this->contact_id);
                $this->db->where('user_type', $this->user_type);
                $this->db->delete(db_prefix() . 'device_tokens');

                $this->output_json([
                    'success' => true,
                    'message' => 'Device token unregistered successfully'
                ]);
            }
        } catch (Exception $e) {
            $this->output_error('Error managing device token: ' . $e->getMessage(), 500);
        }
    }

    // ========================================
    // PROJECT DESIGN APPROVAL ENDPOINTS
    // ========================================

    /**
     * GET PROJECT DESIGNS
     * GET /api/projects/:project_id/designs
     * Returns all design submissions for a project
     */
    public function project_designs($project_id)
    {
        try {
            // Verify project exists and user has access
            $project = $this->db->where('id', $project_id)->get(db_prefix() . 'projects')->row();
            if (!$project) {
                $this->output_error('Project not found', 404);
            }

            // Check access permissions
            if ($this->user_type === 'client') {
                // Verify client owns this project
                if ($project->clientid != $this->contact_data->userid) {
                    $this->output_error('Unauthorized - You do not have access to this project', 403);
                }
            }

            // Check if project_designs table exists
            $table_exists = $this->db->table_exists(db_prefix() . 'project_designs');

            if (!$table_exists) {
                // Return empty array if table doesn't exist
                $this->output_json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No designs available for this project'
                ]);
                return;
            }

            // Get all designs for this project
            $this->db->select('pd.*, s.firstname as uploaded_by_firstname, s.lastname as uploaded_by_lastname, c.firstname as approved_by_firstname, c.lastname as approved_by_lastname, f.file_name, f.filetype');
            $this->db->from(db_prefix() . 'project_designs pd');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = pd.uploaded_by', 'left');
            $this->db->join(db_prefix() . 'contacts c', 'c.id = pd.approved_by', 'left');
            $this->db->join(db_prefix() . 'files f', 'f.id = pd.file_id', 'left');
            $this->db->where('pd.project_id', $project_id);
            $this->db->order_by('pd.created_at', 'DESC');
            $designs = $this->db->get()->result();

            // Get comments for each design
            foreach ($designs as $design) {
                $design->comments = $this->get_design_comments_internal($design->id);
                $design->uploaded_by_name = trim($design->uploaded_by_firstname . ' ' . $design->uploaded_by_lastname);
                $design->approved_by_name = $design->approved_by_firstname ? trim($design->approved_by_firstname . ' ' . $design->approved_by_lastname) : null;
            }

            $this->output_json([
                'success' => true,
                'data' => $designs
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading designs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET SINGLE DESIGN
     * GET /api/designs/:id
     */
    public function design($id)
    {
        try {
            $this->db->select('pd.*, s.firstname as uploaded_by_firstname, s.lastname as uploaded_by_lastname, c.firstname as approved_by_firstname, c.lastname as approved_by_lastname, f.file_name, f.filetype, f.file_name');
            $this->db->from(db_prefix() . 'project_designs pd');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = pd.uploaded_by', 'left');
            $this->db->join(db_prefix() . 'contacts c', 'c.id = pd.approved_by', 'left');
            $this->db->join(db_prefix() . 'files f', 'f.id = pd.file_id', 'left');
            $this->db->where('pd.id', $id);
            $design = $this->db->get()->row();

            if (!$design) {
                $this->output_error('Design not found', 404);
            }

            // Check access permissions
            if ($this->user_type === 'client') {
                $project = $this->db->where('id', $design->project_id)->get(db_prefix() . 'projects')->row();
                if ($project->clientid != $this->contact_data->userid) {
                    $this->output_error('Unauthorized', 403);
                }
            }

            $design->comments = $this->get_design_comments_internal($design->id);
            $design->uploaded_by_name = trim($design->uploaded_by_firstname . ' ' . $design->uploaded_by_lastname);
            $design->approved_by_name = $design->approved_by_firstname ? trim($design->approved_by_firstname . ' ' . $design->approved_by_lastname) : null;

            $this->output_json([
                'success' => true,
                'data' => $design
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading design: ' . $e->getMessage(), 500);
        }
    }

    /**
     * SUBMIT NEW DESIGN (Staff only)
     * POST /api/projects/:project_id/designs
     */
    public function submit_design($project_id)
    {
        if ($this->user_type !== 'staff') {
            $this->output_error('Unauthorized - Staff only', 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->output_error('Method not allowed', 405);
        }

        try {
            $json_input = file_get_contents('php://input');
            $data = json_decode($json_input, true);

            if (!isset($data['title']) || empty(trim($data['title']))) {
                $this->output_error('Title is required');
            }

            // Verify project exists
            $project = $this->db->where('id', $project_id)->get(db_prefix() . 'projects')->row();
            if (!$project) {
                $this->output_error('Project not found', 404);
            }

            $design_data = [
                'project_id' => $project_id,
                'title' => $data['title'],
                'description' => isset($data['description']) ? $data['description'] : null,
                'uploaded_by' => $this->staff_id,
                'file_id' => isset($data['file_id']) ? $data['file_id'] : null,
                'status' => 'pending',
                'approval_note' => 'La empresa no se hace responsable de errores una vez aprobado el diseño. Al aprobar, usted confirma que ha revisado cuidadosamente todos los detalles.',
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert(db_prefix() . 'project_designs', $design_data);
            $design_id = $this->db->insert_id();

            // Log the action
            $this->log_approval_action($design_id, $project_id, 'submitted', $this->staff_id, 'staff', 'Design submitted to client');

            $this->output_json([
                'success' => true,
                'message' => 'Design submitted successfully',
                'data' => ['id' => $design_id]
            ]);
        } catch (Exception $e) {
            $this->output_error('Error submitting design: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ADD COMMENT TO DESIGN
     * POST /api/designs/:id/comments
     */
    public function add_design_comment($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->output_error('Method not allowed', 405);
        }

        try {
            $json_input = file_get_contents('php://input');
            $data = json_decode($json_input, true);

            if (!isset($data['comment']) || empty(trim($data['comment']))) {
                $this->output_error('Comment is required');
            }

            // Verify design exists
            $design = $this->db->where('id', $id)->get(db_prefix() . 'project_designs')->row();
            if (!$design) {
                $this->output_error('Design not found', 404);
            }

            // Check access permissions
            if ($this->user_type === 'client') {
                $project = $this->db->where('id', $design->project_id)->get(db_prefix() . 'projects')->row();
                if ($project->clientid != $this->contact_data->userid) {
                    $this->output_error('Unauthorized', 403);
                }
            }

            $comment_data = [
                'design_id' => $id,
                'user_id' => $this->user_type === 'staff' ? $this->staff_id : $this->contact_id,
                'user_type' => $this->user_type,
                'comment' => $data['comment'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert(db_prefix() . 'project_design_comments', $comment_data);

            // Log the action
            $this->log_approval_action(
                $id,
                $design->project_id,
                'commented',
                $this->user_type === 'staff' ? $this->staff_id : $this->contact_id,
                $this->user_type,
                'Comment added'
            );

            $this->output_json([
                'success' => true,
                'message' => 'Comment added successfully'
            ]);
        } catch (Exception $e) {
            $this->output_error('Error adding comment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * APPROVE DESIGN (Client only)
     * PUT /api/designs/:id/approve
     */
    public function approve_design($id)
    {
        if ($this->user_type !== 'client') {
            $this->output_error('Unauthorized - Clients only', 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->output_error('Method not allowed', 405);
        }

        try {
            // Verify design exists
            $design = $this->db->where('id', $id)->get(db_prefix() . 'project_designs')->row();
            if (!$design) {
                $this->output_error('Design not found', 404);
            }

            // Check access permissions
            $project = $this->db->where('id', $design->project_id)->get(db_prefix() . 'projects')->row();
            if ($project->clientid != $this->contact_data->userid) {
                $this->output_error('Unauthorized', 403);
            }

            // Check if already approved
            if ($design->status === 'approved') {
                $this->output_error('Design is already approved', 400);
            }

            // Update design status
            $update_data = [
                'status' => 'approved',
                'approved_by' => $this->contact_id,
                'approved_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'project_designs', $update_data);

            // Log the approval
            $this->log_approval_action(
                $id,
                $design->project_id,
                'approved',
                $this->contact_id,
                'client',
                'Design approved by client'
            );

            // Update project status if configured
            if ($project->approval_status_id) {
                $this->db->where('id', $project->id);
                $this->db->update(db_prefix() . 'projects', [
                    'status' => $project->approval_status_id
                ]);
            }

            $this->output_json([
                'success' => true,
                'message' => 'Design approved successfully',
                'disclaimer' => $design->approval_note
            ]);
        } catch (Exception $e) {
            $this->output_error('Error approving design: ' . $e->getMessage(), 500);
        }
    }

    /**
     * REJECT DESIGN (Client only)
     * PUT /api/designs/:id/reject
     */
    public function reject_design($id)
    {
        if ($this->user_type !== 'client') {
            $this->output_error('Unauthorized - Clients only', 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->output_error('Method not allowed', 405);
        }

        try {
            $json_input = file_get_contents('php://input');
            $data = json_decode($json_input, true);

            // Verify design exists
            $design = $this->db->where('id', $id)->get(db_prefix() . 'project_designs')->row();
            if (!$design) {
                $this->output_error('Design not found', 404);
            }

            // Check access permissions
            $project = $this->db->where('id', $design->project_id)->get(db_prefix() . 'projects')->row();
            if ($project->clientid != $this->contact_data->userid) {
                $this->output_error('Unauthorized', 403);
            }

            // Update design status
            $update_data = [
                'status' => 'rejected',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'project_designs', $update_data);

            // Log the rejection
            $reason = isset($data['reason']) ? $data['reason'] : 'No reason provided';
            $this->log_approval_action(
                $id,
                $design->project_id,
                'rejected',
                $this->contact_id,
                'client',
                'Design rejected: ' . $reason
            );

            $this->output_json([
                'success' => true,
                'message' => 'Design rejected'
            ]);
        } catch (Exception $e) {
            $this->output_error('Error rejecting design: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET DESIGN COMMENTS
     * GET /api/designs/:id/comments
     */
    public function design_comments($id)
    {
        try {
            // Verify design exists
            $design = $this->db->where('id', $id)->get(db_prefix() . 'project_designs')->row();
            if (!$design) {
                $this->output_error('Design not found', 404);
            }

            // Check access permissions
            if ($this->user_type === 'client') {
                $project = $this->db->where('id', $design->project_id)->get(db_prefix() . 'projects')->row();
                if ($project->clientid != $this->contact_data->userid) {
                    $this->output_error('Unauthorized', 403);
                }
            }

            $comments = $this->get_design_comments_internal($id);

            $this->output_json([
                'success' => true,
                'data' => $comments
            ]);
        } catch (Exception $e) {
            $this->output_error('Error loading comments: ' . $e->getMessage(), 500);
        }
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Internal helper to get design comments with user info
     */
    private function get_design_comments_internal($design_id)
    {
        $this->db->select('pdc.*,
            CASE
                WHEN pdc.user_type = "staff" THEN CONCAT(s.firstname, " ", s.lastname)
                WHEN pdc.user_type = "client" THEN CONCAT(c.firstname, " ", c.lastname)
            END as user_name');
        $this->db->from(db_prefix() . 'project_design_comments pdc');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = pdc.user_id AND pdc.user_type = "staff"', 'left');
        $this->db->join(db_prefix() . 'contacts c', 'c.id = pdc.user_id AND pdc.user_type = "client"', 'left');
        $this->db->where('pdc.design_id', $design_id);
        $this->db->order_by('pdc.created_at', 'ASC');

        return $this->db->get()->result();
    }

    /**
     * Log approval action for audit trail
     */
    private function log_approval_action($design_id, $project_id, $action, $user_id, $user_type, $details = null)
    {
        $log_data = [
            'design_id' => $design_id,
            'project_id' => $project_id,
            'action' => $action,
            'user_id' => $user_id,
            'user_type' => $user_type,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent(),
            'details' => $details,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert(db_prefix() . 'project_approval_logs', $log_data);
    }

    /**
     * DEBUG: Make file visible to customer
     * GET /api/debug/make-file-visible/:id
     * TEMPORARY - Remove after implementation
     */
    public function debug_make_file_visible($file_id)
    {
        header('Content-Type: text/html; charset=utf-8');

        echo "<html><body style='font-family:monospace;background:#1e1e1e;color:#d4d4d4;padding:20px;'>";
        echo "<h1>Make File Visible to Customer</h1>";
        echo "<pre>";

        // Update the file
        $this->db->where('id', $file_id);
        $this->db->update(db_prefix() . 'project_files', ['visible_to_customer' => 1]);

        if ($this->db->affected_rows() > 0) {
            echo "✅ File ID {$file_id} is now visible to customer!\n\n";

            // Show the updated file
            $file = $this->db->where('id', $file_id)->get(db_prefix() . 'project_files')->row();
            echo "Updated file:\n";
            foreach ($file as $key => $value) {
                echo sprintf("%-25s: %s\n", $key, $value);
            }
        } else {
            echo "❌ No file found with ID {$file_id}\n";
        }

        echo "</pre></body></html>";
        exit;
    }

    /**
     * DEBUG: Examine table structures
     * GET /api/debug/tables
     * TEMPORARY - Remove after implementation
     */
    public function debug_tables()
    {
        // Skip authentication for debugging
        header('Content-Type: text/html; charset=utf-8');

        echo "<html><head><style>body{font-family:monospace;background:#1e1e1e;color:#d4d4d4;padding:20px;}pre{background:#2d2d2d;padding:15px;border-radius:5px;}h2{color:#4ec9b0;}</style></head><body>";
        echo "<h1>🔍 Análisis de Tablas de Perfex</h1>";

        // 1. tblfiles structure
        echo "<h2>1. Estructura de tblfiles</h2><pre>";
        $result = $this->db->query("DESCRIBE " . db_prefix() . "files");
        echo sprintf("%-25s | %-20s | %s\n", "Field", "Type", "Null");
        echo str_repeat("-", 60) . "\n";
        foreach ($result->result() as $row) {
            echo sprintf("%-25s | %-20s | %s\n", $row->Field, $row->Type, $row->Null);
        }
        echo "</pre>";

        // 2. tblprojectdiscussions structure
        echo "<h2>2. Estructura de tblprojectdiscussions</h2><pre>";
        $result = $this->db->query("DESCRIBE " . db_prefix() . "projectdiscussions");
        echo sprintf("%-25s | %-20s | %s\n", "Field", "Type", "Null");
        echo str_repeat("-", 60) . "\n";
        foreach ($result->result() as $row) {
            echo sprintf("%-25s | %-20s | %s\n", $row->Field, $row->Type, $row->Null);
        }
        echo "</pre>";

        // 3. ALL FILES - Last 20 files added (no filters)
        echo "<h2>3. TODOS LOS ARCHIVOS (últimos 20)</h2><pre>";
        echo "Tabla: " . db_prefix() . "files\n\n";

        $files = $this->db->order_by('dateadded', 'DESC')
                          ->limit(20)
                          ->get(db_prefix() . 'files')
                          ->result();

        if (count($files) > 0) {
            echo "✅ Encontrados " . count($files) . " archivo(s) en total:\n\n";
            foreach ($files as $file) {
                echo str_repeat("=", 80) . "\n";
                echo "ID: {$file->id} | {$file->file_name}\n";
                echo "rel_type: {$file->rel_type} | rel_id: {$file->rel_id} | visible_to_customer: {$file->visible_to_customer}\n";
                echo str_repeat("=", 80) . "\n";
                foreach ($file as $key => $value) {
                    echo sprintf("%-25s: %s\n", $key, $value !== null && $value !== '' ? $value : '(vacío)');
                }
                echo "\n";
            }
        } else {
            echo "❌ No hay archivos en la base de datos\n";
        }
        echo "</pre>";

        // 3b. Count total files
        echo "<h2>3b. Estadísticas de Archivos</h2><pre>";
        $total = $this->db->count_all(db_prefix() . 'files');
        echo "Total de archivos en la BD: " . $total . "\n\n";

        echo "Archivos por rel_type:\n";
        $types = $this->db->select('rel_type, COUNT(*) as count')
                          ->group_by('rel_type')
                          ->get(db_prefix() . 'files')
                          ->result();
        foreach ($types as $type) {
            echo "  {$type->rel_type}: {$type->count} archivo(s)\n";
        }
        echo "</pre>";

        // 3c. Search for tables with "file" or "attachment" in name
        echo "<h2>3c. Tablas relacionadas con archivos</h2><pre>";
        $tables = $this->db->query("SHOW TABLES LIKE '%file%'")->result_array();
        echo "Tablas que contienen 'file':\n";
        foreach ($tables as $table) {
            $table_name = array_values($table)[0];
            $count = $this->db->count_all($table_name);
            echo "  {$table_name}: {$count} registro(s)\n";
        }

        echo "\nTablas que contienen 'attachment':\n";
        $tables = $this->db->query("SHOW TABLES LIKE '%attachment%'")->result_array();
        foreach ($tables as $table) {
            $table_name = array_values($table)[0];
            $count = $this->db->count_all($table_name);
            echo "  {$table_name}: {$count} registro(s)\n";
        }
        echo "</pre>";

        // 3d. Check project discussions with files
        echo "<h2>3d. Project Discussions (project_id=8)</h2><pre>";
        $discussions = $this->db->where('project_id', 8)
                                ->order_by('datecreated', 'DESC')
                                ->get(db_prefix() . 'projectdiscussions')
                                ->result();

        if (count($discussions) > 0) {
            echo "✅ Encontradas " . count($discussions) . " discussion(s) en proyecto 8:\n\n";
            foreach ($discussions as $disc) {
                echo str_repeat("=", 80) . "\n";
                echo "Discussion ID: {$disc->id} | Subject: {$disc->subject}\n";
                echo str_repeat("=", 80) . "\n";
                foreach ($disc as $key => $value) {
                    echo sprintf("%-25s: %s\n", $key, $value !== null && $value !== '' ? $value : '(vacío)');
                }
                echo "\n";
            }
        } else {
            echo "❌ No hay discussions en el proyecto 8\n";
        }
        echo "</pre>";

        // 4. Examine tblproject_files structure and data
        echo "<h2>4. Estructura de tblproject_files</h2><pre>";
        $result = $this->db->query("DESCRIBE " . db_prefix() . "project_files");
        echo sprintf("%-25s | %-20s | %s\n", "Field", "Type", "Null");
        echo str_repeat("-", 60) . "\n";
        foreach ($result->result() as $row) {
            echo sprintf("%-25s | %-20s | %s\n", $row->Field, $row->Type, $row->Null);
        }
        echo "</pre>";

        // 5. Get all data from tblproject_files
        echo "<h2>5. Datos de tblproject_files</h2><pre>";
        $files = $this->db->order_by('id', 'DESC')
                          ->get(db_prefix() . 'project_files')
                          ->result();

        if (count($files) > 0) {
            echo "✅ Encontrados " . count($files) . " archivo(s):\n\n";
            foreach ($files as $file) {
                echo str_repeat("=", 80) . "\n";
                echo "Archivo ID: " . $file->id . "\n";
                echo str_repeat("=", 80) . "\n";
                foreach ($file as $key => $value) {
                    echo sprintf("%-25s: %s\n", $key, $value !== null && $value !== '' ? $value : '(vacío)');
                }
                echo "\n";
            }
        } else {
            echo "❌ No hay archivos en tblproject_files\n";
        }
        echo "</pre>";

        // 6. Check discussion comments
        echo "<h2>6. Comentarios de Discussions</h2><pre>";
        $comments = $this->db->order_by('id', 'DESC')
                             ->limit(10)
                             ->get(db_prefix() . 'projectdiscussioncomments')
                             ->result();

        if (count($comments) > 0) {
            echo "✅ Encontrados " . count($comments) . " comentario(s):\n\n";
            foreach ($comments as $comment) {
                echo str_repeat("=", 80) . "\n";
                echo "Comment ID: " . $comment->id . "\n";
                echo str_repeat("=", 80) . "\n";
                foreach ($comment as $key => $value) {
                    $display_value = $value;
                    if ($key == 'content' && strlen($value) > 100) {
                        $display_value = substr($value, 0, 100) . "... [truncado]";
                    }
                    echo sprintf("%-25s: %s\n", $key, $display_value !== null && $display_value !== '' ? $display_value : '(vacío)');
                }
                echo "\n";
            }
        } else {
            echo "❌ No hay comentarios de discussions\n";
        }
        echo "</pre>";

        echo "</body></html>";
        exit;
    }
}
