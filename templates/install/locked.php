<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>xVault - Installation Locked</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
    <style>
        body {
            background-color: #F8FAFC;
            color: #1E293B;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            margin: 0;
        }

        .locked-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            width: 100%;
            max-width: 540px;
            padding: 40px 32px;
            text-align: center;
        }

        .locked-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #FEE2E2;
            color: #DC2626;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
        }

        .locked-card h1 {
            font-size: 22px;
            font-weight: 800;
            margin: 0 0 10px 0;
            color: #0F172A;
        }

        .locked-card p {
            font-size: 14px;
            color: #64748B;
            line-height: 1.6;
            margin: 0 0 28px 0;
        }

        .btn-locked {
            display: inline-block;
            background: var(--color-brand-red, #D32F2F);
            color: #FFFFFF;
            font-weight: 700;
            font-size: 14px;
            padding: 12px 32px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.2s ease;
        }

        .btn-locked:hover {
            background: #B71C1C;
        }
    </style>
</head>
<body>

<div class="locked-card">
    <div class="locked-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
    </div>
    <h1>Application Already Installed</h1>
    <p>
        xVault has already been successfully installed and configured on this system.
        For security reasons, the installation wizard is permanently locked.
    </p>
    <a href="<?= APP_URL ?>/login" class="btn-locked">Go to Login</a>
</div>

</body>
</html>
