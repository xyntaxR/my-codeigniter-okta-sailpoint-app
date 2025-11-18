# Quick Start Guide - Okta Integration

## Step 1: Install Dependencies

```bash
composer install
```

## Step 2: Configure Database

Edit `application/config/database.php`:

```php
$db['default'] = array(
    'hostname' => 'localhost',
    'username' => 'root',  // Your MySQL username
    'password' => '',      // Your MySQL password
    'database' => 'okta_app',  // Your database name
    'dbdriver' => 'mysqli',
);
```

Create the database:
```sql
CREATE DATABASE okta_app;
```

## Step 3: Configure Okta

1. **Get Okta Credentials from Okta Admin Console:**
   - Domain: e.g., `dev-12345.okta.com`
   - Client ID: From your application settings
   - Client Secret: From your application settings

2. **Edit `application/config/okta.php`:**

```php
$config['okta_domain'] = 'your-okta-domain.okta.com';
$config['okta_client_id'] = 'your-client-id-here';
$config['okta_client_secret'] = 'your-client-secret-here';
$config['okta_redirect_uri'] = 'http://localhost/my-codeigniter-okta-app/auth/callback';
```

3. **Update Okta Application Settings:**
   - Sign-in redirect URIs: `http://localhost/my-codeigniter-okta-app/auth/callback`
   - Sign-out redirect URIs: `http://localhost/my-codeigniter-okta-app/auth/login`

## Step 4: Configure Base URL

Edit `application/config/config.php`:

```php
$config['base_url'] = 'http://localhost/my-codeigniter-okta-app/';
```

## Step 5: Set Permissions (Linux/Mac)

```bash
chmod -R 755 application/cache
```

## Step 6: Create a Local Admin User (Optional)

Create a PHP script `create_admin.php` in the root directory:

```php
<?php
require_once 'index.php';

$CI =& get_instance();
$CI->load->model('user_model');

$admin = array(
    'username' => 'admin',
    'email' => 'admin@localhost.com',
    'password' => 'admin123',  // Change this!
    'full_name' => 'Local Administrator',
    'roles' => array('admin')
);

$user_id = $CI->user_model->create_local_user($admin);

if ($user_id) {
    echo "Admin user created successfully! ID: $user_id\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
} else {
    echo "Failed to create admin user\n";
}
```

Run it once:
```bash
php create_admin.php
```

Then delete the file for security.

## Step 7: Test the Application

1. **Start your web server** (if using XAMPP, Apache should be running)

2. **Navigate to:** `http://localhost/my-codeigniter-okta-app/`

3. **You should see the login page with two options:**
   - Sign in with Okta
   - Sign in with Local Account

4. **Test Okta Login:**
   - Click "Sign in with Okta"
   - Authenticate with your Okta credentials
   - You'll be redirected back and logged in

5. **Test Local Login:**
   - Use the local admin credentials you created
   - You should be logged in directly

## Troubleshooting

### Cannot connect to database
- Verify MySQL is running
- Check database credentials in `database.php`
- Ensure database exists

### Okta redirect not working
- Verify `base_url` in `config.php`
- Check Okta redirect URIs match exactly
- Ensure no typos in Okta configuration

### Session errors
- Check that `application/cache/sessions` directory exists
- Verify directory is writable
- On Windows: Right-click folder > Properties > Security > Give write permissions

### Composer dependencies not installed
- Run `composer install` from the project root
- If you don't have Composer, download it from getcomposer.org

## Role Mapping

To map Okta groups to roles, edit `application/config/okta.php`:

```php
$config['okta_role_mapping'] = array(
    'Administrators' => 'admin',      // Okta group name => app role
    'Users' => 'user',
    'ReadOnly' => 'viewer'
);
```

Replace the Okta group names (left side) with your actual Okta group names.

## Next Steps

- Review `OKTA_INTEGRATION.md` for detailed documentation
- Customize the dashboard in `application/views/dashboard/index.php`
- Add more protected controllers/pages
- Implement role-based access control in your controllers
- Configure production settings for deployment

## Security Notes

⚠️ **Before going to production:**
- Change all default passwords
- Use environment variables for sensitive data
- Enable HTTPS and set `cookie_secure = TRUE`
- Set `ENVIRONMENT = 'production'` in `index.php`
- Review and adjust session timeout settings
- Implement proper logging and monitoring
