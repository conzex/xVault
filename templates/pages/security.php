<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$pageTitle = 'Security Dashboard - xVault Enterprise Password Manager';
$currentView = 'security';

$user = current_user();
$db = getDB();

// Fetch initial password health statistics
$stmt = $db->prepare('SELECT id, encrypted_password, is_favorite, created_at FROM password_entries WHERE user_id = ?');
$stmt->execute([$user['id']]);
$passwords = $stmt->fetchAll();

$totalCount = count($passwords);
$weakCount = 0;
$strongCount = 0;
$oldCount = 0;
$favCount = 0;
$attentionCount = 0;
$plainList = [];
$now = time();
$ninetyDaysAgo = $now - (90 * 86400);

foreach ($passwords as $p) {
    $plain = decrypt_data($p['encrypted_password']);
    $plainList[] = $plain;

    if (!empty($p['is_favorite'])) {
        $favCount++;
    }

    if (!empty($p['created_at']) && strtotime($p['created_at']) < $ninetyDaysAgo) {
        $oldCount++;
    }
}

$counts = array_count_values($plainList);
$reusedCount = 0;
foreach ($counts as $pText => $cnt) {
    if ($cnt > 1 && $pText !== '********') {
        $reusedCount += ($cnt - 1);
    }
}

$commonWeak = ['123456', 'password', 'admin', 'root', '12345678', 'qwerty', 'password123', 'welcome', '123456789'];
foreach ($passwords as $idx => $p) {
    $plain = $plainList[$idx];
    $isReused = ($counts[$plain] ?? 0) > 1;
    $isWeak = (strlen($plain) < 10 || in_array(strtolower($plain), $commonWeak));
    $isOld = (!empty($p['created_at']) && strtotime($p['created_at']) < $ninetyDaysAgo);

    if ($isWeak) {
        $weakCount++;
    }

    $hasUpper = preg_match('/[A-Z]/', $plain);
    $hasLower = preg_match('/[a-z]/', $plain);
    $hasDigit = preg_match('/[0-9]/', $plain);
    $hasSymbol = preg_match('/[^A-Za-z0-9]/', $plain);

    if (strlen($plain) >= 12 && $hasUpper && $hasLower && $hasDigit && $hasSymbol && !$isReused && !$isWeak) {
        $strongCount++;
    }

    if ($isWeak || $isReused || $isOld) {
        $attentionCount++;
    }
}

$hasData = ($totalCount > 0);
if ($hasData) {
    $weakRatio = $weakCount / $totalCount;
    $reusedRatio = $reusedCount / $totalCount;
    $oldRatio = $oldCount / $totalCount;
    $deductions = ($weakRatio * 40) + ($reusedRatio * 30) + ($oldRatio * 15) + (empty($user['is_verified']) ? 15 : 0);
    $score = max(10, min(100, (int)round(100 - $deductions)));
    $scoreText = $score . '%';
    $gaugeColor = $score >= 80 ? '#22C55E' : ($score >= 60 ? '#3B82F6' : '#EF4444');
    $scoreMsg = $score >= 80 ? 'Strong vault security posture' : ($score >= 60 ? 'Fair security posture — improvements recommended' : 'Critical security vulnerabilities detected');
} else {
    $score = null;
    $scoreText = 'N/A';
    $gaugeColor = '#94A3B8';
    $scoreMsg = 'Not available yet — add password entries to calculate security score.';
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
                    <div id="sec-gauge-circle" style="width: 130px; height: 130px; border-radius: 65px; border: 8px solid <?= $gaugeColor ?>; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <span id="sec-score-val" style="font-size: <?= $hasData ? '36px' : '22px' ?>; font-weight: 800; color: #0F172A;"><?= $scoreText ?></span>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 800; color: #0F172A;">Vault Security Posture</h3>
                    <p id="sec-score-msg" style="font-size: 12px; color: #64748B; margin-top: 4px; line-height: 1.4;">
                        <?= e($scoreMsg) ?>
                    </p>
                </div>

                <!-- Live Metrics Breakdown -->
                <div class="card" style="padding: 28px; display: flex; flex-direction: column; justify-content: center;">
                    <h3 style="font-size: 16px; font-weight: 700; color: #0F172A; margin-bottom: 16px;">Vulnerability Audit Breakdown</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div style="background: #F8FAFC; padding: 12px 16px; border-radius: 8px; border: 1px solid #F1F5F9; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="font-weight: 600; font-size: 13px; color: #0F172A; margin: 0;">Total Credentials</p>
                                <p style="font-size: 11px; color: #64748B; margin: 2px 0 0 0;">All vault entries</p>
                            </div>
                            <span id="sec-total-val" style="font-size: 18px; font-weight: 800; color: #0F172A;"><?= $totalCount ?></span>
                        </div>

                        <div style="background: #F8FAFC; padding: 12px 16px; border-radius: 8px; border: 1px solid #F1F5F9; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="font-weight: 600; font-size: 13px; color: #0F172A; margin: 0;">Strong Passwords</p>
                                <p style="font-size: 11px; color: #64748B; margin: 2px 0 0 0;">High complexity >= 12 chars</p>
                            </div>
                            <span id="sec-strong-val" style="font-size: 18px; font-weight: 800; color: #22C55E;"><?= $strongCount ?></span>
                        </div>

                        <div style="background: #F8FAFC; padding: 12px 16px; border-radius: 8px; border: 1px solid #F1F5F9; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="font-weight: 600; font-size: 13px; color: #0F172A; margin: 0;">Weak Passwords</p>
                                <p style="font-size: 11px; color: #64748B; margin: 2px 0 0 0;">Under 10 chars or simple</p>
                            </div>
                            <span id="sec-weak-val" style="font-size: 18px; font-weight: 800; color: <?= $weakCount > 0 ? '#EF4444' : '#22C55E' ?>;"><?= $weakCount ?></span>
                        </div>

                        <div style="background: #F8FAFC; padding: 12px 16px; border-radius: 8px; border: 1px solid #F1F5F9; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="font-weight: 600; font-size: 13px; color: #0F172A; margin: 0;">Reused Passwords</p>
                                <p style="font-size: 11px; color: #64748B; margin: 2px 0 0 0;">Duplicates across sites</p>
                            </div>
                            <span id="sec-reused-val" style="font-size: 18px; font-weight: 800; color: <?= $reusedCount > 0 ? '#F59E0B' : '#22C55E' ?>;"><?= $reusedCount ?></span>
                        </div>

                        <div style="background: #F8FAFC; padding: 12px 16px; border-radius: 8px; border: 1px solid #F1F5F9; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="font-weight: 600; font-size: 13px; color: #0F172A; margin: 0;">Old Passwords</p>
                                <p style="font-size: 11px; color: #64748B; margin: 2px 0 0 0;">Older than 90 days</p>
                            </div>
                            <span id="sec-old-val" style="font-size: 18px; font-weight: 800; color: <?= $oldCount > 0 ? '#F59E0B' : '#22C55E' ?>;"><?= $oldCount ?></span>
                        </div>

                        <div style="background: #F8FAFC; padding: 12px 16px; border-radius: 8px; border: 1px solid #F1F5F9; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="font-weight: 600; font-size: 13px; color: #0F172A; margin: 0;">Requires Attention</p>
                                <p style="font-size: 11px; color: #64748B; margin: 2px 0 0 0;">Weak, reused, or old</p>
                            </div>
                            <span id="sec-attention-val" style="font-size: 18px; font-weight: 800; color: <?= $attentionCount > 0 ? '#DC2626' : '#22C55E' ?>;"><?= $attentionCount ?></span>
                        </div>
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
