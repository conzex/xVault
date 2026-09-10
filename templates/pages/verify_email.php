<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$pageTitle = 'Verify Email - xVault';

$token = $_GET['token'] ?? '';
$error = null;
$success = null;

if (!empty($token)) {
    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE verification_token = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $up = $db->prepare('UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?');
        $up->execute([$user['id']]);
        $success = 'Your email address has been successfully verified! You can now log in to your vault.';
    } else {
        $error = 'Invalid or expired verification token.';
    }
} else {
    $error = 'No verification token provided.';
}

require __DIR__ . '/../header.php';
?>
<div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 16px;">
    <div class="card" style="width: 100%; max-width: 440px; padding: 36px; text-align: center;">
        <div class="logo-container" style="justify-content: center; margin-bottom: 20px;">
            <div class="logo-icon" style="width: 42px; height: 42px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </div>
            <div class="logo-text" style="font-size: 28px;">
                <span class="x">x</span><span class="vault">Vault</span>
            </div>
        </div>

        <?php if ($success): ?>
            <div style="background: #DCFCE7; border: 1px solid #86EFAC; color: #166534; padding: 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
                ✓ <?= e($success) ?>
            </div>
            <a href="<?= APP_URL ?>/login" class="btn btn-primary btn-full">Go to Login</a>
        <?php else: ?>
            <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
                ✕ <?= e($error) ?>
            </div>
            <a href="<?= APP_URL ?>/login" class="btn btn-secondary btn-full">Back to Login</a>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../footer.php'; ?>
