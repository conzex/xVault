<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');

$pageTitle = 'Email Verification - xVault Enterprise Password Manager';
$res = $verificationResult ?? [
    'status' => 'invalid',
    'message' => 'No verification token provided.',
    'email' => ''
];

$status = $res['status'] ?? 'invalid';
$message = $res['message'] ?? 'Invalid verification request.';
$email = $res['email'] ?? '';

require __DIR__ . '/../header.php';
?>
<div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 16px;">
    <div class="card" style="width: 100%; max-width: 480px; padding: 36px; text-align: center; background: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        <div class="logo-container" style="justify-content: center; margin-bottom: 24px;">
            <div class="logo-icon" style="width: 44px; height: 44px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div class="logo-text" style="font-size: 28px;">
                <span class="x">x</span><span class="vault">Vault</span>
            </div>
        </div>

        <?php if ($status === 'success'): ?>
            <div style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 20px; border-radius: 10px; margin-bottom: 24px; text-align: left;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                    <div style="width: 28px; height: 28px; background: #10B981; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">✓</div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #065F46;">Email Verified</h3>
                </div>
                <p style="margin: 0; font-size: 14px; line-height: 1.5;"><?= e($message) ?></p>
            </div>
            <a href="<?= APP_URL ?>/login" class="btn btn-primary btn-full" style="padding: 12px; font-size: 15px;">Log In to Your Vault</a>

        <?php elseif ($status === 'already_verified'): ?>
            <div style="background: #EFF6FF; border: 1px solid #BFDBFE; color: #1E40AF; padding: 20px; border-radius: 10px; margin-bottom: 24px; text-align: left;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                    <div style="width: 28px; height: 28px; background: #3B82F6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">ℹ</div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1E40AF;">Account Already Verified</h3>
                </div>
                <p style="margin: 0; font-size: 14px; line-height: 1.5;"><?= e($message) ?></p>
            </div>
            <a href="<?= APP_URL ?>/login" class="btn btn-primary btn-full" style="padding: 12px; font-size: 15px;">Proceed to Login</a>

        <?php elseif ($status === 'expired'): ?>
            <div style="background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; padding: 20px; border-radius: 10px; margin-bottom: 24px; text-align: left;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                    <div style="width: 28px; height: 28px; background: #F59E0B; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">⏰</div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #92400E;">Verification Link Expired</h3>
                </div>
                <p style="margin: 0; font-size: 14px; line-height: 1.5;"><?= e($message) ?></p>
            </div>

            <form id="verify-resend-form" onsubmit="handleResendFromVerifyPage(event)" style="text-align: left;">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Account Email Address</label>
                    <input type="email" name="email" id="resend-email-input" class="form-control" value="<?= e($email) ?>" placeholder="Enter email address" required>
                </div>
                <button type="submit" id="btn-resend-submit" class="btn btn-primary btn-full" style="padding: 12px; font-size: 15px;">Resend Verification Email</button>
            </form>
            <div style="margin-top: 16px;">
                <a href="<?= APP_URL ?>/login" style="font-size: 13px; color: var(--color-brand-red); text-decoration: none; font-weight: 600;">Back to Login</a>
            </div>

        <?php else: ?>
            <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 20px; border-radius: 10px; margin-bottom: 24px; text-align: left;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                    <div style="width: 28px; height: 28px; background: #EF4444; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">✕</div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #991B1B;">Invalid Verification Link</h3>
                </div>
                <p style="margin: 0; font-size: 14px; line-height: 1.5;"><?= e($message) ?></p>
            </div>

            <p style="font-size: 13px; color: #64748B; margin-bottom: 16px; text-align: left;">Enter your registered email address below to receive a new verification email.</p>

            <form id="verify-resend-form" onsubmit="handleResendFromVerifyPage(event)" style="text-align: left;">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Account Email Address</label>
                    <input type="email" name="email" id="resend-email-input" class="form-control" value="<?= e($email) ?>" placeholder="Enter email address" required>
                </div>
                <button type="submit" id="btn-resend-submit" class="btn btn-primary btn-full" style="padding: 12px; font-size: 15px;">Resend Verification Email</button>
            </form>
            <div style="margin-top: 16px;">
                <a href="<?= APP_URL ?>/login" style="font-size: 13px; color: var(--color-brand-red); text-decoration: none; font-weight: 600;">Back to Login</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
async function handleResendFromVerifyPage(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-resend-submit');
    const input = document.getElementById('resend-email-input');
    if (!input || !input.value) return;

    btn.disabled = true;
    btn.textContent = 'Sending...';

    try {
        const res = await fetch('<?= APP_URL ?>/api/auth/resend-verification', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: input.value })
        });
        const result = await res.json();
        if (res.ok && result.success) {
            toast.success(result.message || 'Verification email resent successfully!');
            btn.textContent = 'Email Sent!';
            setTimeout(() => {
                btn.disabled = false;
                btn.textContent = 'Resend Verification Email';
            }, 5000);
        } else {
            toast.error(result.error || 'Failed to resend verification email.');
            btn.disabled = false;
            btn.textContent = 'Resend Verification Email';
        }
    } catch (err) {
        toast.error('Network error. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Resend Verification Email';
    }
}
</script>
<?php require __DIR__ . '/../footer.php'; ?>
