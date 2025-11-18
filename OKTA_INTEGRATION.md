# CodeIgniter Okta Authentication Integration

This application implements OpenID Connect (OIDC) authentication with Okta while maintaining support for local database authentication as a fallback.

## Features

✅ **Dual Authentication Methods**
- Okta SSO via OpenID Connect (OIDC)
- Local database authentication fallback

✅ **Security Features**
- JWT token verification
- CSRF protection with state parameter
- Session management with automatic expiration
- Token refresh for extended sessions
- Secure password hashing for local users

✅ **Role Management**
- Map Okta groups to application roles
- Support for multiple roles per user
- Configurable role-based access control
- Three default roles: Admin, User, Viewer

✅ **User Management**
- Automatic user provisioning from Okta
- Support for both local and external users
- User information caching
- Last login tracking

## Installation

### 1. Install Dependencies

```bash
composer install
```

This will install:
- `firebase/php-jwt` - JWT token verification
- `guzzlehttp/guzzle` - HTTP client for API calls

### 2. Database Setup

Configure your database in `application/config/database.php`:

```php
$db['default'] = array(
    'hostname' => 'localhost',
    'username' => 'your_db_username',
    'password' => 'your_db_password',
    'database' => 'your_database_name',
    'dbdriver' => 'mysqli',
    // ... other settings
);
```

The `users` table will be created automatically on first run.

### 3. Okta Configuration

1. **Create an Okta Application**
   - Log in to your Okta Admin Console
   - Go to Applications > Applications
   - Click "Create App Integration"
   - Select "OIDC - OpenID Connect"
   - Select "Web Application"
   - Configure:
     - Sign-in redirect URIs: `http://your-domain.com/auth/callback`
     - Sign-out redirect URIs: `http://your-domain.com/auth/login`
     - Controlled access: as needed for your organization

2. **Update Configuration**

Edit `application/config/okta.php`:

```php
$config['okta_domain'] = 'your-okta-domain.okta.com';
$config['okta_client_id'] = 'your-client-id';
$config['okta_client_secret'] = 'your-client-secret';
$config['okta_redirect_uri'] = base_url('auth/callback');
```

**Environment Variables (Recommended)**

For production, use environment variables:

```bash
export OKTA_DOMAIN=your-okta-domain.okta.com
export OKTA_CLIENT_ID=your-client-id
export OKTA_CLIENT_SECRET=your-client-secret
export OKTA_REDIRECT_URI=https://your-domain.com/auth/callback
```

### 4. Role Mapping Configuration

Edit `application/config/okta.php` to map Okta groups to application roles:

```php
$config['okta_role_mapping'] = array(
    'OktaAdminGroup' => 'admin',
    'OktaUserGroup' => 'user',
    'OktaViewerGroup' => 'viewer'
);
```

### 5. File Permissions

Ensure the session directory is writable:

```bash
chmod -R 755 application/cache/sessions
```

On Windows (PowerShell):
```powershell
icacls application\cache\sessions /grant Everyone:F
```

## Configuration Options

### Okta Settings (`application/config/okta.php`)

| Setting | Description | Default |
|---------|-------------|---------|
| `okta_enabled` | Enable/disable Okta authentication | `TRUE` |
| `okta_local_fallback` | Enable local database authentication | `TRUE` |
| `okta_session_timeout` | Session timeout in seconds | `3600` (1 hour) |
| `okta_leeway` | Clock skew tolerance in seconds | `120` |
| `okta_default_role` | Default role for unmapped users | `'user'` |
| `okta_cache_user_info` | Cache user information | `TRUE` |

## Usage

### Authentication Flow

#### Okta Login (Primary Flow)

1. User clicks "Sign in with Okta" on login page
2. User is redirected to Okta's hosted login page
3. User authenticates with Okta (username/password + 2FA)
4. Okta redirects back with authorization code
5. Application exchanges code for tokens
6. Application verifies JWT token
7. User account is created/updated in local database
8. Session is created and user is redirected to dashboard

#### Local Login (Fallback Flow)

1. User enters username and password
2. Credentials are validated against local database
3. Session is created on successful authentication
4. User is redirected to dashboard

### Creating Local Users

You can create local users programmatically:

```php
$this->load->model('user_model');

$user_data = array(
    'username' => 'admin',
    'email' => 'admin@example.com',
    'password' => 'your-secure-password',
    'full_name' => 'Administrator',
    'roles' => array('admin')
);

$user_id = $this->user_model->create_local_user($user_data);
```

Or create a database migration/seeder script.

### Protecting Routes

The `Auth_filter` hook automatically protects all routes except public ones.

**Public routes** (no authentication required):
- `auth/login`
- `auth/okta_login`
- `auth/callback`
- `auth/local_login`
- `welcome`

To make a controller/route public, add it to the `$public_routes` array in `application/hooks/Auth_filter.php`.

### Role-Based Access Control

Check user roles in your controllers:

```php
public function admin_only() {
    $this->load->library('role_mapper');
    
    $user_roles = $this->session->userdata('roles');
    
    if (!$this->role_mapper->has_role($user_roles, 'admin')) {
        redirect('auth/access_denied');
    }
    
    // Admin-only code here
}
```

Check for multiple roles:

```php
$allowed_roles = array('admin', 'user');
if (!$this->role_mapper->has_any_role($user_roles, $allowed_roles)) {
    redirect('auth/access_denied');
}
```

## Architecture

### Components

1. **Controllers**
   - `Auth` - Handles login, logout, and callbacks
   - `Dashboard` - Protected area example

2. **Libraries**
   - `Okta_jwt_verifier` - JWT token validation
   - `Okta_service` - Okta API interactions
   - `Role_mapper` - Group-to-role mapping

3. **Models**
   - `User_model` - User data management

4. **Hooks**
   - `Auth_filter` - Authentication check and session management

5. **Views**
   - `auth/login` - Login page with Okta and local options
   - `auth/access_denied` - Access denied page
   - `dashboard/index` - Dashboard example

### Database Schema

**users table:**
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- username (VARCHAR(100), UNIQUE)
- email (VARCHAR(255))
- password_hash (VARCHAR(255), NULL for external users)
- full_name (VARCHAR(255))
- user_type (ENUM: 'local', 'external')
- external_id (VARCHAR(255), NULL for local users)
- roles (TEXT, JSON encoded)
- is_active (TINYINT)
- last_login (DATETIME)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### Session Data

The following data is stored in the session:
- `user_id` - Database user ID
- `username` - Username
- `email` - Email address
- `full_name` - Full name
- `user_type` - 'local' or 'external'
- `roles` - Array of user roles
- `primary_role` - Primary role
- `logged_in` - Authentication status
- `login_time` - Login timestamp
- `access_token` - Okta access token (external users only)
- `id_token` - Okta ID token (external users only)
- `refresh_token` - Okta refresh token (external users only)
- `token_expires_at` - Token expiration timestamp (external users only)

## Security Considerations

1. **Use HTTPS in Production**
   - Set `$config['cookie_secure'] = TRUE;` in `config.php`
   - Update Okta redirect URIs to use HTTPS

2. **Environment Variables**
   - Store sensitive configuration in environment variables
   - Never commit credentials to version control

3. **Encryption Key**
   - The encryption key is auto-generated
   - For production, set a fixed key in `config.php`

4. **Session Security**
   - Sessions expire after configured timeout
   - Tokens are automatically refreshed when possible
   - Failed token refresh forces re-authentication

5. **CSRF Protection**
   - State parameter validates OAuth callbacks
   - Nonce parameter validates ID tokens

## Troubleshooting

### Common Issues

**"Failed to retrieve JWKS from Okta"**
- Check that `okta_domain` is correct
- Ensure network connectivity to Okta
- Verify firewall/proxy settings

**"Invalid issuer in JWT token"**
- Verify `okta_auth_server_id` configuration
- Check that Okta authorization server is active

**"State parameter mismatch"**
- Session may have expired during login
- Clear browser cookies and try again
- Check session configuration

**"Failed to create user account"**
- Verify database connectivity
- Check database permissions
- Review application logs

**Session directory not writable**
- Set proper permissions on `application/cache/sessions`
- Ensure web server has write access

### Logging

Check CodeIgniter logs for detailed error information:
- Location: `application/logs/`
- Format: `log-YYYY-MM-DD.php`

## Testing

### Test with Local Users

1. Create a test user in the database
2. Navigate to `/login`
3. Use local credentials

### Test with Okta

1. Configure Okta application
2. Navigate to `/login`
3. Click "Sign in with Okta"
4. Authenticate with Okta credentials

## Production Deployment

### Checklist

- [ ] Update `base_url` in `config.php`
- [ ] Set database credentials
- [ ] Configure Okta with production credentials
- [ ] Enable HTTPS and set `cookie_secure = TRUE`
- [ ] Set fixed `encryption_key`
- [ ] Set `ENVIRONMENT` to `production` in `index.php`
- [ ] Disable error display
- [ ] Set proper file permissions
- [ ] Configure logging
- [ ] Test authentication flows
- [ ] Test role mappings
- [ ] Monitor logs after deployment

## License

This implementation follows the CodeIgniter framework license (MIT).

## Support

For issues related to:
- **Okta Configuration**: Consult Okta documentation
- **CodeIgniter**: Visit CodeIgniter forums
- **Application Code**: Review logs and check configuration

## Version History

- **1.0.0** - Initial implementation
  - Okta OIDC authentication
  - Local database fallback
  - Role mapping
  - JWT verification
  - Session management
