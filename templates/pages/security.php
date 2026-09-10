<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$pageTitle = 'Security Dashboard - xVault Enterprise Password Manager';
$currentView = 'security';

$user = current_user();
$db = getDB();

// Fetch initial password health statistics
$stmt = $db->prepare('SELECT id, encrypted_password FROM password_entries WHERE user_id = ?');
$stmt->execute([$user['id']]);
$passwords = $stmt->fetchAll();

$totalCount = count($passwords);
$weakCount = 0;
$plainList = [];

foreach ($passwords as $p) {
    $plain = decrypt_data($p['encrypted_password']);
    $plainList[] = $plain;
    if (strlen($plain) < 10 || in_array(strtolower($plain), ['123456', 'password', 'admin', 'root', '12345678'])) {
        $weakCount++;
    }
}

$counts = array_count_values($plainList);
$reusedCount = 0;
foreach ($counts as $pText => $cnt) {
    if ($cnt > 1 && $pText !== '********') {
        $reusedCount += ($cnt - 1);
    }
}

$deductions = ($weakCount * 15) + ($reusedCount * 10);
$score = $totalCount > 0 ? max(20, min(100, 100 - $deductions)) : 100;

// Fetch initial security logs
$logStmt = $db->prepare('
    SELECT event_type, ip_address, details, created_at 
    FROM security_logs 
    WHERE user_id = ? OR user_id IS NULL
    ORDER BY created_at DESC 
    LIMIT 20
');
$logStmt->execute([$user['id']]);
$initialLogs = $logStmt->fetchAll();

require __DIR__ . '/../header.php';
?>
<div class="main-body" id="sec-dashboard-page">
    <?php require __DIR__ . '/../sidebar.php'; ?>

    <main class="content-area">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 28px;">
                <div>
                    <h1 style="font-size: 24px; font-weight: 800; color: #0F172A;">Security Dashboard</h1>
                    <p style="font-size: 14px; color: #64748B; margin-top: 2px;">Real-time security health monitoring & database audit logs</p>
                </div>
                <div style="font-size: 12px; color: #64748B; background: #F1F5F9; padding: 6px 12px; border-radius: 20px; font-weight: 600;" id="sec-last-updated">
                    Live Polling Active (15s)
                </div>
            </div>

            <!-- Health Score & Metrics -->
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; margin-bottom: 32px;">
                <!-- Health Gauge -->
                <div class="card" style="padding: 32px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div style="width: 130px; height: 130px; border-radius: 65px; border: 8px solid <?= $score >= 80 ? '#22C55E' : ($score >= 60 ? '#3B82F6' : '#EF4444') ?>; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <span id="sec-score-val" style="font-size: 38px; font-weight: 800; color: #0F172A;"><?= $score ?>%</span>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 800; color: #0F172A;">Vault Health Score</h3>
                    <p style="font-size: 12px; color: #64748B; margin-top: 4px;">Computed live from stored secret entropy.</p>
                </div>

                <!-- Live Metrics Breakdown -->
                <div class="card" style="padding: 28px; display: flex; flex-direction: column; justify-content: center;">
                    <h3 style="font-size: 16px; font-weight: 700; color: #0F172A; margin-bottom: 20px;">Vulnerability Audit Breakdown</h3>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #F1F5F9;">
                        <div>
                            <p style="font-weight: 600; font-size: 14px; color: #0F172A;">Total Vault Credentials</p>
                            <p style="font-size: 12px; color: #64748B;">All AES-256 encrypted items</p>
                        </div>
                        <span id="sec-total-val" style="font-size: 20px; font-weight: 800; color: #0F172A;"><?= $totalCount ?></span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #F1F5F9;">
                        <div>
                            <p style="font-weight: 600; font-size: 14px; color: #0F172A;">Weak Passwords Identified</p>
                            <p style="font-size: 12px; color: #64748B;">Passwords under 10 chars or predictable</p>
                        </div>
                        <span id="sec-weak-val" style="font-size: 20px; font-weight: 800; color: <?= $weakCount > 0 ? '#EF4444' : '#22C55E' ?>;"><?= $weakCount ?></span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0;">
                        <div>
                            <p style="font-weight: 600; font-size: 14px; color: #0F172A;">Reused Passwords Identified</p>
                            <p style="font-size: 12px; color: #64748B;">Duplicate secrets used across sites</p>
                        </div>
                        <span id="sec-reused-val" style="font-size: 20px; font-weight: 800; color: <?= $reusedCount > 0 ? '#F59E0B' : '#22C55E' ?>;"><?= $reusedCount ?></span>
                    </div>
                </div>
            </div>

            <!-- Database Audit Logs Table -->
            <div class="card">
                <div class="card-header">
                    <h3 style="font-size: 16px; font-weight: 700; color: #0F172A; margin: 0;">Real-Time Security Audit Event Log</h3>
                    <span style="font-size: 12px; font-weight: 600; color: var(--color-brand-red);">Database Event Stream</span>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive" style="max-height: 360px;">
                        <table class="vault-table">
                            <thead>
                                <tr>
                                    <th>Event Type</th>
                                    <th>Context Details</th>
                                    <th>IP Address</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="sec-logs-tbody">
                                <?php if (empty($initialLogs)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 40px 20px; color: #94A3B8;">
                                            No security events logged yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($initialLogs as $l): ?>
                                        <tr>
                                            <td><span class="sidebar-badge" style="font-weight: 700;"><?= e($l['event_type']) ?></span></td>
                                            <td style="font-size: 13px; color: #334155;"><?= e($l['details'] ?: '-') ?></td>
                                            <td style="font-size: 12px; font-family: monospace; color: #64748B;"><?= e($l['ip_address'] ?: '-') ?></td>
                                            <td style="font-size: 12px; color: #94A3B8;"><?= e(date('M j, Y H:i:s', strtotime($l['created_at']))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if (($user['role'] ?? '') === 'admin'): ?>
            <!-- Admin SMTP Mailer Configuration Panel -->
            <div class="card" style="padding: 28px; margin-top: 32px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #F1F5F9; padding-bottom: 16px;">
                    <div>
                        <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">Production SMTP Mailer Settings</h3>
                        <p style="font-size: 12px; color: #64748B; margin: 4px 0 0 0;">Configure transactional email settings for password resets, invitations, and security alerts.</p>
                    </div>
                    <div>
                        <span class="sidebar-badge" style="background: <?= (defined('SMTP_ENABLED') && SMTP_ENABLED) ? '#D1FAE5; color: #065F46;' : '#FEF3C7; color: #92400E;' ?>">
                            <?= (defined('SMTP_ENABLED') && SMTP_ENABLED) ? '● SMTP Active' : '○ SMTP Disabled' ?>
                        </span>
                    </div>
                </div>

                <form onsubmit="saveAdminSMTPSettings(event)">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; background: #F8FAFC; padding: 12px 16px; border-radius: 8px; border: 1px solid #E2E8F0;">
                        <input type="checkbox" id="admin_smtp_enabled" <?= (defined('SMTP_ENABLED') && SMTP_ENABLED) ? 'checked' : '' ?> style="width: auto; cursor: pointer;">
                        <label for="admin_smtp_enabled" style="font-size: 13px; font-weight: 700; color: #0F172A; margin: 0; cursor: pointer;">Enable Transactional SMTP Email Delivery</label>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">SMTP Host</label>
                            <input type="text" id="admin_smtp_host" value="<?= e(defined('SMTP_HOST') ? SMTP_HOST : '') ?>" placeholder="e.g. smtp.example.com" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">SMTP Port</label>
                            <input type="number" id="admin_smtp_port" value="<?= e(defined('SMTP_PORT') ? SMTP_PORT : 587) ?>" placeholder="e.g. 587" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">Encryption Security</label>
                            <select id="admin_smtp_enc" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px;">
                                <option value="tls" <?= (defined('SMTP_ENCRYPTION') && SMTP_ENCRYPTION === 'tls') ? 'selected' : '' ?>>TLS / STARTTLS (Port 587)</option>
                                <option value="ssl" <?= (defined('SMTP_ENCRYPTION') && SMTP_ENCRYPTION === 'ssl') ? 'selected' : '' ?>>SSL (Port 465)</option>
                                <option value="none" <?= (defined('SMTP_ENCRYPTION') && SMTP_ENCRYPTION === 'none') ? 'selected' : '' ?>>None (Port 25)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">SMTP Username</label>
                            <input type="text" id="admin_smtp_user" value="<?= e(defined('SMTP_USER') ? SMTP_USER : '') ?>" placeholder="Enter SMTP username" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">SMTP Password</label>
                            <input type="password" id="admin_smtp_pass" placeholder="Enter new password (leave blank to keep current)" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">Sender Name</label>
                            <input type="text" id="admin_smtp_from_name" value="<?= e(defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'xVault Security') ?>" placeholder="Enter sender name" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px;">
                        </div>
                        <div style="grid-column: span 2;">
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">Sender Email Address</label>
                            <input type="email" id="admin_smtp_from_email" value="<?= e(defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : '') ?>" placeholder="Enter sender email address" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px;">
                        </div>
                    </div>

                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">Send Test Email To (Optional)</label>
                        <input type="email" id="admin_smtp_test_email" value="<?= e($user['email']) ?>" placeholder="Enter recipient email for test message" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px; background: #fff;">
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" id="btn-save-smtp" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">Save & Test SMTP Configuration</button>
                    </div>
                </form>
            </div>
            <script>
            async function saveAdminSMTPSettings(e) {
                e.preventDefault();
                const btn = document.getElementById('btn-save-smtp');
                btn.disabled = true;
                btn.innerText = 'Saving Configuration...';

                const payload = {
                    smtp_enabled: document.getElementById('admin_smtp_enabled').checked,
                    smtp_host: document.getElementById('admin_smtp_host').value.trim(),
                    smtp_port: document.getElementById('admin_smtp_port').value.trim(),
                    smtp_enc: document.getElementById('admin_smtp_enc').value,
                    smtp_user: document.getElementById('admin_smtp_user').value.trim(),
                    smtp_pass: document.getElementById('admin_smtp_pass').value,
                    smtp_from_email: document.getElementById('admin_smtp_from_email').value.trim(),
                    smtp_from_name: document.getElementById('admin_smtp_from_name').value.trim(),
                    test_email: document.getElementById('admin_smtp_test_email').value.trim()
                };

                try {
                    const res = await fetch('<?= APP_URL ?>/api/security/update-smtp', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        toast.error(data.error || 'Failed to update SMTP settings');
                    } else {
                        toast.success(data.message || 'SMTP settings updated successfully');
                        setTimeout(() => location.reload(), 1200);
                    }
                } catch (err) {
                    toast.error('Network error saving SMTP configuration');
                } finally {
                    btn.disabled = false;
                    btn.innerText = 'Save & Test SMTP Configuration';
                }
            }
            </script>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../footer.php'; ?>
