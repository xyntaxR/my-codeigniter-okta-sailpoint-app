# Quick Setup Guide - SailPoint Governance Integration

## What's New

Your CodeIgniter application now uses a **dual-layer approach**:

1. **Okta** - Handles authentication (WHO you are) ✅ Already configured
2. **SailPoint** - Provides governance data (WHAT access you have) ✨ NEW
3. **Local Login** - Optional fallback ✅ Already configured

## How It Works

```
User logs in with Okta → App queries SailPoint API → Gets roles/entitlements → Grants access
```

**Key Point:** Users don't log in to SailPoint directly. They log in via Okta, and the app automatically retrieves their access rights from SailPoint.

## Quick Start (5 minutes)

### Step 1: Get SailPoint API Credentials

In your SailPoint admin console:

**For IdentityNow:**
1. Go to **Admin** → **API Management**
2. Click **Create New** OAuth Client
3. Set grant type: **client_credentials**
4. Select scopes: `idn:identity:read`, `idn:role:read`, `idn:entitlement:read`
5. Copy **Client ID** and **Client Secret**

**For IdentityIQ:**
1. Go to **Gear Icon** → **API Access**
2. Create new API credentials
3. Grant read permissions for identities and roles

### Step 2: Configure SailPoint in Your App

Edit `application/config/sailpoint.php`:

```php
// Your SailPoint tenant domain
$config['sailpoint_domain'] = 'your-tenant.api.identitynow.com';

// API Client ID (for querying user data)
$config['sailpoint_client_id'] = 'your_api_client_id';

// API Client Secret
$config['sailpoint_client_secret'] = 'your_api_client_secret';

// Authentication method for API
$config['sailpoint_auth_method'] = 'client_credentials';

// Enable SailPoint governance
$config['sailpoint_enabled'] = TRUE;

// Sync user data on every login
$config['sailpoint_sync_on_login'] = TRUE;
```

### Step 3: Configure Role Mapping

Map SailPoint roles to your application roles in `sailpoint.php`:

```php
$config['sailpoint_role_mapping'] = array(
    'IT-Admin' => 'admin',           // SailPoint role => App role
    'Manager' => 'manager',
    'Standard-User' => 'user',
    'default' => 'user'
);
```

### Step 4: Test the Integration

1. Open your application: `http://localhost/my-codeigniter-okta-app/`
2. Click **"Sign in with Okta"** (authentication)
3. Enter your Okta credentials
4. **Behind the scenes**: App queries SailPoint for your roles
5. You're logged in with correct permissions from SailPoint!

## Files Created/Modified

### New Files
- ✨ `application/config/sailpoint.php` - SailPoint API configuration
- ✨ `application/libraries/Sailpoint_service.php` - SailPoint governance service
- 📄 `SAILPOINT_INTEGRATION.md` - Complete documentation
- 📄 `SAILPOINT_QUICKSTART.md` - This file

### Modified Files
- 🔧 `application/controllers/Auth.php` - Added SailPoint governance sync
- ⚙️ `application/config/autoload.php` - Auto-load SailPoint config

### Login Page
- No visual changes - users still see "Sign in with Okta" button
- SailPoint works behind the scenes during Okta login

## Configuration Options

### Role Mapping

Map SailPoint roles/entitlements to your app roles in `sailpoint.php`:

```php
$config['sailpoint_role_mapping'] = array(
    'IT-Admin' => 'admin',
    'IT-Administrator' => 'admin',
    'Manager' => 'manager',
    'Standard-User' => 'user',
    'Employee' => 'user',
    'default' => 'user'
);
```

### Sync Settings

```php
// Sync user data from SailPoint on every login
$config['sailpoint_sync_on_login'] = TRUE;

// How often to refresh data (in seconds)
$config['sailpoint_sync_interval'] = 3600;  // 1 hour

// What data to retrieve from SailPoint
$config['sailpoint_role_source'] = 'all';  // 'roles', 'entitlements', 'access', 'all'
```

### Enable/Disable Integration

```php
// application/config/okta.php
$config['okta_enabled'] = TRUE;  // Authentication provider

// application/config/sailpoint.php
$config['sailpoint_enabled'] = TRUE;  // Governance data source

// Local fallback
$config['okta_local_fallback'] = TRUE;  // Fallback authentication
```

## How It Works

### Complete Login Flow

```
1. User clicks "Sign in with Okta"
    ↓
2. User authenticates on Okta
    ↓
3. Okta redirects back with authorization code
    ↓
4. App exchanges code for Okta tokens
    ↓
5. App extracts user email from Okta
    ↓
6. App queries SailPoint API:
   - GET /identities?email=user@company.com
    ↓
7. SailPoint returns:
   - Identity data
   - Roles and entitlements
   - Access profiles
   - Department, manager, etc.
    ↓
8. App maps SailPoint roles to application roles
    ↓
9. User created/updated in database with:
   - Okta authentication data
   - SailPoint governance data
   - Merged attributes
    ↓
10. Session created with provider = 'okta'
    ↓
11. User redirected to dashboard
```

### Session Data

```php
// User authenticated via Okta, authorized via SailPoint
$session = array(
    'user_id' => 123,
    'username' => 'john.doe',
    'email' => 'john.doe@company.com',
    'full_name' => 'John Doe',
    'user_type' => 'external',
    'roles' => 'manager',  // ← From SailPoint
    'auth_provider' => 'okta',  // ← Authentication source
    'department' => 'Sales',  // ← From SailPoint
    'logged_in' => TRUE
);
```

## Troubleshooting

### Issue: Users logging in but don't have correct roles
**Solution**: 
- Check SailPoint role mapping in config
- Verify user has roles assigned in SailPoint
- Check logs: `application/logs/log-YYYY-MM-DD.php`
- Look for "SailPoint role mapped" messages

### Issue: "Could not sync user from SailPoint"
**Solution**:
- Verify SailPoint API credentials are correct
- Check that client has proper API scopes/permissions
- Ensure user exists in SailPoint with same email as Okta
- Review logs for specific API errors

### Issue: SailPoint not being queried
**Solution**: 
- Set `$config['sailpoint_enabled'] = TRUE;` in `sailpoint.php`
- Verify `sailpoint_sync_on_login = TRUE`
- Check logs for "Syncing user data from SailPoint" message

### Issue: API authentication failing
**Solution**:
- Verify `sailpoint_auth_method` is set correctly
- For client_credentials, check Client ID and Secret
- For personal_access_token, check token is valid
- Test API access using Postman or curl

## Testing Checklist

- [ ] Can login with Okta
- [ ] User redirected to dashboard after login
- [ ] Check logs show "Syncing user data from SailPoint"
- [ ] Check logs show "SailPoint role mapped: X => Y"
- [ ] User has correct role from SailPoint
- [ ] User info displays correctly (including SailPoint data)
- [ ] Logout works properly
- [ ] Can still login with local account (if enabled)
- [ ] SailPoint sync respects cache interval
- [ ] Different users get different roles from SailPoint

## Security Notes

### ⚠️ Before Going to Production

1. **Use environment variables for secrets**:
   ```php
   $config['sailpoint_client_secret'] = getenv('SAILPOINT_CLIENT_SECRET');
   ```

2. **Enable HTTPS**:
   - Update all URLs to use `https://`
   - Configure SSL certificate on your server

3. **Update redirect URI**:
   ```php
   $config['sailpoint_redirect_uri'] = 'https://yourdomain.com/auth/sailpoint_callback';
   ```

4. **Add to .gitignore**:
   ```
   application/config/sailpoint.php
   application/config/okta.php
   .env
   ```

5. **Disable debug mode**:
   ```php
   $config['sailpoint_debug'] = FALSE;
   ```

## Need Help?

- 📖 **Full Documentation**: See `SAILPOINT_INTEGRATION.md`
- 📖 **Okta Documentation**: See `OKTA_INTEGRATION.md`
- 🔍 **Debug Logs**: Check `application/logs/log-YYYY-MM-DD.php`
- 🌐 **SailPoint Docs**: https://documentation.sailpoint.com/

## Okta vs SailPoint: What's the Difference?

| Feature | Okta | SailPoint |
|---------|------|-----------|
| **Purpose** | Authentication (IdP) | Identity Governance (IGA) |
| **Function** | Verifies WHO you are | Manages WHAT access you have |
| **Protocol** | OpenID Connect | RESTful API |
| **User Login** | Yes - users log in here | No - background API queries only |
| **Data Provided** | Username, email, basic info | Roles, entitlements, access profiles, certifications |
| **When Used** | During login flow | After authentication, for authorization |
| **Integration Type** | OAuth 2.0 / OIDC flow | API client credentials |
| **Token** | ID Token, Access Token | API Access Token (machine-to-machine) |

## Next Steps

1. ✅ Configure SailPoint credentials
2. ✅ Test SailPoint login
3. ✅ Configure role mappings
4. ✅ Test with different user roles
5. 📄 Update production environment
6. 📄 Train users on new login options
7. 📄 Monitor authentication logs

## Architecture Overview

```
                    ┌─────────────┐
                    │ Login Page  │
                    └──────┬──────┘
                           │
              ┌────────────┴────────────┐
              │                         │
        ┌─────▼─────┐            ┌─────▼─────┐
        │   Okta    │            │   Local   │
        │   Login   │            │   Login   │
        └─────┬─────┘            └─────┬─────┘
              │                        │
              │ Authentication         │
              │                        │
        ┌─────▼──────────────────┐    │
        │  Okta Service          │    │
        │  - Verify user         │    │
        │  - Get ID token        │    │
        │  - Extract email       │    │
        └─────┬──────────────────┘    │
              │                        │
              │ Email: john@corp.com   │
              │                        │
        ┌─────▼──────────────────┐    │
        │  SailPoint Service     │    │
        │  - Query API           │    │
        │  - Get roles           │    │
        │  - Get entitlements    │    │
        │  - Get access data     │    │
        └─────┬──────────────────┘    │
              │                        │
              │ Authorization          │
              │                        │
        ┌─────▼────────────────────────▼─┐
        │  User Model                    │
        │  - Create/update user          │
        │  - Store Okta + SailPoint data │
        │  - Assign application role     │
        └─────┬──────────────────────────┘
              │
        ┌─────▼─────┐
        │  Session  │
        │  Created  │
        └───────────┘
```

## Key Takeaway

**Authentication = Okta** (users log in here)  
**Authorization = SailPoint** (app queries roles/access behind the scenes)

Enjoy your integrated identity governance! 🚀
