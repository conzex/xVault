<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$pageTitle = 'Reset Password - xVault';

$token = $_GET['token'] ?? '';
$error = null;
$user = null;

if (!empty($token)) {
    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > ?');
    $stmt->execute([$token, date('Y-m-d H:i:s')]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'Invalid or expired password reset token.';
    }
} else {
    $error = 'No reset token provided.';
}

require __DIR__ . '/../header.php';
?>
<div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 16px;">
    <div class="card" style="width: 100%; max-width: 440px; padding: 36px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="logo-container" style="justify-content: center; margin-bottom: 12px;">
                <div class="logo-icon" style="width: 42px; height: 42px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </div>
                <div class="logo-text" style="font-size: 28px;">
                    <span class="x">x</span><span class="vault">Vault</span>
                </div>
            </div>
            <h3 style="font-size: 18px; font-weight: 700; color: #0F172A;">Reset Master Password</h3>
        </div>
        <?php if ($error): ?>
            <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span><?= e($error) ?></span>
            </div>
            <a href="<?= APP_URL ?>/login" class="btn btn-secondary btn-full">Back to Login</a>
        <?php else: ?>
            <form id="form-reset-password" onsubmit="handleResetPasswordSubmit(event)">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="form-group">
                    <label class="form-label">New Master Password</label>
                    <input type="password" name="password" class="form-control" minlength="6" placeholder="Enter new master password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full" style="padding: 12px; margin-top: 8px;">Update Master Password</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
async function handleResetPasswordSubmit(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    try {
        const res = await fetch('<?= APP_URL ?>/api/auth/reset-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (res.ok && result.success) {
            toast.success('Password updated successfully!');
            setTimeout(() => location.href = '<?= APP_URL ?>/login', 800);
        } else {
            toast.error(result.error || 'Password reset failed');
        }
    } catch (err) {
        toast.error('Network error');
    }
}
</script>
<?php require __DIR__ . '/../footer.php'; ?>
