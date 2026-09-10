<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$currentUser = current_user();
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'xVault Enterprise Password Manager') ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
    <script>
        window.CSRF_TOKEN = "<?= $csrfToken ?>";
        window.APP_URL = "<?= APP_URL ?>";
    </script>
</head>
<body>
<div class="app-container">
    <header class="header">
        <div class="logo-container" onclick="location.href='<?= APP_URL ?>/'">
            <div class="logo-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div class="logo-text">
                <span class="x">x</span><span class="vault">Vault</span>
            </div>
        </div>

        <?php if ($currentUser): ?>
        <nav class="nav-links">
            <a href="<?= APP_URL ?>/dashboard" class="nav-link <?= ($currentView ?? '') === 'dashboard' ? 'active' : '' ?>">Home</a>
            <a href="<?= APP_URL ?>/vault" class="nav-link <?= ($currentView ?? '') === 'vault' ? 'active' : '' ?>">My Vault</a>
            <a href="<?= APP_URL ?>/addresses" class="nav-link <?= ($currentView ?? '') === 'addresses' ? 'active' : '' ?>">Addresses</a>
            <a href="<?= APP_URL ?>/notes" class="nav-link <?= ($currentView ?? '') === 'notes' ? 'active' : '' ?>">Secure Notes</a>
            <button type="button" class="nav-link" onclick="openPasswordGenerator()">Generate Password</button>
        </nav>

        <div style="display: flex; items-center; gap: 16px;">
            <div style="position: relative;">
                <button type="button" onclick="document.getElementById('profile-dropdown').classList.toggle('show')" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: #E2E8F0; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #475569;">
                        <?= strtoupper(substr($currentUser['name'] ?? $currentUser['email'], 0, 1)) ?>
                    </div>
                    <span style="font-weight: 600; font-size: 13px; color: #1E293B;"><?= e($currentUser['name']) ?></span>
                </button>
                <div id="profile-dropdown" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 6px; width: 180px; background: #fff; border: 1px solid #E2E8F0; border-radius: 10px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 50;">
                    <a href="<?= APP_URL ?>/profile" style="display: block; padding: 10px 16px; font-size: 13px; color: #334155; border-bottom: 1px solid #F1F5F9;">Manage Profile</a>
                    <button type="button" onclick="handleLogout()" style="width: 100%; text-align: left; background: none; border: none; padding: 10px 16px; font-size: 13px; color: #EF4444; cursor: pointer;">Log Out</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </header>
    <script>
        async function handleLogout() {
            try {
                const res = await fetch('<?= APP_URL ?>/api/auth/logout', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    location.href = '<?= APP_URL ?>/login';
                }
            } catch (err) {
                location.href = '<?= APP_URL ?>/login';
            }
        }
        window.addEventListener('click', function(e) {
            const drop = document.getElementById('profile-dropdown');
            if (drop && !e.target.closest('#profile-dropdown') && !e.target.closest('button[onclick*="profile-dropdown"]')) {
                drop.classList.remove('show');
                drop.style.display = 'none';
            }
            if (e.target.closest('button[onclick*="profile-dropdown"]')) {
                drop.style.display = drop.style.display === 'none' ? 'block' : 'none';
            }
        });
    </script>
