# Setup Checklist

Use this checklist to ensure your Okta integration is properly configured.

## ☐ Step 1: Dependencies
- [ ] Run `composer install` successfully
- [ ] Verify `vendor/` directory exists
- [ ] Check that `firebase/php-jwt` is installed
- [ ] Check that `guzzlehttp/guzzle` is installed

## ☐ Step 2: Database Setup
- [ ] Create database: `CREATE DATABASE okta_app;`
- [ ] Update database credentials in `application/config/database.php`:
  - [ ] hostname
  - [ ] username
  - [ ] password
  - [ ] database name
- [ ] Test database connection
- [ ] Verify `users` table is created automatically on first run

## ☐ Step 3: Okta Application Setup
- [ ] Log in to Okta Admin Console
- [ ] Create new Web Application
- [ ] Note down Client ID
- [ ] Note down Client Secret
- [ ] Configure Sign-in redirect URIs
- [ ] Configure Sign-out redirect URIs
- [ ] Assign users/groups to application

## ☐ Step 4: Application Configuration
- [ ] Update `application/config/okta.php`:
  - [ ] Set `okta_domain`
  - [ ] Set `okta_client_id`
  - [ ] Set `okta_client_secret`
  - [ ] Update `okta_redirect_uri`
- [ ] Update `application/config/config.php`:
  - [ ] Set correct `base_url`
- [ ] Verify `application/cache/sessions/` directory exists
- [ ] Verify `application/cache/sessions/` is writable

## ☐ Step 5: Role Mapping (Optional)
- [ ] Identify Okta groups to map
- [ ] Update role mapping in `application/config/okta.php`
- [ ] Test role assignments

## ☐ Step 6: Create Local Admin (Optional)
- [ ] Run `php create_admin.php`
- [ ] Note down admin credentials
- [ ] Delete `create_admin.php` file
- [ ] Test local login with admin account

## ☐ Step 7: Testing
- [ ] Navigate to application URL
- [ ] Verify login page loads correctly
- [ ] Test Okta login:
  - [ ] Click "Sign in with Okta"
  - [ ] Authenticate with Okta
  - [ ] Verify redirect back to application
  - [ ] Verify user is logged in
  - [ ] Check dashboard displays user info
- [ ] Test local login (if enabled):
  - [ ] Enter local credentials
  - [ ] Verify login succeeds
  - [ ] Check dashboard displays user info
- [ ] Test logout:
  - [ ] Click logout
  - [ ] Verify redirect to login page
  - [ ] Verify cannot access protected pages
- [ ] Test protected routes:
  - [ ] Try accessing dashboard without login
  - [ ] Verify redirect to login page

## ☐ Step 8: Security Verification
- [ ] Check session directory permissions
- [ ] Verify `.htaccess` in session directory
- [ ] Test session timeout
- [ ] Verify CSRF protection (state parameter)
- [ ] Check error logs for any issues
- [ ] Verify sensitive files not in version control

## ☐ Step 9: Documentation Review
- [ ] Read `OKTA_INTEGRATION.md`
- [ ] Read `QUICKSTART.md`
- [ ] Read `DEVELOPER_NOTES.md`
- [ ] Review `IMPLEMENTATION_SUMMARY.md`

## ☐ Step 10: Production Preparation (When Ready)
- [ ] Change `ENVIRONMENT` to `'production'` in `index.php`
- [ ] Set `base_url` to production URL
- [ ] Update Okta redirect URIs to production URLs
- [ ] Use environment variables for secrets
- [ ] Enable HTTPS
- [ ] Set `$config['cookie_secure'] = TRUE;`
- [ ] Disable error display
- [ ] Configure proper logging
- [ ] Set appropriate session timeout
- [ ] Test all authentication flows in production
- [ ] Set up monitoring/alerting
- [ ] Create backup strategy
- [ ] Document production configuration

## Troubleshooting

### Database Issues
- [ ] MySQL/MariaDB running?
- [ ] Database exists?
- [ ] Credentials correct?
- [ ] User has proper permissions?

### Okta Issues
- [ ] Okta domain correct?
- [ ] Client ID/Secret correct?
- [ ] Redirect URIs match exactly?
- [ ] Users assigned to Okta application?
- [ ] Network connectivity to Okta?

### Session Issues
- [ ] Session directory exists?
- [ ] Session directory writable?
- [ ] `.htaccess` file in session directory?
- [ ] Session configuration correct?

### Authentication Issues
- [ ] Check `application/logs/` for errors
- [ ] Verify hooks are enabled
- [ ] Check base_url configuration
- [ ] Test with browser console open
- [ ] Clear browser cache/cookies

## Configuration File Checklist

### application/config/okta.php
```php
✓ okta_domain
✓ okta_client_id
✓ okta_client_secret
✓ okta_redirect_uri
✓ okta_role_mapping (if using)
```

### application/config/config.php
```php
✓ base_url
✓ enable_hooks = TRUE
✓ composer_autoload path
✓ sess_save_path
✓ encryption_key (auto-generated)
```

### application/config/database.php
```php
✓ hostname
✓ username
✓ password
✓ database
```

### application/config/autoload.php
```php
✓ libraries: database, session
✓ helpers: url, security
```

### application/config/routes.php
```php
✓ default_controller = 'auth/login'
✓ auth routes configured
✓ dashboard routes configured
```

## Support Resources

- **Logs**: `application/logs/log-YYYY-MM-DD.php`
- **Okta Docs**: https://developer.okta.com/docs/
- **JWT Debugger**: https://jwt.io/
- **CodeIgniter Docs**: https://codeigniter.com/userguide3/

## Sign Off

Once all items are checked, your Okta integration is ready!

- [ ] Development environment fully configured and tested
- [ ] Documentation reviewed
- [ ] Team trained on new authentication flow
- [ ] Production deployment plan created
- [ ] Rollback plan documented

**Configured by**: _________________
**Date**: _________________
**Environment**: [ ] Development [ ] Staging [ ] Production
