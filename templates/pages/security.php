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

$hasData = ($totalCount > 0);
if ($hasData) {
    $deductions = ($weakCount * 15) + ($reusedCount * 10) + (empty($user['is_verified']) ? 20 : 0);
    $score = max(10, min(100, 100 - $deductions));
    $scoreText = $score . '%';
    $gaugeColor = $score >= 80 ? '#22C55E' : ($score >= 60 ? '#3B82F6' : '#EF4444');
} else {
    $score = null;
    $scoreText = 'N/A';
    $gaugeColor = '#94A3B8';
}

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
                    <div style="width: 130px; height: 130px; border-radius: 65px; border: 8px solid <?= $gaugeColor ?>; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <span id="sec-score-val" style="font-size: <?= $hasData ? '36px' : '22px' ?>; font-weight: 800; color: #0F172A;"><?= $scoreText ?></span>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 800; color: #0F172A;">Vault Security Posture</h3>
                    <p id="sec-score-msg" style="font-size: 12px; color: #64748B; margin-top: 4px; line-height: 1.4;">
                        <?= $hasData ? 'Computed live from stored credential entropy.' : 'Not available yet — add password entries to calculate security score.' ?>
                    </p>
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
        </div>
    </main>
</div>
<?php require __DIR__ . '/../footer.php'; ?>
