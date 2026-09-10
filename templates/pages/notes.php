<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$pageTitle = 'Secure Notes - xVault Enterprise Password Manager';
$currentView = 'notes';

$user = current_user();
$db = getDB();

$stmt = $db->prepare('SELECT * FROM secure_notes WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$notes = $stmt->fetchAll();

require __DIR__ . '/../header.php';
?>
<div class="main-body">
    <?php require __DIR__ . '/../sidebar.php'; ?>

    <main class="content-area">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 28px;">
                <div>
                    <h1 style="font-size: 24px; font-weight: 800; color: #0F172A;">Secure Notes & Tokens</h1>
                    <p style="font-size: 14px; color: #64748B; margin-top: 2px;">Keep sensitive documentation, SSH keys, and API tokens encrypted</p>
                </div>
                <button type="button" class="btn btn-primary" onclick="openAddItemModal('note')">
                    + Add Secure Note
                </button>
            </div>

            <?php if (empty($notes)): ?>
                <div class="card" style="padding: 60px 20px; text-align: center; color: #94A3B8;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 12px; opacity: 0.3;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    </svg>
                    <p style="font-size: 15px; font-weight: 600; color: #64748B;">No secure notes or tokens stored yet</p>
                    <p style="font-size: 13px; margin-top: 4px;">Click '+ Add Secure Note' to encrypt text files and secrets.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px;">
                    <?php foreach ($notes as $n): ?>
                        <div class="card" style="padding: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #D32F2F; background: #FFEBEE; padding: 2px 8px; border-radius: 4px;"><?= e($n['type']) ?></span>
                                    <h3 style="font-size: 16px; font-weight: 700; color: #0F172A; margin: 0;"><?= e($n['title']) ?></h3>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button type="button" data-copy="<?= e($n['content']) ?>" data-copy-msg="Note content copied" style="background: none; border: none; color: #64748B; cursor: pointer;" title="Copy Content">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                    </button>
                                    <button type="button" onclick="deleteVaultItem(<?= $n['id'] ?>, 'notes')" style="background: none; border: none; color: #EF4444; cursor: pointer;" title="Delete Note">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 12px; border-radius: 8px; max-height: 140px; overflow-y: auto;">
                                <pre class="font-mono" style="font-size: 12px; color: #334155; white-space: pre-wrap; word-break: break-all; margin: 0;"><?= e($n['content']) ?></pre>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../footer.php'; ?>
