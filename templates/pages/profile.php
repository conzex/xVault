<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$pageTitle = 'Manage Profile - xVault Enterprise Password Manager';
$currentView = 'profile';

$user = require_auth();
require __DIR__ . '/../header.php';
?>
<div class="main-body">
    <?php require __DIR__ . '/../sidebar.php'; ?>

    <main class="content-area">
        <div class="container">
            <div style="margin-bottom: 28px;">
                <h1 style="font-size: 24px; font-weight: 800; color: #0F172A;">Profile & Master Credentials</h1>
                <p style="font-size: 14px; color: #64748B; margin-top: 2px;">Manage account profile details and security master password</p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 28px;">
                <!-- Profile Form -->
                <div class="card" style="padding: 28px;">
                    <h3 style="font-size: 16px; font-weight: 700; color: #0F172A; margin-bottom: 16px;">Account Info</h3>
                    <form id="form-update-profile" onsubmit="handleUpdateProfile(event)">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" value="<?= e($user['name']) ?>" placeholder="e.g. Alex Morgan" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" value="<?= e($user['email']) ?>" placeholder="e.g. user@domain.com" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 8px;">Update Profile</button>
                    </form>
                </div>

                <!-- Password Change Form -->
                <div class="card" style="padding: 28px;">
                    <h3 style="font-size: 16px; font-weight: 700; color: #0F172A; margin-bottom: 16px;">Change Master Password</h3>
                    <form id="form-change-password" onsubmit="handleChangePassword(event)">
                        <div class="form-group">
                            <label class="form-label">Current Master Password</label>
                            <input type="password" name="currentPassword" placeholder="Enter current master password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Master Password</label>
                            <input type="password" name="newPassword" placeholder="Enter new master password (min 6 chars)" class="form-control" minlength="6" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 8px;">Update Master Password</button>
                    </form>
                </div>

            </div>
        </div>
    </main>
</div>

<script>
async function handleUpdateProfile(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    try {
        const res = await fetch('<?= APP_URL ?>/api/auth/update-profile', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (res.ok && result.success) {
            toast.success(result.message);
            setTimeout(() => location.reload(), 800);
        } else {
            toast.error(result.error || 'Failed to update profile');
        }
    } catch (err) {
        toast.error('Network error');
    }
}

async function handleChangePassword(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    try {
        const res = await fetch('<?= APP_URL ?>/api/auth/change-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (res.ok && result.success) {
            toast.success(result.message);
            e.target.reset();
        } else {
            toast.error(result.error || 'Failed to change master password');
        }
    } catch (err) {
        toast.error('Network error');
    }
}
</script>
<?php require __DIR__ . '/../footer.php'; ?>
