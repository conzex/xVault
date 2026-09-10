<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$pageTitle = 'Enterprise Share Center - xVault Admin';
$currentView = 'admin_share';

$admin = require_admin();
$db = getDB();

$stmt = $db->prepare('
    SELECT s.*, p.app_name, u.email as owner_email
    FROM shared_links s
    LEFT JOIN password_entries p ON s.entry_id = p.id
    LEFT JOIN users u ON s.created_by = u.id
    ORDER BY s.created_at DESC
');
$stmt->execute();
$links = $stmt->fetchAll();

// Fetch password entries for dropdown
$passStmt = $db->prepare('SELECT id, app_name, username FROM password_entries ORDER BY app_name ASC');
$passStmt->execute();
$entries = $passStmt->fetchAll();

require __DIR__ . '/../header.php';
?>
<div class="main-body">
    <?php require __DIR__ . '/../sidebar.php'; ?>

    <main class="content-area">
        <div class="container">
            <div style="margin-bottom: 28px;">
                <h1 style="font-size: 24px; font-weight: 800; color: #0F172A;">Enterprise Share Center</h1>
                <p style="font-size: 14px; color: #64748B; margin-top: 2px;">Generate secure, expiring access links for specific individual credential records</p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 28px;">
                <!-- Link Generator Form -->
                <div class="card" style="padding: 24px; height: fit-content;">
                    <h3 style="font-size: 16px; font-weight: 700; color: #0F172A; margin-bottom: 16px;">Generate Secure Share Link</h3>
                    <form id="form-share-generate" onsubmit="handleGenerateShareLink(event)">
                        <div class="form-group">
                            <label class="form-label">Select Credential Record *</label>
                            <select name="entry_id" id="share-entry-id" class="form-control" required>
                                <option value="">-- Choose Credential Record --</option>
                                <?php foreach ($entries as $item): ?>
                                    <option value="<?= $item['id'] ?>"><?= e($item['app_name']) ?> <?= !empty($item['username']) ? '(' . e($item['username']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Customer / Client Email (Optional)</label>
                            <input type="email" name="email" id="share-email" class="form-control" placeholder="Enter customer or client email address">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Link Expiration</label>
                            <select name="expiry" id="share-expiry" class="form-control" onchange="toggleCustomHours(this.value)">
                                <option value="24h">24 Hours</option>
                                <option value="7d">7 Days</option>
                                <option value="30d">30 Days</option>
                                <option value="custom">Custom Hours</option>
                                <option value="lifetime">Lifetime (No Expiry)</option>
                            </select>
                        </div>
                        <div class="form-group" id="group-custom-hours" style="display: none;">
                            <label class="form-label">Custom Hours</label>
                            <input type="number" name="customHours" min="1" max="8760" placeholder="e.g. 12" class="form-control">
                        </div>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                                <input type="checkbox" name="oneTime" value="1" style="accent-color: var(--color-brand-red);">
                                One-Time Access Only (Invalidated after 1 view)
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full" style="padding: 12px; margin-top: 8px;">
                            Generate & Email Link
                        </button>
                    </form>
                </div>

                <!-- Active Share Links List -->
                <div class="card">
                    <div class="card-header">
                        <h3 style="font-size: 15px; font-weight: 700; color: #0F172A; margin: 0;">Active Share Links</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (empty($links)): ?>
                            <div style="padding: 48px 20px; text-align: center; color: #94A3B8; font-size: 14px;">
                                No active share links generated yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="vault-table">
                                    <thead>
                                        <tr>
                                            <th>Shared Credential</th>
                                            <th>Recipient</th>
                                            <th>Expiration</th>
                                            <th>Accesses</th>
                                            <th>Status</th>
                                            <th style="text-align: right;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($links as $l): 
                                            $isExpired = !empty($l['expires_at']) && strtotime($l['expires_at']) < time();
                                            $isUsed = (!empty($l['one_time']) && !empty($l['used'])) || (!empty($l['max_uses']) && (int)($l['access_count'] ?? 0) >= (int)$l['max_uses']);
                                            $fullUrl = APP_URL . '/share/' . $l['token'];
                                        ?>
                                            <tr id="share-row-<?= $l['id'] ?>">
                                                <td style="font-weight: 700; color: #0F172A;">
                                                    <?= e($l['app_name'] ?: 'Entry #' . $l['entry_id']) ?>
                                                    <span style="display: block; font-weight: 400; font-size: 11px; color: #64748B;">By: <?= e($l['owner_email'] ?? 'Admin') ?></span>
                                                </td>
                                                <td style="font-size: 13px; color: #334155;"><?= e($l['target_email'] ?: 'Any link holder') ?></td>
                                                <td style="font-size: 12px; color: #64748B;">
                                                    <?= !empty($l['expires_at']) ? date('M j, Y H:i', strtotime($l['expires_at'])) : 'Never' ?>
                                                    <?php if (!empty($l['one_time'])): ?>
                                                        <span style="display: block; font-size: 10px; color: #D32F2F; font-weight: 700;">(One-Time View)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span style="font-size: 12px; font-weight: 700; color: #0F172A; background: #F1F5F9; padding: 2px 8px; border-radius: 10px;">
                                                        <?= (int)($l['access_count'] ?? 0) ?> views
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($isUsed): ?>
                                                        <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: #FEF2F2; color: #EF4444; font-weight: 700;">USED</span>
                                                    <?php elseif ($isExpired): ?>
                                                        <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: #FEF2F2; color: #EF4444; font-weight: 700;">EXPIRED</span>
                                                    <?php else: ?>
                                                        <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: #DCFCE7; color: #166534; font-weight: 700;">ACTIVE</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align: right;">
                                                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                                        <button type="button" data-copy="<?= e($fullUrl) ?>" data-copy-msg="Share link URL copied" class="btn btn-secondary btn-sm" title="Copy Link URL">Copy</button>
                                                        <button type="button" onclick="revokeShareLink(<?= $l['id'] ?>)" class="btn btn-secondary btn-sm" style="color: #EF4444;" title="Revoke Link">Revoke</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function toggleCustomHours(val) {
    document.getElementById('group-custom-hours').style.display = val === 'custom' ? 'block' : 'none';
}

async function handleGenerateShareLink(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    try {
        const res = await fetch('<?= APP_URL ?>/api/share/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (res.ok && result.success) {
            toast.success('Share link generated and sent!');
            copyToClipboard(result.url, 'Link copied to clipboard');
            setTimeout(() => location.reload(), 800);
        } else {
            toast.error(result.error || 'Failed to generate link');
        }
    } catch (err) {
        toast.error('Network error');
    }
}

async function revokeShareLink(id) {
    const confirmed = await CustomModal.confirm({
        title: 'Revoke Share Link',
        message: 'Are you sure you want to revoke this share link? Anyone holding this link will immediately lose access.',
        confirmText: 'Revoke Link',
        isDanger: true
    });
    if (!confirmed) return;
    try {
        const res = await fetch(`<?= APP_URL ?>/api/share/revoke/${id}`, { method: 'DELETE' });
        const result = await res.json();
        if (result.success) {
            toast.success('Share link revoked');
            const row = document.getElementById(`share-row-${id}`);
            if (row) row.remove();
            else location.reload();
        } else {
            toast.error(result.error || 'Revoke failed');
        }
    } catch (err) {
        toast.error('Network error');
    }
}
</script>
<?php require __DIR__ . '/../footer.php'; ?>
