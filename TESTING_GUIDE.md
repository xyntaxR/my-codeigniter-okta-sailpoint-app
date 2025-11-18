# Okta Authentication Flow Testing Guide

## Testing URLs

1. **Login Page**: http://localhost/my-codeigniter-okta-app/index.php/auth/login
2. **Debug Session**: http://localhost/my-codeigniter-okta-app/index.php/debug/session
3. **Dashboard**: http://localhost/my-codeigniter-okta-app/index.php/dashboard
4. **Test Auth**: http://localhost/my-codeigniter-okta-app/test_auth.php

## Step-by-Step Test Process

### Test 1: Check Initial State
1. Clear your browser cookies/session
2. Visit: http://localhost/my-codeigniter-okta-app/index.php/debug/session
3. **Expected**: Should show `logged_in: false` or `null`

### Test 2: Complete Okta Login Flow
1. Visit: http://localhost/my-codeigniter-okta-app/index.php/auth/login
2. Click "Login with Okta" button
3. Authenticate with your Okta credentials
4. **Expected Behavior**:
   - Okta redirects to: `http://localhost/my-codeigniter-okta-app/index.php/auth/callback?code=...`
   - System processes callback
   - **Should redirect to dashboard automatically**
5. **If redirected to login page instead**, proceed to Test 3

### Test 3: Check Session After Login
Right after the Okta callback (whether it redirects correctly or not):
1. Visit: http://localhost/my-codeigniter-okta-app/index.php/debug/session
2. **Check if**:
   - `logged_in` is `true`
   - `username`, `email`, `full_name` are populated
   - `user_type` is `external`
   - `roles` array is present

### Test 4: Check Logs
After attempting Okta login, check the log file:
```powershell
Get-Content "c:\xampp\htdocs\my-codeigniter-okta-app\application\logs\log-2025-11-18.php" -Tail 100
```

**Look for these log entries**:
- `User [username] logged in via Okta - redirecting to dashboard`
- `Session created. logged_in status: TRUE`
- `Auth_filter: User is authenticated, allowing access to dashboard/index`

### Test 5: Manual Dashboard Access
If login redirects to wrong page but session is created:
1. Manually visit: http://localhost/my-codeigniter-okta-app/index.php/dashboard
2. **Expected**: Should display dashboard with your user info

## Common Issues and Solutions

### Issue 1: Redirects to login page after Okta callback
**Cause**: Session not persisting
**Solution**: Check session save path permissions
```powershell
icacls "c:\xampp\htdocs\my-codeigniter-okta-app\application\cache\sessions" /grant Everyone:F
```

### Issue 2: Session shows logged_in=false after callback
**Cause**: Session data not being saved
**Check**:
1. Session directory writable
2. No PHP errors in logs
3. Session configuration in config.php

### Issue 3: Infinite redirect loop
**Cause**: Session not being read properly
**Check**: Cookie settings in browser

## Debug Checklist

- [ ] XAMPP/Apache is running
- [ ] MySQL is running and `okta_app` database exists
- [ ] Session directory exists and is writable
- [ ] Okta credentials are correct in `config/okta.php`
- [ ] Redirect URI in Okta matches callback URL
- [ ] No PHP errors in application logs
- [ ] Browser cookies are enabled
- [ ] Session is being created (check debug/session page)

## What Should Happen (Correct Flow)

```
User clicks "Login with Okta"
    ↓
Redirected to Okta login page
    ↓
User enters Okta credentials
    ↓
Okta redirects to: /auth/callback?code=xxx&state=yyy
    ↓
Callback processes:
  - Validates state
  - Exchanges code for tokens
  - Verifies ID token
  - Creates/updates user in database
  - Creates session with logged_in=TRUE
  - Clears temp data
    ↓
Redirects to /dashboard
    ↓
Auth_filter hook checks:
  - Route is /dashboard/index
  - Checks logged_in session variable
  - logged_in=TRUE, so allows access
    ↓
Dashboard displays user information
```

## Quick Test Commands

```powershell
# Check if Apache is running
Get-Process -Name httpd

# Check session files
Get-ChildItem "c:\xampp\htdocs\my-codeigniter-okta-app\application\cache\sessions"

# View recent logs
Get-Content "c:\xampp\htdocs\my-codeigniter-okta-app\application\logs\log-2025-11-18.php" -Tail 50

# Check database users
& "C:\xampp\mysql\bin\mysql.exe" -u root okta_app -e "SELECT id, username, email, user_type FROM users;"
```

## Next Steps After Testing

Once you complete the tests above, report back with:
1. Which test failed (if any)
2. What you see in the debug/session page after Okta login
3. Any error messages in the logs
4. Where the browser redirects you after Okta callback

This will help identify the exact issue!
