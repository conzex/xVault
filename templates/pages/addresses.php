<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$pageTitle = 'Addresses - xVault Enterprise Password Manager';
$currentView = 'addresses';

$user = current_user();
$db = getDB();

$stmt = $db->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$addresses = $stmt->fetchAll();

require __DIR__ . '/../header.php';
?>
<div class="main-body">
    <?php require __DIR__ . '/../sidebar.php'; ?>

    <main class="content-area">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 28px;">
                <div>
                    <h1 style="font-size: 24px; font-weight: 800; color: #0F172A;">Addresses</h1>
                    <p style="font-size: 14px; color: #64748B; margin-top: 2px;">Store and auto-fill contact information and physical addresses</p>
                </div>
                <button type="button" class="btn btn-primary" onclick="openAddItemModal('address')">
                    + Add Address
                </button>
            </div>

            <?php if (empty($addresses)): ?>
                <div class="card" style="padding: 60px 20px; text-align: center; color: #94A3B8;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 12px; opacity: 0.3;">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <p style="font-size: 15px; font-weight: 600; color: #64748B;">No addresses saved yet</p>
                    <p style="font-size: 13px; margin-top: 4px;">Click '+ Add Address' to store contact and billing addresses.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                    <?php foreach ($addresses as $a): ?>
                        <div class="card" style="padding: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                <div>
                                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--color-brand-red); background: #FFEBEE; padding: 2px 8px; border-radius: 4px;"><?= e($a['label']) ?></span>
                                    <h3 style="font-size: 16px; font-weight: 700; color: #0F172A; margin: 8px 0 0 0;"><?= e($a['first_name'] . ' ' . $a['last_name']) ?></h3>
                                </div>
                                <button type="button" onclick="deleteVaultItem(<?= $a['id'] ?>, 'addresses')" style="background: none; border: none; color: #EF4444; cursor: pointer;" title="Delete Address">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </div>
                            <p style="font-size: 13px; color: #475569; line-height: 1.5; margin-bottom: 12px;">
                                <?= e($a['address_line1']) ?><br>
                                <?php if (!empty($a['address_line2'])): ?><?= e($a['address_line2']) ?><br><?php endif; ?>
                                <?= e($a['city']) ?>, <?= e($a['state']) ?> <?= e($a['zip_code']) ?><br>
                                <?= e($a['country']) ?>
                            </p>
                            <?php if (!empty($a['phone'])): ?>
                                <p style="font-size: 12px; color: #64748B;">📞 <?= e($a['phone']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../footer.php'; ?>
