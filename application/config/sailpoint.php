<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| SailPoint Identity Governance Configuration
|--------------------------------------------------------------------------
|
| Configuration settings for SailPoint IdentityIQ/IdentityNow API integration
| SailPoint is used for Identity Governance & Administration (IGA):
| - Access certifications and reviews
| - Entitlement management
| - Role-based access control
| - Compliance and audit
|
| Note: Authentication is handled by Okta. SailPoint provides governance data.
|
*/

// SailPoint Domain (e.g., your-tenant.identityiq.com or your-tenant.api.identitynow.com)
$config['sailpoint_domain'] = $_ENV['SAILPOINT_DOMAIN'];

// SailPoint API Client ID (for API access, not OAuth login)
$config['sailpoint_client_id'] = $_ENV['SAILPOINT_CLIENT_ID'];

// SailPoint API Client Secret (for API access, not OAuth login)
$config['sailpoint_client_secret'] = $_ENV['SAILPOINT_CLIENT_SECRET'];

// SailPoint API Version (v3 is the current stable version for IdentityNow)
$config['sailpoint_api_version'] = $_ENV['SAILPOINT_API_VERSION'];

// SailPoint API Base URL
$config['sailpoint_api_base_url'] = 'https://' . ($config['sailpoint_domain'] ?? 'your-tenant.api.identitynow.com') . '/v3';

// Alternative: For IdentityIQ (on-premise), use:
// $config['sailpoint_api_base_url'] = 'https://' . ($config['sailpoint_domain'] ?? 'your-server.com') . '/identityiq/rest';

// OAuth Token endpoint (for API authentication, not user login)
$config['sailpoint_token_url'] = 'https://' . ($config['sailpoint_domain'] ?? 'your-tenant.api.identitynow.com') . '/oauth/token';

// Enable/Disable SailPoint governance integration
$config['sailpoint_enabled'] = FALSE; // Set to TRUE when configured

// Authentication method for SailPoint API
// Options: 'client_credentials', 'personal_access_token'
$config['sailpoint_auth_method'] = 'client_credentials';

// Personal Access Token (alternative to client credentials)
$config['sailpoint_access_token'] = $_ENV['SAILPOINT_ACCESS_TOKEN'] ?? '';

// Cache settings for API responses
$config['sailpoint_cache_enabled'] = TRUE;
$config['sailpoint_cache_ttl'] = 300; // Cache API responses for 5 minutes

// HTTP request timeout (in seconds)
$config['sailpoint_timeout'] = 30;

// Retry settings for API calls
$config['sailpoint_retry_attempts'] = 3;
$config['sailpoint_retry_delay'] = 1; // seconds

// Role/Entitlement mapping from SailPoint to application roles
// Map SailPoint role/entitlement names to your application's role names
$config['sailpoint_role_mapping'] = array(
    // SailPoint Role Name => Application Role
    'IT-Admin' => 'admin',
    'IT-Administrator' => 'admin',
    'Application-Admin' => 'admin',
    'Manager' => 'manager',
    'Standard-User' => 'user',
    'Employee' => 'user',
    'default' => 'user'
);

// Default role if no role mapping found
$config['sailpoint_default_role'] = 'user';

// Identity attribute mapping
// Map SailPoint identity attributes to application user fields
$config['sailpoint_attribute_mapping'] = array(
    'id' => 'external_id',
    'name' => 'full_name',
    'email' => 'email',
    'displayName' => 'display_name',
    'firstName' => 'first_name',
    'lastName' => 'last_name',
    'department' => 'department',
    'manager' => 'manager_id'
);

// Entitlement sources to check for roles
// Can be: 'roles', 'entitlements', 'access', 'all'
$config['sailpoint_role_source'] = 'all';

// Specific entitlement/application names to check (optional)
// Leave empty to check all, or specify application names
$config['sailpoint_target_applications'] = array();
// Example: array('Active Directory', 'Salesforce', 'Your App Name')

// Enable debug logging
$config['sailpoint_debug'] = FALSE;

// Sync settings - how often to refresh user data from SailPoint
$config['sailpoint_sync_on_login'] = TRUE;  // Refresh user data on every login
$config['sailpoint_sync_interval'] = 3600;  // Sync interval in seconds (1 hour)
