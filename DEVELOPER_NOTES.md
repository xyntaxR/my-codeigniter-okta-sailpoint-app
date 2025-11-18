# Developer Notes - Okta Integration

## Quick Reference

### Session Variables Available
Access these in any controller after authentication:

```php
$this->session->userdata('user_id');        // Database user ID
$this->session->userdata('username');       // Username
$this->session->userdata('email');          // Email address
$this->session->userdata('full_name');      // Full name
$this->session->userdata('user_type');      // 'local' or 'external'
$this->session->userdata('roles');          // Array of roles
$this->session->userdata('primary_role');   // Primary role string
$this->session->userdata('logged_in');      // Boolean
$this->session->userdata('access_token');   // Okta access token (external only)
$this->session->userdata('id_token');       // Okta ID token (external only)
```

### Check User Roles

```php
// In any controller
$user_roles = $this->session->userdata('roles');

// Check for specific role
if (in_array('admin', $user_roles)) {
    // Admin code
}

// Using Role_mapper library
$this->load->library('role_mapper');

// Check if user has a role
if ($this->role_mapper->has_role($user_roles, 'admin')) {
    // Admin code
}

// Check if user has any of multiple roles
$allowed = array('admin', 'user');
if ($this->role_mapper->has_any_role($user_roles, $allowed)) {
    // Code for admin or user
}
```

### Creating Protected Controllers

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class My_Protected_Controller extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        // Auth_filter hook handles authentication automatically
        
        // Optional: Add role check
        $user_roles = $this->session->userdata('roles');
        $this->load->library('role_mapper');
        
        if (!$this->role_mapper->has_role($user_roles, 'admin')) {
            redirect('auth/access_denied');
        }
    }
    
    public function index() {
        // Only admins can access this
    }
}
```

### Making Routes Public

Edit `application/hooks/Auth_filter.php` and add to `$public_routes`:

```php
private $public_routes = array(
    'auth/login',
    'auth/okta_login',
    'auth/callback',
    'auth/local_login',
    'welcome',
    'api/public',        // Add your public routes
    'pages/about',       // Add your public routes
);
```

### User Management Examples

#### Get User Information
```php
$this->load->model('user_model');

// By ID
$user = $this->user_model->get_by_id($user_id);

// By username
$user = $this->user_model->get_by_username('johndoe');

// By email
$user = $this->user_model->get_by_email('john@example.com');

// By Okta external ID
$user = $this->user_model->get_by_external_id($okta_sub);
```

#### Create Local User
```php
$this->load->model('user_model');

$data = array(
    'username' => 'newuser',
    'email' => 'user@example.com',
    'password' => 'SecurePassword123',
    'full_name' => 'New User',
    'roles' => array('user')
);

$user_id = $this->user_model->create_local_user($data);
```

#### Update User Roles
```php
$this->load->model('user_model');
$this->user_model->update_roles($user_id, array('admin', 'user'));
```

#### Get All Users
```php
$this->load->model('user_model');

// All users
$all_users = $this->user_model->get_all_users();

// Only local users
$local_users = $this->user_model->get_all_users('local');

// Only external users
$external_users = $this->user_model->get_all_users('external');
```

### Okta Service Usage

```php
$this->load->library('okta_service');

// Get authorization URL (used in Auth controller)
$auth_url = $this->okta_service->get_authorization_url();

// Exchange code for tokens
$tokens = $this->okta_service->exchange_code_for_tokens($code);

// Verify ID token
$user_info = $this->okta_service->verify_id_token($id_token);

// Get user info from access token
$user_data = $this->okta_service->get_user_info($access_token);

// Extract groups from token
$groups = $this->okta_service->extract_groups($token_data);

// Refresh expired token
$new_tokens = $this->okta_service->refresh_token($refresh_token);

// Get logout URL
$logout_url = $this->okta_service->get_logout_url($id_token);
```

### Custom Views with User Data

```php
// In controller
public function profile() {
    $data['user'] = array(
        'username' => $this->session->userdata('username'),
        'email' => $this->session->userdata('email'),
        'full_name' => $this->session->userdata('full_name'),
        'user_type' => $this->session->userdata('user_type'),
        'roles' => $this->session->userdata('roles'),
        'primary_role' => $this->session->userdata('primary_role')
    );
    
    $this->load->view('profile', $data);
}
```

```php
// In view
<h1>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></h1>

<?php if ($user['user_type'] === 'external'): ?>
    <p>Authenticated via Okta</p>
<?php else: ?>
    <p>Local account</p>
<?php endif; ?>

<?php if (in_array('admin', $user['roles'])): ?>
    <a href="<?php echo site_url('admin/dashboard'); ?>">Admin Panel</a>
<?php endif; ?>
```

### Adding Custom Role Checks

Create a helper function `application/helpers/auth_helper.php`:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function is_logged_in() {
    $CI =& get_instance();
    return $CI->session->userdata('logged_in') === TRUE;
}

function has_role($role) {
    $CI =& get_instance();
    $roles = $CI->session->userdata('roles');
    return is_array($roles) && in_array($role, $roles);
}

function is_admin() {
    return has_role('admin');
}

function current_user_id() {
    $CI =& get_instance();
    return $CI->session->userdata('user_id');
}

function current_username() {
    $CI =& get_instance();
    return $CI->session->userdata('username');
}
```

Load in autoload.php:
```php
$autoload['helper'] = array('url', 'security', 'auth');
```

Use in controllers and views:
```php
if (is_admin()) {
    // Admin code
}
```

### Debugging Tips

#### Check JWT Token
Visit https://jwt.io/ and paste the ID token to inspect claims.

#### Enable Debug Logging
Edit `application/config/config.php`:
```php
$config['log_threshold'] = 4; // All logs
```

Check logs in `application/logs/log-YYYY-MM-DD.php`

#### Test Token Validation
```php
$this->load->library('okta_jwt_verifier');
$decoded = $this->okta_jwt_verifier->verify($id_token);
var_dump($decoded);
```

#### Session Debugging
```php
// View all session data
print_r($this->session->userdata());

// Check specific values
echo "Logged in: " . ($this->session->userdata('logged_in') ? 'Yes' : 'No');
echo "User type: " . $this->session->userdata('user_type');
```

### Common Customizations

#### Change Session Timeout
Edit `application/config/okta.php`:
```php
$config['okta_session_timeout'] = 7200; // 2 hours
```

#### Change Default Role
Edit `application/config/okta.php`:
```php
$config['okta_default_role'] = 'viewer';
```

#### Add More Role Mappings
Edit `application/config/okta.php`:
```php
$config['okta_role_mapping'] = array(
    'Okta-Admins' => 'admin',
    'Okta-Users' => 'user',
    'Okta-Viewers' => 'viewer',
    'Okta-Managers' => 'manager',  // Add custom roles
    'Okta-Support' => 'support',
);
```

#### Disable Local Login
Edit `application/config/okta.php`:
```php
$config['okta_local_fallback'] = FALSE;
```

#### Customize Login Page
Edit `application/views/auth/login.php` - full HTML/CSS customization

#### Add Remember Me
You'll need to implement this yourself:
1. Add checkbox to login form
2. Set longer session expiration
3. Store encrypted token in cookie
4. Validate on subsequent visits

### API Authentication

For API endpoints using bearer tokens:

```php
public function api_endpoint() {
    // Get Authorization header
    $auth_header = $this->input->get_request_header('Authorization');
    
    if (!$auth_header || strpos($auth_header, 'Bearer ') !== 0) {
        $this->output
            ->set_status_header(401)
            ->set_output(json_encode(array('error' => 'Unauthorized')));
        return;
    }
    
    $token = substr($auth_header, 7);
    
    // Verify token
    $this->load->library('okta_jwt_verifier');
    $decoded = $this->okta_jwt_verifier->verify($token);
    
    if (!$decoded) {
        $this->output
            ->set_status_header(401)
            ->set_output(json_encode(array('error' => 'Invalid token')));
        return;
    }
    
    // Process API request
    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode(array('success' => true)));
}
```

### Testing Checklist

- [ ] Can login with Okta
- [ ] Can login with local account
- [ ] User is created in database on first Okta login
- [ ] User data is updated on subsequent Okta logins
- [ ] Roles are correctly mapped from Okta groups
- [ ] Session persists across page loads
- [ ] Session expires after timeout
- [ ] Token refresh works for external users
- [ ] Logout works correctly
- [ ] Protected pages redirect to login when not authenticated
- [ ] Access denied page shows for insufficient permissions
- [ ] Dashboard shows correct user information

### Performance Tips

1. **Cache JWKS Keys** - Already implemented with caching
2. **Use Database Sessions** for multiple servers:
   ```php
   $config['sess_driver'] = 'database';
   ```
3. **Index Database Properly** - Already included in schema
4. **Enable Query Caching** if needed

### Security Best Practices

1. Always use `htmlspecialchars()` or `xss_clean()` when displaying user data
2. Validate and sanitize all user inputs
3. Use HTTPS in production
4. Rotate encryption keys periodically
5. Monitor authentication logs
6. Implement rate limiting on login endpoints
7. Use prepared statements (CodeIgniter does this automatically)
8. Keep dependencies updated

---

**Need Help?**
- Check `OKTA_INTEGRATION.md` for detailed documentation
- Review `application/logs/` for error messages
- Test tokens at https://jwt.io/
- Check Okta developer documentation
