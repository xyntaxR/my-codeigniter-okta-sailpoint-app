<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Okta Configuration
|--------------------------------------------------------------------------
|
| Configuration settings for Okta OpenID Connect (OIDC) authentication
|
*/

// Okta Domain (e.g., dev-12345.okta.com)
$config['okta_domain'] = getenv('OKTA_DOMAIN') ?: 'integrator-4569280.okta.com';

// Okta Client ID
$config['okta_client_id'] = getenv('OKTA_CLIENT_ID') ?: '0oaxisl7lmsKan8co697';

// Okta Client Secret
$config['okta_client_secret'] = getenv('OKTA_CLIENT_SECRET') ?: '_rGyxNtu6l597DQheE5lc4vQdSe4CCB4FoFsoLpIKePrgv44GbY8LHlGQ8ZKEhHQ';

// Okta Authorization Server ID (use 'default' for the default authorization server)
$config['okta_auth_server_id'] = getenv('OKTA_AUTH_SERVER_ID') ?: 'default';

// Redirect URI after authentication
$config['okta_redirect_uri'] = getenv('OKTA_REDIRECT_URI') ?: 'http://localhost/my-codeigniter-okta-app/index.php/auth/callback';

// Post logout redirect URI (where to redirect after logout)
$config['okta_post_logout_redirect_uri'] = getenv('OKTA_POST_LOGOUT_REDIRECT_URI') ?: 'http://localhost/my-codeigniter-okta-app/';

// Scopes to request
$config['okta_scopes'] = array('openid', 'profile', 'email');

// Enable/Disable Okta authentication
$config['okta_enabled'] = TRUE;

// Enable local database fallback authentication
$config['okta_local_fallback'] = TRUE;

// Session timeout in seconds (default: 3600 = 1 hour)
$config['okta_session_timeout'] = 3600;

// Token validation settings
$config['okta_leeway'] = 120; // Allow 120 seconds clock skew

// Role mapping from Okta groups to application roles
// Map Okta group names to your application's role names
$config['okta_role_mapping'] = array(
    'Admin' => 'admin',
    'User' => 'user',
    'Viewer' => 'viewer'
);

// Default role for users without a mapped group
$config['okta_default_role'] = 'user';

// Cache user information
$config['okta_cache_user_info'] = TRUE;
$config['okta_cache_duration'] = 900; // 15 minutes
