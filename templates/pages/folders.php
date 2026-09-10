<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$pageTitle = 'Customer Folders - xVault Enterprise Password Manager';
$currentView = 'folders';

$user = current_user();
$db = getDB();

$stmt = $db->prepare('SELECT * FROM folders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$folders = $stmt->fetchAll();

require __DIR__ . '/../header.php';
?>
<div class="main-body">
    <?php require __DIR__ . '/../sidebar.php'; ?>

    <main class="content-area">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 28px;">
                <div>
                    <h1 style="font-size: 24px; font-weight: 800; color: #0F172A;">Customer Folders</h1>
                    <p style="font-size: 14px; color: #64748B; margin-top: 2px;">Organize credentials by client, project, or department</p>
                </div>
                <button type="button" class="btn btn-primary" onclick="openAddItemModal('folder')">
                    + Add Folder
                </button>
            </div>

            <?php if (empty($folders)): ?>
                <div class="card" style="padding: 60px 20px; text-align: center; color: #94A3B8;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 12px; opacity: 0.3;">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <p style="font-size: 15px; font-weight: 600; color: #64748B;">No customer folders created yet</p>
                    <p style="font-size: 13px; margin-top: 4px;">Click '+ Add Folder' to create your first client folder.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                    <?php foreach ($folders as $f): ?>
                        <div class="card" style="padding: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #FFEBEE; color: var(--color-brand-red); display: flex; align-items: center; justify-content: center;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                                    </div>
                                    <div>
                                        <h3 style="font-size: 16px; font-weight: 700; color: #0F172A; margin: 0;"><?= e($f['name']) ?></h3>
                                        <p style="font-size: 12px; color: #64748B; margin-top: 2px;"><?= e($f['customer_name'] ?: 'No Client Name') ?></p>
                                    </div>
                                </div>
                                <button type="button" onclick="deleteVaultItem(<?= $f['id'] ?>, 'folders')" style="background: none; border: none; color: #EF4444; cursor: pointer;" title="Delete Folder">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </div>
                            <?php if (!empty($f['customer_email'])): ?>
                                <div style="font-size: 12px; color: #475569; background: #F8FAFC; padding: 8px 12px; border-radius: 6px; margin-top: 12px;">
                                    <strong>Contact:</strong> <?= e($f['customer_email']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../footer.php'; ?>
