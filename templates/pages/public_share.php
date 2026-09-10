<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$pageTitle = 'Secure Shared Vault Access - xVault';

$token = $shareToken ?? '';
$db = getDB();

$stmt = $db->prepare('SELECT * FROM shared_links WHERE token = ?');
$stmt->execute([$token]);
$link = $stmt->fetch();

$error = null;
$items = [];

if (!$link) {
    $error = 'Invalid or non-existent share link token.';
} elseif (!empty($link['expires_at']) && strtotime($link['expires_at']) < time()) {
    $error = 'This share link has expired and is no longer accessible.';
} elseif (!empty($link['one_time']) && !empty($link['used'])) {
    $error = 'This one-time share link has already been accessed and invalidated.';
} else {
    // If valid one-time link, mark as used upon access
    if (!empty($link['one_time'])) {
        $up = $db->prepare('UPDATE shared_links SET used = 1 WHERE id = ?');
        $up->execute([$link['id']]);
    }

    // Retrieve shared items for target email / creator
    $passStmt = $db->prepare('
        SELECT p.app_name, p.login_url, p.username, p.encrypted_password
        FROM password_entries p
        LEFT JOIN folders f ON p.folder_id = f.id
        WHERE f.customer_email = ? OR p.user_id = ?
        ORDER BY p.created_at DESC
    ');
    $passStmt->execute([$link['target_email'], $link['created_by']]);
    $rawItems = $passStmt->fetchAll();

    $items = array_map(function($p) {
        return [
            'app_name' => $p['app_name'],
            'login_url' => $p['login_url'],
            'username' => $p['username'],
            'password' => decrypt_data($p['encrypted_password'])
        ];
    }, $rawItems);
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
            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                <p style="font-size: 13px; color: #475569; margin: 0;">
                    Shared with: <strong><?= e($link['target_email']) ?></strong>
                    <?php if (!empty($link['one_time'])): ?>
                        <span style="float: right; color: #D32F2F; font-weight: 700;">[One-Time Link]</span>
                    <?php endif; ?>
                </p>
            </div>

            <?php if (empty($items)): ?>
                <div style="text-align: center; padding: 40px; color: #94A3B8; font-size: 14px;">
                    No shared credentials were found for this token.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="vault-table">
                        <thead>
                            <tr>
                                <th>Site / Application</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $idx => $item): ?>
                                <tr>
                                    <td>
                                        <p style="font-weight: 700; color: #0F172A; margin: 0;"><?= e($item['app_name']) ?></p>
                                        <?php if (!empty($item['login_url'])): ?>
                                            <a href="<?= e($item['login_url']) ?>" target="_blank" style="font-size: 11px; color: #64748B; text-decoration: none;"><?= e($item['login_url']) ?></a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span><?= e($item['username']) ?></span>
                                            <button type="button" data-copy="<?= e($item['username']) ?>" data-copy-msg="Username copied" style="background: none; border: none; cursor: pointer; color: #94A3B8;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span id="shared-pass-<?= $idx ?>" class="font-mono" style="font-size: 13px;">••••••••</span>
                                            <button type="button" onclick="const el = document.getElementById('shared-pass-<?= $idx ?>'); el.textContent = el.textContent === '••••••••' ? '<?= e(addslashes($item['password'])) ?>' : '••••••••';" style="background: none; border: none; cursor: pointer; color: #94A3B8;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            </button>
                                            <button type="button" data-copy="<?= e($item['password']) ?>" data-copy-msg="Password copied" style="background: none; border: none; cursor: pointer; color: #94A3B8;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if (!empty($item['login_url'])): ?>
                                            <a href="<?= e($item['login_url']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="color: var(--color-brand-red);">Launch</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../footer.php'; ?>
