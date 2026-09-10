<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$currentUser = current_user();
$view = $currentView ?? 'dashboard';

// Fetch item & favorite counts for logged in user
$itemCount = 0;
$favCount = 0;
if ($currentUser) {
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM password_entries WHERE user_id = ?');
    $stmt->execute([$currentUser['id']]);
    $itemCount = (int)$stmt->fetch()['cnt'];

    $favStmt = $db->prepare('SELECT COUNT(*) as cnt FROM password_entries WHERE user_id = ? AND is_favorite = 1');
    $favStmt->execute([$currentUser['id']]);
    $favCount = (int)$favStmt->fetch()['cnt'];
}
?>
<aside class="sidebar">
    <div class="sidebar-add-btn" style="position: relative;">
        <button type="button" class="btn btn-primary btn-full" onclick="toggleAddMenu(event)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>+ Add</span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left: auto;"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>

        <div id="add-menu-dropdown" style="display: none; position: absolute; left: 0; right: 0; top: 100%; margin-top: 6px; background: #fff; border: 1px solid #E2E8F0; border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.12); z-index: 60; overflow: hidden;">
            <button type="button" onclick="openAddItemModal('password'); hideAddMenu();" style="width: 100%; text-align: left; background: none; border: none; padding: 12px 16px; font-size: 13px; font-weight: 600; color: #0F172A; cursor: pointer; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='none'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Add Password Entry</span>
            </button>
            <div style="height: 1px; background: #F1F5F9;"></div>
            <button type="button" onclick="openPasswordGenerator(); hideAddMenu();" style="width: 100%; text-align: left; background: none; border: none; padding: 12px 16px; font-size: 13px; font-weight: 600; color: #D32F2F; cursor: pointer; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.background='#FEF2F2'" onmouseout="this.style.background='none'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                <span>Generate Password</span>
            </button>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= APP_URL ?>/dashboard" class="sidebar-item <?= $view === 'dashboard' ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            <span>All Items</span>
            <span class="sidebar-badge"><?= $itemCount ?></span>
        </a>

        <a href="<?= APP_URL ?>/favorites" class="sidebar-item <?= $view === 'favorites' ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
            <span>Favorites</span>
            <span class="sidebar-badge" style="color: #D97706; background: #FEF3C7;"><?= $favCount ?></span>
        </a>

        <a href="<?= APP_URL ?>/folders" class="sidebar-item <?= $view === 'folders' ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            </svg>
            <span>Customer Folders</span>
        </a>

        <a href="<?= APP_URL ?>/addresses" class="sidebar-item <?= $view === 'addresses' ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <span>Addresses</span>
        </a>

        <a href="<?= APP_URL ?>/notes" class="sidebar-item <?= $view === 'notes' ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
            <span>Secure Notes</span>
        </a>

        <a href="<?= APP_URL ?>/security" class="sidebar-item <?= $view === 'security' ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            </svg>
            <span>Security Dashboard</span>
        </a>

        <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
        <div style="padding: 16px 24px 6px 24px; font-size: 10px; font-weight: 800; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.1em;">
            Enterprise Admin
        </div>
        <a href="<?= APP_URL ?>/config" class="sidebar-item <?= $view === 'config' ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
            <span>Configuration</span>
        </a>
        <a href="<?= APP_URL ?>/admin/share" class="sidebar-item <?= $view === 'admin_share' ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
            </svg>
            <span>Share Links</span>
        </a>
        <?php endif; ?>
    </nav>

</aside>

<script>
function toggleAddMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('add-menu-dropdown');
    if (menu) {
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }
}
function hideAddMenu() {
    const menu = document.getElementById('add-menu-dropdown');
    if (menu) menu.style.display = 'none';
}
window.addEventListener('click', function(e) {
    if (!e.target.closest('#add-menu-dropdown') && !e.target.closest('button[onclick*="toggleAddMenu"]')) {
        hideAddMenu();
    }
});
</script>

