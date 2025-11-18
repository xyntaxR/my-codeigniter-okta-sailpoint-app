<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
        }
        
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            color: #333;
        }
        
        .user-role {
            font-size: 12px;
            color: #666;
        }
        
        .btn-logout {
            padding: 8px 20px;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        
        .btn-logout:hover {
            background-color: #c0392b;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-bottom: 30px;
        }
        
        .welcome-card h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .welcome-card p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .info-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .info-card h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .badge-okta {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-local {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }
        
        .badge-role {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="navbar-brand">My Application</div>
            <div class="navbar-user">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></div>
                    <div class="user-role"><?php echo ucfirst(htmlspecialchars($user['primary_role'])); ?></div>
                </div>
                <a href="<?php echo site_url('auth/logout'); ?>" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="welcome-card">
            <h1>Welcome to Your Dashboard!</h1>
            <p>You have successfully authenticated. This is a protected area that requires valid authentication to access.</p>
        </div>
        
        <div class="info-grid">
            <div class="info-card">
                <h3>User Information</h3>
                
                <div class="info-item">
                    <div class="info-label">Username</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['full_name'] ?: 'Not provided'); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Authentication Type</div>
                    <div class="info-value">
                        <?php if ($user['user_type'] === 'external'): ?>
                            <span class="badge badge-okta">Okta (External)</span>
                        <?php else: ?>
                            <span class="badge badge-local">Local Database</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="info-card">
                <h3>Roles & Permissions</h3>
                
                <div class="info-item">
                    <div class="info-label">Primary Role</div>
                    <div class="info-value"><?php echo ucfirst(htmlspecialchars($user['primary_role'])); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">All Roles</div>
                    <div class="info-value">
                        <?php if (!empty($user['roles'])): ?>
                            <?php foreach ($user['roles'] as $role): ?>
                                <span class="badge badge-role"><?php echo ucfirst(htmlspecialchars($role)); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            No roles assigned
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Access Level</div>
                    <div class="info-value">
                        <?php
                        $access_level = 'Standard';
                        if (in_array('admin', $user['roles'])) {
                            $access_level = 'Administrator';
                        } elseif (in_array('viewer', $user['roles'])) {
                            $access_level = 'Read-Only';
                        }
                        echo $access_level;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
