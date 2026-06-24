<?php
/**
 * Landing page - clean admin-style entry point.
 */
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

if (!masterAdminExists()) {
    header('Location: /setup.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Todo</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="landing-page">
    <div class="landing-container">
        <div class="landing-card">
            <div class="landing-logo">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11l3 3L22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                </div>
                <h1>Admin Todo</h1>
            </div>
            <p class="landing-subtitle">Secure task management dashboard</p>
            <div class="landing-features">
                <div class="feature-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span>Secure Authentication</span>
                </div>
                <div class="feature-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    <span>Priority Management</span>
                </div>
                <div class="feature-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>Team Collaboration</span>
                </div>
            </div>
            <a href="/login.php" class="btn btn-primary btn-lg landing-login-btn">Sign In</a>
        </div>
    </div>
</body>
</html>
