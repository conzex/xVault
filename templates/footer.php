<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$currentUser = current_user();
?>
    <footer class="footer">
        <div class="footer-inner">
            <p>© <?= date('Y') ?> xVault v<?= APP_VERSION ?> Enterprise. All rights reserved.</p>
            <a href="https://www.conzex.com" target="_blank" rel="noopener noreferrer" class="rainbow-text">
                A Conzex Global Product
            </a>
        </div>
    </footer>
</div><!-- .app-container -->

<?php if ($currentUser): ?>
<!-- Floating Action Button (FAB) for Adding Password Entry -->
<div class="fab-container">
    <button type="button" class="fab-btn" onclick="openAddItemModal('password')" title="Add New Password">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Add Password</span>
    </button>
</div>
<?php endif; ?>

<div id="toast-container"></div>

<?php require __DIR__ . '/modals/generator.php'; ?>
<?php require __DIR__ . '/modals/add_item.php'; ?>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
