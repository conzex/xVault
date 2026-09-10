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
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/favicon.svg">
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/images/favicon.svg">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/images/favicon.svg">
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
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div class="logo-text">
                <span class="x">x</span><span class="vault">Vault</span>
            </div>
        </div>

        <?php if ($currentUser): ?>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="position: relative;">
                <button type="button" onclick="toggleProfileDropdown()" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 8px; border-radius: 20px; padding: 4px 12px 4px 4px;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--color-brand-red-light); color: var(--color-brand-red); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;">
                        <?= strtoupper(substr($currentUser['name'] ?? $currentUser['email'], 0, 1)) ?>
                    </div>
                    <span style="font-weight: 600; font-size: 13px; color: #1E293B;"><?= e($currentUser['name']) ?></span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>

                <div id="profile-dropdown" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 8px; width: 200px; background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); z-index: 50; padding: 6px 0;">
                    <div style="padding: 8px 16px; border-bottom: 1px solid #F1F5F9; font-size: 12px; color: #64748B;">
                        Signed in as <strong style="color: #0F172A; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= e($currentUser['email']) ?></strong>
                    </div>
                    <a href="<?= APP_URL ?>/profile" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; font-size: 13px; color: #334155; transition: background 0.15s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='none'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        <span>Manage Profile</span>
                    </a>
                    <button type="button" onclick="handleLockVault()" style="width: 100%; text-align: left; background: none; border: none; padding: 10px 16px; font-size: 13px; color: #334155; cursor: pointer; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='none'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        <span>Lock Vault</span>
                    </button>
                    <div style="height: 1px; background: #F1F5F9; margin: 4px 0;"></div>
                    <button type="button" onclick="handleLogout()" style="width: 100%; text-align: left; background: none; border: none; padding: 10px 16px; font-size: 13px; color: #EF4444; cursor: pointer; display: flex; align-items: center; gap: 10px; font-weight: 600;" onmouseover="this.style.background='#FEF2F2'" onmouseout="this.style.background='none'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        <span>Log Out</span>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </header>

    <script>
        function toggleProfileDropdown() {
            const drop = document.getElementById('profile-dropdown');
            if (drop) {
                drop.style.display = drop.style.display === 'none' ? 'block' : 'none';
            }
        }
        async function handleLockVault() {
            try {
                await fetch('<?= APP_URL ?>/api/auth/lock', { method: 'POST' });
                toast.info('Vault locked');
                setTimeout(() => location.href = '<?= APP_URL ?>/login', 300);
            } catch (err) {
                location.href = '<?= APP_URL ?>/login';
            }
        }
        async function handleLogout() {
            try {
                await fetch('<?= APP_URL ?>/api/auth/logout', { method: 'POST' });
                toast.info('Logged out');
                setTimeout(() => location.href = '<?= APP_URL ?>/login', 300);
            } catch (err) {
                location.href = '<?= APP_URL ?>/login';
            }
        }
        window.addEventListener('click', function(e) {
            const drop = document.getElementById('profile-dropdown');
            if (drop && !e.target.closest('#profile-dropdown') && !e.target.closest('button[onclick*="toggleProfileDropdown"]')) {
                drop.style.display = 'none';
            }
        });
    </script>
