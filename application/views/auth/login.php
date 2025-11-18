<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Application</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }
        
        .alert-success {
            background-color: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }
        
        .okta-login-section {
            margin-bottom: 30px;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
        }
        
        .btn-okta {
            background-color: #007dc1;
            color: white;
        }
        
        .btn-okta:hover {
            background-color: #006ba1;
        }
        
        .btn-local {
            background-color: #667eea;
            color: white;
        }
        
        .btn-local:hover {
            background-color: #5568d3;
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #ddd;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #999;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-error {
            color: #c33;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .local-login-section {
            display: none;
        }
        
        .local-login-section.active {
            display: block;
        }
        
        .toggle-login {
            text-align: center;
            margin-top: 20px;
        }
        
        .toggle-login a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .toggle-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Welcome</h1>
            <p>Sign in to your account</p>
        </div>
        
        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-error">
                <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success">
                <?php echo $this->session->flashdata('success'); ?>
            </div>
        <?php endif; ?>
        
        <?php if (validation_errors()): ?>
            <div class="alert alert-error">
                <?php echo validation_errors(); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($okta_enabled): ?>
            <div class="okta-login-section">
                <a href="<?php echo site_url('auth/okta_login'); ?>" class="btn btn-okta">
                    Sign in with Okta
                </a>
            </div>
        <?php endif; ?>
        
        <?php if ($okta_enabled && $local_fallback): ?>
            <div class="divider">
                <span>OR</span>
            </div>
        <?php endif; ?>
        
        <?php if ($local_fallback): ?>
            <div class="local-login-section <?php echo !$okta_enabled ? 'active' : ''; ?>" id="localLoginForm">
                <?php echo form_open('auth/local_login'); ?>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo set_value('username'); ?>" 
                               required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-local">
                        Sign in with Local Account
                    </button>
                <?php echo form_close(); ?>
            </div>
            
            <?php if ($okta_enabled): ?>
                <div class="toggle-login">
                    <a href="#" onclick="toggleLoginForm(); return false;" id="toggleLink">
                        Use local account instead
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php if ($okta_enabled && $local_fallback): ?>
    <script>
        function toggleLoginForm() {
            const localForm = document.getElementById('localLoginForm');
            const oktaSection = document.querySelector('.okta-login-section');
            const divider = document.querySelector('.divider');
            const toggleLink = document.getElementById('toggleLink');
            
            if (localForm.classList.contains('active')) {
                localForm.classList.remove('active');
                oktaSection.style.display = 'block';
                divider.style.display = 'block';
                toggleLink.textContent = 'Use local account instead';
            } else {
                localForm.classList.add('active');
                oktaSection.style.display = 'none';
                divider.style.display = 'none';
                toggleLink.textContent = 'Use Okta instead';
            }
        }
    </script>
    <?php endif; ?>
</body>
</html>
