# SailPoint Identity Governance Integration Guide

This guide explains how to integrate SailPoint IdentityIQ/IdentityNow for identity governance and administration (IGA) with your CodeIgniter application.

## Overview

The application uses a **layered approach** to authentication and authorization:

### Authentication Layer (Okta)
- **Okta SSO** - Handles user authentication (WHO the user is)
- **Local Authentication** - Optional database-backed fallback

### Governance Layer (SailPoint)
- **SailPoint IGA** - Provides access governance data (WHAT access the user has)
- Retrieves user entitlements, roles, and access profiles
- Maps SailPoint roles to application permissions
- Syncs user attributes from identity governance system

## Architecture

```
User Login Flow:
┌──────────┐
│  User    │
└────┬─────┘
     │
     ▼
┌──────────────────┐
│  Okta Login      │  ← Authentication
│  (WHO are you?)  │
└────┬─────────────┘
     │ Success
     ▼
┌──────────────────┐
│  SailPoint API   │  ← Authorization/Governance
│  (WHAT access?)  │
│  - Get roles     │
│  - Get entitle   │
│  - Get profiles  │
└────┬─────────────┘
     │
     ▼
┌──────────────────┐
│  Application     │
│  - Create user   │
│  - Set roles     │
│  - Grant access  │
└──────────────────┘
```

## SailPoint API Configuration

### 1. Create API Client in SailPoint

You need API credentials to query SailPoint for governance data.

#### For SailPoint IdentityNow:
1. Log in to your IdentityNow admin console
2. Navigate to **Admin** → **API Management**
3. Click **Create New** (OAuth Client)
4. Configure:
   - **Name**: CodeIgniter Application API Client
   - **Description**: API access for user governance data
   - **Grant Type**: `client_credentials` (for machine-to-machine API access)
   - **Scopes**: Select necessary API scopes:
     - `sp:scopes:all` (full access) OR specific scopes:
     - `idn:identity:read`
     - `idn:entitlement:read`
     - `idn:role:read`
     - `idn:access-profile:read`
5. Save and copy:
   - **Client ID**
   - **Client Secret**

#### For SailPoint IdentityIQ:
1. Log in to IdentityIQ as administrator
2. Navigate to **Gear Icon** → **Global Settings** → **API Access**
3. Create new API credentials:
   - **Type**: OAuth Client or Personal Access Token
   - **Grant Type**: `client_credentials`
   - **Permissions**: Read access to identities, roles, and entitlements
4. Copy the credentials

### 2. Application Configuration

Edit `application/config/sailpoint.php`:

```php
// Your SailPoint tenant domain
$config['sailpoint_domain'] = 'your-tenant.api.identitynow.com';

// API Client credentials from SailPoint (for governance queries)
$config['sailpoint_client_id'] = 'your_api_client_id';
$config['sailpoint_client_secret'] = 'your_api_client_secret';

// API authentication method
$config['sailpoint_auth_method'] = 'client_credentials';

// Enable SailPoint governance integration
$config['sailpoint_enabled'] = TRUE;

// Sync user data from SailPoint on every login
$config['sailpoint_sync_on_login'] = TRUE;
```

### 3. Role Mapping Configuration

Map SailPoint roles/entitlements to your application roles:

```php
$config['sailpoint_role_mapping'] = array(
    'IT-Admin' => 'admin',
    'IT-Administrator' => 'admin',
    'Application-Admin' => 'admin',
    'Manager' => 'manager',
    'Standard-User' => 'user',
    'Employee' => 'user',
    'default' => 'user'
);
```

### 4. Environment Variables (Recommended)

For better security, use environment variables:

```bash
SAILPOINT_DOMAIN=your-tenant.api.identitynow.com
SAILPOINT_CLIENT_ID=your_api_client_id
SAILPOINT_CLIENT_SECRET=your_api_client_secret
```

## Files Modified

**New Files:**
- `application/config/sailpoint.php` - SailPoint API configuration
- `application/libraries/Sailpoint_service.php` - SailPoint governance service

**Modified Files:**
- `application/controllers/Auth.php` - Added SailPoint governance sync
- `application/config/autoload.php` - Auto-load SailPoint config

### Authentication & Governance Flow

1. **User Initiates Login**
   - User clicks "Sign in with Okta" button
   - System calls `Auth::okta_login()`

2. **Okta Authentication**
   - User is redirected to Okta login page
   - User enters credentials and authenticates
   - Okta redirects back with authorization code

3. **Token Exchange & Validation**
   - System exchanges code for tokens
   - ID token is verified
   - Basic user info extracted from Okta (username, email, name)

4. **SailPoint Governance Sync** (NEW)
   - System queries SailPoint API using user's email
   - `Sailpoint_service::get_user_access_data($email)` retrieves:
     - Identity data
     - Assigned roles
     - Entitlements
     - Access profiles
   - System maps SailPoint roles to application roles
   - Additional user attributes synced (department, manager, etc.)

5. **Local User Creation/Update**
   - User record created or updated in local database
   - Roles from SailPoint override Okta groups (if configured)
   - SailPoint attributes merged with Okta data

6. **Session Creation**
   - User session created with merged data
   - Auth provider marked as 'okta'
   - User redirected to dashboard

### Data Flow Diagram

```
┌──────────────┐
│  Okta Login  │
└──────┬───────┘
       │
       ▼
┌──────────────────┐
│ Get Okta Tokens  │
│  - ID Token      │
│  - Access Token  │
└──────┬───────────┘
       │
       ▼
┌───────────────────────────┐
│ Extract Okta User Data    │
│  - email: john@corp.com   │
│  - name: John Doe         │
│  - groups: [Sales, User]  │
└───────┬───────────────────┘
        │
        ▼
┌───────────────────────────┐
│ Query SailPoint API       │
│  GET /identities?email=   │
└───────┬───────────────────┘
        │
        ▼
┌───────────────────────────┐
│ SailPoint Returns:        │
│  - id: abc123             │
│  - department: Sales      │
│  - manager: Jane Smith    │
│  - roles: [Manager]       │
│  - entitlements: [...]    │
└───────┬───────────────────┘
        │
        ▼
┌───────────────────────────┐
│ Map Roles                 │
│  SailPoint "Manager"      │
│  →  App Role "manager"    │
└───────┬───────────────────┘
        │
        ▼
┌───────────────────────────┐
│ Create/Update User        │
│  - Okta data + SailPoint  │
│  - Role: manager          │
│  - Department: Sales      │
└───────┬───────────────────┘
        │
        ▼
┌───────────────────────────┐
│ User Logged In            │
└───────────────────────────┘
```

## API Endpoints

### Authentication Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/auth/okta_login` | GET | Initiates Okta OAuth flow (primary authentication) |
| `/auth/callback` | GET | Handles OAuth callback from Okta |
| `/auth/local_login` | POST | Handles local username/password login |
| `/auth/logout` | GET | Logs out and redirects to Okta (if applicable) |

### SailPoint Integration Points

SailPoint is called automatically during the Okta authentication flow:
- After successful Okta authentication
- Before user session is created
- During user data sync operations

## Role Mapping

SailPoint roles/entitlements are mapped to application roles using the configuration in `sailpoint.php`:

```php
$config['sailpoint_role_mapping'] = array(
    'Administrator' => 'admin',      // SailPoint role => App role
    'User' => 'user',
    'Manager' => 'manager',
    'default' => 'user'              // Default if no mapping found
);
```

### How Role Mapping Works

1. User authenticates via SailPoint
2. System retrieves user's roles/entitlements from SailPoint
3. Roles are compared against `sailpoint_role_mapping`
4. First matching role is assigned to user
5. If no match, `sailpoint_default_role` is assigned

## Session Management

### Session Data Structure

When a user logs in via SailPoint, the following session data is stored:

```php
array(
    'user_id' => 123,
    'username' => 'john.doe',
    'email' => 'john.doe@company.com',
    'full_name' => 'John Doe',
    'user_type' => 'external',
    'roles' => 'admin',
    'primary_role' => 'admin',
    'auth_provider' => 'sailpoint',   // Identifies authentication provider
    'access_token' => 'eyJ...',        // SailPoint access token
    'id_token' => 'eyJ...',            // ID token (if available)
    'logged_in' => TRUE,
    'login_time' => 1637123456
)
```

### Dual Provider Support

The application differentiates between providers using the `auth_provider` session variable:
- `'okta'` - User authenticated via Okta
- `'sailpoint'` - User authenticated via SailPoint  
- `'local'` - User authenticated locally

This enables proper logout handling, token refresh, and provider-specific operations.

## Security Features

### 1. CSRF Protection
- State parameter generated and validated for each OAuth flow
- Prevents cross-site request forgery attacks

### 2. Token Validation
- ID token signature verification (when using ID tokens)
- Nonce validation to prevent replay attacks
- Token expiration checking

### 3. Session Security
- Session regeneration on login
- Secure token storage
- Configurable session timeout

### 4. HTTPS Enforcement
For production, ensure:
- All OAuth redirect URIs use HTTPS
- SailPoint domain uses HTTPS
- Configure your web server for SSL/TLS

## Testing the Integration

### 1. Enable SailPoint in Configuration

```php
// application/config/sailpoint.php
$config['sailpoint_enabled'] = TRUE;
```

### 2. Test Login Flow

1. Navigate to login page
2. Click "Sign in with SailPoint"
3. Enter SailPoint credentials
4. Verify redirect back to application
5. Check dashboard access

### 3. Verify Session Data

Add to `Dashboard.php`:

```php
public function debug_session() {
    echo '<pre>';
    print_r($this->session->userdata());
    echo '</pre>';
}
```

### 4. Test Logout

1. Click logout
2. Verify redirect to SailPoint logout (if configured)
3. Verify session is destroyed
4. Attempt to access protected page (should redirect to login)

## Troubleshooting

### Common Issues

#### 1. "Invalid state parameter" Error
**Cause**: State mismatch or session issues
**Solution**: 
- Clear browser cookies/sessions
- Verify session is working properly
- Check that redirect URI matches exactly

#### 2. "Could not obtain tokens" Error
**Cause**: Token exchange failed
**Solution**:
- Verify client ID and secret are correct
- Check SailPoint application configuration
- Review logs: `application/logs/log-YYYY-MM-DD.php`

#### 3. "Could not retrieve user information" Error
**Cause**: User info endpoint failed
**Solution**:
- Verify token is valid
- Check SailPoint API permissions
- Ensure correct scopes are requested

#### 4. User Has No Roles
**Cause**: Role mapping not configured or roles not returned by SailPoint
**Solution**:
- Verify role mapping in `sailpoint.php`
- Check that user has roles assigned in SailPoint
- Review `sailpoint_default_role` setting

### Debug Logging

Enable debug logging in `sailpoint.php`:

```php
$config['sailpoint_debug'] = TRUE;
```

Check logs at: `application/logs/log-YYYY-MM-DD.php`

## Production Deployment

### Pre-Deployment Checklist

- [ ] Update `sailpoint_domain` to production tenant
- [ ] Configure production redirect URIs in SailPoint
- [ ] Store secrets in environment variables, not in config files
- [ ] Enable HTTPS for all endpoints
- [ ] Set `sailpoint_debug` to `FALSE`
- [ ] Configure appropriate session timeout
- [ ] Test role mappings with production roles
- [ ] Verify logout flow works correctly
- [ ] Test token refresh (if implemented)
- [ ] Set up monitoring for authentication failures

### Security Best Practices

1. **Never commit secrets to version control**
   ```bash
   # Add to .gitignore
   application/config/sailpoint.php
   .env
   ```

2. **Use environment variables**
   ```php
   $config['sailpoint_client_secret'] = getenv('SAILPOINT_CLIENT_SECRET');
   ```

3. **Implement token refresh** (if needed for long-running sessions)

4. **Regular security audits**
   - Review authentication logs
   - Monitor failed login attempts
   - Check for unusual activity

5. **Keep dependencies updated**
   ```bash
   composer update
   ```

## Multi-Provider Configuration

### Enabling Both Okta and SailPoint

Both providers can be enabled simultaneously:

```php
// application/config/okta.php
$config['okta_enabled'] = TRUE;

// application/config/sailpoint.php
$config['sailpoint_enabled'] = TRUE;

// Local fallback
$config['okta_local_fallback'] = TRUE;
```

Users will see all three options on the login page:
- Sign in with Okta
- Sign in with SailPoint
- Sign in with Local Account (if enabled)

### Provider Selection Logic

The login page displays buttons based on configuration:
1. If only Okta enabled → Show Okta button only
2. If only SailPoint enabled → Show SailPoint button only
3. If both enabled → Show both buttons
4. If local fallback enabled → Show local login form with toggle

## API Reference

### Sailpoint_service Methods

#### `get_authorization_url()`
Generates OAuth authorization URL for user redirect.

**Returns**: `string` - Authorization URL

#### `exchange_code_for_tokens($code)`
Exchanges authorization code for access and ID tokens.

**Parameters**:
- `$code` (string) - Authorization code from callback

**Returns**: `array|false` - Token array or false on failure

#### `get_user_info($access_token)`
Retrieves user information from SailPoint.

**Parameters**:
- `$access_token` (string) - Access token

**Returns**: `array|false` - User info or false on failure

#### `verify_id_token($id_token)`
Verifies and decodes ID token.

**Parameters**:
- `$id_token` (string) - JWT ID token

**Returns**: `array|false` - Decoded payload or false on failure

#### `get_user_roles($access_token, $user_id)`
Retrieves user's roles/entitlements from SailPoint API.

**Parameters**:
- `$access_token` (string) - Access token
- `$user_id` (string) - User identifier

**Returns**: `array` - Array of role names

#### `map_roles($sailpoint_roles)`
Maps SailPoint roles to application roles.

**Parameters**:
- `$sailpoint_roles` (array) - Array of SailPoint role names

**Returns**: `string` - Mapped application role

#### `get_logout_url()`
Generates logout URL for SailPoint.

**Returns**: `string` - Logout URL

## Support and Resources

### SailPoint Resources
- [SailPoint IdentityNow Documentation](https://documentation.sailpoint.com/)
- [SailPoint OAuth 2.0 Guide](https://developer.sailpoint.com/apis/oauth/)
- [SailPoint API Reference](https://developer.sailpoint.com/idn/api/)

### CodeIgniter Resources
- [CodeIgniter 3 Documentation](https://codeigniter.com/userguide3/)
- [CodeIgniter Security](https://codeigniter.com/userguide3/general/security.html)

## Changelog

### Version 1.0.0 (November 2025)
- Initial SailPoint integration
- Dual provider support (Okta + SailPoint)
- Role mapping functionality
- Session management with provider tracking
- Comprehensive error handling and logging
