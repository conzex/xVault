<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$pageTitle = 'Secure Shared Vault Access - xVault';

$token = $shareToken ?? ($_GET['token'] ?? '');
$db = getDB();

$error = null;
$item = null;
$linkInfo = null;

if (empty($token)) {
    $error = 'No share token provided.';
} else {
    $stmt = $db->prepare('
        SELECT s.*, p.app_name, p.login_url, p.username, p.encrypted_password, p.created_at as item_created_at
        FROM shared_links s
        JOIN password_entries p ON s.entry_id = p.id
        WHERE s.token = ?
    ');
    $stmt->execute([$token]);
    $link = $stmt->fetch();

    if (!$link) {
        $error = 'Invalid, revoked, or non-existent share link.';
    } elseif (!empty($link['expires_at']) && strtotime($link['expires_at']) < time()) {
        $error = 'This share link has expired and is no longer accessible.';
    } elseif (!empty($link['one_time']) && !empty($link['used'])) {
        $error = 'This one-time share link has already been used and is now invalidated.';
    } elseif (!empty($link['max_uses']) && (int)$link['access_count'] >= (int)$link['max_uses']) {
        $error = 'This share link has reached its maximum allowed access count.';
    } else {
        $newCount = (int)$link['access_count'] + 1;
        $nowStr = date('Y-m-d H:i:s');
        $isUsedNow = (!empty($link['one_time']) || (!empty($link['max_uses']) && $newCount >= (int)$link['max_uses'])) ? 1 : 0;

        $up = $db->prepare('UPDATE shared_links SET access_count = ?, last_accessed_at = ?, used = ? WHERE id = ?');
        $up->execute([$newCount, $nowStr, $isUsedNow, $link['id']]);

        log_security_event('SHARE_LINK_ACCESSED', "Share link accessed for item #{$link['entry_id']} ({$link['app_name']})", null);

        $linkInfo = $link;
        $item = [
            'app_name' => $link['app_name'],
            'login_url' => $link['login_url'],
            'username' => $link['username'],
            'password' => decrypt_data($link['encrypted_password']),
            'created_at' => $link['item_created_at']
        ];
    }
}

require __DIR__ . '/../header.php';
?>
<div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 16px;">
    <div class="card" style="width: 100%; max-width: 680px; padding: 36px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="logo-container" style="justify-content: center; margin-bottom: 12px;">
                <div class="logo-icon" style="width: 42px; height: 42px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </div>
                <div class="logo-text" style="font-size: 28px;">
                    <span class="x">x</span><span class="vault">Vault</span>
                </div>
            </div>
            <h2 style="font-size: 20px; font-weight: 800; color: #0F172A;">Secure Shared Vault Access</h2>
        </div>

        <?php if ($error): ?>
            <div style="background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: 10px; padding: 24px; text-align: center; color: #991B1B;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 8px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 4px;">Link Unavailable</h3>
                <p style="font-size: 14px; margin: 0;"><?= e($error) ?></p>
            </div>
        <?php else: ?>
            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 14px 18px; border-radius: 8px; margin-bottom: 24px; font-size: 13px; color: #475569; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span>Access Rule: </span>
                    <strong style="color: #0F172A;"><?= !empty($linkInfo['one_time']) ? 'One-Time View' : 'Time-Limited Link' ?></strong>
                    <?php if (!empty($linkInfo['target_email'])): ?>
                        <span style="margin-left: 8px;">(Shared for: <strong><?= e($linkInfo['target_email']) ?></strong>)</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($linkInfo['expires_at'])): ?>
                    <span style="font-size: 12px; color: #64748B;">Expires: <?= date('M j, Y H:i', strtotime($linkInfo['expires_at'])) ?></span>
                <?php endif; ?>
            </div>

            <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <div style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #F1F5F9;">
                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--color-brand-red); letter-spacing: 0.05em; margin-bottom: 4px;">Shared Credential Record</div>
                    <h2 style="margin: 0; font-size: 22px; font-weight: 800; color: #0F172A;"><?= e($item['app_name']) ?></h2>
                    <?php if (!empty($item['login_url'])): ?>
                        <a href="<?= e($item['login_url']) ?>" target="_blank" style="font-size: 13px; color: #0284C7; text-decoration: none; word-break: break-all; display: inline-block; margin-top: 4px;"><?= e($item['login_url']) ?> ↗</a>
                    <?php endif; ?>
                </div>

                <div style="display: grid; gap: 16px; margin-bottom: 24px;">
                    <div style="background: #F8FAFC; padding: 14px 16px; border-radius: 8px; border: 1px solid #F1F5F9;">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; margin-bottom: 4px;">Username / Account Email</label>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="font-size: 15px; font-weight: 600; color: #0F172A; word-break: break-all;"><?= e($item['username'] ?: '—') ?></span>
                            <?php if (!empty($item['username'])): ?>
                                <button type="button" data-copy="<?= e($item['username']) ?>" data-copy-msg="Username copied" class="btn btn-secondary btn-sm" style="padding: 4px 10px; font-size: 12px;">Copy</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="background: #F8FAFC; padding: 14px 16px; border-radius: 8px; border: 1px solid #F1F5F9;">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; margin-bottom: 4px;">Password</label>
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                            <span id="shared-pass-single" class="font-mono" style="font-size: 16px; font-weight: 700; color: #0F172A; letter-spacing: 2px;">••••••••</span>
                            <div style="display: flex; gap: 8px;">
                                <button type="button" onclick="const el = document.getElementById('shared-pass-single'); el.textContent = el.textContent === '••••••••' ? '<?= e(addslashes($item['password'])) ?>' : '••••••••';" class="btn btn-secondary btn-sm" style="padding: 4px 10px; font-size: 12px;">View / Hide</button>
                                <button type="button" data-copy="<?= e($item['password']) ?>" data-copy-msg="Password copied" class="btn btn-primary btn-sm" style="padding: 4px 12px; font-size: 12px;">Copy Password</button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($item['login_url'])): ?>
                    <a href="<?= e($item['login_url']) ?>" target="_blank" class="btn btn-primary btn-full" style="padding: 12px; font-size: 14px;">Launch Website</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../footer.php'; ?>
