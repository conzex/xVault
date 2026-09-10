<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$pageTitle = 'Dashboard - xVault Enterprise Password Manager';
$currentView = $currentView ?? 'dashboard';

$user = current_user();
$db = getDB();

// Fetch password entries for current user
$stmt = $db->prepare('
    SELECT p.*, f.name as folder_name 
    FROM password_entries p 
    LEFT JOIN folders f ON p.folder_id = f.id 
    WHERE p.user_id = ? 
    ORDER BY p.is_favorite DESC, p.created_at DESC
');
$stmt->execute([$user['id']]);
$passwords = $stmt->fetchAll();

$totalCount = count($passwords);
$favCount = count(array_filter($passwords, fn($p) => !empty($p['is_favorite'])));

if ($totalCount === 0) {
    $scoreDisplay = 'N/A';
    $scoreColor = '#94A3B8';
} else {
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
    $deductions = ($weakCount * 15) + ($reusedCount * 10) + (empty($user['is_verified']) ? 20 : 0);
    $calculatedScore = max(10, min(100, 100 - $deductions));
    $scoreDisplay = $calculatedScore . '%';
    $scoreColor = $calculatedScore >= 80 ? '#16A34A' : ($calculatedScore >= 60 ? '#D97706' : '#DC2626');
}

require __DIR__ . '/../header.php';
?>
<div class="main-body">
    <?php require __DIR__ . '/../sidebar.php'; ?>

    <main class="content-area">
        <div class="container">
            <!-- Header Bar -->
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 28px;">
                <div>
                    <h1 style="font-size: 24px; font-weight: 800; color: #0F172A;">All Items</h1>
                    <p style="font-size: 14px; color: #64748B; margin-top: 2px;">Manage and access your secure logins and credentials</p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <div style="position: relative;">
                        <input type="text" id="vault-search" oninput="filterVaultTable()" placeholder="Search vault..." class="form-control" style="width: 260px; padding-left: 36px; font-size: 13px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%);">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 32px;">
                <div class="card" style="padding: 20px;">
                    <p style="font-size: 11px; font-weight: 800; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Total Items</p>
                    <p style="font-size: 32px; font-weight: 800; color: #0F172A; line-height: 1;"><?= $totalCount ?></p>
                </div>
                <div class="card" style="padding: 20px;">
                    <p style="font-size: 11px; font-weight: 800; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Favorites</p>
                    <p style="font-size: 32px; font-weight: 800; color: #0F172A; line-height: 1;"><?= $favCount ?></p>
                </div>
                <div class="card" style="padding: 20px;">
                    <p style="font-size: 11px; font-weight: 800; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Security Score</p>
                    <p style="font-size: <?= $totalCount === 0 ? '22px' : '32px' ?>; font-weight: 800; color: <?= $scoreColor ?>; line-height: 1;"><?= $scoreDisplay ?></p>
                </div>
            </div>

            <!-- Password Table Card -->
            <div class="card">
                <div class="table-responsive">
                    <table class="vault-table" id="vault-table">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;"></th>
                                <th style="width: 25%;">Item / Site</th>
                                <th style="width: 20%;">Username</th>
                                <th style="width: 200px;">Password</th>
                                <th style="width: 15%;">Folder</th>
                                <th style="width: 150px;">Action</th>
                                <th style="width: 50px; text-align: center;"></th>
                            </tr>
                        </thead>
                        <tbody id="vault-tbody">
                            <?php if (empty($passwords)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 60px 20px; color: #94A3B8;">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 12px; opacity: 0.3;">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                        </svg>
                                        <p style="font-size: 15px; font-weight: 600; color: #64748B;">Your vault is currently empty</p>
                                        <p style="font-size: 13px; margin-top: 4px;">Click 'Add Item' to store your first password entry securely.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($passwords as $p): ?>
                                    <tr id="item-row-<?= $p['id'] ?>" class="vault-row" data-search="<?= e(strtolower($p['app_name'] . ' ' . $p['username'] . ' ' . $p['login_url'])) ?>">
                                        <td style="text-align: center;">
                                            <button type="button" class="star-btn <?= !empty($p['is_favorite']) ? 'active' : '' ?>" onclick="toggleFavoriteItem(<?= $p['id'] ?>, this)" aria-label="Toggle Favorite">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                            </button>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div class="item-logo">
                                                    <?= strtoupper(substr($p['app_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <p style="font-weight: 700; color: #0F172A; margin: 0; line-height: 1.2;"><?= e($p['app_name']) ?></p>
                                                    <?php if (!empty($p['login_url'])): ?>
                                                        <a href="<?= e($p['login_url']) ?>" target="_blank" style="font-size: 11px; color: #64748B; word-break: break-all; text-decoration: none;">
                                                            <?= e(parse_url($p['login_url'], PHP_URL_HOST) ?: $p['login_url']) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span style="font-weight: 500; font-size: 13px; color: #334155;"><?= e($p['username']) ?></span>
                                                <?php if (!empty($p['username'])): ?>
                                                    <button type="button" data-copy="<?= e($p['username']) ?>" data-copy-msg="Username copied" style="background: none; border: none; cursor: pointer; color: #94A3B8;" title="Copy Username">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span id="pass-text-<?= $p['id'] ?>" class="password-cell-text">••••••••</span>
                                                <button type="button" onclick="decryptAndShowPassword(<?= $p['id'] ?>, 'pass-text-<?= $p['id'] ?>')" style="background: none; border: none; cursor: pointer; color: #94A3B8;" title="Show/Hide">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                </button>
                                                <button type="button" onclick="copyDecryptedPassword(<?= $p['id'] ?>)" style="background: none; border: none; cursor: pointer; color: #94A3B8;" title="Copy Password">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size: 12px; color: #64748B; background: #F1F5F9; padding: 2px 8px; border-radius: 4px;">
                                                <?= e($p['folder_name'] ?? 'Personal') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 6px; align-items: center;">
                                                <button type="button" onclick="openShareItemModal(<?= $p['id'] ?>, '<?= e(addslashes($p['app_name'])) ?>')" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px;">Share</button>
                                                <?php if (!empty($p['login_url'])): ?>
                                                    <a href="<?= e($p['login_url']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="color: var(--color-brand-red); font-weight: 700; border-color: #FFCDD2; padding: 4px 8px; font-size: 11px;">Launch</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" onclick="deleteVaultItem(<?= $p['id'] ?>, 'vault')" style="background: none; border: none; color: #EF4444; cursor: pointer;" title="Delete Item">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function filterVaultTable() {
    const q = document.getElementById('vault-search').value.toLowerCase();
    document.querySelectorAll('.vault-row').forEach(row => {
        const text = row.getAttribute('data-search') || '';
        row.style.display = text.includes(q) ? '' : 'none';
    });
}

async function copyDecryptedPassword(itemId) {
    try {
        const res = await fetch(`<?= APP_URL ?>/api/vault/decrypt/${itemId}`);
        const data = await res.json();
        if (data.success && data.password) {
            copyToClipboard(data.password, 'Password copied to clipboard');
        } else {
            toast.error(data.error || 'Failed to decrypt');
        }
    } catch (err) {
        toast.error('Network error');
    }
}
</script>

<?php require __DIR__ . '/../footer.php'; ?>
